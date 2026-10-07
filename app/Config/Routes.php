<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::attempt');
$routes->post('logout', 'AuthController::logout', ['filter' => 'auth']);
$routes->get('dashboard', 'DashboardController::index', ['filter' => 'auth']);
$routes->get('reports', 'ReportController::index', ['filter' => 'auth']);
$routes->get('reports/create', 'ReportController::create', ['filter' => 'auth']);
$routes->post('reports', 'ReportController::store', ['filter' => 'auth']);
$routes->get('reports/(:num)', 'ReportController::show/$1', ['filter' => 'auth']);
$routes->post('reports/(:num)/checklists/(:segment)', 'ReportController::saveChecklist/$1/$2', ['filter' => 'auth']);
$routes->post('reports/(:num)/monitoring', 'ReportController::saveMonitoring/$1', ['filter' => 'auth']);
$routes->post('reports/(:num)/incidents', 'ReportController::addIncident/$1', ['filter' => 'auth']);
$routes->post('reports/(:num)/handover', 'ReportController::saveHandover/$1', ['filter' => 'auth']);

$routes->group('master', ['filter' => 'auth'], static function ($routes): void {
    $routes->get('(:segment)', 'MasterDataController::index/$1');
    $routes->get('(:segment)/create', 'MasterDataController::create/$1');
    $routes->post('(:segment)', 'MasterDataController::store/$1');
    $routes->get('(:segment)/(:num)/edit', 'MasterDataController::edit/$1/$2');
    $routes->post('(:segment)/(:num)', 'MasterDataController::update/$1/$2');
    $routes->post('(:segment)/(:num)/deactivate', 'MasterDataController::deactivate/$1/$2');
    $routes->get('checklist-templates/(:num)/items', 'MasterDataController::checklistItems/$1');
    $routes->post('checklist-templates/(:num)/items', 'MasterDataController::addChecklistItem/$1');
});
