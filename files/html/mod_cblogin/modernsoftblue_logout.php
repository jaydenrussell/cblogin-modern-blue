<?php
/**
 * CB Login — Modern Soft Blue layout override (logged-in / logout state) v1.1.0
 * ---------------------------------------------------------------------------
 * Shows: avatar in header, "Welcome, [name]" header as hyperlink to profile,
 * last login timestamp, + logout button.
 *
 * Avatar + display name use the Community Builder API (CBuser::getInstance() +
 * getField). This override only runs inside the CB Login module, so CB's full
 * API + fieldtype renderer are always available — no direct DB query needed.
 *
 * @version 1.2.1
 */
defined('_JEXEC') or die;

$scc_id = 'scc' . substr(md5(uniqid()), 0, 10);
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

// --- Display name via CB typename (consistent across pages) ---
if (class_exists('CBuser') && !$user->guest) {
    $cbUser = CBuser::getInstance((int) $user->id, false);
    if ($cbUser) {
        $cbName = $cbUser->getField('typename', null, 'raw');
        if ($cbName) {
            $displayName = $cbName;
        }
    }
}

// --- Avatar: CB API (CB is always loaded for this module) ---
// 'profile' reason returns the full master image; we extract the src URL.
if (class_exists('CBuser') && !$user->guest) {
    $cbUser = CBuser::getInstance((int) $user->id, false);
    if ($cbUser) {
        $avatarHtml = $cbUser->getField('avatar', null, 'html', 'none', 'profile', 0, false);
        if ($avatarHtml && preg_match('#src="([^"]+)"#i', $avatarHtml, $m)) {
            $avatarUrl = $m[1];
        } elseif ($avatarHtml && preg_match("#src='([^']+)'#i", $avatarHtml, $m)) {
            $avatarUrl = $m[1];
        }
    }
}

// URL normalization: keep only a root-relative path for the current host.
// This avoids leaking to a wrong domain and keeps the markup simple/portable.
if ($avatarUrl !== '') {
    $abs = (strpos($avatarUrl, 'http') === 0);
    if (!$abs && strpos($avatarUrl, '/') === 0) {
        // already root-relative — leave as-is
    } elseif ($abs) {
        $host = parse_url($avatarUrl, PHP_URL_HOST);
        if ($host && $host === JUri::getInstance()->getHost()) {
            $avatarUrl = '/' . ltrim(parse_url($avatarUrl, PHP_URL_PATH), '/');
        }
        // If the host differs, leave the absolute URL (cross-domain avatar) untouched.
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
  line-height:1.25;
  color:#15324a;
  margin:0;
  padding:0 0 .55rem .7rem;
  border-bottom:1px solid #e6ecf0 !important;
}
#<?php echo $scc_id; ?> .scc-card-title .scc-greeting {
  font-weight:600;
  color:#4a647a;
}
#<?php echo $scc_id; ?> .scc-card-title .scc-name {
  display:block;
  overflow-wrap:anywhere;
  word-break:break-word;
}
#<?php echo $scc_id; ?> .scc-header {
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:.75rem;
  overflow:visible;
  position:relative;
}
#<?php echo $scc_id; ?> .scc-header-text {
  min-width:0;
  flex:1 1 auto;
}
#<?php echo $scc_id; ?> .scc-header-avatar {
  flex:0 0 auto;
  width:48px; height:48px;
  border-radius:50%;
  object-fit:cover;
  border:2px solid #e3ebf5;
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
      <div class="scc-header-text">
        <h3 class="scc-card-title">
          <a href="<?php echo $escProfile; ?>" style="color:#15324a;text-decoration:none;">
            <span class="scc-greeting">Welcome<?php echo $displayName ? ',' : ''; ?></span><span class="scc-name"><?php echo $displayName ? $escName : ''; ?></span>
          </a>
        </h3>
      </div>
      <?php if ($showAvatar): ?>
        <a href="<?php echo $escProfile; ?>">
          <img src="<?php echo $escAvatar; ?>" alt="<?php echo $escName; ?>"
             class="scc-header-avatar" />
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
