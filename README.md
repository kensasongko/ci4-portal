# Portal — CodeIgniter 4 App with Microsoft Entra ID SSO

A CodeIgniter 4 portal application that combines a local username/password login
with Microsoft Entra ID (Azure AD) single sign-on via OpenID Connect. After
sign-in users land on a dashboard that launches the applications they have
access to, with per-app login URLs chosen by authentication source. It also
ships a small RESTful user API and JIT user provisioning.

## Tech Stack

- PHP `^7.4 || ^8.0`
- [CodeIgniter 4](https://codeigniter.com) `^4.5`
- [thenetworg/oauth2-azure](https://github.com/TheNetworg/oauth2-azure) `^2.2` for Entra OIDC
- PHPUnit `^9.1`, fakerphp/faker, vfsstream (dev)

## Features

- Local login form (`Account` controller) with CodeIgniter Validation and real
  credential checks (`Maccount::validateLogin` via `password_verify`).
- Microsoft Entra ID OIDC sign-in (authorization code flow with `state` and
  `nonce` verification, optional strict tenant check).
- Federated logout against Microsoft's `oauth2/v2.0/logout` end-session endpoint.
- JIT user provisioning, plus linking of existing local accounts by username.
- Portal dashboard listing the active applications a signed-in user can open,
  each with separate SSO and local login URLs resolved by auth source.
- Applications management (CRUD) for the catalog: name, description, icon,
  color, login URLs, active toggle, and sort order.
- Authenticated home page and login/redirect filters.
- RESTful `userapi` resource controller (CRUD over the `user` table).

## Project Layout

```
app/
  Config/
    Azure.php          # Tenant/client/redirect/scope settings (env-driven)
    Filters.php        # Aliases: authlogin, redirectlogin
    Routes.php         # Login, home, applications, /auth/azure/*, userapi resource
  Controllers/
    Account.php        # Local login/logout
    Applications.php   # Applications catalog CRUD + status toggle
    AzureAuth.php      # /auth/azure[/callback|/logout]
    Home.php           # Authenticated dashboard (lists active applications)
    Userapi.php        # RESTful CRUD
  Filters/
    Authlogin.php      # Requires session('user'); redirects to /login
    Redirectlogin.php  # Sends already-authenticated users to /home
  Libraries/
    AzureAuthService.php  # OAuth state/nonce, token exchange, claims normalization
  Models/
    Maccount.php       # validateLogin, findOrCreateByAzureOid (JIT)
    Mapplication.php   # Applications table; getActiveApplications()
    Muserapi.php       # Simple model over `user`
  Database/Migrations/
    2026-04-27-000001_AddSsoColumnsToUser.php
    2026-05-08-000001_CreateApplicationsTable.php
  Views/
    v_login.php, welcome_message.php  # login form, dashboard
    applications/index.php, applications/form.php
public/                # Web root (point your server here)
writable/              # Logs, cache, uploads, sessions
tests/                 # PHPUnit suite scaffolding
```

## Routes

| Method | Path                   | Handler                  | Notes                              |
|--------|------------------------|--------------------------|------------------------------------|
| GET    | `/`                    | `Account::login`         | `redirectlogin` filter             |
| GET    | `/login`               | `Account::login`         | `redirectlogin` filter             |
| POST   | `/login`               | `Account::login`         | Local credential login             |
| GET    | `/logout`              | `Account::logout`        | Destroys session                   |
| GET    | `/home`                | `Home::index`            | Dashboard of active applications    |
| GET    | `/applications`        | `Applications::index`    | `authlogin`; lists the catalog     |
| GET    | `/applications/create` | `Applications::create`   | `authlogin`; new-app form          |
| POST   | `/applications/store`  | `Applications::store`    | `authlogin`; validates and inserts |
| GET    | `/applications/edit/(:num)`   | `Applications::edit/$1`        | `authlogin`; edit form     |
| POST   | `/applications/update/(:num)` | `Applications::update/$1`      | `authlogin`; validates/updates |
| POST   | `/applications/delete/(:num)` | `Applications::delete/$1`      | `authlogin`; deletes a row |
| POST   | `/applications/toggle/(:num)` | `Applications::toggleStatus/$1`| `authlogin`; flips `is_active` |
| GET    | `/auth/azure`          | `AzureAuth::start`       | Builds Entra authorize URL         |
| GET    | `/auth/azure/callback` | `AzureAuth::callback`    | Exchanges code, validates claims   |
| GET    | `/auth/azure/logout`   | `AzureAuth::logout`      | Federated logout via Microsoft     |
| *      | `/userapi[/(:num)]`    | `Userapi` (resource)     | `index`/`create`/`show`/`update`/`delete` |

## Setup

1. Install dependencies:

   ```bash
   composer install
   ```

2. Create your environment file:

   ```bash
   cp env .env
   ```

3. Edit `.env` and set at minimum:

   ```ini
   CI_ENVIRONMENT = development
   app.baseURL = 'https://your-host/'

   database.default.hostname = localhost
   database.default.database = ci4
   database.default.username = root
   database.default.password = root
   database.default.DBDriver = MySQLi
   ```

4. Configure the web server document root to point to the `public/` folder, not
   the project root. `index.php` lives in `public/` for security.

## Database Migrations

- **`AddSsoColumnsToUser`** adds the columns the auth flow expects (`azure_oid`,
  `email`, `name`, `auth_source`, `last_login_at`) and a unique index on
  `azure_oid`. It assumes a pre-existing `user` table (with at least `id`,
  `username`, `password`).
- **`CreateApplicationsTable`** creates the `applications` catalog table: `name`,
  `description`, `icon` (Font Awesome class), `color` (Bootstrap color name),
  `sso_login_url`, `local_login_url`, `is_active`, `sort_order`, and timestamps,
  with indexes on `sort_order` and `is_active`.

```bash
php spark migrate
```

To roll back:

```bash
php spark migrate:rollback
```

## Configuring Microsoft Entra ID SSO

1. **Register an app** in the Entra portal. Add a Web platform redirect URI:
   `https://your-host/auth/azure/callback`. Optionally set a post-logout
   redirect URI: `https://your-host/login`.
2. **Create a client secret** under *Certificates & secrets*.
3. **Grant API permissions** for `openid`, `profile`, `email`, `offline_access`
   (Microsoft Graph delegated). Grant admin consent if your tenant requires it.
4. **Populate `.env`**:

   ```ini
   azure.tenantId             = '<tenant-guid>'
   azure.clientId             = '<application-client-id>'
   azure.clientSecret         = '<client-secret-value>'
   azure.redirectUri          = 'https://your-host/auth/azure/callback'
   azure.postLogoutRedirectUri = 'https://your-host/login'
   azure.allowLocalLogin      = true
   azure.strictTenantCheck    = true
   azure.jitProvisioning      = true
   ```

   Defaults from `app/Config/Azure.php`:
   - `scopes = ['openid', 'profile', 'email', 'offline_access']`
   - `endpointVersion = '2.0'`
   - `strictTenantCheck = true` (rejects ID tokens whose `tid` is not
     `tenantId`; flip to `false` only for multi-tenant apps)
   - `jitProvisioning = true` (auto-creates a `user` row on first login;
     when `false`, unknown Entra users are rejected)

## Auth Flow

1. User clicks the Microsoft button on `/login`, hitting `/auth/azure`.
2. `AzureAuthService::getAuthorizationUrl` builds the OIDC authorize URL,
   stashing `state` and `nonce` in the session.
3. Microsoft redirects to `/auth/azure/callback` with `code` + `state`.
4. The callback verifies `state` (`hash_equals`), exchanges the code for
   tokens, validates ID-token `nonce`, and (when enabled) checks `tid` against
   the configured tenant.
5. `Maccount::findOrCreateByAzureOid` looks up the user by `azure_oid`, falls
   back to linking by `username`, and otherwise inserts a new row when JIT is
   on. It also stamps `last_login_at` and refreshes `email`/`name`.
6. The session is regenerated and the user is sent to `/home`.

## Applications & Dashboard

After login, `/home` renders the portal dashboard listing the active entries
from the `applications` table (`Mapplication::getActiveApplications`, ordered by
`sort_order` then `name`). Each application card shows its icon and color and
links to a login URL.

Manage the catalog under `/applications` (all routes require the `authlogin`
filter):

- Create, edit, and delete applications, or toggle `is_active` without opening
  the form.
- Each application carries two destinations — `sso_login_url` for users
  authenticated via Entra ID and `local_login_url` for locally authenticated
  users — so the dashboard can route by auth source.
- `color` is constrained to a Bootstrap palette (`primary`, `success`, `danger`,
  `warning`, `info`, `secondary`, `dark`); `icon` holds a Font Awesome class
  (e.g. `fa-cogs`).

## Running Tests

```bash
composer test
```

This runs `phpunit` with the bundled `phpunit.xml.dist` configuration.

## Server Requirements

PHP 7.4 or higher, with the standard CodeIgniter 4 extension set:

- `intl`
- `mbstring`
- `json` (default)
- `mysqlnd` for MySQL
- `libcurl` for HTTP client features
- `openssl` (used by the Entra OAuth library)

## License

MIT — see `LICENSE`.
