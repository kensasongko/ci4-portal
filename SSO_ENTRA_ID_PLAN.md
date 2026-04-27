# SSO Implementation Plan: Microsoft Entra ID for CodeIgniter 4

## 1. Current State of the Project

The project is the standard CodeIgniter 4 starter (`codeigniter4/appstarter`, PHP `^7.4 || ^8.0`) with a small custom auth layer already in place:

- `app/Controllers/Account.php` — `Login()` and `Logout()` actions. Username/password validation rules are wired up; the actual DB check (`Maccount::validateLogin`) is currently commented out and the controller redirects straight to `home`.
- `app/Models/Maccount.php` — verifies `username` + `password_verify()` against a `user` table and writes `['user' => ..., 'logged_in' => true]` into the session.
- `app/Filters/Authlogin.php` — guards routes by checking `session('user')`; redirects to `/login` if missing.
- `app/Filters/Redirectlogin.php` — bounces already-authenticated users from `/login` to `/home`.
- `app/Config/Routes.php` — `/`, `/login` (GET/POST), `/logout`, plus `/home` and a `userapi` resource.
- `app/Config/Filters.php` — registers `authlogin` and `redirectlogin` aliases. No global `before` filters protect `home` yet (relies on per-route filters).
- `app/Views/v_login.php` — Bootstrap-based login form posting to `/login`.
- No migrations exist (`app/Database/Migrations/` is empty); the `user` table is presumed to exist out-of-band.

The session-based identity contract — i.e. `session('user')` populated and `logged_in => true` — is the integration seam SSO must hook into so the rest of the app keeps working unchanged.

## 2. Goal & Approach

Add **Microsoft Entra ID** (formerly Azure AD) as an authentication option using the **OpenID Connect Authorization Code flow** (v2.0 endpoint). Local username/password login can either coexist or be replaced — the plan supports both modes via a flag.

**Library choice:** `thenetworg/oauth2-azure` (built on `league/oauth2-client`).

Why this library:
- Maintained, widely used, supports the Entra ID v2.0 endpoint, multi-tenant tenants, and exposes ID-token claims directly.
- Pure PHP, no framework coupling — drops cleanly into CI4 controllers.
- Compatible with PHP 7.4+ which matches `composer.json`.

Alternative considered: raw `league/oauth2-client` + manual Microsoft endpoints (more code, no benefit) and SAML via `simplesamlphp/simplesamlphp` (heavier, only choose if the IdP team mandates SAML). OIDC is recommended unless SAML is a hard requirement.

## 3. Prerequisites

1. Tenant access in Microsoft Entra admin center to register an app.
2. Composer available locally and on the deployment server.
3. The app must be served over **HTTPS** in any non-local environment (Entra ID will reject non-HTTPS redirect URIs except `http://localhost`).
4. PHP extensions: `openssl`, `curl`, `json`, `mbstring` (already standard for CI4).

## 4. Entra ID App Registration (one-time, in Azure portal)

Done by an Entra ID admin once per environment (dev / staging / prod):

1. Microsoft Entra admin center → **App registrations** → **New registration**.
2. Name: `CodeIgniter4 App (dev)` (or per-env name).
3. Supported account types: typically **Single tenant** (most enterprise apps). Use multi-tenant only if external orgs need to sign in.
4. Redirect URI: type **Web**, value `https://<host>/auth/azure/callback` (and `http://localhost:8080/auth/azure/callback` for local dev).
5. After creation, capture:
   - **Application (client) ID**
   - **Directory (tenant) ID**
6. **Certificates & secrets** → **New client secret** → copy the secret **value** immediately (shown once).
7. **Token configuration** → **Add optional claim** → ID token: add `email`, `upn`, `preferred_username` if you need them as direct claims.
8. **API permissions** → ensure `Microsoft Graph → User.Read` (delegated) + `openid`, `profile`, `email` are present. Grant admin consent if your tenant requires it.

These five values land in `.env`:

```
azure.tenantId      = <directory-tenant-id>
azure.clientId      = <application-client-id>
azure.clientSecret  = <client-secret-value>
azure.redirectUri   = https://<host>/auth/azure/callback
azure.postLogoutRedirectUri = https://<host>/login
```

## 5. Code Changes — Step by Step

### 5.1 Add the dependency

```
composer require thenetworg/oauth2-azure
```

This pulls in `league/oauth2-client` transitively. Commit `composer.json` and `composer.lock`.

