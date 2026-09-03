# Community Builder Login - Modern Blue — Joomla CB Login module skin

A Joomla **installable package** that skins the **Community Builder Login module**
(`mod_cblogin`) with a modern soft-blue "Modern Soft Blue" layout, and themes the CB
forgot-login page. Installs into the Astroid `tpl_jdseattle` template.

## Install

1. In Joomla Administration: **Extensions → Install**, upload
   `scc-login-card-overrides.zip`.
2. The package copies the overrides into:
   ```
   templates/tpl_jdseattle/html/mod_cblogin/modernsoftblue.php
   templates/tpl_jdseattle/html/mod_cblogin/modernsoftblue_logout.php
   templates/tpl_jdseattle/html/mod_cblogin/modernsoftblue.css
   templates/tpl_jdseattle/html/mod_cblogin/modernsoftblue_logout.css
   templates/tpl_jdseattle/html/mod_cblogin/modernsoftblue.js
   ```
3. In the CB Login module: **Advanced tab → Module Layout = "Modern Soft Blue"** (login),
   and the logout state uses `modernsoftblue_logout.php` automatically.
4. Clear Joomla cache.

> **Template-scoped (modified extension):** This is a customized layout override
> for the Community Builder Login module (`mod_cblogin`), modified for the
> **`tpl_jdseattle` (Astroid) template specifically**.
> Joomla layout overrides must live inside a template's `html/` folder, so the
> template name is hardcoded in the package manifest. If you switch templates,
> reinstall against the new template or copy the `html/mod_cblogin/` files manually.

## Files

```
pkg_cblogin-modern-blue.xml   # package manifest (type=package)
update.xml                    # update feed; hosted directly in the GitHub repo (NO release asset)
files/sccfiles.xml            # inner file-extension manifest (target = template html/)
files/html/mod_cblogin/
├── modernsoftblue.php            # Logged-OUT state: styled login form
├── modernsoftblue_logout.php     # Logged-IN state: avatar + welcome name + logout
├── modernsoftblue.css            # Externalized login styles (cacheable)
├── modernsoftblue_logout.css     # Externalized logout styles (cacheable)
└── modernsoftblue.js             # Externalized password-toggle JS (cacheable)
```

## Update feed

The Joomla update site is

```
https://raw.githubusercontent.com/jaydenrussell/cblogin-modern-blue/master/update.xml
```

This is the `update.xml` **file hosted in the repo itself**, served directly from GitHub's
raw host. It is a plain HTTP 200 — no release-asset redirect, no third-party CDN, nothing to
cache or "poison." When `update.xml` is committed/updated, Joomla sees it on the next
"Check for Updates" immediately; a new version is announced without re-releasing the feed.

The only release asset is the package zip (`cblogin-modern-blue.zip`) that `update.xml`
points its `<downloadurl>` at. The feed is a tracked repo file, so it is always up to date.

The installer's postflight (`repairUpdateSite`) rewrites/enables the update site to this raw
URL on every install/upgrade, so stale per-version feed URLs self-heal.

## Behaviour

- **Logged out:** card with username/email + password fields, remember-me
  (respects `remember_enabled`), forgot-login link, login button, sign-up link,
  password show/hide toggle.
- **Logged in:** avatar + display name via the **Community Builder API**
  (CB is always loaded for this module — no direct DB query needed), last-login
  time, logout button (CSRF-tokened).

## Avatar / image source

This package **only runs inside the CB Login module**, so Community Builder's full
API and fieldtype renderer are **always loaded** when this code executes. There is
no scenario where this override runs on a page without CB present.

Therefore the avatar and display name use the **Community Builder API directly**:
- Display name: `CBuser::getInstance($id)->getField('typename', null, 'raw')`
- Avatar: `CBuser::getInstance($id)->getField('avatar', null, 'html', 'none', 'profile')`

No direct database query is needed (unlike a generic module that could render
outside a CB context). If CB is ever absent, the `class_exists('CBuser')` guard
safely skips avatar rendering.

## Security

- `defined('_JEXEC') or die;` on both overrides.
- CSRF tokens via `JHtml::_('form.token')` on login + logout forms.
- URLs built with `JRoute::_()` / `JUri`.
- **v1.2.0 hardening:** all admin-controlled values (`$module->title` and the
  `$style*_cssclass` params) are escaped with `htmlspecialchars()` — eliminates
  the admin-only XSS vectors present in earlier versions.
- **v1.3.5 hardening:** CSS/JS externalized (cacheable, CSP-friendly); element
  IDs use `random_bytes()` (unpredictable, unique); avatar URLs restricted to
  same-origin/root-relative only (blocks data:/javascript:/cross-domain leaks);
  logout form uses a scoped, unique `id` to prevent duplicate-ID collisions;
  CB API calls wrapped in `try/catch` with graceful fallback.

## Version history

| Version | Notes |
|---------|-------|
| 1.1.4 | sccard.php initial card layout |
| 1.1.6 | sccard_logout.php: direct DB query for avatar (consistent path) |
| 1.2.0 | Packaged as installable Joomla package; security hardening (escape admin-controlled output) |
| 1.3.0 | Added CB forgot-login page theming (com_comprofiler override) + update.xml |
| 1.3.1 | Avatar + display name via CB API (no direct DB query — CB always loaded for this module) |
| 1.3.5 | Externalize CSS/JS; random_bytes() IDs; avatar URL sanitization; unique form IDs; try/catch around CB API |
| 1.3.6 | Update feed moved off GitHub Pages to the GitHub release asset (`releases/latest/download/update.xml`) |
| 1.3.7 | Update feed now served directly from the repo's tracked `update.xml` on raw.githubusercontent.com — 200, no release-asset redirect, no CDN; no release needed to announce a new version |

## License

GNU General Public License v2 or later.
