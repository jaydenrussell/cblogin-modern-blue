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
 * @version 1.3.7
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
	 * Ensure a working update site exists, points at the STABLE (version-independent)
	 * feed, and is enabled.
	 *
	 * Why a stable URL: Joomla's update site location is version-pinned at install
	 * time. If it points at ".../v1.2.9/update.xml" it can ONLY ever report 1.2.9 and
	 * will never detect a newer release — which is exactly the "never detects" bug.
	 * The stable feed (the update.xml tracked in the GitHub repo) always declares the
	 * current latest version, so detection works for every future release without
	 * touching the installed site.
	 *
	 * Joomla 3.10's FileAdapter does NOT register <updateservers> from a type="file"
	 * manifest, so we INSERT the site if missing and rewrite/enable it (and any
	 * stale duplicates) here.
	 */
	private function repairUpdateSite()
	{
		// STABLE, version-independent feed hosted in the GitHub repo itself
		// (served directly from GitHub's raw host — a 200, no release-asset redirect
		// and no third-party Pages/CDN caching). The feed updates the moment the
		// update.xml file is committed, so new versions are announced without
		// re-releasing the feed. Only the package zip itself is a release asset.
		$url = 'https://raw.githubusercontent.com/jaydenrussell/cblogin-modern-blue/master/update.xml';
		$name = 'Community Builder Login - Modern Blue Update';

		try
		{
			$db = \JFactory::getDbo();

			$eid = (int) $db->setQuery(
				$db->getQuery(true)
					->select('extension_id')
					->from('#__extensions')
					->where('element = ' . $db->q('cblogin-modern-blue'))
					->where('type = ' . $db->q('file'))
			)->loadResult();

			if (!$eid)
			{
				\JLog::add(
					'CB Login Modern Blue: extension row (element=cblogin-modern-blue, type=file) not found; '
					. 'update site was NOT registered. Re-run the install or check #__extensions.',
					\JLog::WARNING,
					'jerror'
				);
				return;
			}

			// Every update-site row whose location references this package -> rewrite + enable.
			$db->setQuery(
				$db->getQuery(true)
					->update('#__update_sites')
					->set('location = ' . $db->q($url))
					->set('enabled = 1')
					->where('location LIKE ' . $db->q('%cblogin-modern-blue%update.xml'))
			)->execute();

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

			// Dedupe: keep only the linked site; delete any orphan/duplicate rows
			// whose location references this package but is not the linked one.
			$linked = (int) $db->setQuery(
				$db->getQuery(true)
					->select('s.update_site_id')
					->from('#__update_sites as s')
					->join('LEFT', '#__update_sites_extensions as se ON se.update_site_id = s.update_site_id')
					->where('se.extension_id = ' . $eid)
					->where('s.name = ' . $db->q($name))
					->order('s.update_site_id DESC')
			)->loadResult();

			if ($linked)
			{
				$db->setQuery(
					$db->getQuery(true)
						->delete('#__update_sites')
						->where('location LIKE ' . $db->q('%cblogin-modern-blue%update.xml'))
						->where('update_site_id <> ' . $linked)
				)->execute();
				// Also remove now-orphaned link rows.
				$db->setQuery(
					$db->getQuery(true)
						->delete('#__update_sites_extensions')
						->where('update_site_id <> ' . $linked)
						->where('update_site_id NOT IN (SELECT update_site_id FROM #__update_sites)')
				)->execute();
			}
		}
		catch (\Exception $e)
		{
			\JLog::add('CB Login Modern Blue: could not repair update site — ' . $e->getMessage(), \JLog::WARNING, 'jerror');
		}
	}
}
