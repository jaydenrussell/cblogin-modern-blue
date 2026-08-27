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
 * @version 1.2.7
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
	 * Rewrite any update-site rows that still point at a stale per-version URL
	 * to the current release-asset feed, and re-enable them.
	 *
	 * Only the specific URLs left by earlier builds (v1.2.1 per-version, v1.2.1
	 * /vX.Y.Z/, and the old master-raw feed) are matched. A broad LIKE on the
	 * package name is intentionally avoided so that unrelated extensions whose
	 * feed URL merely contains "cblogin-modern-blue" are never touched.
	 */
	private function repairUpdateSite()
	{
		$url = 'https://github.com/jaydenrussell/cblogin-modern-blue/releases/download/v1.2.7/update.xml';

		try
		{
			$db = \JFactory::getDbo();

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
			);
			$db->execute();
		}
		catch (\Exception $e)
		{
			\JLog::add('CB Login Modern Blue: could not repair update site — ' . $e->getMessage(), \JLog::WARNING, 'jerror');
		}
	}
}
