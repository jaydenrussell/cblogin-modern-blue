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
update.xml                    # GitHub-based update feed
files/sccfiles.xml            # inner file-extension manifest (target = template html/)
files/html/mod_cblogin/
├── modernsoftblue.php          # Logged-OUT state: styled login form
└── modernsoftblue_logout.php   # Logged-IN state: avatar + welcome name + logout
```

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

## Version history

| Version | Notes |
|---------|-------|
| 1.1.4 | sccard.php initial card layout |
| 1.1.6 | sccard_logout.php: direct DB query for avatar (consistent path) |
| 1.2.0 | Packaged as installable Joomla package; security hardening (escape admin-controlled output) |
| 1.3.0 | Added CB forgot-login page theming (com_comprofiler override) + update.xml |
| 1.3.1 | Avatar + display name via CB API (no direct DB query — CB always loaded for this module) |

## License

GNU General Public License v2 or later.
