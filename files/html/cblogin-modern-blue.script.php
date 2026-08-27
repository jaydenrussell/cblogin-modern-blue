<?php
/**
 * Community Builder Login - Modern Blue — installer script.
 *
 * Postflight:
 *  1. Warns loudly if the active front-end template is not tpl_jdseattle, because
 *     every override file this package installs is scoped to that template.
 *  2. Guarantees the update site is ENABLED so Joomla's native Extensions > Update
 *     finds future releases. The site itself is registered by the manifest
 *     <updateservers> element (canonical registrar); this script only re-enables it
 *     on every install/update so a transient fetch timeout during "Find" cannot
 *     leave it permanently disabled on cheap hosting.
 *
 * @version 1.2.1
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
		$this->ensureUpdateSiteEnabled();
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
	 * Re-enable the registered update site on every install/update so a transient
	 * fetch timeout during "Extensions > Update > Find" cannot leave it disabled.
	 * Registration is owned by the manifest <updateservers>; this never inserts.
	 */
	private function ensureUpdateSiteEnabled()
	{
		$url = 'https://raw.githubusercontent.com/jaydenrussell/cblogin-modern-blue/master/update.xml';

		try
		{
			$db = \JFactory::getDbo();

			$check = $db->getQuery(true)
				->select('update_site_id')
				->from('#__update_sites')
				->where('location = ' . $db->q($url));
			$db->setQuery($check);
			$siteId = $db->loadResult();

			if ($siteId)
			{
				$db->setQuery(
					$db->getQuery(true)
						->update('#__update_sites')
						->set('enabled = 1')
						->where('update_site_id = ' . (int) $siteId)
				);
				$db->execute();
			}
		}
		catch (\Exception $e)
		{
			// Non-fatal: the manifest <updateservers> already registered the site.
			\JLog::add('CB Login Modern Blue: could not re-enable update site — ' . $e->getMessage(), \JLog::WARNING, 'jerror');
		}
	}
}
