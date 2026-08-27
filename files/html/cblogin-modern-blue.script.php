<?php
/**
 * CB Login Modern Blue — installer script.
 *
 * Postflight warns loudly if the active site template is not tpl_jdseattle,
 * because every override file this package installs is scoped to that template.
 * Without this check a later maintainer installs blindly, sees nothing change,
 * and wastes time.
 *
 * @version 1.1.1
 */
defined('_JEXEC') or die;

class cbloginmodernblueInstallerScript
{
	public function postflight($route, $adapter)
	{
		// Active template (front-end) as chosen by the template style assignment.
		try {
			$db = \JFactory::getDbo();
			$query = $db->getQuery(true)
				->select('template')
				->from('#__template_styles')
				->where('client_id = 0')
				->where('home = 1');
			$db->setQuery($query);
			$active = (string) $db->loadResult();
		} catch (\Exception $e) {
			$active = '';
		}

		if ($active !== '' && $active !== 'tpl_jdseattle') {
			\JLog::add(
				'CB Login Modern Blue installed, but the active front-end template is "'
					. $active
					. '", not "tpl_jdseattle". All overrides in this package are scoped to tpl_jdseattle '
					. 'and will NOT render until the template matches or the override files are copied '
					. 'into templates/' . $active . '/html/.',
				\JLog::WARNING,
				'jerror'
			);
		}
	}
}
