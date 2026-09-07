<?php
/**
 * SccCbMenuResolver stub harness.
 * ---------------------------------------------------------------------------
 * Standalone test for files/html/mod_cblogin/cbmenu.php that runs WITHOUT a
 * Joomla install: JFactory/JRoute are stubbed and the #__menu is a fake row
 * set, so matching, access filtering, memoization and DB-failure fallbacks can
 * be asserted on PHP 5.6 / 7.4 / 8.x alike.
 *
 * Run:   php tests/cbmenu_resolver_harness.php        (exit 0 = green, 1 = red)
 */
define('_JEXEC', 1);

$GLOBALS['t_pass'] = 0;
$GLOBALS['t_fail'] = 0;
$GLOBALS['t_bad']  = array();

function t_pass($label)
{
	$GLOBALS['t_pass']++;
	echo "  PASS  $label\n";
}

function t_fail($label, $detail = '')
{
	$GLOBALS['t_fail']++;
	$GLOBALS['t_bad'][] = $label . ($detail ? " :: $detail" : '');
	echo "  FAIL  $label" . ($detail ? " :: $detail" : '') . "\n";
}

function t_eq($label, $got, $want)
{
	if ((string) $got === (string) $want)
	{
		t_pass($label);
	}
	else
	{
		t_fail($label, 'got=' . var_export($got, true) . ' want=' . var_export($want, true));
	}
}

function url_params($url)
{
	$q      = parse_url($url, PHP_URL_QUERY);
	$params = array();
	if ($q && $q !== false)
	{
		parse_str($q, $params);
	}

	return $params;
}

function t_has($label, $url, $want)
{
	$got = url_params($url);
	foreach ($want as $k => $v)
	{
		if (!isset($got[$k]) || (string) $got[$k] !== (string) $v)
		{
			t_fail($label, '[' . $url . '] missing ' . $k . '=' . var_export($v, true));

			return;
		}
	}
	t_pass($label);
}

function t_not($label, $url, $key)
{
	$got = url_params($url);
	if (isset($got[$key]))
	{
		t_fail($label, '[' . $url . '] has unexpected ' . $key);
	}
	else
	{
		t_pass($label);
	}
}

/* ------------------------------------------------ stubs ------------------------------------------------ */

class HarnessUser
{
	protected $levels;

	public function __construct(array $levels)
	{
		$this->levels = $levels;
	}

	public function getAuthorisedViewLevels()
	{
		return $this->levels;
	}
}

class JFactory
{
	public static $levels = array(1, 2);
	public static $dbo    = null;

	public static function getUser()
	{
		return new HarnessUser(self::$levels);
	}

	public static function getDbo()
	{
		return self::$dbo;
	}
}

class JRoute
{
	public static $calls = 0;

	// SEF off: identity route. Assertions read params from the returned URL.
	public static function _($url, $xssl = null)
	{
		self::$calls++;

		return (string) $url;
	}
}

class HarnessQuery
{
	public $conds = array();

	public function select($cols = null)
	{
		return $this;
	}

	public function from($table)
	{
		return $this;
	}

	public function where($cond)
	{
		$this->conds[] = $cond;

		return $this;
	}

	public function order($cols = '*')
	{
		return $this;
	}

	public function __toString()
	{
		return 'SELECT ... WHERE ' . implode(' AND ', $this->conds);
	}
}

class HarnessDb
{
	public $rows           = array();
	public $throwOnList    = false;
	public $throwOnObject  = false;
	public $listLoads      = 0;
	public $objectLoads    = 0;

	public function getQuery($new = false)
	{
		return new HarnessQuery();
	}

	public function q($text)
	{
		return "'" . str_replace("'", "''", (string) $text) . "'";
	}

	public function setQuery($query)
	{
		return $this;
	}

	public function loadObject()
	{
		$this->objectLoads++;
		if ($this->throwOnObject)
		{
			throw new Exception('simulated db failure');
		}

		return isset($this->rows[0]) ? $this->rows[0] : null;
	}

