<?php
/**
 * CB Login — SCC card layout override (logged-in / logout state) v1.1.6
 * -----------------------------------------------------------------------
 * Shows: avatar in header, "Welcome, [name]" header as hyperlink to
 * profile, last login timestamp, + logout button.
 *
 * FIX (v1.1.6): Uses direct DB query to #__comprofiler for avatar field.
 * CB's getField('avatar') returns context-dependent URLs:
 *   - /383_xxxx.png on home page (404 HTML, 200 status — broken)
 *   - /images/comprofiler/383_xxxx.webp on calendar page (real image)
 * DB query always returns raw value; we construct /images/comprofiler/{name}.png
 *
 * @version 1.1.6
 */
defined('_JEXEC') or die;

$scc_id = 'scc' . substr(md5(uniqid()), 0, 10);
$user = JFactory::getUser();

$avatarUrl     = '';
$displayName   = $user->get('name');
$showAvatar    = $params->get('show_avatar', 1);
$showLastLogin = $params->get('show_last_login', 1);
$lastLoginTxt  = $params->get('text_last_login', 'Last login');

// Profile URL — SCC profile page (requires login)
$profileUrl = $params->get('profile_url', 'https://simcoecurlingclub.ca/scc-profile');

// Profile edit URL — SCC profile edit page (requires login)
$editProfileUrl = $params->get('profile_edit_url', 'https://simcoecurlingclub.ca/scc-profile');

// --- Display name via CB typename (consistent across pages) ---
if (class_exists('CBuser') && !$user->guest) {
    $cbUser = CBuser::getInstance((int) $user->id, false);
    if ($cbUser) {
        $cbName = $cbUser->getField('typename', null, 'raw');
        if ($cbName) $displayName = $cbName;
    }
}

// --- Avatar: Direct DB query for CONSISTENT path on every page ---
// CB's getField('avatar') returns different URLs depending on context.
// We bypass CB's field rendering and query #__comprofiler directly.
// The avatar column stores the raw filename (e.g., "383_68acd902af225");
// we strip any extension and construct /images/comprofiler/{filename}.png
// Verified: returns 200 OK, Content-Type: image/png
$db = JFactory::getDbo();
$db->setQuery(
    $db->getQuery(true)
        ->select('avatar')
        ->from('#__comprofiler')
        ->where('id = ' . (int) $user->id)
);
$dbAvatar = $db->loadResult();

if ($dbAvatar && $dbAvatar !== '0' && $dbAvatar !== '') {
    $avatarFilename = preg_replace('/\.[^.]+$/', '', $dbAvatar);
    $avatarUrl = '/images/comprofiler/' . $avatarFilename . '.png';
}

// URL normalization (J3 compatible)
if ($avatarUrl && strpos($avatarUrl, JUri::root()) === 0) {
    $avatarUrl = '/' . ltrim(str_replace(JUri::root(), '', $avatarUrl), '/');
} elseif ($avatarUrl && strpos($avatarUrl, 'http') === 0) {
    $currentHost = JUri::getInstance()->getHost();
    $avatarHost = parse_url($avatarUrl, PHP_URL_HOST);
    if ($avatarHost === $currentHost || 'www.' . $currentHost === $avatarHost) {
        $avatarUrl = '/' . ltrim(parse_url($avatarUrl, PHP_URL_PATH), '/');
    }
}

// SVG initial fallback (prepared early for JS onerror)
$initial = mb_substr($displayName ?? 'U', 0, 1, 'UTF-8');
$svgFallback = 'data:image/svg+xml;base64,' . base64_encode(
    '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 32 32">'
    . '<circle cx="16" cy="16" r="16" fill="#1890d7"/>'
    . '<text x="50%" y="58%" text-anchor="middle" fill="#ffffff" font-size="14" font-family="sans-serif">'
    . htmlspecialchars($initial, ENT_COMPAT, 'UTF-8') . '</text></svg>'
);

if (!$avatarUrl) {
    $avatarUrl = $svgFallback;
}

