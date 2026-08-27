<?php
/**
 * Community Builder Login - Modern Blue — installer script.
 *
 * Postflight:
 *  1. Warns loudly if the active front-end template is not tpl_jdseattle, because
 *     every override file this package installs is scoped to that template.
 *  2. Guarantees the update site URL is registered + enabled in #__update_sites so
 *     Joomla's native Extensions > Update finds future releases. type="file"
 *     extensions occasionally don't persist the <updateservers> registration on
 *     some hosts; this makes update detection reliable regardless.
 *
 * @version 1.2.0
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
		$this->ensureUpdateSite();
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
	 * Make sure the GitHub update.xml is registered as an update site and enabled,
	 * so Extensions > Update detects new releases. Idempotent: does nothing if the
	 * site is already registered at the same URL.
	 */
	private function ensureUpdateSite()
	{
		$url  = 'https://raw.githubusercontent.com/jaydenrussell/cblogin-modern-blue/master/update.xml';
		$name = 'Community Builder Login - Modern Blue';

		try
		{
			$db = \JFactory::getDbo();

			$check = $db->getQuery(true)
				->select('update_site_id')
				->from('#__update_sites')
				->where('location = ' . $db->q($url));
			$db->setQuery($check);
			$siteId = $db->loadResult();

			if (!$siteId)
			{
				$site = new \stdClass;
				$site->name = $name;
				$site->type = 'extension';
				$site->location = $url;
				$site->enabled = 1;
				$site->last_check_timestamp = 0;
				$site->extra_query = '';
				$db->insertObject('#__update_sites', $site);
				$siteId = $db->insertid();

				// Link the update site to this installed extension.
				$extQ = $db->getQuery(true)
					->select('extension_id')
					->from('#__extensions')
					->where('element = ' . $db->q('cblogin-modern-blue'))
					->where('type = ' . $db->q('file'));
				$db->setQuery($extQ);
				$extId = $db->loadResult();

				if ($extId)
				{
					$link = new \stdClass;
					$link->update_site_id = $siteId;
					$link->extension_id = $extId;
					$db->insertObject('#__update_sites_extensions', $link);
				}
			}
			else
			{
				// Already registered — just make sure it is enabled.
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
			// Non-fatal: the manifest <updateservers> already provides a fallback registration.
			\JLog::add('CB Login Modern Blue: could not verify update site registration — ' . $e->getMessage(), \JLog::WARNING, 'jerror');
		}
	}
}
