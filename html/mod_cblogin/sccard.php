<?php
/**
 * CB Login — SCC card layout override (logged-out / login form)
 * ---------------------------------------------------------------------
 * Install: templates/tpl_jdseattle/html/mod_cblogin/sccard.php
 * Select:   Module → Advanced tab → Module Layout = "sccard"
 */
defined('_JEXEC') or die;

$scc_id = 'scc' . substr(md5(uniqid()), 0, 10);
$styleUsername = $params->get('style_username_cssclass', '');
$stylePassword = $params->get('style_password_cssclass', '');
$styleLoginBtn = $params->get('style_login_cssclass', '');
$styleForgot   = $params->get('style_forgotlogin_cssclass', '');
$styleRegister = $params->get('style_register_cssclass', '');
$showRemember  = $params->get('remember_enabled', 1);
$showForgot    = $params->get('show_lostpass', 1);
$showRegister  = $params->get('show_newaccount', 1);
?>
<style>
#<?php echo $scc_id; ?> .scc-card {
  background:#ffffff;
  border:1px solid #e3ebf5;
  border-radius:16px;
  box-shadow:0 6px 18px rgba(17,24,39,0.08);
  padding:1.1rem 1.2rem;
  margin:0 0 1.5rem 0;
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
#<?php echo $scc_id; ?> .scc-login-form .scc-field { margin-bottom:.6rem; }
#<?php echo $scc_id; ?> .scc-login-form .scc-field:last-child { margin-bottom:0; }
#<?php echo $scc_id; ?> .scc-login-form label {
  font-size:.7rem;
  font-weight:600;
  color:#4a647a;
  margin-bottom:.2rem;
  display:block;
}
#<?php echo $scc_id; ?> .scc-field-wrapper {
  position:relative;
  display:flex;
  align-items:center;
}
#<?php echo $scc_id; ?> .scc-field-icon {
  position:absolute;
  left:.55rem;
  width:14px; height:14px;
  fill:#92a7b9;
  pointer-events:none;
}
#<?php echo $scc_id; ?> .scc-login-form input[type=text],
#<?php echo $scc_id; ?> .scc-login-form input[type=password] {
  width:100%;
  padding:.42rem .6rem .42rem 2rem;
  border:1px solid #d0dbe5;
  border-radius:8px;
  font-size:.82rem;
  color:#15324a;
  background:#f8fbfd;
  box-sizing:border-box;
}
#<?php echo $scc_id; ?> .scc-login-form input:focus {
  outline:none;
  border-color:#1890d7;
  background:#ffffff;
  box-shadow:0 0 0 2px rgba(24,144,215,0.12);
}
#<?php echo $scc_id; ?> .scc-password-toggle {
  position:absolute;
  right:.6rem;
  background:none;
  border:0;
  cursor:pointer;
  padding:0;
  display:flex;
  align-items:center;
  justify-content:center;
  width:18px; height:18px;
  color:#92a7b9;
}
#<?php echo $scc_id; ?> .scc-password-toggle:hover { color:#1890d7; }
#<?php echo $scc_id; ?> .scc-remember-row {
  display:flex;
  align-items:center;
  gap:.4rem;
  font-size:.75rem;
  color:#4a647a;
  margin:.6rem 0;
}
#<?php echo $scc_id; ?> .scc-remember-row input[type=checkbox] {
  width:14px; height:14px;
  accent-color:#1890d7;
}
#<?php echo $scc_id; ?> .scc-action-row {
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-top:.6rem;
  font-size:.75rem;
}
#<?php echo $scc_id; ?> .scc-login-btn {
  background:#1890d7;
  color:#ffffff;
  border:0;
  border-radius:8px;
  padding:.5rem 1.2rem;
  font-size:.82rem;
  font-weight:600;
  cursor:pointer;
  transition:background .15s;
}
#<?php echo $scc_id; ?> .scc-login-btn:hover { background:#157bb3; }
#<?php echo $scc_id; ?> .scc-login-links {
  display:flex;
  gap:1rem;
  font-size:.75rem;
}
#<?php echo $scc_id; ?> .scc-login-links a {
  color:#1890d7;
  text-decoration:none;
}
#<?php echo $scc_id; ?> .scc-login-links a:hover { text-decoration:underline; }
#<?php echo $scc_id; ?> .scc-divider {
  text-align:center;
  font-size:.75rem;
  color:#b0becc;
  margin:.6rem 0;
  position:relative;
  letter-spacing:.3px;
}
#<?php echo $scc_id; ?> .scc-divider::before,
#<?php echo $scc_id; ?> .scc-divider::after {
  content:"";
  position:absolute;
  top:50%;
  width:40%;
  height:1px;
  background:#e3ebf5;
}
#<?php echo $scc_id; ?> .scc-divider::before { left:0; }
#<?php echo $scc_id; ?> .scc-divider::after { right:0; }
</style>

