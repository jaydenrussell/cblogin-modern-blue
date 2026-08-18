# scc-login-card-overrides — SCC CB Login card layout override

Joomla **installable package** that drops the "sccard" layout overrides for the
**Community Builder Login module** (`mod_cblogin`) into the Astroid
`tpl_jdseattle` template. Replaces the default CB login / logout rendering with a
styled "SCC card" layout (avatar in header, welcome name, login form with icons +
password toggle, logout button).

## Install

1. In Joomla Administration: **Extensions → Install**, upload
   `scc-login-card-overrides.zip`.
2. The package copies the overrides into:
   ```
   templates/tpl_jdseattle/html/mod_cblogin/sccard.php
   templates/tpl_jdseattle/html/mod_cblogin/sccard_logout.php
   ```
3. In the CB Login module: **Advanced tab → Module Layout = "sccard"** (login),
   and the logout state uses `sccard_logout.php` automatically.
4. Clear Joomla cache.

> The template name `tpl_jdseattle` is hardcoded in the package manifest. If you
> switch templates, reinstall against the new template or copy the files manually.

## Files

```
scc-login-card-overrides.xml   # package manifest (type=package)
files/sccfiles.xml             # inner file-extension manifest (target = template html/)
files/html/mod_cblogin/
├── sccard.php         # Logged-OUT state: styled login form
└── sccard_logout.php  # Logged-IN state: avatar + welcome name + logout
```

## Behaviour

- **Logged out:** card with username/email + password fields, remember-me
  (respects `remember_enabled`), forgot-login link, login button, sign-up link,
  password show/hide toggle.
- **Logged in:** avatar (direct `#__comprofiler` query — the proven approach
  that works on every page), display name via CB `typename`, last-login time,
  logout button (CSRF-tokened).

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

## License

GNU General Public License v2 or later.
