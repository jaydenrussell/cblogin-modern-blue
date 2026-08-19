<?php
/**
 * CB lostpassword view override — SCC theming
 * ----------------------------------------------------------------------
 * Joomla component override: templates/tpl_jdseattle/html/com_comprofiler/lostpassword/default.php
 * Injects the SCC forgot-login CSS, then includes Community Builder's original
 * lostpassword view so all CB rendering is preserved.
 */
defined('_JEXEC') or die;

// Inject SCC forgot-login CSS
$cssFile = __DIR__ . '/../scc-forgot-login.css';
if (file_exists($cssFile)) {
    echo '<style>' . file_get_contents($cssFile) . '</style>';
}

// Include CB's original lostpassword view (try known CB 2.x locations)
$origCandidates = array(
    JPATH_ROOT . '/components/com_comprofiler/views/lostpassword/tmpl/default.php',
    JPATH_ROOT . '/components/com_comprofiler/plugin/user/plug_lostpassword/lostpassword.php',
    JPATH_ROOT . '/components/com_comprofiler/plugin/user/plug_lostpassword/tmpl/default.php',
);

foreach ($origCandidates as $orig) {
    if (file_exists($orig)) {
        include $orig;
        break;
    }
}