<div id="<?php echo $scc_id; ?>">
  <section class="scc-card">
    <?php if (trim($module->title) !== '') : ?>
      <h3 class="scc-card-title"><?php echo $module->title; ?></h3>
    <?php endif; ?>

    <form action="<?php echo JRoute::_('index.php?option=com_comprofiler&view=login&op2=login', false); ?>"
          method="post" id="login-form" class="scc-login-form" name="loginform">
      <input type="hidden" name="option" value="com_comprofiler" />
      <input type="hidden" name="view" value="login" />
      <input type="hidden" name="op2" value="login" />
      <input type="hidden" name="return" value="<?php echo base64_encode(JUri::getInstance()->toString()); ?>" />
      <input type="hidden" name="message" value="0" />
      <input type="hidden" name="loginfrom" value="loginmodule" />
      <?php echo JHtml::_('form.token'); ?>

      <!-- Username -->
      <div class="scc-field">
        <label for="modlgn-username">Username</label>
        <div class="scc-field-wrapper">
          <svg class="scc-field-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 12C14.67 12 17 9.67 17 7C17 4.33 14.67 2 12 2C9.33 2 7 4.33 7 7C7 9.67 9.33 12 12 12ZM12 14C8.69 14 6 11.31 6 8C6 4.69 8.69 2 12 2C15.31 2 18 4.69 18 8C18 11.31 15.31 14 12 14Z" fill="#92a7b9"/>
            <path d="M12 15.5C9.15 15.5 4 16.85 4 20.5V22H20V20.5C20 16.85 14.85 15.5 12 15.5Z" fill="#92a7b9"/>
          </svg>
          <input id="modlgn-username" type="text" name="username"
                 class="form-control <?php echo $styleUsername; ?>"
                 placeholder="Username or Email" required />
        </div>
      </div>

      <!-- Password -->
      <div class="scc-field">
        <label for="modlgn-passwd">Password</label>
        <div class="scc-field-wrapper">
          <svg class="scc-field-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="5" y="9" width="14" height="11" rx="2" fill="none" stroke="#92a7b9" stroke-width="1.5"/>
            <path d="M8 9V6C8 4 9 2.5 12 2.5C15 2.5 16 4 16 6V9" fill="none" stroke="#92a7b9" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          <input id="modlgn-passwd" type="password" name="passwd"
                 class="form-control <?php echo $stylePassword; ?>"
                 placeholder="Password" required />
          <button type="button" class="scc-password-toggle" id="scc-toggle-pw" title="Show/hide password">
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
          <input type="checkbox" id="modlgn-remember" name="remember" value="yes"
                 <?php if ($rememberChecked) echo 'checked'; ?> />
          <label for="modlgn-remember">Remember me</label>
        </div>
      <?php endif; ?>

      <!-- Actions: Forgot Login (left) + Log in button (right) -->
      <?php
      $forgotUrl   = 'https://simcoecurlingclub.ca/scc-forgot-login';
      $registerUrl = JRoute::_('index.php?option=com_users&view=registration', false);
      ?>
      <div class="scc-action-row">
        <?php if ($showForgot): ?>
          <a href="<?php echo $forgotUrl; ?>" class="<?php echo $styleForgot; ?>">Forgot Login?</a>
        <?php endif; ?>
        <button type="submit" name="Submit" class="scc-login-btn <?php echo $styleLoginBtn; ?>">Log in</button>
      </div>

      <!-- Divider + Sign up -->
      <div class="scc-divider">New to SCC?</div>
      <?php if ($showRegister): ?>
        <div class="scc-login-links" style="justify-content:center; font-size:.75rem;">
          <a href="<?php echo $registerUrl; ?>" class="<?php echo $styleRegister; ?>">Sign up</a>
        </div>
      <?php endif; ?>
    </form>
  </section>
</div>

<script>
(function() {
  var toggle = document.getElementById('scc-toggle-pw');
  var pw     = document.getElementById('modlgn-passwd');
  if (!toggle || !pw) return;

  var svgShow = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">'
    + '<path d="M2 12s3-6 10-6 10 6 10 6-3 6-10 6-10-6-10-6z" stroke="currentColor" stroke-width="1.5" fill="none"/>'
    + '<circle cx="12" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>';
  var svgHide = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">'
    + '<path d="M1 1l22 22M12 7.5V10.5M12 13.5V16.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>'
    + '<circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>';

  toggle.addEventListener('click', function() {
    if (pw.type === 'password') {
      pw.type = 'text';
      toggle.innerHTML = svgShow;
    } else {
      pw.type = 'password';
      toggle.innerHTML = svgHide;
    }
  });
})();
</script>
