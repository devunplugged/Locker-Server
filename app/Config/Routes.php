<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);//true

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');
$routes->post('dashboard/generate-token/', 'Dashboard::generateToken', ['filter' => 'loginAuth']);
$routes->get('dashboard/api-key', 'Dashboard::selectKeyToGenerate', ['filter' => 'loginAuth']);

$routes->get('dashboard/locker/remote/pick-locker', 'Dashboard::pickLocker');//, ['filter' => 'loginAuth']
$routes->get('dashboard/locker/remote/(:alphanum)', 'Dashboard::lockerRemote/$1');//, ['filter' => 'loginAuth']

$routes->get('dashboard/locker/cells/reset/(:segment)', 'Dashboard::resetCells/$1');//, ['filter' => 'loginAuth']
$routes->get('dashboard/locker/settings/edit/(:segment)', 'Dashboard::settings/$1');//, ['filter' => 'loginAuth']
$routes->post('dashboard/locker/settings/save', 'Dashboard::saveSettings');//, ['filter' => 'loginAuth']
$routes->get('dashboard/tasks/list', 'Dashboard::listTasks');//, ['filter' => 'loginAuth']
$routes->get('dashboard/packages/list', 'Dashboard::listPackages');//, ['filter' => 'loginAuth']
$routes->get('dashboard/package/add', 'Dashboard::generatePackageForm');//, ['filter' => 'loginAuth']
$routes->post('dashboard/package/generate', 'Dashboard::generatePackage');//, ['filter' => 'loginAuth']
$routes->get('dashboard/package/show/(:segment)', 'Dashboard::showPackage/$1');//, ['filter' => 'loginAuth']
$routes->get('dashboard/package/(:segment)', 'Dashboard::package/$1');//, ['filter' => 'loginAuth']
$routes->get('dashboard/locker/(:segment)', 'Dashboard::locker/$1');//, ['filter' => 'loginAuth']
$routes->get('dashboard/print', 'Dashboard::print');//, ['filter' => 'loginAuth']

$routes->get('test', 'Test::test');//, ['filter' => 'loginAuth']

$routes->post('ajax/get-clients-by-type/', 'Ajax::clientsByType', ['filter' => 'loginAuth']);
$routes->get('ajax/get-clients-by-type/', 'Ajax::clientsByType', ['filter' => 'loginAuth']);
$routes->post('ajax/get-locker-info/(:segment)', 'Locker::info/$1'/*, ['filter' => 'loginAuth']*/);

