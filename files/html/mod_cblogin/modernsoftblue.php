<?php
/**
 * CB Login — Modern Soft Blue layout override (logged-out / login form)
 * ---------------------------------------------------------------------
 * Install: templates/tpl_jdseattle/html/mod_cblogin/modernsoftblue.php
 * Select:   Module → Advanced tab → Module Layout = "Modern Soft Blue"
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
$styleUsername = (string) $params->get('style_username_cssclass', '');
$stylePassword = (string) $params->get('style_password_cssclass', '');
$styleLoginBtn = (string) $params->get('style_login_cssclass', '');
$styleForgot   = (string) $params->get('style_forgotlogin_cssclass', '');
$styleRegister = (string) $params->get('style_register_cssclass', '');
$showRemember  = $params->get('remember_enabled', 1);
$showForgot    = $params->get('show_lostpass', 1);
$showRegister  = $params->get('show_newaccount', 1);

// Forgot-login link: canonical CB menu route (auto-discovered via the Joomla
// menu system, or the configured Itemid if set). NO hardcoded domain/alias.
$forgotItemid  = (int) $params->get('forgot_login_itemid', 0);
if ($cbMenuResolver)
{
	$forgotUrl   = $cbMenuResolver->getForgotUrl($forgotItemid);
	$registerUrl = $cbMenuResolver->getRegisterUrl();
	$loginAction = $cbMenuResolver->getLoginUrl();
}
else
{
	$forgotUrl   = JRoute::_('index.php?option=com_comprofiler&view=lostpassword' . ($forgotItemid ? '&Itemid=' . $forgotItemid : ''), false);
	$registerUrl = JRoute::_('index.php?option=com_users&view=registration', false);
	$loginAction = JRoute::_('index.php?option=com_comprofiler&view=login&op2=login', false);
}

// Return URL: CB native protocol ("B:" + base64). Honour the module's CB
// "Login Redirection URL" param (key: 'login' — per mod_cblogin.php) exactly
// like CB's own default layout does ($params->get('login', $return)); blank →
// reload current page. The current-page fallback stays same-origin +
// root-relative so no open redirect is possible.
$returnUrl = JUri::getInstance()->toString();
$returnPath = parse_url($returnUrl, PHP_URL_PATH);
$returnQuery = parse_url($returnUrl, PHP_URL_QUERY);
$safeReturn = '/' . ltrim((string) $returnPath, '/');
if ($returnQuery !== '' && $returnQuery !== false && $returnQuery !== null) {
    $safeReturn .= '?' . $returnQuery;
}

$loginRedirect = trim((string) $params->get('login', ''));
if ($loginRedirect !== '' && preg_match('#^([a-z][a-z0-9+.\-]*):#i', $loginRedirect, $schemeMatch))
{
    // Absolute URL: http(s) only. Anything else (javascript:, data:,
    // vbscript:, ...) is rejected and the current-page fallback is used.
    if (in_array(strtolower($schemeMatch[1]), array('http', 'https'), true))
    {
        $safeReturn = $loginRedirect;
    }
}
elseif ($loginRedirect !== '')
{
    // No scheme: root-relative (/cb-profile) or non-SEF (index.php?...) — safe.
    $safeReturn = $loginRedirect;
}
// CB's login handler expects the module's native "B:" prefix + base64 wrapper.
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
$encodedReturn = 'B:' . base64_encode($safeReturn);

// Escape all admin-controllable params once.
$escTitle    = htmlspecialchars($module->title, ENT_COMPAT, 'UTF-8');
$escUsername = htmlspecialchars($styleUsername, ENT_COMPAT, 'UTF-8');
$escPassword = htmlspecialchars($stylePassword, ENT_COMPAT, 'UTF-8');
$escLoginBtn = htmlspecialchars($styleLoginBtn, ENT_COMPAT, 'UTF-8');
$escForgot   = htmlspecialchars($styleForgot, ENT_COMPAT, 'UTF-8');
$escRegister = htmlspecialchars($styleRegister, ENT_COMPAT, 'UTF-8');
$escReturn   = htmlspecialchars($encodedReturn, ENT_COMPAT, 'UTF-8');
$escLoginAction = htmlspecialchars($loginAction, ENT_COMPAT, 'UTF-8');
$escForgotUrl   = htmlspecialchars($forgotUrl, ENT_COMPAT, 'UTF-8');
$escRegisterUrl = htmlspecialchars($registerUrl, ENT_COMPAT, 'UTF-8');

// Enqueue external CSS (cacheable). Root-absolute URL: a plain relative URL
// like "templates/..." resolves against the current page path — on the
// homepage that is the site root, but on deeper routes (e.g.
// /cb-profile/jaydenrussell) it becomes /cb-profile/templates/... → 404, so
// the module renders unstyled ("default look"). Root-absolute works at any
// depth; rtrim() guarantees the leading slash even if JUri::root(true) is an
// empty string or already-slashed path.
$basePath = rtrim((string) JUri::root(true), '/\\');
$tplPath  = 'templates/' . JFactory::getApplication()->getTemplate();
$cssUrl   = $basePath . '/' . $tplPath . '/html/mod_cblogin/modernsoftblue.css';
echo '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl, ENT_COMPAT, 'UTF-8') . '" />';

// Enqueue external JS (cacheable).
$jsUrl = $basePath . '/' . $tplPath . '/html/mod_cblogin/modernsoftblue.js';
echo '<script src="' . htmlspecialchars($jsUrl, ENT_COMPAT, 'UTF-8') . '"></script>';
?>
<div class="scc-modern-blue" id="<?php echo $scc_id; ?>">
  <section class="scc-card">
    <?php if (trim($module->title) !== '') : ?>
      <h3 class="scc-card-title"><?php echo $escTitle; ?></h3>
    <?php endif; ?>

    <form action="<?php echo $escLoginAction; ?>"
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
          <a href="<?php echo $escForgotUrl; ?>" class="<?php echo $escForgot; ?>">Forgot Login?</a>
        <?php endif; ?>
        <button type="submit" name="Submit" class="scc-login-btn <?php echo $escLoginBtn; ?>">Log in</button>
      </div>

      <!-- Divider + Sign up -->
      <div class="scc-divider">New to SCC?</div>
      <?php if ($showRegister): ?>
        <div class="scc-login-links">
          <a href="<?php echo $escRegisterUrl; ?>" class="<?php echo $escRegister; ?>">Sign up</a>
        </div>
      <?php endif; ?>
    </form>
  </section>
</div>
