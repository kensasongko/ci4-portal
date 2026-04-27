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

$routes->resource('userapi');