<?php
/**
 * CB lostpassword view override — SCC theming
 * ----------------------------------------------------------------------
 * Joomla component override: templates/tpl_jdseattle/html/com_comprofiler/lostpassword/default.php
 * Injects the SCC forgot-login CSS, then includes Community Builder's original
 * lostpassword view so all CB rendering is preserved.
 *
 * @version 1.1.0
 */
defined('_JEXEC') or die;

// Inject SCC forgot-login CSS (read via JFile; escape the markup we echo).
$cssFile = __DIR__ . '/../scc-forgot-login.css';
if (is_file($cssFile) && is_readable($cssFile)) {
    $css = \JFile::read($cssFile);
    if ($css !== false && $css !== '') {
        echo '<style>' . $css . '</style>';
    }
}

// Include CB's original lostpassword view (try known CB 2.x locations).
// Only COMPONENT paths under JPATH_ROOT are allowed — never anything user-controlled.
$origCandidates = array(
    JPATH_ROOT . '/components/com_comprofiler/views/lostpassword/tmpl/default.php',
    JPATH_ROOT . '/components/com_comprofiler/plugin/user/plug_lostpassword/lostpassword.php',
    JPATH_ROOT . '/components/com_comprofiler/plugin/user/plug_lostpassword/tmpl/default.php',
);

$found = false;
foreach ($origCandidates as $orig) {
    if (is_file($orig) && strpos(realpath($orig), JPATH_ROOT . '/components/com_comprofiler') === 0) {
        include $orig;
        $found = true;
        break;
    }
}

if (!$found) {
    echo '<p class="scc-forgot-login-fallback">Unable to load the Community Builder lost-password form. Please contact the site administrator.</p>';
}
