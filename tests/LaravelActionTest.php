<?php

use Illuminate\Support\Facades\Artisan;

it('has make:action command', function () {
    $commands = array_keys(Artisan::all());
    sort($commands);
    $this->assertTrue(in_array('make:action', $commands));
});

it('can make an action with model option', function () {
    Artisan::call('make:action CreateOrUpdateUser --model=User');
    $this->assertTrue(
        file_exists(base_path('app/Actions/CreateOrUpdateUser.php'))
    );
    unlink(base_path('app/Actions/CreateOrUpdateUser.php'));
});

it('can make an action without model option', function () {
    Artisan::call('make:action CreateNewInvoice');
    $this->assertTrue(
        file_exists(base_path('app/Actions/CreateNewInvoice.php'))
    );
    unlink(base_path('app/Actions/CreateNewInvoice.php'));
});

it('can make an API action', function () {
    Artisan::call('make:action StoreInvoiceDetails --api');
    $this->assertTrue(
        file_exists(base_path('app/Actions/Api/StoreInvoiceDetails.php'))
    );
    unlink(base_path('app/Actions/Api/StoreInvoiceDetails.php'));
});

it('can make a menu action', function () {
    Artisan::call('make:action Sidebar --menu');
    $this->assertTrue(
        file_exists(base_path('app/Actions/Builder/Menu/Sidebar.php'))
    );
    unlink(base_path('app/Actions/Builder/Menu/Sidebar.php'));
});
