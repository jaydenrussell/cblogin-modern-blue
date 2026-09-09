<?php
/**
 * CB Login — Modern Soft Blue layout override (logged-in / logout state) v1.3.17
 * ---------------------------------------------------------------------------
 * Shows: avatar in header, "Welcome, [name]" header as hyperlink to profile,
 * last login timestamp, + logout button.
 *
 * Avatar + display name use the Community Builder API (CBuser::getInstance() +
 * getField). This override only runs inside the CB Login module, so CB's full
 * API + fieldtype renderer are always available — no direct DB query needed.
 *
 * @version 1.3.17
 */
defined('_JEXEC') or die;

// Shared CB menu resolver (optional): canonical SEF routes resolved from the
// Joomla menu system — no hardcoded aliases like "cb-profile".
if (is_file(__DIR__ . '/cbmenu.php'))
{
	require_once __DIR__ . '/cbmenu.php';
}
$cbMenuResolver = class_exists('SccCbMenuResolver') ? SccCbMenuResolver::instance() : null;

// DOM id seed: random_bytes (PHP 7+); Joomla's own PRNG as a fallback so
// legacy PHP 5.x shared hosts (still common with Joomla 3) do not fatal.
$scc_id = 'scc' . bin2hex(function_exists('random_bytes') ? random_bytes(8) : JUserHelper::genRandomPassword(8));
$user = JFactory::getUser();

$avatarUrl     = '';
$displayName   = $user->get('name');
$showAvatar    = $params->get('show_avatar', 1);
$showLastLogin = $params->get('show_last_login', 1);
$lastLoginTxt  = (string) $params->get('text_last_login', 'Last login');

// Profile / profile-edit links: canonical CB menu route (auto-discovered via
// the Joomla menu system, or the configured Itemid if set). NO hardcoded alias
// or /component/com_comprofiler/ URL — SEF routes built from the menu Itemid.
$profileItemid = (int) $params->get('profile_itemid', 0);
if ($cbMenuResolver)
{
	$profileUrl   = $cbMenuResolver->getProfileUrl((int) $user->id, $profileItemid);
	$logoutAction = $cbMenuResolver->getLogoutUrl();
}
else
{
	$profileUrl   = JRoute::_('index.php?option=com_comprofiler&view=userprofile' . ($profileItemid ? '&Itemid=' . $profileItemid : ''), false);
	$logoutAction = JRoute::_('index.php?option=com_comprofiler&view=logout&task=logout', false);
}

// Return URL: CB native protocol ("B:" + base64). Honour the module's CB
// "Logout Redirection URL" param (key: 'logout' — per mod_cblogin.php) exactly
// like CB's own default layout does: '#' → reload current page; blank or
// 'index.php' → home-page; otherwise an explicit URL. The current-page
// fallback stays same-origin + root-relative.
$returnUrl = JUri::getInstance()->toString();
$returnPath = parse_url($returnUrl, PHP_URL_PATH);
$returnQuery = parse_url($returnUrl, PHP_URL_QUERY);
$safeReturn = '/' . ltrim((string) $returnPath, '/');
if ($returnQuery !== '' && $returnQuery !== false && $returnQuery !== null) {
    $safeReturn .= '?' . $returnQuery;
}

$logoutRedirect = trim((string) $params->get('logout', 'index.php'));
if ($logoutRedirect === '#')
{
    // Double-cross: reload current page (CB semantics).
    $logoutRedirect = '';
}
elseif ($logoutRedirect === '' || strcasecmp($logoutRedirect, 'index.php') === 0)
{
    // Blank or 'index.php': go to home-page (CB semantics).
    $logoutRedirect = 'index.php';
}
if ($logoutRedirect !== '' && preg_match('#^([a-z][a-z0-9+.\-]*):#i', $logoutRedirect, $schemeMatch))
{
    // Absolute URL: http(s) only. Anything else (javascript:, data:, ...) is
    // rejected and the current-page fallback is used.
    if (in_array(strtolower($schemeMatch[1]), array('http', 'https'), true))
    {
        $safeReturn = $logoutRedirect;
    }
}
elseif ($logoutRedirect !== '')
{
    // No scheme: root-relative (/...) or non-SEF (index.php?...) — safe.
    $safeReturn = $logoutRedirect;
}
// CB's logout handler expects the module's native "B:" prefix + base64 wrapper.
// Native mod_cblogin runs the target through cbSef() first, which absolutizes
// root-relative paths so the decoded return starts with live_site: CB 2.x
// whitelists the posted return to ( live_site | index.php ) prefixes and
// blanks "/cb-profile" (→ homepage). cbSef is loaded by mod_cblogin before
// this layout renders, but fall back to the raw value if it is unavailable.
if (function_exists('cbSef') && $safeReturn !== '') {
    $sefReturn = cbSef($safeReturn, true, 'html', (int) $params->get('https_post', 0));
    if (is_string($sefReturn) && $sefReturn !== '') {
        $safeReturn = $sefReturn;
    }
}
$encodedLogoutReturn = 'B:' . base64_encode($safeReturn);

