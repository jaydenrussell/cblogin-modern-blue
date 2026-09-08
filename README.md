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
├── cbmenu.php                    # Shared CB menu URL resolver (canonical SEF routes)
├── modernsoftblue.php            # Logged-OUT state: styled login form
├── modernsoftblue_logout.php     # Logged-IN state: avatar + welcome name + logout
├── modernsoftblue.css            # Externalized login styles (cacheable)
├── modernsoftblue_logout.css     # Externalized logout styles (cacheable)
└── modernsoftblue.js             # Externalized password-toggle JS (cacheable)
```

## URL resolution

Profile / edit-profile / forgot-login / login / logout links are **not** built from
hardcoded aliases (`cb-profile`, etc.) or raw `/component/com_comprofiler/` URLs. The
shared `cbmenu.php` helper (`SccCbMenuResolver`) finds the canonical public CB menu item
from the Joomla menu system (`#__menu`) by component/view — or honours an explicitly
configured `Itemid` — then emits SEF URLs through `JRoute::_()` with that `Itemid`. Custom
CB menu aliases keep working without code changes, and one route stays canonical.

Optional module params that are read when present:
- `profile_itemid` — canonical CB profile menu item id (auto-discovered if 0)
- `forgot_login_itemid` — canonical CB forgot-login menu item id (auto-discovered if 0)

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
| 1.3.8 | Logout header: greeting + name on one line; avatar pops out of the top-right rounded card corner (unchanged 48px) |
| 1.3.9 | Logout avatar: position via negative rem margins (margin-top:-4rem; margin-left:-1rem) instead of top/right px offsets; revert extra card padding |
| 1.3.10 | Mobile (<480px): tighter margins/padding on logout card; avatar negative margins reduced; static 48px avatar kept |
| 1.3.11 | Shared `cbmenu.php` URL resolver: canonical CB menu routes (no hardcoded aliases/`/component/com_comprofiler/`), optional `profile_itemid`/`forgot_login_itemid`, used by login + logout overrides |
| 1.3.12 | Production hardening: PHP 5.x id-seed fallback (`random_bytes` guard) + manifest PHP/Joomla minimums; resolver DB failures fall back to routed URLs instead of white-screening; per-request memoized `#__menu` scans; option/view boundary matching; escaped URL output; stub-based resolver test harness + CI |
| 1.3.13 | First (inactive) attempt at honoring the CB redirect params — used guessed keys `login_redirection_url` / `logout_redirection_url`, which do not exist in `mod_cblogin`; no functional effect, superseded by 1.3.14 |
| 1.3.14 | **Fix:** CB redirect params actually honored. Real `mod_cblogin` keys are `login` (Login Redirection URL) and `logout` (Logout Redirection URL); values are emitted as CB's native `B:` + base64 `return` fragment exactly like CB's default layout (blank login → current page; logout `#` → current page, blank/`index.php` → home; safe scheme validation) |
| 1.3.15 | **Fix:** login/logout redirects now actually redirect. CB 2.x whitelists the posted `return` to `live_site`- or `index.php`-prefixed URLs and silently discards bare root-relative aliases (e.g. `/cb-profile` → homepage). The layouts now run the target through CB's `cbSef()` (like native `mod_cblogin`) so the encoded value is absolute and passes the whitelist |

## Tests

`tests/cbmenu_resolver_harness.php` stubs `JFactory`/`JRoute` and a fake
`#__menu` so the resolver's matching, access filtering, memoization and
DB-failure fallbacks run **without a Joomla install**:

    php tests/cbmenu_resolver_harness.php

CI (`.github/workflows/php.yml`) lints every PHP file and runs the harness on
PHP 5.6 / 7.4 / 8.1.

## License

GNU General Public License v2 or later.
