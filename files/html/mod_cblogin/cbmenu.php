<?php
/**
 * CB Menu Resolver — one canonical profile/login/edit URL for the whole site.
 * ---------------------------------------------------------------------------
 * Shared by the mod_cblogin overrides (login + logout states).
 *
 * Finds the canonical public Joomla menu item for Community Builder
 * (option=com_comprofiler) by component / view, or uses an explicitly
 * configured Itemid when provided. It NEVER hardcodes aliases like
 * "cb-profile" and NEVER emits raw /component/com_comprofiler/ URLs: links are
 * built through JRoute::_() with the menu item's Itemid so SEF output stays
 * clean and custom CB menu aliases keep working without code changes.
 *
 * The Joomla #__menu table is the single source of truth here — exactly the
 * records JMenu serves, so the Joomla menu system decides what is canonical.
 *
 * @version 1.3.11
 */
defined('_JEXEC') or die;

if (!class_exists('SccCbMenuResolver'))
{
/**
 * Resolves CB menu items and builds SEF URLs from their Itemid.
 */
class SccCbMenuResolver
{
	protected static $instance = null;

	/**
	 * Singleton.
	 *
	 * @return SccCbMenuResolver
	 */
	public static function instance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Find the best public menu item for an option/view.
	 *
	 * Resolution order:
	 *   1) an explicitly configured Itemid that is a valid public menu item
	 *      for the option (+ optional view);
	 *   2) the first public menu item matching option + view;
	 *   3) (fallback pass) the first public menu item matching option.
	 *
	 * @param int    $preferredItemId configured canonical Itemid (0 = auto-discover)
	 * @param string $option          component name (default com_comprofiler)
	 * @param string $view            view to prefer ('' = any CB view)
	 * @return object|null            menu item with ->id and ->link, or null
	 */
	public function findMenuItem($preferredItemId = 0, $option = 'com_comprofiler', $view = '')
	{
		$levels = JFactory::getUser()->getAuthorisedViewLevels();
		$pref   = (int) $preferredItemId;

		// Pass 1: explicitly configured Itemid.
		if ($pref > 0)
		{
			$item = $this->menuItemById($pref, $levels, $option);
			if ($this->matches($item, $option, $view))
			{
				return $item;
			}
		}

		$items = $this->menuItemsForOption($option, $levels);

		// Pass 2: option + view.
		foreach ($items as $item)
		{
			if ($view === '' || $this->hasView($item, $view))
			{
				return $item;
			}
		}

		// Pass 3 (only when a view filter was requested): option alone.
		if ($view !== '' && !empty($items))
		{
			return $items[0];
		}

		return null;
	}

	/**
	 * Load a single published menu item by id (component type, matching option,
	 * accessible to the user).
	 *
	 * @param int    $id
	 * @param array  $levels user's authorised view levels
	 * @param string $option component name
	 * @return object|null
	 */
	protected function menuItemById($id, $levels, $option)
	{
		$db = JFactory::getDbo();
		$q  = $db->getQuery(true)
			->select(array('id', 'title', 'link', 'access', 'published'))
			->from('#__menu')
			->where('id = ' . (int) $id)
			->where('client_id = 0')
			->where('published = 1')
			->where('type = ' . $db->q('component'))
			->where('link LIKE ' . $db->q('index.php?option=' . $option . '%'));
		$db->setQuery($q);
		$item = $db->loadObject();

		if (!$item || !in_array((int) $item->access, $levels))
		{
			return null;
		}

		return $item;
	}

	/**
	 * All published, user-accessible component menu items for an option,
	 * most-public first, then by creation order.
	 *
	 * @param string $option component name
	 * @param array  $levels user's authorised view levels
	 * @return object[]
	 */
	protected function menuItemsForOption($option, $levels)
	{
		$db = JFactory::getDbo();
		$q  = $db->getQuery(true)
			->select(array('id', 'title', 'link', 'access', 'published'))
			->from('#__menu')
			->where('client_id = 0')
			->where('published = 1')
			->where('type = ' . $db->q('component'))
			->where('link LIKE ' . $db->q('index.php?option=' . $option . '%'))
			->order('access ASC, id ASC');
		$db->setQuery($q);

		$out = array();
		foreach ((array) $db->loadObjectList() as $item)
		{
			if (in_array((int) $item->access, $levels))
			{
				$out[] = $item;
			}
		}

		return $out;
	}

	/**
	 * Does a menu item link to the given option (and optional view)?
	 *
	 * @param object|null $item
	 * @param string      $option
	 * @param string      $view
	 * @return bool
	 */
	protected function matches($item, $option, $view)
	{
		return $item && !empty($item->link)
			&& strpos($item->link, 'option=' . $option) !== false
			&& ($view === '' || strpos($item->link, 'view=' . $view) !== false);
	}

	/**
	 * Does a menu item link contain the requested view?
	 *
	 * @param object|null $item
	 * @param string      $view
	 * @return bool
	 */
	protected function hasView($item, $view)
	{
		return $item && !empty($item->link) && strpos($item->link, 'view=' . $view) !== false;
	}

	/**
	 * Build a Joomla SEF URL from a menu item, appending Itemid if not present,
	 * plus any extra query params.
	 *
	 * @param object|null $item     menu item (->link, ->id); null = fallback only
	 * @param array       $extra    extra query params to append
	 * @param array       $fallback query params used when no item resolved
	 * @return string               routed URL (no domain)
	 */
	public function buildMenuUrl($item = null, $extra = array(), $fallback = array())
	{
		$query = array();

		if ($item && !empty($item->link))
		{
			$q = parse_url($item->link, PHP_URL_QUERY);
			if ($q)
			{
				parse_str($q, $query);
			}

			if (!isset($query['Itemid']))
			{
				$query['Itemid'] = (int) $item->id;
			}
		}
		else
		{
			$query = array_merge(array('option' => 'com_comprofiler'), (array) $fallback);
		}

		foreach ((array) $extra as $k => $v)
		{
			if ($v !== null && $v !== '')
			{
				$query[$k] = $v;
			}
		}

		return JRoute::_('index.php?' . http_build_query($query), false);
	}

	/**
	 * Canonical profile URL.
	 *
	 * @param int $userId          user to view (0 = current user)
	 * @param int $preferredItemId optional configured CB profile Itemid
	 * @return string
	 */
	public function getProfileUrl($userId = 0, $preferredItemId = 0)
	{
		$item  = $this->findMenuItem($preferredItemId, 'com_comprofiler', 'userprofile');
		$extra = array();
		if ((int) $userId > 0)
		{
			$extra['user'] = (int) $userId;
		}

		return $this->buildMenuUrl($item, $extra, array('view' => 'userprofile'));
	}

	/**
	 * Canonical "edit profile" URL (task=edit on the profile menu item).
	 *
	 * @param int $userId          user to edit (0 = current user)
	 * @param int $preferredItemId optional configured CB profile Itemid
	 * @return string
	 */
	public function getEditProfileUrl($userId = 0, $preferredItemId = 0)
	{
		$item  = $this->findMenuItem($preferredItemId, 'com_comprofiler', 'userprofile');
		$extra = array('task' => 'edit');
		if ((int) $userId > 0)
		{
			$extra['user'] = (int) $userId;
		}

		return $this->buildMenuUrl(
			$item,
			$extra,
			array('view' => 'userprofile', 'task' => 'edit')
		);
	}

	/**
	 * Login URL (menu-resolved if a login menu item exists, else routed).
	 *
	 * @return string
	 */
	public function getLoginUrl()
	{
		$item = $this->findMenuItem(0, 'com_comprofiler', 'login');

		return $this->buildMenuUrl($item, array('view' => 'login'), array('view' => 'login'));
	}

	/**
	 * Logout URL (menu-resolved if a logout menu item exists, else routed).
	 *
	 * @return string
	 */
	public function getLogoutUrl()
	{
		$item = $this->findMenuItem(0, 'com_comprofiler', 'logout');

		return $this->buildMenuUrl(
			$item,
			array('view' => 'logout', 'task' => 'logout'),
			array('view' => 'logout', 'task' => 'logout')
		);
	}

	/**
	 * Forgot-login / lost-password URL.
	 *
	 * @param int $preferredItemId optional configured CB forgot Itemid
	 * @return string
	 */
	public function getForgotUrl($preferredItemId = 0)
	{
		$item = $this->findMenuItem($preferredItemId, 'com_comprofiler', 'lostpassword');

		return $this->buildMenuUrl($item, array('view' => 'lostpassword'), array('view' => 'lostpassword'));
	}

	/**
	 * Registration URL (com_users; menu-resolved if a register item exists).
	 *
	 * @return string
	 */
	public function getRegisterUrl()
	{
		$item = $this->findMenuItem(0, 'com_users', 'registration');

		return $this->buildMenuUrl(
			$item,
			array('view' => 'registration'),
			array('option' => 'com_users', 'view' => 'registration')
		);
	}
}
}