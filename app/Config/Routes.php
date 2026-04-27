<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('home', 'Home::index');

$routes->get('/', 'Account::login', ['filter' => 'redirectlogin']);
$routes->get('login', 'Account::login', ['filter' => 'redirectlogin']);
$routes->post('login', 'Account::login', ['filter' => 'redirectlogin']);
$routes->get('logout', 'Account::logout');

$routes->get('auth/azure',          'AzureAuth::start',    ['filter' => 'redirectlogin']);
$routes->get('auth/azure/callback', 'AzureAuth::callback');
$routes->get('auth/azure/logout',   'AzureAuth::logout');

$routes->resource('userapi');