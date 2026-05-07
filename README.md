# CodeIgniter 4 App with Microsoft Entra ID SSO

A CodeIgniter 4 application that combines a local username/password login with
Microsoft Entra ID (Azure AD) single sign-on via OpenID Connect, plus a small
RESTful user API and JIT user provisioning.

## Tech Stack

- PHP `^7.4 || ^8.0`
- [CodeIgniter 4](https://codeigniter.com) `^4.5`
- [thenetworg/oauth2-azure](https://github.com/TheNetworg/oauth2-azure) `^2.2` for Entra OIDC
- PHPUnit `^9.1`, fakerphp/faker, vfsstream (dev)

## Features

- Local login form (`Account` controller) with CodeIgniter Validation.
- Microsoft Entra ID OIDC sign-in (authorization code flow with `state` and
  `nonce` verification, optional strict tenant check).
- Federated logout against Microsoft's `oauth2/v2.0/logout` end-session endpoint.
- JIT user provisioning, plus linking of existing local accounts by username.
- Authenticated home page and login/redirect filters.
- RESTful `userapi` resource controller (CRUD over the `user` table).

## Project Layout

```
app/
  Config/
    Azure.php          # Tenant/client/redirect/scope settings (env-driven)
    Filters.php        # Aliases: authlogin, redirectlogin
    Routes.php         # Login, home, /auth/azure/*, userapi resource
  Controllers/
    Account.php        # Local login/logout
    AzureAuth.php      # /auth/azure[/callback|/logout]
    Home.php           # Authenticated landing page
    Userapi.php        # RESTful CRUD
  Filters/
    Authlogin.php      # Requires session('user'); redirects to /login
    Redirectlogin.php  # Sends already-authenticated users to /home
  Libraries/
    AzureAuthService.php  # OAuth state/nonce, token exchange, claims normalization
  Models/
    Maccount.php       # validateLogin, findOrCreateByAzureOid (JIT)
    Muserapi.php       # Simple model over `user`
  Database/Migrations/
    2026-04-27-000001_AddSsoColumnsToUser.php
  Views/
    v_login.php, welcome_message.php
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
| GET    | `/home`                | `Home::index`            | Renders welcome view with session  |
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

## Database Migration

The SSO migration adds the columns the auth flow expects (`azure_oid`, `email`,
`name`, `auth_source`, `last_login_at`) and a unique index on `azure_oid`. It
assumes a pre-existing `user` table (with at least `id`, `username`, `password`).

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
