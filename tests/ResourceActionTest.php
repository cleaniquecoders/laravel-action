<?php

use CleaniqueCoders\LaravelAction\Exceptions\ActionException;
use CleaniqueCoders\LaravelAction\Tests\Stubs\Actions\CreateUserAction;
use CleaniqueCoders\LaravelAction\Tests\Stubs\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// it creates a user with valid data
it('creates a user with valid data', function () {
    // Arrange
    $inputs = [
        'name' => 'John Doe',
        'email' => 'johndoe@example.com',
        'password' => 'secretpassword',
    ];

    $action = new CreateUserAction($inputs);

    // Act
    $record = $action->execute();

    // Assert
    expect($record)->toBeInstanceOf(User::class)
        ->and($record->name)->toBe('John Doe')
        ->and($record->email)->toBe('johndoe@example.com')
        ->and(Hash::check('secretpassword', $record->password))->toBeTrue();
});

// it validates required fields
it('validates required fields', function () {
    // Arrange
    $inputs = [
        'email' => 'johndoe@example.com',
        'password' => 'secretpassword',
    ];

    $action = new CreateUserAction($inputs);

    // Act & Assert
    expect(fn () => $action->execute())->toThrow(\Illuminate\Validation\ValidationException::class);
});

// it throws exception if model is not set
it('throws exception if model is not set', function () {
    // Arrange
    $inputs = [
        'name' => 'John Doe',
        'email' => 'johndoe@example.com',
        'password' => 'secretpassword',
    ];

    // Stub a class without a model definition
    $stubAction = new class($inputs) extends CreateUserAction {
        protected string $model = ''; // Intentionally leave model empty
    };

    // Act & Assert
    expect(fn () => $stubAction->execute())->toThrow(ActionException::class);
});

// it applies hashing to password field
it('applies hashing to password field', function () {
    // Arrange
    $inputs = [
        'name' => 'John Doe',
        'email' => 'johndoe@example.com',
        'password' => 'secretpassword',
    ];

    $action = new CreateUserAction($inputs);
    $action->setProperty('hashFields', ['password']); // Use setProperty to define hash fields

    // Act
    $record = $action->execute();

    // Assert
    expect(Hash::check('secretpassword', $record->password))->toBeTrue();
});

// it removes confirmation fields from inputs
it('removes confirmation fields from inputs', function () {
    // Arrange
    $inputs = [
        'name' => 'John Doe',
        'email' => 'johndoe@example.com',
        'password' => 'secretpassword',
        'password_confirmation' => 'secretpassword',
    ];

    $action = new CreateUserAction($inputs);

    // Act
    $action->removeConfirmationFields();
    $filteredInputs = $action->inputs();

    // Assert
    expect($filteredInputs)->not->toHaveKey('password_confirmation');
});

// it uses transactions during execution
it('uses transactions during execution', function () {
    // Arrange
    $inputs = [
        'name' => 'Jane Doe',
        'email' => 'janedoe1@example.com',
        'password' => 'secretpassword',
    ];

    $action = new CreateUserAction($inputs);

    // Mock the database connection and query builder
    $mockConnection = Mockery::mock();
    $mockQueryBuilder = Mockery::mock();

    // Mock the chain of methods on the query builder
    $mockConnection->shouldReceive('table')->andReturn($mockQueryBuilder);
    $mockQueryBuilder->shouldReceive('useWritePdo')->andReturn($mockQueryBuilder);
    $mockQueryBuilder->shouldReceive('where')->andReturn($mockQueryBuilder);
    $mockQueryBuilder->shouldReceive('count')->andReturn(0);
    $mockQueryBuilder->shouldReceive('updateOrCreate')->andReturn(Mockery::mock(User::class));

    // Mock the transaction flow
    DB::shouldReceive('connection')->andReturn($mockConnection);
    DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
        return $callback();
    });

    // Act
    $record = $action->execute();

    // Assert
    expect($record)->toBeInstanceOf(User::class);
});

// it applies encryption to specified fields
it('applies encryption to specified fields', function () {
    // Arrange
    $inputs = [
        'name' => 'John Doe',
        'email' => 'johndoe@example.com',
        'password' => 'secretpassword', // password will be hashed
        'ssn' => '123-45-6789', // This is the field to be encrypted
    ];

    $action = new CreateUserAction($inputs);
    $action->setProperty('encryptFields', ['ssn']); // Use setProperty to define encryption fields

    // Act
    $record = $action->execute();

    // Assert
    expect($record)->toBeInstanceOf(User::class);

    // Ensure 'ssn' field was encrypted
    $decryptedSSN = decrypt($record->ssn);
    expect($decryptedSSN)->toBe('123-45-6789');
});