// --- Display name + Avatar via CB API (single getInstance call) ---
if (class_exists('CBuser') && !$user->guest) {
    try {
        $cbUser = CBuser::getInstance((int) $user->id, false);
        if ($cbUser) {
            $cbName = $cbUser->getField('typename', null, 'raw');
            if ($cbName) {
                $displayName = $cbName;
            }

            $avatarHtml = $cbUser->getField('avatar', null, 'html', 'none', 'profile', 0, false);
            if ($avatarHtml) {
                if (preg_match('#src="([^"]+)"#i', $avatarHtml, $m)) {
                    $avatarUrl = $m[1];
                } elseif (preg_match("#src='([^']+)'#i", $avatarHtml, $m)) {
                    $avatarUrl = $m[1];
                }
            }
        }
    } catch (\Exception $e) {
        // CB API failed — fall back to Joomla user name and no avatar.
        $avatarUrl = '';
    }
}

// Avatar URL sanitization: only allow root-relative paths or same-origin absolute.
// Reject data:, javascript:, file: schemes. Reject cross-domain absolute URLs.
if ($avatarUrl !== '') {
    $scheme = parse_url($avatarUrl, PHP_URL_SCHEME);
    if ($scheme !== null && !in_array(strtolower($scheme), array('http', 'https'), true)) {
        $avatarUrl = '';
    } elseif (strpos($avatarUrl, '/') === 0) {
        // Root-relative — safe, leave as-is.
    } elseif (preg_match('#^https?://#i', $avatarUrl)) {
        $host = parse_url($avatarUrl, PHP_URL_HOST);
        if ($host && $host === JUri::getInstance()->getHost()) {
            $avatarUrl = '/' . ltrim(parse_url($avatarUrl, PHP_URL_PATH), '/');
        } else {
            // Cross-domain — block to prevent cross-origin info leak.
            $avatarUrl = '';
        }
    } else {
        // Relative path or unknown — reject.
        $avatarUrl = '';
    }
}

// --- Last login time ---
$lastLoginHtml = '';
if ($showLastLogin) {
    $lastLogin = $user->get('lastvisitDate');
    if (!empty($lastLogin) && $lastLogin !== '0000-00-00 00:00:00') {
        $d = JFactory::getDate($lastLogin);
        $lastLoginHtml = $d->format('M j, Y \a\t g:i a');
    } else {
        $lastLoginHtml = 'Never logged in';
    }
}

// Escape output once.
$escName      = htmlspecialchars($displayName, ENT_COMPAT, 'UTF-8');
$escAvatar    = htmlspecialchars($avatarUrl, ENT_COMPAT, 'UTF-8');
$escLastTxt   = htmlspecialchars($lastLoginTxt, ENT_COMPAT, 'UTF-8');
$escLastHtml  = htmlspecialchars($lastLoginHtml, ENT_COMPAT, 'UTF-8');
$escProfile   = htmlspecialchars($profileUrl, ENT_COMPAT, 'UTF-8');
$escLogoutAction = htmlspecialchars($logoutAction, ENT_COMPAT, 'UTF-8');
$escLogoutReturn = htmlspecialchars($encodedLogoutReturn, ENT_COMPAT, 'UTF-8');

// Enqueue external CSS (cacheable). Root-absolute URL: a plain relative URL
// like "templates/..." resolves against the current page path — on the
// homepage that is the site root, but on deeper routes (e.g.
// /cb-profile/jaydenrussell) it becomes /cb-profile/templates/... → 404, so
// the module renders unstyled ("default look"). Root-absolute works at any
// depth; rtrim() guarantees the leading slash even if JUri::root(true) is an
// empty string or already-slashed path.
$basePath = rtrim((string) JUri::root(true), '/\\');
$tplPath  = 'templates/' . JFactory::getApplication()->getTemplate();
$cssUrl   = $basePath . '/' . $tplPath . '/html/mod_cblogin/modernsoftblue_logout.css';
echo '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl, ENT_COMPAT, 'UTF-8') . '" />';
?>
<div class="scc-modern-blue" id="<?php echo $scc_id; ?>">
  <section class="scc-card">
    <!-- Header: Welcome + name + avatar (both link to profile) -->
    <div class="scc-header">
      <div class="scc-header-text">
        <h3 class="scc-card-title">
          <a href="<?php echo $escProfile; ?>">
            <span class="scc-greeting">Welcome<?php echo $displayName ? ',' : ''; ?>&nbsp;</span><span class="scc-name"><?php echo $displayName ? $escName : ''; ?></span>
          </a>
        </h3>
      </div>
      <?php if ($showAvatar && $avatarUrl !== ''): ?>
        <a href="<?php echo $escProfile; ?>">
          <img src="<?php echo $escAvatar; ?>" alt="<?php echo $escName; ?>"
             class="scc-header-avatar" loading="lazy" />
        </a>
      <?php endif; ?>
    </div>

    <!-- Last login timestamp (muted) -->
    <?php if ($showLastLogin && $lastLoginHtml): ?>
      <div class="scc-last-login">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="9" fill="none" stroke="#92a7b9" stroke-width="1.5"/>
          <path d="M12 7V12 L16 14" stroke="#92a7b9" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <span><?php echo $escLastTxt; ?>: <?php echo $escLastHtml; ?></span>
      </div>
    <?php endif; ?>

    <!-- Logout button -->
    <form action="<?php echo $escLogoutAction; ?>" method="post" class="scc-logout-form">
      <?php echo JHtml::_('form.token'); ?>
      <input type="hidden" name="return" value="<?php echo $escLogoutReturn; ?>" />
      <button type="submit" class="scc-logout-btn">Logout</button>
    </form>
  </section>
</div>