$routes->group("api", function ($routes) {
    //$routes->get("users", "User::index", ['filter' => 'jwtLockerAuth']);
    //$routes->post("locker/task/raport", "Locker::taskRaport", ['filter' => 'jwtLockerAuth']);
    $routes->post("locker/add", "Locker::add", ['filter' => 'jwtLockerAuth']);
    $routes->post("locker/code", "Locker::code", ['filter' => 'jwtLockerAuth']); //locker sends QR code
    //$routes->post("locker/test", "Locker::test", ['filter' => 'jwtLockerAuth']);
    $routes->get("locker/list", "Locker::list", ['filter' => 'jwtAuth']);
    $routes->get("locker/info/(:segment)", "Locker::info/$1", ['filter' => 'jwtStaffAuth']);
    $routes->get("locker/tasks", "Locker::task", ['filter' => 'jwtLockerAuth']);
    //$routes->get("locker/heartbeat", "Locker::heartbeat", ['filter' => 'jwtLockerAuth']);
    $routes->post("locker/raport", "Locker::raport", ['filter' => 'jwtLockerAuth']);
    $routes->post("locker/open-cell", "Locker::createOpenCellTask", ['filter' => 'jwtStaffAuth']);
    $routes->post("locker/reset-cell", "Locker::resetCell", ['filter' => 'jwtStaffAuth']);
    $routes->get("locker/get/(:segment)", "Locker::get/$1", ['filter' => 'jwtAuth']);
    $routes->get("locker/generate-codes/(:segment)", "Locker::generateLockerServiceCodes/$1", ['filter' => 'jwtAdminAuth']);
    $routes->get("locker/print-codes/(:segment)", "Locker::printLockerServiceCodes/$1", ['filter' => 'jwtStaffAuth']);
    
    
    $routes->post("package/add", "Package::add", ['filter' => 'jwtStaffAuth']);
    $routes->post("package/update", "Package::update", ['filter' => 'jwtCompanyAuth']);
    //$routes->post("package/retrive", "Package::retrive", ['filter' => 'jwtLockerAuth']);
    $routes->post("package/list/overdue", "Package::listOverdueLockerPackages", ['filter' => 'jwtStaffAuth']);
    $routes->post("package/list", "Package::list", ['filter' => 'jwtStaffAuth']);
    //$routes->get("package/delete", "Package::delete", ['filter' => 'jwtAdminAuth']);
    $routes->post("package/cancel/insert", "Package::cancelInsert", ['filter' => 'jwtStaffAuth']);
    $routes->get("package/print/(:segment)", "Package::print/$1", ['filter' => 'jwtStaffAuth']);
    $routes->get("package/details/(:segment)", "Package::details/$1", ['filter' => 'jwtAuth']);
    $routes->get("package/cancel/(:segment)", "Package::cancel/$1", ['filter' => 'jwtStaffAuth']);
    $routes->get("package/reset/(:segment)", "Package::reset/$1", ['filter' => 'jwtStaffAuth']);
    $routes->get('package/notify/recipient/(:segment)/(:segment)', 'Package::emailRecipient/$1/$2', ['filter' => 'jwtStaffAuth']);
    
    $routes->post("package/retrive", "Package::retrive"/*, ['filter' => 'jwtAuth']*/);
    $routes->post("package/insert", "Package::insert", ['filter' => 'jwtStaffAuth']);
    
    $routes->post("company/add", "Company::add", ['filter' => 'jwtAdminAuth']);
    $routes->get("company/get/(:segment)", "Company::get/$1", ['filter' => 'jwtAdminAuth']);
    $routes->get("company/list", "Company::list", ['filter' => 'jwtStaffAuth']);
    $routes->post("company/update", "Company::update", ['filter' => 'jwtAdminAuth']);
    $routes->post("company/delete", "Company::delete", ['filter' => 'jwtAdminAuth']);
    $routes->get("companies/locker/access/(:segment)", "Company::getLockerAccess/$1", ['filter' => 'jwtAdminAuth']);
    $routes->post("companies/locker/access/(:segment)", "Company::setLockerAccess/$1", ['filter' => 'jwtAdminAuth']);

    $routes->post("client/add", "Client::add", ['filter' => 'jwtAdminAuth']/*, ['filter' => 'jwtAdminAuth']*/); 
    $routes->get("client/get/(:segment)", "Client::get/$1", ['filter' => 'jwtStaffAuth']); 
    $routes->post("client/update", "Client::update", ['filter' => 'jwtAdminAuth']); 
    $routes->get("client/list", "Client::list", ['filter' => 'jwtStaffAuth']); 
   // $routes->post("client/list", "Client::list", ['filter' => 'jwtAdminAuth']); // is it nessesary?
    $routes->post("client/delete", "Client::delete", ['filter' => 'jwtAdminAuth']); 
    $routes->get("client/my-account", "Client::myAccount", ['filter' => 'jwtAuth']); 

    $routes->post("cell/add", "Cell::add", ['filter' => 'jwtAdminAuth']);
    $routes->post("cell/get", "Cell::get", ['filter' => 'jwtAdminAuth']);
    $routes->post("cell/update", "Cell::update", ['filter' => 'jwtAdminAuth']);
    $routes->post("cell/list", "Cell::list", ['filter' => 'jwtAdminAuth']);
    $routes->post("cell/delete", "Cell::delete", ['filter' => 'jwtAdminAuth']);

    $routes->post("token/add", "Token::add", ['filter' => 'jwtAdminAuth']); 
    $routes->post("token/decode", "Token::decode"); 
    $routes->get("token/get-client", "Token::getClientType", ['filter' => 'jwtAuth']); 
    $routes->post("token/issue-change", "Token::issueChange", ['filter' => 'jwtAdminAuth']); 

    $routes->get("package/mail-test", "Test::testMail"); 
});

$routes->group("api/my", function ($routes) {
    
    $routes->get("locker/list", "Locker::companyLockerList", ['filter' => 'jwtAuth']);
    $routes->get("package/list", "Package::companyPackageList", ['filter' => 'jwtAuth']);
    $routes->get("account/type", "Client::getAccountTypeFromToken");

});
/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
