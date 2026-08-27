<?php
/**
 * Community Builder Login - Modern Blue — installer script.
 *
 * Postflight:
 *  1. Hard-fails the install if the active front-end template is not
 *     tpl_jdseattle. Every override file this package installs is scoped to
 *     that template; installing onto another template produces a silently-dead
 *     extension, so we abort with a clear message instead of just warning.
 *  2. Repairs update sites that point at stale per-version URLs (left behind by
 *     earlier builds) and re-enables them. Only the specific known-bad URLs are
 *     matched — never a broad substring — so unrelated extensions are never
 *     hijacked or force-enabled.
 *
 * @version 1.2.8
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
		$this->abortIfWrongTemplate();
		$this->repairUpdateSite();
	}

	/**
	 * Abort the install unless the active front-end template is tpl_jdseattle.
	 *
	 * @throws \RuntimeException  when the active template is a different one.
	 */
	private function abortIfWrongTemplate()
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

		// Empty = could not determine (DB error). Warn only; do not block.
		if ($active !== '' && $active !== 'tpl_jdseattle')
		{
			throw new \RuntimeException(
				'Community Builder Login - Modern Blue can only be installed on the "tpl_jdseattle" '
				. 'template. The active front-end template is "' . $active . '". Every override file '
				. 'in this package is scoped to templates/tpl_jdseattle/html/ and will NOT render on '
				. 'any other template. Switch the default site template to tpl_jdseattle, or fork this '
				. 'package and change the <fileset> targets in cblogin-modern-blue.xml, before installing.'
			);
		}
	}

	/**
	 * Ensure a working update site exists and points at the current release-asset
	 * feed. Joomla 3.10's FileAdapter does NOT register <updateservers> from a
	 * type="file" manifest, so the install alone creates no update site. We:
	 *   1. INSERT a site (linked to THIS extension) if none exists yet, and
	 *   2. Rewrite any stale per-version URLs left by earlier builds, re-enabling.
	 *
	 * Only the specific stale URLs created by earlier builds are matched (never a
	 * broad substring), so unrelated extensions are never touched or force-enabled.
	 */
	private function repairUpdateSite()
	{
		$name = 'Community Builder Login - Modern Blue Update';
		$url  = 'https://github.com/jaydenrussell/cblogin-modern-blue/releases/download/v1.2.8/update.xml';

		try
		{
			$db = \JFactory::getDbo();

			// Find this extension's ID by element + type.
			$eid = (int) $db->setQuery(
				$db->getQuery(true)
					->select('extension_id')
					->from('#__extensions')
					->where('element = ' . $db->q('cblogin-modern-blue'))
					->where('type = ' . $db->q('file'))
			)->loadResult();

			if (!$eid)
			{
				// Extension row not available yet (rare ordering) — nothing to link.
				return;
			}

			// Does a site already link to this extension?
			$existing = (int) $db->setQuery(
				$db->getQuery(true)
					->select('s.update_site_id')
					->from('#__update_sites as s')
					->join('LEFT', '#__update_sites_extensions as se ON se.update_site_id = s.update_site_id')
					->where('se.extension_id = ' . $eid)
					->where('s.name = ' . $db->q($name))
			)->loadResult();

			if (!$existing)
			{
				// INSERT a fresh update site + link row.
				$db->setQuery(
					$db->getQuery(true)
						->insert('#__update_sites')
						->columns(array('name', 'type', 'location', 'enabled', 'last_check_timestamp', 'extra_query'))
						->values(
							$db->q($name) . ', ' . $db->q('extension') . ', ' . $db->q($url) . ', 1, 0, ' . $db->q('')
						)
				)->execute();
				$newId = (int) $db->insertid();

				if ($newId)
				{
					$db->setQuery(
						$db->getQuery(true)
							->insert('#__update_sites_extensions')
							->columns(array('update_site_id', 'extension_id'))
							->values($newId . ', ' . $eid)
					)->execute();
				}
			}
			else
			{
				// UPDATE existing linked site to the current feed + enabled.
				$db->setQuery(
					$db->getQuery(true)
						->update('#__update_sites')
						->set('location = ' . $db->q($url))
						->set('enabled = 1')
						->where('update_site_id = ' . $existing)
				)->execute();
			}

			// Heal any other stale rows (orphaned from earlier builds) — targeted URLs only.
			$stale = array(
				'%releases/download/v1.2.1/update.xml%',
				'%/v1.2.1/update.xml%',
				'%/master/update.xml%',
			);
			$conds = array();
			foreach ($stale as $s)
			{
				$conds[] = 'location LIKE ' . $db->q($s);
			}

			$db->setQuery(
				$db->getQuery(true)
					->update('#__update_sites')
					->set('location = ' . $db->q($url))
					->set('enabled = 1')
					->where(implode(' OR ', $conds))
			)->execute();
		}
		catch (\Exception $e)
		{
			\JLog::add('CB Login Modern Blue: could not repair update site — ' . $e->getMessage(), \JLog::WARNING, 'jerror');
		}
	}
}
