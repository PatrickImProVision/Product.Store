<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', static fn() => redirect()->to('/Index'));
$routes->get('Index', 'Home::index');

$routes->group('', ['filter' => 'membercapability'], static function ($routes) {
    $routes->get('Store/Index', 'Store::index');
    $routes->match(['get', 'post'], 'Store/Search/Index', 'Store::Search_Index');
    $routes->get('Store/Search/Whisper', 'Store::Search_Whisper');
    $routes->match(['get', 'post'], 'Store/Product/Create', 'Store::Product_Create');
    $routes->get('Store/Product/View/(:num)', 'Store::Product_View/$1');
    $routes->get('Store/Basket', static fn() => redirect()->to('/Store/Basket/Index'));
    $routes->get('Store/Basket/Index', 'Store::Basket_Index');
    $routes->get('Store/Basket/Add/(:num)', 'Store::Basket_Add/$1');
    $routes->get('Store/Basket/Create', 'Store::Basket_Create');
    $routes->match(['get', 'post'], 'Store/Basket/Edit/(:num)', 'Store::Basket_Edit/$1');
    $routes->get('Store/Basket/Delete/(:num)', 'Store::Basket_Delete/$1');
    $routes->match(['get', 'post'], 'Store/Product/Edit/(:num)', 'Store::Product_Edit/$1');
    $routes->match(['get', 'post'], 'Store/Product/Delete/(:num)', 'Store::Product_Delete/$1');
    $routes->get('Store/CheckOut/Index', 'Store::CheckOut_Index');
    $routes->match(['get', 'post'], 'Store/CheckOut/Create', 'Store::CheckOut_Create');
    $routes->match(['get', 'post'], 'Store/CheckOut/Edit/(:num)', 'Store::CheckOut_Edit/$1');
    $routes->get('Store/CheckOut/Delete/(:num)', 'Store::CheckOut_Delete/$1');

    $routes->get('Member/User/Profile', 'Member::User_Profile');
    $routes->get('Member/User/Logout', 'Member::User_Logout');
    $routes->match(['get', 'post'], 'Member/User/Register', 'Member::User_Register');
    $routes->match(['get', 'post'], 'Member/User/Login', 'Member::User_Login');
    $routes->match(['get', 'post'], 'Member/User/ForgotPassword', 'Member::User_ForgotPassword');
    $routes->match(['get', 'post'], 'Member/User/Activate/(:segment)', 'Member::User_Activate/$1');
    $routes->match(['get', 'post'], 'Member/User/DeActivateRequest', 'Member::User_DeActivateRequest');
    $routes->match(['get', 'post'], 'Member/User/DeActivate/(:segment)', 'Member::User_DeActivate/$1');
    $routes->match(['get', 'post'], 'Member/User/Edit/(:num)', 'Member::User_Edit/$1');
    $routes->get('Member/User/Delete/(:segment)', 'Member::User_Delete/$1');
});

$routes->get('Member/Admin/Profile', 'Member::Admin_Profile');
$routes->match(['get', 'post'], 'Member/Admin/Register', 'Member::Admin_Register');
$routes->match(['get', 'post'], 'Member/Admin/Login', 'Member::Admin_Login');
$routes->match(['get', 'post'], 'Member/Admin/Edit/(:num)', 'Member::Admin_Edit/$1');
$routes->get('Member/Admin/Delete/(:num)', 'Member::Admin_Delete/$1');

$routes->group('DashBoard', ['filter' => 'dashboardadmin'], static function ($routes) {
    $routes->get('Index', 'DashBoard::index');
    $routes->get('Site_Contacts', 'DashBoard::Site_Contacts');
    $routes->match(['get', 'post'], 'Site_Contact/Create', 'DashBoard::Site_Contact_Create');
    $routes->match(['get', 'post'], 'Site_Contact/Edit/(:num)', 'DashBoard::Site_Contact_Edit/$1');
    $routes->get('Site_Contact/Delete/(:num)', 'DashBoard::Site_Contact_Delete/$1');
    $routes->match(['get', 'post'], 'SEO_Settings', 'Store::SEO_Settings');
    $routes->match(['get', 'post'], 'Web_Settings', 'Store::Web_Settings');
    $routes->get('Web_Promoting', 'DashBoard::Web_Promoting');
    $routes->match(['get', 'post'], 'Web_Promoting/Create', 'DashBoard::Web_Promoting_Create');
    $routes->match(['get', 'post'], 'Web_Promoting/Edit/(:num)', 'DashBoard::Web_Promoting_Edit/$1');
    $routes->get('Web_Promoting/Delete/(:num)', 'DashBoard::Web_Promoting_Delete/$1');
    $routes->get('Member/Admin/Roles', 'DashBoard::Member_Admin_Roles');
    $routes->match(['get', 'post'], 'Member/Admin/Role/Create', 'DashBoard::Member_Admin_Role_Create');
    $routes->match(['get', 'post'], 'Member/Admin/Role/Edit/(:num)', 'DashBoard::Member_Admin_Role_Edit/$1');
    $routes->get('Member/Admin/Role/Delete/(:num)', 'DashBoard::Member_Admin_Role_Delete/$1');
    $routes->get('Member/User/Profiles', 'DashBoard::Member_User_Profiles');
    $routes->match(['get', 'post'], 'Member/User/Profile/Create', 'DashBoard::Member_User_Profile_Create');
    $routes->match(['get', 'post'], 'Member/User/Profile/Edit/(:num)', 'DashBoard::Member_User_Profile_Edit/$1');
    $routes->get('Member/User/Profile/Delete/(:num)', 'DashBoard::Member_User_Profile_Delete/$1');
});
