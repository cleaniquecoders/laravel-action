# Simple Actionable for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/laravel-action.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/laravel-action) [![PHPStan](https://github.com/cleaniquecoders/laravel-action/actions/workflows/phpstan.yml/badge.svg)](https://github.com/cleaniquecoders/laravel-action/actions/workflows/phpstan.yml) [![run-tests](https://github.com/cleaniquecoders/laravel-action/actions/workflows/run-tests.yml/badge.svg)](https://github.com/cleaniquecoders/laravel-action/actions/workflows/run-tests.yml) [![Fix PHP code style issues](https://github.com/cleaniquecoders/laravel-action/actions/workflows/fix-styling.yml/badge.svg)](https://github.com/cleaniquecoders/laravel-action/actions/workflows/fix-styling.yml) [![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/laravel-action.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/laravel-action)

Simple Actionable for Laravel.

## Installation

You can install the package via composer:

```bash
composer require cleaniquecoders/laravel-action
```

## Todo

- [ ] Be able to publish stubs and using custom stubs if exists.

## Usage

You can create an action using the Artisan command:

```bash
php artisan make:action User\\CreateOrUpdateUser --model=User
```

This will create an action in `app\Actions\User`:

```php
<?php

namespace App\Actions\User;

use App\Models\User;
use CleaniqueCoders\LaravelAction\ResourceAction;

class CreateOrUpdateUser extends ResourceAction
{
    public string $model = User::class;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8',
        ];
    }
}
```

### New Features and Example Usage

#### 1. Flexible Property Setter

You can now set various properties, like `hashFields`, `encryptFields`, and `constrainedBy`, dynamically using the `setProperty` method:

```php
$action = new CreateOrUpdateUser(['name' => 'John Doe', 'email' => 'johndoe@example.com', 'password' => 'secretpassword']);
$action->setProperty('hashFields', ['password']); // Hash the password
$action->setProperty('encryptFields', ['ssn']); // Encrypt SSN
$action->setProperty('constrainedBy', ['email' => 'johndoe@example.com']); // Use email as a unique constraint
```

This flexible property setting reduces boilerplate and simplifies configuration of actions.

#### 2. Field Transformation with Hashing and Encryption

The `ResourceAction` class supports field-level transformations. For example, you can hash a `password` field and encrypt an `ssn` field:

```php
$inputs = [
    'name' => 'Jane Doe',
    'email' => 'janedoe@example.com',
    'password' => 'securepassword',
    'ssn' => '123-45-6789',
];

$action = new CreateOrUpdateUser($inputs);
$action->setProperty('hashFields', ['password']);
$action->setProperty('encryptFields', ['ssn']);
$record = $action->execute();
```

After execution:
- The `password` field will be hashed for security.
- The `ssn` field will be encrypted, ensuring secure storage.

#### 3. Constraint-Based `updateOrCreate`

You can specify constraints to perform `updateOrCreate` actions based on unique fields or identifiers. Here’s an example of updating an existing user by `id`:

```php
// Assume there's an existing user with this email
$existingUser = User::create([
    'name' => 'Old Name',
    'email' => 'uniqueemail@example.com',
    'password' => Hash::make('oldpassword'),
]);

// Define the inputs to update the existing user
$inputs = [
    'name' => 'John Doe Updated',
    'email' => 'uniqueemail@example.com', // Same email
    'password' => 'newpassword',
];

$action = new CreateOrUpdateUser($inputs);
$action->setProperty('constrainedBy', ['id' => $existingUser->id]); // Update by user ID

$record = $action->execute();

// The existing user record with the specified ID will be updated.
```

This allows precise control over `updateOrCreate` behavior based on custom constraints.

## Testing

Run the tests with:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on recent changes.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nasrul Hazim Bin Mohamad](https://github.com/nasrulhazim)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
