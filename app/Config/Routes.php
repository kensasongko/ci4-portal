<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('home', 'Home::index', ['filter' => 'authlogin']);

$routes->group('applications', ['filter' => 'authlogin'], function ($routes) {
    $routes->get('/',               'Applications::index');
    $routes->get('create',          'Applications::create');
    $routes->post('store',          'Applications::store');
    $routes->get('edit/(:num)',     'Applications::edit/$1');
    $routes->post('update/(:num)',  'Applications::update/$1');
    $routes->post('delete/(:num)',  'Applications::delete/$1');
    $routes->post('toggle/(:num)',  'Applications::toggleStatus/$1');
});

$routes->get('/', 'Account::login', ['filter' => 'redirectlogin']);
$routes->get('login', 'Account::login', ['filter' => 'redirectlogin']);
$routes->post('login', 'Account::login', ['filter' => 'redirectlogin']);
$routes->get('logout', 'Account::logout');

$routes->get('auth/azure',          'AzureAuth::start',    ['filter' => 'redirectlogin']);
$routes->get('auth/azure/callback', 'AzureAuth::callback');
$routes->get('auth/azure/logout',   'AzureAuth::logout');

$routes->resource('userapi');