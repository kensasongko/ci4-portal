<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Azure extends BaseConfig
{
    public string $tenantId = '';

    public string $clientId = '';

    public string $clientSecret = '';

    public string $redirectUri = '';

    public string $postLogoutRedirectUri = '';

    /**
     * @var string[]
     */
    public array $scopes = ['openid', 'profile', 'email', 'offline_access'];

    public string $endpointVersion = '2.0';

    public bool $allowLocalLogin = true;

    /**
     * If true, only Entra users whose `tid` matches `$tenantId` are accepted.
     * Set false only for multi-tenant apps that explicitly want cross-tenant sign-in.
     */
    public bool $strictTenantCheck = true;

    /**
     * Auto-create a row in the `user` table on first SSO login.
     * If false, unknown Entra users are rejected.
     */
    public bool $jitProvisioning = true;
}
