<?php
/**
 * Community Builder Login - Modern Blue — installer script.
 *
 * Postflight:
 *  1. Warns loudly if the active front-end template is not tpl_jdseattle, because
 *     every override file this package installs is scoped to that template.
 *  2. Repairs + enables the update site on every install/update. The update URL is
 *     version-independent (.../releases/latest/download/update.xml) so deleting old
 *     releases can never break update checks. If a stale per-version URL was left
 *     behind by an earlier build, this rewrites it to the permanent URL.
 *
 * @version 1.2.4
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
	 * Ensure the registered update site points at the permanent, version-independent
	 * URL and is enabled. This repairs stale per-version URLs left by earlier builds
	 * (e.g. .../v1.2.1/update.xml after that release was deleted) so update checks
	 * stop 404-ing. Matches the site via the linked extension, not a hardcoded URL.
	 */
	private function repairUpdateSite()
	{
		$url = 'https://raw.githubusercontent.com/jaydenrussell/cblogin-modern-blue/master/update.xml';

		try
		{
			$db = \JFactory::getDbo();

			// Find the update site linked to THIS extension.
			$sub = $db->getQuery(true)
				->select('update_site_id')
				->from('#__update_sites_extensions')
				->where('extension_id = (SELECT extension_id FROM #__extensions WHERE element = ' . $db->q('cblogin-modern-blue') . ' AND type = ' . $db->q('file') . ')');
			$db->setQuery($sub);
			$siteId = $db->loadResult();

			if (!$siteId)
			{
				// Fallback: match by the old/current location string.
				$db->setQuery(
					$db->getQuery(true)
						->select('update_site_id')
						->from('#__update_sites')
						->where('location LIKE ' . $db->q('%cblogin-modern-blue%update.xml'))
				);
				$siteId = $db->loadResult();
			}

			if ($siteId)
			{
				$db->setQuery(
					$db->getQuery(true)
						->update('#__update_sites')
						->set('location = ' . $db->q($url))
						->set('enabled = 1')
						->where('update_site_id = ' . (int) $siteId)
				);
				$db->execute();
			}
		}
		catch (\Exception $e)
		{
			\JLog::add('CB Login Modern Blue: could not repair update site — ' . $e->getMessage(), \JLog::WARNING, 'jerror');
		}
	}
}
