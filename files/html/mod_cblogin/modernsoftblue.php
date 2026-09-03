<?php
/**
 * CB Login — Modern Soft Blue layout override (logged-out / login form)
 * ---------------------------------------------------------------------
 * Install: templates/tpl_jdseattle/html/mod_cblogin/modernsoftblue.php
 * Select:   Module → Advanced tab → Module Layout = "Modern Soft Blue"
 *
 * @version 1.3.7
 */
defined('_JEXEC') or die;

$scc_id = 'scc' . bin2hex(random_bytes(8));
$styleUsername = (string) $params->get('style_username_cssclass', '');
$stylePassword = (string) $params->get('style_password_cssclass', '');
$styleLoginBtn = (string) $params->get('style_login_cssclass', '');
$styleForgot   = (string) $params->get('style_forgotlogin_cssclass', '');
$styleRegister = (string) $params->get('style_register_cssclass', '');
$showRemember  = $params->get('remember_enabled', 1);
$showForgot    = $params->get('show_lostpass', 1);
$showRegister  = $params->get('show_newaccount', 1);

// Forgot-login link: build a local route with the CB Itemid so the link works
// regardless of the SEF/menu setup. NO hardcoded domain.
$forgotItemid  = (int) $params->get('forgot_login_itemid', 0);
$forgotUrl     = JRoute::_('index.php?option=com_comprofiler&view=lostpassword' . ($forgotItemid ? '&Itemid=' . $forgotItemid : ''), false);
$registerUrl   = JRoute::_('index.php?option=com_users&view=registration', false);

// Return URL: restrict to same-origin root-relative path (CB convention requires
// a base64-encoded value). Root-relative-only makes an open redirect impossible.
$returnUrl = JUri::getInstance()->toString();
$returnPath = parse_url($returnUrl, PHP_URL_PATH);
$returnQuery = parse_url($returnUrl, PHP_URL_QUERY);
$safeReturn = '/' . ltrim((string) $returnPath, '/');
if ($returnQuery !== '' && $returnQuery !== false && $returnQuery !== null) {
    $safeReturn .= '?' . $returnQuery;
}
$encodedReturn = base64_encode($safeReturn);

// Escape all admin-controllable params once.
$escTitle    = htmlspecialchars($module->title, ENT_COMPAT, 'UTF-8');
$escUsername = htmlspecialchars($styleUsername, ENT_COMPAT, 'UTF-8');
$escPassword = htmlspecialchars($stylePassword, ENT_COMPAT, 'UTF-8');
$escLoginBtn = htmlspecialchars($styleLoginBtn, ENT_COMPAT, 'UTF-8');
$escForgot   = htmlspecialchars($styleForgot, ENT_COMPAT, 'UTF-8');
$escRegister = htmlspecialchars($styleRegister, ENT_COMPAT, 'UTF-8');
$escReturn   = htmlspecialchars($encodedReturn, ENT_COMPAT, 'UTF-8');

// Enqueue external CSS (cacheable).
$tplPath = 'templates/' . JFactory::getApplication()->getTemplate();
$cssUrl  = $tplPath . '/html/mod_cblogin/modernsoftblue.css';
echo '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl, ENT_COMPAT, 'UTF-8') . '" />';

// Enqueue external JS (cacheable).
$jsUrl = $tplPath . '/html/mod_cblogin/modernsoftblue.js';
echo '<script src="' . htmlspecialchars($jsUrl, ENT_COMPAT, 'UTF-8') . '"></script>';
?>
<div class="scc-modern-blue" id="<?php echo $scc_id; ?>">
  <section class="scc-card">
    <?php if (trim($module->title) !== '') : ?>
      <h3 class="scc-card-title"><?php echo $escTitle; ?></h3>
    <?php endif; ?>

    <form action="<?php echo JRoute::_('index.php?option=com_comprofiler&view=login&op2=login', false); ?>"
          method="post" id="<?php echo $scc_id; ?>-login-form" class="scc-login-form" name="loginform">
      <input type="hidden" name="option" value="com_comprofiler" />
      <input type="hidden" name="view" value="login" />
      <input type="hidden" name="op2" value="login" />
      <input type="hidden" name="return" value="<?php echo $escReturn; ?>" />
      <input type="hidden" name="message" value="0" />
      <input type="hidden" name="loginfrom" value="loginmodule" />
      <?php echo JHtml::_('form.token'); ?>

      <!-- Username -->
      <div class="scc-field">
        <label for="<?php echo $scc_id; ?>-username">Username</label>
        <div class="scc-field-wrapper">
          <svg class="scc-field-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 12C14.67 12 17 9.67 17 7C17 4.33 14.67 2 12 2C9.33 2 7 4.33 7 7C7 9.67 9.33 12 12 12ZM12 14C8.69 14 6 11.31 6 8C6 4.69 8.69 2 12 2C15.31 2 18 4.69 18 8C18 11.31 15.31 14 12 14Z" fill="#92a7b9"/>
            <path d="M12 15.5C9.15 15.5 4 16.85 4 20.5V22H20V20.5C20 16.85 14.85 15.5 12 15.5Z" fill="#92a7b9"/>
          </svg>
          <input id="<?php echo $scc_id; ?>-username" type="text" name="username"
                 class="form-control <?php echo $escUsername; ?>"
                 placeholder="Username or Email" autocomplete="username" required />
        </div>
      </div>

      <!-- Password -->
      <div class="scc-field">
        <label for="<?php echo $scc_id; ?>-passwd">Password</label>
        <div class="scc-field-wrapper">
          <svg class="scc-field-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="5" y="9" width="14" height="11" rx="2" fill="none" stroke="#92a7b9" stroke-width="1.5"/>
            <path d="M8 9V6C8 4 9 2.5 12 2.5C15 2.5 16 4 16 6V9" fill="none" stroke="#92a7b9" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          <input id="<?php echo $scc_id; ?>-passwd" type="password" name="passwd"
                 class="form-control scc-pw-field <?php echo $escPassword; ?>"
                 placeholder="Password" autocomplete="current-password" required />
          <button type="button" class="scc-password-toggle" title="Show/hide password">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M1 1l22 22M12 7.5V10.5M12 13.5V16.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              <circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.5"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Remember Me (respects remember_enabled param) -->
      <?php
      $rememberChecked = ($showRemember == 3);
      $rememberShow    = ($showRemember == 1 || $showRemember == 3);
      if ($rememberShow):
      ?>
        <div class="scc-remember-row">
          <input type="checkbox" id="<?php echo $scc_id; ?>-remember" name="remember" value="yes"
                 <?php if ($rememberChecked) echo 'checked'; ?> />
          <label for="<?php echo $scc_id; ?>-remember">Remember me</label>
        </div>
      <?php endif; ?>

      <!-- Actions: Forgot Login (left) + Log in button (right) -->
      <div class="scc-action-row">
        <?php if ($showForgot): ?>
          <a href="<?php echo $forgotUrl; ?>" class="<?php echo $escForgot; ?>">Forgot Login?</a>
        <?php endif; ?>
        <button type="submit" name="Submit" class="scc-login-btn <?php echo $escLoginBtn; ?>">Log in</button>
      </div>

      <!-- Divider + Sign up -->
      <div class="scc-divider">New to SCC?</div>
      <?php if ($showRegister): ?>
        <div class="scc-login-links">
          <a href="<?php echo $registerUrl; ?>" class="<?php echo $escRegister; ?>">Sign up</a>
        </div>
      <?php endif; ?>
    </form>
  </section>
</div>
