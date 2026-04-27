<?php

namespace App\Libraries;

use Config\Azure as AzureConfig;
use TheNetworg\OAuth2\Client\Provider\Azure as AzureProvider;

class AzureAuthService
{
    private AzureConfig $config;

    private AzureProvider $provider;

    private \CodeIgniter\Session\Session $session;

    public function __construct(?AzureConfig $config = null, ?AzureProvider $provider = null)
    {
        $this->config  = $config ?? config(AzureConfig::class);
        $this->session = \Config\Services::session();

        $this->provider = $provider ?? new AzureProvider([
            'clientId'                => $this->config->clientId,
            'clientSecret'            => $this->config->clientSecret,
            'redirectUri'             => $this->config->redirectUri,
            'scopes'                  => $this->config->scopes,
            'defaultEndPointVersion'  => $this->config->endpointVersion,
        ]);
        $this->provider->tenant = $this->config->tenantId !== '' ? $this->config->tenantId : 'common';
    }

    /**
     * Build the Entra authorization URL and stash state + nonce in the session
     * so the callback can verify them.
     */
    public function getAuthorizationUrl(): string
    {
        $nonce = bin2hex(random_bytes(16));

        $url = $this->provider->getAuthorizationUrl([
            'scope'         => $this->config->scopes,
            'response_mode' => 'query',
            'nonce'         => $nonce,
        ]);

        $this->session->set('azure_oauth_state', $this->provider->getState());
        $this->session->set('azure_oauth_nonce', $nonce);

        return $url;
    }

    /**
     * Exchange the authorization code for tokens, validate state/nonce,
     * and return a normalized claim array.
     *
     * @throws \RuntimeException on any verification failure
     */
    public function handleCallback(string $code, string $state): array
    {
        $expectedState = $this->session->get('azure_oauth_state');
        $expectedNonce = $this->session->get('azure_oauth_nonce');

        $this->session->remove('azure_oauth_state');
        $this->session->remove('azure_oauth_nonce');

        if (! $expectedState) {
            log_message(
                'warning',
                'AzureAuth callback: no azure_oauth_state in session. session_id={sid} keys={keys}',
                ['sid' => session_id(), 'keys' => implode(',', array_keys($this->session->get() ?? []))]
            );
            throw new \RuntimeException('Sign-in session expired or callback was opened without starting at /auth/azure. Please retry sign-in from the login page.');
        }

        if (! hash_equals((string) $expectedState, $state)) {
            throw new \RuntimeException('Invalid OAuth state.');
        }

        $token  = $this->provider->getAccessToken('authorization_code', ['code' => $code]);
        $claims = $token->getIdTokenClaims();

        if (! is_array($claims) || empty($claims)) {
            throw new \RuntimeException('Entra ID returned no ID token claims.');
        }

        if (! isset($claims['nonce']) || ! hash_equals((string) $expectedNonce, (string) $claims['nonce'])) {
            throw new \RuntimeException('ID token nonce mismatch.');
        }

        if ($this->config->strictTenantCheck && $this->config->tenantId !== '') {
            if (($claims['tid'] ?? null) !== $this->config->tenantId) {
                throw new \RuntimeException('ID token tenant does not match the configured tenant.');
            }
        }

        return [
            'oid'      => $claims['oid'] ?? null,
            'tid'      => $claims['tid'] ?? null,
            'email'    => $claims['email'] ?? $claims['upn'] ?? $claims['preferred_username'] ?? null,
            'username' => $claims['preferred_username'] ?? $claims['upn'] ?? $claims['email'] ?? null,
            'name'     => $claims['name'] ?? '',
            'upn'      => $claims['upn'] ?? null,
        ];
    }

    /**
     * Entra v2.0 federated logout URL. Hits Microsoft's end-session endpoint
     * so the user is also signed out at the IdP.
     */
    public function buildLogoutUrl(): string
    {
        $tenant = $this->config->tenantId !== '' ? $this->config->tenantId : 'common';
        $base   = "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/logout";

        if ($this->config->postLogoutRedirectUri === '') {
            return $base;
        }

        return $base . '?post_logout_redirect_uri=' . urlencode($this->config->postLogoutRedirectUri);
    }
}