// --- Last login time ---
$lastLoginHtml = '';
if ($showLastLogin) {
    $lastLogin = $user->get('lastvisitDate');
    if (!empty($lastLogin) && $lastLogin !== '0000-00-00 00:00:00') {
        $d = JFactory::getDate($lastLogin);
        $lastLoginHtml = $d->format('M j, Y \\a\\t g:i a');
    } else {
        $lastLoginHtml = 'Never logged in';
    }
}

// --- Logout ---
$logoutAction  = JRoute::_('index.php?option=com_comprofiler&view=logout&task=logout', false);
$logoutReturn  = $params->get('logout', 'index.php');
?>
<style>
#<?php echo $scc_id; ?> .scc-card {
  background:#ffffff;
  border:1px solid #e3ebf5;
  border-radius:16px;
  box-shadow:0 6px 18px rgba(17,24,39,0.08);
  padding:1rem 1.2rem 1.1rem 1.2rem;
  margin:0 0 1.5rem 0;
  overflow:visible;
}
#<?php echo $scc_id; ?> .scc-card-title {
  position:relative;
  font-size:1.05rem;
  font-weight:700;
  letter-spacing:.2px;
  color:#15324a;
  margin:0 0 .6rem 0;
  padding:0 0 .55rem .7rem;
  border-bottom:1px solid #e6ecf0 !important;
}
#<?php echo $scc_id; ?> .scc-card-title::before {
  content:"";
  position:absolute;
  left:0; top:.1em;
  height:1.15em; width:4px;
  border-radius:3px;
  background:#1890d7;
}
#<?php echo $scc_id; ?> .scc-header {
  display:flex;
  align-items:center;
  justify-content:space-between;
  overflow:visible;
  position:relative;
}
#<?php echo $scc_id; ?> .scc-header-avatar {
  width:48px; height:48px;
  border-radius:50%;
  object-fit:cover;
  border:2px solid #e3ebf5;
  /* Pull up so the avatar overlaps the title line */
  margin-top:-2.5rem;
}
#<?php echo $scc_id; ?> .scc-header-avatar svg {
  width:48px; height:48px;
}
#<?php echo $scc_id; ?> .scc-last-login {
  font-size:.72rem;
  color:#92a7b9;
  margin-top:.3rem;
  display:flex;
  align-items:center;
  gap:.3rem;
}
#<?php echo $scc_id; ?> .scc-logout-form { margin-top:.6rem; }
#<?php echo $scc_id; ?> .scc-logout-btn {
  width:100%;
  background:#1890d7;
  color:#ffffff;
  border:0;
  border-radius:8px;
  padding:.42rem;
  font-size:.78rem;
  font-weight:600;
  cursor:pointer;
  transition:background .15s;
}
#<?php echo $scc_id; ?> .scc-logout-btn:hover { background:#157bb3; }
</style>

<div id="<?php echo $scc_id; ?>">
  <section class="scc-card">
    <!-- Header: Welcome + name + avatar (both link to profile) -->
    <div class="scc-header">
      <h3 class="scc-card-title">
        <a href="<?php echo $profileUrl; ?>" style="color:#15324a;text-decoration:none;">
          Welcome<?php echo $displayName ? ', ' . htmlspecialchars($displayName) : ''; ?>
        </a>
      </h3>
      <?php if ($showAvatar): ?>
        <a href="<?php echo $profileUrl; ?>">
          <img src="<?php echo $avatarUrl; ?>" alt="<?php echo htmlspecialchars($displayName); ?>"
             class="scc-header-avatar"
             onerror="this.onerror=null;this.src='<?php echo $svgFallback; ?>';" />
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
        <span><?php echo $lastLoginTxt; ?>: <?php echo htmlspecialchars($lastLoginHtml); ?></span>
      </div>
    <?php endif; ?>

    <!-- Logout button -->
    <form action="<?php echo $logoutAction; ?>" method="post" class="scc-logout-form">
      <?php echo JHtml::_('form.token'); ?>
      <input type="hidden" name="option" value="com_comprofiler" />
      <input type="hidden" name="view" value="logout" />
      <input type="hidden" name="task" value="logout" />
      <input type="hidden" name="return" value="<?php echo urlencode($logoutReturn); ?>" />
      <button type="submit" class="scc-logout-btn">Logout</button>
    </form>
  </section>
</div>
