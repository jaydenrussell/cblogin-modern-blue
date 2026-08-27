<?php
/**
 * Community Builder Login - Modern Blue — installer script.
 *
 * Postflight:
 *  1. Warns loudly if the active front-end template is not tpl_jdseattle, because
 *     every override file this package installs is scoped to that template.
 *  2. Repairs + enables the update site on every install/update. The update URL is
 *     served from the repo master branch (raw) so deleting old releases can never
 *     break update checks. Any stale per-version URL (e.g. .../v1.2.1/update.xml)
 *     left by an earlier build is rewritten to the current URL.
 *
 * @version 1.2.5
 */
defined('_JEXEC') or die;

class cbloginmodernblueInstallerScript
{
	/**
	 * @param   string  $route    install|update|discover_install
	 * @param   object  $adapter  Joomla installer adapter
	 */
	public function postflight($route, $adapter)
	{
		$this->warnIfWrongTemplate();
		$this->repairUpdateSite();
	}

	/**
	 * Warn if the active front-end template is not the one these overrides target.
	 */
	private function warnIfWrongTemplate()
	{
		try
		{
			$db = \JFactory::getDbo();
			$query = $db->getQuery(true)
				->select('template')
				->from('#__template_styles')
				->where('client_id = 0')
				->where('home = 1');
			$db->setQuery($query);
			$active = (string) $db->loadResult();
		}
		catch (\Exception $e)
		{
			$active = '';
		}

		if ($active !== '' && $active !== 'tpl_jdseattle')
		{
			\JLog::add(
				'Community Builder Login - Modern Blue installed, but the active front-end template is "'
					. $active
					. '", not "tpl_jdseattle". All overrides in this package are scoped to tpl_jdseattle '
					. 'and will NOT render until the template matches or the override files are copied '
					. 'into templates/' . $active . '/html/.',
				\JLog::WARNING,
				'jerror'
			);
		}
	}

	/**
	 * Ensure ALL registered update sites for this package point at the permanent,
	 * version-independent URL and are enabled. This repairs stale per-version URLs
	 * left by earlier builds (e.g. .../v1.2.1/update.xml after that release was
	 * deleted) so update checks stop 404-ing. Updates EVERY matching row, not just
	 * the first, so orphaned sites (like #91) are healed regardless of how Joomla
	 * matched the manifest <updateservers> on reinstall.
	 */
	private function repairUpdateSite()
	{
		$url = 'https://raw.githubusercontent.com/jaydenrussell/cblogin-modern-blue/master/update.xml';

		try
		{
			$db = \JFactory::getDbo();

			// Heal every row whose location references this package's update.xml,
			// whether linked to this extension or left orphaned by an earlier build.
			$db->setQuery(
				$db->getQuery(true)
					->update('#__update_sites')
					->set('location = ' . $db->q($url))
					->set('enabled = 1')
					->where('location LIKE ' . $db->q('%cblogin-modern-blue%update.xml'))
			);
			$db->execute();
		}
		catch (\Exception $e)
		{
			\JLog::add('CB Login Modern Blue: could not repair update site — ' . $e->getMessage(), \JLog::WARNING, 'jerror');
		}
	}
}
