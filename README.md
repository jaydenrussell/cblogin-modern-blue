# scc-login-card-overrides — SCC CB Login card layout override

Joomla template **layout overrides** for the **Community Builder Login module**
(`mod_cblogin`) on the Astroid `tpl_jdseattle` template. Replaces the default
CB login / logout rendering with a styled "SCC card" layout (avatar in header,
welcome name, login form with icons + password toggle, logout button).

This is **not** an installable extension — it is a template override.

## Install

1. Copy the `html/mod_cblogin/` folder into your template:
   ```
   templates/tpl_jdseattle/html/mod_cblogin/sccard.php
   templates/tpl_jdseattle/html/mod_cblogin/sccard_logout.php
   ```
2. In the CB Login module: **Advanced tab → Module Layout = "sccard"** (login)
   and the logout state uses `sccard_logout.php`.
3. Clear Joomla cache.

> The packaged `scc-login-card-overrides.zip` contains the `html/` folder
> structure ready to extract into the template root.

## Files

```
html/mod_cblogin/
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

## Notes

- Avatar uses a **direct DB query** to `#__comprofiler` (`user_id` keyed) because
  Community Builder's `getField('avatar')` only renders reliably when CB's
  fieldtype renderer is loaded (i.e. on a CB page or when a CB module is present).
  The DB query guarantees the image loads everywhere.
- Both files carry `defined('_JEXEC') or die;` and use `JHtml::_('form.token')`
  on state-changing forms (CSRF protected).

## Version history

| Version | Notes |
|---------|-------|
| 1.1.4 | sccard.php initial card layout |
| 1.1.6 | sccard_logout.php: direct DB query for avatar (consistent path) |

## License

GNU General Public License v2 or later.
