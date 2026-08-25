<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'v1'], function() use ($router) {
    $router->group(['prefix' => 'auth'], function() use ($router){
        $router->post('login', 'AuthController@login');
        $router->post('update-fcm-token', 'AuthController@UpdateFCMToken');
        $router->post('logout', [
            'middleware' => 'auth',
            'uses' => 'AuthController@logout'
        ]);
        $router->post('role-user', 'AuthController@GetRoleUser');
    });

    $router->group(['prefix' => 'test', 'middleware' => ['auth']], function() use ($router) {
        $router->get('get-test', 'TestingController@getSesuatu');
    });

    $router->group(['prefix' => 'qr_code', 'middleware' => 'auth'], function () use ($router){
        $router->post('get-qr_code', 'QrController@getQrScan');
        $router->post('get-qr_code_bulk', 'QrController@getQrScanBulk');
        $router->post('get-qr_code-ins', 'QrController@getQrScanInspecting');
    });

    $router->group(['prefix' => 'locs', 'middleware' => 'auth'], function () use ($router){
        $router->post('get-locs', 'LocsController@getLocs');
    });

    $router->group(['prefix' => 'set-loc', 'middleware' => 'auth'], function () use ($router){
        $router->post('insert', 'SetLocController@Insert');
        $router->post('delete', 'SetLocController@Delete');
        $router->post('get-list', 'SetLocController@GetList');
    });

    $router->group(['prefix' => 'move-loc', 'middleware' => 'auth'], function () use ($router){
        $router->post('insert', 'MoveLocController@Insert');
        $router->post('delete', 'MoveLocController@Delete');
        $router->post('get-list', 'MoveLocController@GetList');
    });

    $router->group(['prefix' => 'packing-list', 'middleware' => 'auth'], function () use ($router){
        $router->post('get-customer', 'PackingController@GetCustomerPacking');
        $router->post('get-list', 'PackingController@GetItemPackingList');
        $router->post('insert', 'PackingController@Insert');
    });

    $router->group(['prefix' => 'opname', 'middleware' => 'auth'], function () use ($router){
        $router->post('get-list', 'OpnameController@GetListOpname');
        $router->post('get-detail', 'OpnameController@GetOpnamedDetail');
        $router->post('get-not-detected', 'OpnameController@GetOpnamedNotDetected');
        $router->post('insert', 'OpnameController@Insert');
    });

    $router->group(['prefix' => 'opname-pcs', 'middleware' => 'auth'], function () use ($router){
        $router->post('get-list', 'OpnamePcsController@GetList');
        $router->post('get-detail', 'OpnamePcsController@GetDetail');
        $router->get('get-next-code', 'OpnamePcsController@GetNextOpnameCode');
        $router->post('get-next-code', 'OpnamePcsController@GetNextOpnameCode');
        $router->post('insert', 'OpnamePcsController@Insert');
        $router->post('update', 'OpnamePcsController@Update');
        $router->post('delete', 'OpnamePcsController@Delete');
    });

    $router->group(['prefix' => 'receipt', 'middleware' => 'auth'], function () use ($router){
        $router->get('get-header-inspecting', 'ReceiptController@GetInspectingHeaderList');
        $router->post('get-item-inspecting', 'ReceiptController@GetInspectingItemList');
        $router->get('get-header-mklbj', 'ReceiptController@GetMklbjHeaderList');
        $router->post('get-item-mklbj', 'ReceiptController@GetMklbjItemList');
        $router->put('reject-receipt-inspecting', 'ReceiptController@RejectItemInspecting');
        $router->put('reject-receipt-mklbj', 'ReceiptController@RejectItemMklbj');
        $router->post('submit-receipt-inspecting', 'ReceiptController@ReceiptItemInspecting');
        $router->post('submit-receipt-mklbj', 'ReceiptController@ReceiptItemMklbj');
    });

    $router->group(['prefix' => 'pengiriman', 'middleware' => 'auth'], function () use ($router){
        $router->get('get-cust-pengiriman', 'PengirimanController@GetCustomerPengiriman');
        $router->post('get-wo-pengiriman', 'PengirimanController@GetWOPengiriman');
        $router->get('get-header-pengiriman', 'PengirimanController@GetHeaderPengiriman');
        $router->post('get-detail-header', 'PengirimanController@GetDetailHeaderPengiriman');
        $router->post('insert-pengiriman', 'PengirimanController@InsertPengiriman');
        $router->post('get-item-wo-pengiriman', 'PengirimanController@getQrScanItemWO');
    });

});