### 5.2 New config class — `app/Config/Azure.php`

A typed CI4 config that reads from `.env` (`azure.*`) so secrets stay out of source control.

Fields: `tenantId`, `clientId`, `clientSecret`, `redirectUri`, `postLogoutRedirectUri`, `scopes` (default `['openid', 'profile', 'email', 'offline_access']`), `endpointVersion` (default `2.0`).

### 5.3 New service — `app/Libraries/AzureAuthService.php`

Thin wrapper that constructs the `TheNetworg\OAuth2\Client\Provider\Azure` provider from the config and exposes:

- `getAuthorizationUrl(): string` — also stores `state` and `nonce` in the CI4 session.
- `handleCallback(string $code, string $state): array` — verifies `state`, exchanges code for tokens, validates the ID token's `nonce` and `aud` (= `clientId`), returns a normalized user array (`oid`, `email`, `name`, `tenant_id`, `upn`).
- `buildLogoutUrl(): string` — returns the Entra `https://login.microsoftonline.com/{tenant}/oauth2/v2.0/logout?post_logout_redirect_uri=...` URL (optional, for federated logout).

Why a service rather than inlining in the controller: keeps the controller thin, makes the flow unit-testable with a mocked provider.

### 5.4 New controller — `app/Controllers/AzureAuth.php`

Three actions:

- `start()` — generates auth URL via the service and redirects the browser to Entra.
- `callback()` — receives `?code=&state=`, calls the service, on success populates the session in the same shape `Maccount` does today (`['user' => [...], 'logged_in' => true]`) and redirects to `/home`. On failure renders the login view with an error.
- `logout()` (optional) — destroys the local session, then redirects to the Entra logout URL so the user is also signed out at the IdP. If federated logout is not required, the existing `Account::Logout()` is sufficient.

Critical session shape (must match what `Authlogin` expects):

```php
$this->session->set([
    'user' => [
        'username' => $claims['preferred_username'] ?? $claims['upn'] ?? $claims['email'],
        'name'     => $claims['name'] ?? '',
        'email'    => $claims['email'] ?? $claims['upn'] ?? '',
        'oid'      => $claims['oid'],          // Entra object id — stable user key
        'tenant'   => $claims['tid'],
        'source'   => 'azure',
    ],
    'logged_in' => true,
]);
```

### 5.5 Routes — `app/Config/Routes.php`

Add (placing the `redirectlogin` filter on `start` so already-logged-in users skip the round-trip):

```php
$routes->get('auth/azure',          'AzureAuth::start',    ['filter' => 'redirectlogin']);
$routes->get('auth/azure/callback', 'AzureAuth::callback');
// Optional federated logout:
// $routes->get('auth/azure/logout', 'AzureAuth::logout');
```

Keep the existing `/login`, `/logout`, `/home` routes unchanged.

### 5.6 Login view — `app/Views/v_login.php`

Add a **"Sign in with Microsoft"** button just above or below the existing username/password form, linking to `<?= site_url('auth/azure') ?>`. Keep the local form behind a config flag (`Azure::$allowLocalLogin`) so the team can later disable password login entirely without code changes.

### 5.7 Database — optional but recommended

If you want to track which Entra users have logged in (for auditing, role mapping, deactivation), add a migration that either:

**Option A — extend the existing `user` table:**
- `azure_oid VARCHAR(64) NULL UNIQUE`
- `email VARCHAR(255) NULL`
- `auth_source ENUM('local','azure') DEFAULT 'local'`
- `last_login_at DATETIME NULL`

**Option B — new `user_sso` table** keyed on `azure_oid`, joined to `user` by `user_id` for SSO-only accounts created on first login (just-in-time provisioning).

Recommended: **Option A + JIT provisioning** in `AzureAuth::callback()` — if no row matches `azure_oid`, insert one. This is the simplest path and avoids a pre-flight admin step for every new hire.

Decide and document whether unknown Entra users should be auto-provisioned or rejected (the latter is safer for tenants with external guests).

### 5.8 Filter — `app/Filters/Authlogin.php`

No change required. It already keys on `session('user')`, which the new controller populates identically.

### 5.9 Environment template — `env`

Append a new section so deployments know what to set:

```
#--------------------------------------------------------------------
# AZURE / ENTRA ID SSO
#--------------------------------------------------------------------
# azure.tenantId      =
# azure.clientId      =
# azure.clientSecret  =
# azure.redirectUri   = https://your-host/auth/azure/callback
# azure.postLogoutRedirectUri = https://your-host/login
# azure.allowLocalLogin = true
```

