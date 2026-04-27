<?php

namespace App\Controllers;

use App\Libraries\AzureAuthService;
use Config\Azure as AzureConfig;

class AzureAuth extends BaseController
{
    private AzureAuthService $azureAuth;

    private AzureConfig $azureConfig;

    public function __construct()
    {
        $this->azureConfig = config(AzureConfig::class);
        $this->azureAuth   = new AzureAuthService($this->azureConfig);
    }

    public function start()
    {
        try {
            $url = $this->azureAuth->getAuthorizationUrl();
        } catch (\Throwable $e) {
            log_message('error', 'AzureAuth::start failed: ' . $e->getMessage());

            return view('v_login', [
                'title'  => 'Login Page',
                'errors' => ['azure' => 'Unable to start Microsoft sign-in. Contact the administrator.'],
            ]);
        }

        return redirect()->to($url);
    }

    public function callback()
    {
        $code  = (string) $this->request->getGet('code');
        $state = (string) $this->request->getGet('state');
        $error = $this->request->getGet('error');

        if ($error) {
            $description = $this->request->getGet('error_description') ?? '';
            log_message('warning', 'AzureAuth callback error: ' . $error . ' ' . $description);

            return view('v_login', [
                'title'  => 'Login Page',
                'errors' => ['azure' => 'Microsoft sign-in was cancelled or failed.'],
            ]);
        }

        if ($code === '' || $state === '') {
            return view('v_login', [
                'title'  => 'Login Page',
                'errors' => ['azure' => 'Missing authorization code from Microsoft.'],
            ]);
        }

        try {
            $claims = $this->azureAuth->handleCallback($code, $state);
        } catch (\Throwable $e) {
            log_message('error', 'AzureAuth::callback failed: ' . $e->getMessage());

            return view('v_login', [
                'title'  => 'Login Page',
                'errors' => ['azure' => 'Microsoft sign-in failed: ' . $e->getMessage()],
            ]);
        }

        if (empty($claims['oid'])) {
            return view('v_login', [
                'title'  => 'Login Page',
                'errors' => ['azure' => 'Microsoft did not return a stable user identifier (oid).'],
            ]);
        }

        $userRow = model('Maccount')->findOrCreateByAzureOid($claims, $this->azureConfig->jitProvisioning);

        if ($userRow === null) {
            return view('v_login', [
                'title'  => 'Login Page',
                'errors' => ['azure' => 'Your Microsoft account is not authorized for this application.'],
            ]);
        }

        $this->session->regenerate(true);
        $this->session->set([
            'user' => [
                'id'       => $userRow['id'] ?? null,
                'username' => $userRow['username'] ?? $claims['username'],
                'name'     => $userRow['name'] ?? $claims['name'],
                'email'    => $userRow['email'] ?? $claims['email'],
                'oid'      => $claims['oid'],
                'tenant'   => $claims['tid'],
                'source'   => 'azure',
            ],
            'logged_in' => true,
        ]);

        return redirect()->route('home');
    }

    public function logout()
    {
        $this->session->destroy();

        return redirect()->to($this->azureAuth->buildLogoutUrl());
    }
}
