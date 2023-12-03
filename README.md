# Simple Actionable for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/laravel-action.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/laravel-action)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/cleaniquecoders/laravel-action/run-tests?label=tests)](https://github.com/cleaniquecoders/laravel-action/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/cleaniquecoders/laravel-action/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/cleaniquecoders/laravel-action/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/laravel-action.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/laravel-action)

Simple Actionable for Laravel.

## Installation

You can install the package via composer:

```bash
composer require cleaniquecoders/laravel-action
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-action-config"
```

Optionally, you can publish the views using

## Usage

```bash
php artisan make:action User\\CreateOrUpdateUser --model=User
```

This will create an action in `app\Actions\User`:

```php
<?php

namespace App\Actions\User;

use App\Models\User;
use CleaniqueCoders\LaravelAction\AbstractAction as Action;

class CreateOrUpdateUser extends Action
{
    public $model = User::class;

    public function rules(): array
    {
        return [];
    }
}
```

Then you can use it like:

```php
use App\Actions\User\CreateOrUpdateUser;

$user = (new CreateOrUpdateUser(['name' => 'Nasrul Hazim', 'email' => 'nasrul@work.com']));

// do more with \App\Models\User object
// $user->assignRole(...)
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nasrul Hazim Bin Mohamad](https://github.com/nasrulhazim)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