## 6. Security Checklist

These are non-negotiable; mark each before going live:

- [ ] **HTTPS** enforced for all non-local environments (`app.forceGlobalSecureRequests = true`).
- [ ] `state` parameter generated per-request and verified on callback (CSRF protection on the OAuth flow).
- [ ] `nonce` parameter included in the auth request and validated against the ID token claim.
- [ ] ID token signature **and** `aud`, `iss`, `exp`, `tid` claims validated. (`thenetworg/oauth2-azure` validates the JWT signature; you must still check `tid` matches your tenant for single-tenant apps.)
- [ ] Client secret stored only in `.env` / secret manager, never committed.
- [ ] Session ID regenerated on successful login (`$this->session->regenerate(true);`) to prevent session fixation.
- [ ] Cookies: `cookie.secure = true`, `cookie.httponly = true`, `cookie.samesite = 'Lax'` (already supported by `Config/Cookie.php`).
- [ ] Redirect URI in Entra registration matches `azure.redirectUri` **exactly** (trailing slash, scheme, port).
- [ ] Decide & document the unknown-user policy (reject vs. JIT provision).

## 7. Testing Plan

1. **Local dev**: register a second app in Entra (or use the same with localhost redirect added), run `php spark serve`, browse `/login`, click "Sign in with Microsoft", complete the flow, confirm `/home` loads.
2. **Negative cases**:
   - Tamper with `state` on callback → expect rejection.
   - Use a user from a different tenant (multi-tenant test) → expect rejection if `tid` check is on.
   - Expired/invalid `code` → expect graceful error on the login view, no fatal.
3. **Session contract**: `var_dump(session('user'))` after login matches the shape `Maccount` produces, so existing `Authlogin`-protected routes still authorize.
4. **Logout**: confirm `session->destroy()` clears the session and (if federated logout enabled) the Entra logout page is reached.
5. **Unit tests** (optional): mock the Azure provider in `AzureAuthService` to verify state/nonce handling and claim normalization without hitting the network.

## 8. Rollout Strategy

1. Ship behind `azure.allowLocalLogin = true` so both methods work in parallel during pilot.
2. Pilot with a small group; confirm session/audit logs.
3. Once stable, set `azure.allowLocalLogin = false` to retire local passwords (or keep both indefinitely if break-glass accounts are needed).
4. Communicate the change and document the new login URL in internal onboarding.

## 9. File Touch List (summary)

| File | Action |
|---|---|
| `composer.json` / `composer.lock` | add `thenetworg/oauth2-azure` |
| `env` | append Azure section |
| `app/Config/Azure.php` | **new** |
| `app/Libraries/AzureAuthService.php` | **new** |
| `app/Controllers/AzureAuth.php` | **new** |
| `app/Config/Routes.php` | add 2 routes |
| `app/Views/v_login.php` | add "Sign in with Microsoft" button |
| `app/Database/Migrations/2026-04-25-AddSsoColumnsToUser.php` | **new** (Option A) |
| `app/Models/Maccount.php` | add `findOrCreateByAzureOid()` for JIT |

## 10. Open Questions for the Team

1. Single-tenant or multi-tenant? (Affects `tid` validation and Entra registration.)
2. Keep local password login as a fallback, or hard cutover?
3. Just-in-time user provisioning, or pre-create users in the `user` table?
4. Federated logout (sign out of Entra too), or local logout only?
5. Any role/group mapping required from Entra (group claims) for authorization beyond "logged in"?

## Sources

- [Microsoft: OpenID Connect on the Microsoft identity platform](https://learn.microsoft.com/en-us/entra/identity-platform/v2-protocols-oidc)
- [Microsoft: Configure OIDC SSO for gallery and custom applications](https://learn.microsoft.com/en-us/entra/identity/enterprise-apps/add-application-portal-setup-oidc-sso)
- [TheNetworg/oauth2-azure (GitHub)](https://github.com/TheNetworg/oauth2-azure)
- [LoginRadius: Implement SSO with Microsoft Entra ID using PHP](https://www.loginradius.com/enterprise-sso/microsoft-entra-id-azure-ad/php)
- [Logto: Build Microsoft Entra ID OIDC enterprise SSO with PHP](https://tutorials.logto.io/how-to/build-microsoft-entra-id-oidc-enterprise-sso-sign-in-with-php-and-logto)