	public function loadObjectList()
	{
		$this->listLoads++;
		if ($this->throwOnList)
		{
			throw new Exception('simulated db failure');
		}

		return $this->rows;
	}
}

function row($id, $link, $access = 1)
{
	return (object) array(
		'id'        => (int) $id,
		'title'     => 'Item ' . (int) $id,
		'link'      => $link,
		'access'    => (int) $access,
		'published' => 1,
	);
}

function seed(array $rows, $throwOnList = false)
{
	$db              = new HarnessDb();
	$db->rows        = $rows;
	$db->throwOnList = $throwOnList;
	JFactory::$dbo   = $db;

	return $db;
}

function fresh()
{
	JFactory::$levels = array(1, 2);
	JRoute::$calls    = 0;
	$rp = new ReflectionProperty('SccCbMenuResolver', 'itemCache');
	$rp->setAccessible(true);
	$rp->setValue(null, array());
}

/* ------------------------------------------------ load under test ------------------------------------------------ */

require_once dirname(__DIR__) . '/files/html/mod_cblogin/cbmenu.php';

if (!class_exists('SccCbMenuResolver'))
{
	fwrite(STDERR, "SccCbMenuResolver did not load\n");
	exit(2);
}

$R = 'SccCbMenuResolver';

/* ------------------------------------------------ scenarios ------------------------------------------------ */

echo "T1  no menu rows -> routed fallback (no Itemid)\n";
fresh();
seed(array());
$url = $R::instance()->getProfileUrl(42);
t_has('T1 option/view/user', $url, array('option' => 'com_comprofiler', 'view' => 'userprofile', 'user' => '42'));
t_not('T1 no Itemid', $url, 'Itemid');

echo "T2  canonical menu -> Itemid appended + user\n";
fresh();
seed(array(row(7, 'index.php?option=com_comprofiler&view=userprofile')));
$url = $R::instance()->getProfileUrl(42);
t_has('T2 canonical', $url, array('option' => 'com_comprofiler', 'view' => 'userprofile', 'Itemid' => '7', 'user' => '42'));

echo "T3  restricted access filtered out\n";
fresh();
seed(array(row(7, 'index.php?option=com_comprofiler&view=userprofile', 3)));
$url = $R::instance()->getProfileUrl(42);
t_has('T3 filtered fallback', $url, array('view' => 'userprofile', 'user' => '42'));
t_not('T3 no Itemid', $url, 'Itemid');

echo "T4  option boundary: com_comprofiler2 NOT treated as com_comprofiler\n";
fresh();
seed(array(
	row(8, 'index.php?option=com_comprofiler2&view=userprofile'),
	row(7, 'index.php?option=com_comprofiler&view=userprofile'),
));
$url = $R::instance()->getProfileUrl(42);
t_has('T4 picks real component', $url, array('Itemid' => '7'));

echo "T5  preferred Itemid honored\n";
fresh();
seed(array(row(99, 'index.php?option=com_comprofiler&view=userprofile')));
$url = $R::instance()->getProfileUrl(5, 99);
t_has('T5 pref used', $url, array('Itemid' => '99', 'user' => '5'));

echo "T6  preferred Itemid mismatched view -> discovery\n";
fresh();
seed(array(
	row(99, 'index.php?option=com_comprofiler&view=login'),
	row(7, 'index.php?option=com_comprofiler&view=userprofile'),
));
$url = $R::instance()->getProfileUrl(5, 99);
t_has('T6 discovered instead', $url, array('Itemid' => '7', 'user' => '5'));

echo "T7  DB list failure -> routed fallback, no fatal\n";
fresh();
seed(array(row(7, 'index.php?option=com_comprofiler&view=userprofile')), true);
$url = $R::instance()->getProfileUrl(42);
t_has('T7 falls back', $url, array('option' => 'com_comprofiler', 'view' => 'userprofile', 'user' => '42'));
t_not('T7 no Itemid', $url, 'Itemid');

