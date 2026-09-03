<?php
/**
 * CB Login — Modern Soft Blue layout override (logged-in / logout state) v1.3.7
 * ---------------------------------------------------------------------------
 * Shows: avatar in header, "Welcome, [name]" header as hyperlink to profile,
 * last login timestamp, + logout button.
 *
 * Avatar + display name use the Community Builder API (CBuser::getInstance() +
 * getField). This override only runs inside the CB Login module, so CB's full
 * API + fieldtype renderer are always available — no direct DB query needed.
 *
 * @version 1.3.7
 */
defined('_JEXEC') or die;

$scc_id = 'scc' . bin2hex(random_bytes(8));
$user = JFactory::getUser();

$avatarUrl     = '';
$displayName   = $user->get('name');
$showAvatar    = $params->get('show_avatar', 1);
$showLastLogin = $params->get('show_last_login', 1);
$lastLoginTxt  = (string) $params->get('text_last_login', 'Last login');

// Profile / profile-edit links: build local routes with the CB Itemid so the
// links work regardless of SEF/menu setup. NO hardcoded domain.
$profileItemid  = (int) $params->get('profile_itemid', 0);
$profileUrl     = JRoute::_('index.php?option=com_comprofiler&view=userprofile' . ($profileItemid ? '&Itemid=' . $profileItemid : ''), false);

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

// --- Logout (route carries the return + token; CSRF-protected by CB) ---
$logoutAction = JRoute::_('index.php?option=com_comprofiler&view=logout&task=logout', false);

// Escape output once.
$escName      = htmlspecialchars($displayName, ENT_COMPAT, 'UTF-8');
$escAvatar    = htmlspecialchars($avatarUrl, ENT_COMPAT, 'UTF-8');
$escLastTxt   = htmlspecialchars($lastLoginTxt, ENT_COMPAT, 'UTF-8');
$escLastHtml  = htmlspecialchars($lastLoginHtml, ENT_COMPAT, 'UTF-8');
$escProfile   = htmlspecialchars($profileUrl, ENT_COMPAT, 'UTF-8');

// Enqueue external CSS (cacheable).
$tplPath = 'templates/' . JFactory::getApplication()->getTemplate();
$cssUrl  = $tplPath . '/html/mod_cblogin/modernsoftblue_logout.css';
echo '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl, ENT_COMPAT, 'UTF-8') . '" />';
?>
<div class="scc-modern-blue" id="<?php echo $scc_id; ?>">
  <section class="scc-card">
    <!-- Header: Welcome + name + avatar (both link to profile) -->
    <div class="scc-header">
      <div class="scc-header-text">
        <h3 class="scc-card-title">
          <a href="<?php echo $escProfile; ?>" style="color:#15324a;text-decoration:none;">
            <span class="scc-greeting">Welcome<?php echo $displayName ? ',' : ''; ?></span><span class="scc-name"><?php echo $displayName ? $escName : ''; ?></span>
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
    <form action="<?php echo $logoutAction; ?>" method="post" class="scc-logout-form">
      <?php echo JHtml::_('form.token'); ?>
      <button type="submit" class="scc-logout-btn">Logout</button>
    </form>
  </section>
</div>
