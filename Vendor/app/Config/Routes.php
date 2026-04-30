<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', static fn() => redirect()->to('/Index'));
$routes->get('Index', 'Home::index');
$routes->get('Store/Index', 'Store::index');
$routes->get('Store/Search/Whisper', 'Store::Search_Whisper');
$routes->match(['get', 'post'], 'Store/Product/Create', 'Store::Product_Create');
$routes->get('Store/Product/View/(:num)', 'Store::Product_View/$1');
$routes->get('Store/Basket', static fn() => redirect()->to('/Store/Basket/Index'));
$routes->get('Store/Basket/Index', 'Store::Basket_Index');
$routes->get('Store/Basket/Add/(:num)', 'Store::Basket_Add/$1');
$routes->get('Store/Basket/Delete/(:num)', 'Store::Basket_Delete/$1');
$routes->match(['get', 'post'], 'Store/Product/Edit/(:num)', 'Store::Product_Edit/$1');
$routes->get('Store/Product/Delete/(:num)', 'Store::Product_Delete/$1');
$routes->match(['get', 'post'], 'DashBoard/SEO_Settings', 'Store::SEO_Settings');
$routes->match(['get', 'post'], 'DashBoard/Web_Settings', 'Store::Web_Settings');
