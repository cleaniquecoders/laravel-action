<?php

use Illuminate\Support\Facades\Artisan;

it('has make:action command', function () {
    $commands = array_keys(Artisan::all());
    sort($commands);
    $this->assertTrue(in_array('make:action', $commands));
});

it('can make an action', function () {
    Artisan::call('make:action CreateOrUpdateUser --model=User');
    $this->assertTrue(
        file_exists(base_path('app/Actions/CreateOrUpdateUser.php'))
    );
    unlink(base_path('app/Actions/CreateOrUpdateUser.php'));
});