echo "T8  memoization: same option scanned once per request\n";
fresh();
$db = seed(array(row(7, 'index.php?option=com_comprofiler&view=userprofile')));
$R::instance()->getProfileUrl(1);
$R::instance()->getLogoutUrl();
t_eq('T8 list scanned once', $db->listLoads, 1);

echo "T9  edit profile adds task=edit\n";
fresh();
seed(array(row(7, 'index.php?option=com_comprofiler&view=userprofile')));
$url = $R::instance()->getEditProfileUrl(5);
t_has('T9 edit', $url, array('task' => 'edit', 'user' => '5', 'Itemid' => '7'));

echo "T10 logout carries task=logout\n";
fresh();
seed(array(row(7, 'index.php?option=com_comprofiler&view=userprofile')));
$url = $R::instance()->getLogoutUrl();
t_has('T10 logout', $url, array('view' => 'logout', 'task' => 'logout'));

echo "T11 login resolves to login view\n";
fresh();
seed(array(row(7, 'index.php?option=com_comprofiler&view=login')));
$url = $R::instance()->getLoginUrl();
t_has('T11 login', $url, array('view' => 'login', 'Itemid' => '7'));

echo "T12 forgot-login with preferred Itemid\n";
fresh();
seed(array(row(50, 'index.php?option=com_comprofiler&view=lostpassword')));
$url = $R::instance()->getForgotUrl(50);
t_has('T12 forgot', $url, array('view' => 'lostpassword', 'Itemid' => '50'));

echo "T13 registration (com_users) + boundary\n";
fresh();
seed(array(
	row(61, 'index.php?option=com_users_x&view=registration'),
	row(60, 'index.php?option=com_users&view=registration'),
));
$url = $R::instance()->getRegisterUrl();
t_has('T13 register', $url, array('option' => 'com_users', 'view' => 'registration', 'Itemid' => '60'));

echo "T14 buildMenuUrl(null) raw fallback\n";
fresh();
$url = $R::instance()->buildMenuUrl(null, array('view' => 'login'), array('option' => 'com_comprofiler', 'view' => 'login'));
t_has('T14 raw fallback', $url, array('option' => 'com_comprofiler', 'view' => 'login'));
t_not('T14 no Itemid', $url, 'Itemid');

echo "T15 extra params override existing item params\n";
fresh();
seed(array(row(7, 'index.php?option=com_comprofiler&view=userprofile&user=999')));
$url = $R::instance()->getProfileUrl(3);
t_has('T15 user overrides', $url, array('user' => '3'));

echo "T16 preferred Itemid rejected by view boundary\n";
fresh();
seed(array(
	row(77, 'index.php?option=com_comprofiler&view=userprofileX'),
	row(7, 'index.php?option=com_comprofiler&view=userprofile'),
));
$url = $R::instance()->getProfileUrl(1, 77);
t_has('T16 view boundary', $url, array('Itemid' => '7'));

echo "T17 DB object(single lookup) failure containment\n";
fresh();
$db = seed(array(row(7, 'index.php?option=com_comprofiler&view=userprofile')));
$db->throwOnObject = true;
$db->throwOnList   = true;
$url = $R::instance()->getProfileUrl(1, 7);
t_has('T17 no fatal', $url, array('view' => 'userprofile', 'user' => '1'));
t_not('T17 no Itemid', $url, 'Itemid');

/* ------------------------------------------------ summary ------------------------------------------------ */

echo "\nRESOLVER HARNESS: " . $GLOBALS['t_pass'] . ' passed, ' . $GLOBALS['t_fail'] . ' failed' . "\n";
if ($GLOBALS['t_fail'])
{
	foreach ($GLOBALS['t_bad'] as $b)
	{
		echo '  - ' . $b . "\n";
	}
	exit(1);
}
exit(0);