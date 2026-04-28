<?php

/**
 * Definición de rutas de la aplicación.
 *
 * Formato: $router->METHOD('uri', 'Controlador@metodo')
 * Parámetros dinámicos: {id} = entero, {slug} = alfanumérico
 */

// ── Autenticación ────────────────────────────────────────────
$router->get( '/auth/login',   'AuthController@showLogin');
$router->post('/auth/login',   'AuthController@login');
$router->get( '/auth/logout',  'AuthController@logout');

// ── Dashboard ────────────────────────────────────────────────
$router->get('/',          'DashboardController@index');
$router->get('/dashboard', 'DashboardController@index');

// ── CRM - Clientes ───────────────────────────────────────────
$router->get( '/clientes',              'ClienteController@index');
$router->get( '/clientes/nuevo',        'ClienteController@create');
$router->post('/clientes/nuevo',        'ClienteController@store');
$router->get( '/clientes/{id}',         'ClienteController@show');
$router->get( '/clientes/{id}/editar',  'ClienteController@edit');
$router->post('/clientes/{id}/editar',  'ClienteController@update');
$router->post('/clientes/{id}/estado',  'ClienteController@cambiarEstado');
$router->post('/clientes/{id}/eliminar','ClienteController@destroy');

// Seguimiento comercial
$router->post('/clientes/{id}/seguimiento',       'SeguimientoController@store');
$router->get( '/clientes/{id}/seguimiento',       'SeguimientoController@index');

// ── Facturas ─────────────────────────────────────────────────
$router->get( '/clientes/{id}/facturas',          'FacturaController@index');
$router->get( '/clientes/{id}/facturas/subir',    'FacturaController@create');
$router->post('/clientes/{id}/facturas/subir',    'FacturaController@store');
$router->get( '/facturas/{id}',                   'FacturaController@show');
$router->get( '/facturas/{id}/datos',             'FacturaController@datos');
$router->post('/facturas/{id}/datos',             'FacturaController@guardarDatos');
$router->post('/facturas/{id}/extraer',           'FacturaController@extraerDatos');
$router->get( '/facturas/{id}/ver',               'FacturaController@verArchivo');

// ── Estudios / Comparativas ───────────────────────────────────
$router->get( '/clientes/{id}/estudios',          'EstudioController@index');
$router->post('/clientes/{id}/estudios',          'EstudioController@store');
$router->get( '/estudios/{id}',                   'EstudioController@show');
$router->post('/estudios/{id}/calcular',          'EstudioController@calcular');
$router->post('/estudios/{id}/estado',            'EstudioController@cambiarEstado');

// ── Panel administrativo (solo rol admin) ─────────────────────
$router->get('/admin',                    'AdminController@index');
$router->get('/admin/clientes',           'AdminController@clientes');
$router->get('/admin/estudios',           'AdminController@estudios');
$router->get('/admin/comercializadoras',  'AdminController@comercializadoras');
$router->get('/admin/tarifas',            'AdminController@tarifas');
$router->post('/admin/tarifas/nueva',     'AdminController@nuevaTarifa');
$router->get('/admin/usuarios',           'AdminController@usuarios');
$router->post('/admin/usuarios/nuevo',    'AdminController@nuevoUsuario');
