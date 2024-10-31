# Simple Actionable for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cleaniquecoders/laravel-action.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/laravel-action) [![PHPStan](https://github.com/cleaniquecoders/laravel-action/actions/workflows/phpstan.yml/badge.svg)](https://github.com/cleaniquecoders/laravel-action/actions/workflows/phpstan.yml) [![run-tests](https://github.com/cleaniquecoders/laravel-action/actions/workflows/run-tests.yml/badge.svg)](https://github.com/cleaniquecoders/laravel-action/actions/workflows/run-tests.yml) [![Fix PHP code style issues](https://github.com/cleaniquecoders/laravel-action/actions/workflows/fix-styling.yml/badge.svg)](https://github.com/cleaniquecoders/laravel-action/actions/workflows/fix-styling.yml) [![Total Downloads](https://img.shields.io/packagist/dt/cleaniquecoders/laravel-action.svg?style=flat-square)](https://packagist.org/packages/cleaniquecoders/laravel-action)

This package, **Simple Actionable for Laravel**, provides a structured way to manage action classes in your Laravel applications, making it easier to encapsulate business logic, transformations, and validations within reusable classes. The package utilizes [lorisleiva/laravel-actions](https://github.com/lorisleiva/laravel-actions) to offer extended functionality, enabling actions to be executed in multiple contexts (e.g., jobs, controllers, event listeners) and simplifying your codebase.

## Installation

You can install the package via composer:

```bash
composer require cleaniquecoders/laravel-action
```

## Features

This package builds on top of `lorisleiva/laravel-actions`, allowing you to:
- Create versatile action classes that can be executed as invokable objects, controllers, or dispatched as jobs.
- Use property setters to configure actions dynamically.
- Apply transformations like hashing or encryption to specific fields.
- Manage `updateOrCreate` behavior with constraints on unique fields.

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

You can set properties, such as `hashFields`, `encryptFields`, and `constrainedBy`, dynamically using the `setProperty` method:

```php
$action = new CreateOrUpdateUser(['name' => 'John Doe', 'email' => 'johndoe@example.com', 'password' => 'secretpassword']);
$action->setProperty('hashFields', ['password']); // Hash the password
$action->setProperty('encryptFields', ['ssn']); // Encrypt SSN
$action->setProperty('constrainedBy', ['email' => 'johndoe@example.com']); // Use email as a unique constraint
```

This flexible property setting reduces boilerplate and simplifies action configuration.

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
$record = $action->handle();
```

After execution:
- The `password` field will be hashed for security.
- The `ssn` field will be encrypted, ensuring secure storage.

#### 3. Constraint-Based `updateOrCreate`

Specify constraints to perform `updateOrCreate` actions based on unique fields or identifiers. Here’s an example of updating an existing user by `id`:

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

$record = $action->handle();

// The existing user record with the specified ID will be updated.
```

This allows precise control over `updateOrCreate` behavior based on custom constraints.

## Using `lorisleiva/laravel-actions` for Multi-Context Execution

With `lorisleiva/laravel-actions`, actions created with this package can be used in multiple contexts. You can run the action as:
- **An Invokable Object**:
  ```php
  $user = (new CreateOrUpdateUser(['name' => 'Jane', 'email' => 'jane@example.com']))->handle();
  ```
- **A Controller**:
  ```php
  Route::post('users', CreateOrUpdateUser::class);
  ```
- **A Job**:
  ```php
  CreateOrUpdateUser::dispatch(['name' => 'Jane', 'email' => 'jane@example.com']);
  ```
- **An Event Listener**:
  ```php
  Event::listen(UserRegistered::class, CreateOrUpdateUser::class);
  ```

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
