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
    $stubAction = new class($inputs) extends CreateUserAction
    {
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

// it applies both hashing and encryption to different fields
it('applies both hashing and encryption to specified fields', function () {
    // Arrange
    $inputs = [
        'name' => 'John Doe',
        'email' => 'johndoe@example.com',
        'password' => 'secretpassword',
        'ssn' => '123-45-6789',
    ];

    $action = new CreateUserAction($inputs);
    $action->setProperty('hashFields', ['password']);
    $action->setProperty('encryptFields', ['ssn']);

    // Act
    $record = $action->execute();

    // Assert
    expect(Hash::check('secretpassword', $record->password))->toBeTrue();
    expect(decrypt($record->ssn))->toBe('123-45-6789');
});

// it handles multiple fields for hashing and encryption
it('handles multiple fields for hashing and encryption', function () {
    // Arrange
    $inputs = [
        'name' => 'Jane Doe',
        'email' => 'janedoe@example.com',
        'password' => 'anotherpassword',
        'ssn' => '987-65-4321',
        'security_answer' => 'My first car',
    ];

    $action = new CreateUserAction($inputs);
    $action->setProperty('hashFields', ['password', 'security_answer']);
    $action->setProperty('encryptFields', ['ssn', 'email']);

    // Act
    $record = $action->execute();

    // Assert
    // Only use Hash::check on fields known to be hashed
    expect(Hash::check('anotherpassword', $record->password))->toBeTrue();
    expect(Hash::check('My first car', $record->security_answer))->toBeTrue();

    // Decrypt and verify the encrypted fields
    expect(decrypt($record->ssn))->toBe('987-65-4321');
    expect(decrypt($record->email))->toBe('janedoe@example.com');
});

// it applies constraints for update or create
it('applies constraints for update or create', function () {
    // Arrange
    $inputs = [
        'name' => 'John Doe Updated',
        'email' => 'uniqueemail@example.com', // Use a unique email to avoid the validation error
        'password' => 'newpassword',
    ];

    // Pre-create a user with a different email to simulate an existing record
    $existingUser = User::create([
        'name' => 'Old Name',
        'email' => 'oldemail@example.com', // Different email to avoid triggering unique constraint
        'password' => Hash::make('oldpassword'),
    ]);

    // Now set the constraints to match the unique constraint check
    $action = new CreateUserAction($inputs);
    $action->setProperty('constrainedBy', ['id' => $existingUser->id]); // Use ID as a unique constraint for update

    // Act
    $record = $action->execute();

    // Assert
    expect($record->name)->toBe('John Doe Updated')
        ->and(Hash::check('newpassword', $record->password))->toBeTrue();
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

    // Set expectation for DB::connection()
    DB::shouldReceive('connection')->once()->andReturn($mockConnection);

    // Set up transaction mock and method chaining on the query builder
    DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
        return $callback();
    });

    // Set up expectations for methods on the query builder
    $mockConnection->shouldReceive('table')->andReturn($mockQueryBuilder);
    $mockQueryBuilder->shouldReceive('useWritePdo')->andReturn($mockQueryBuilder);
    $mockQueryBuilder->shouldReceive('where')->andReturn($mockQueryBuilder);
    $mockQueryBuilder->shouldReceive('count')->andReturn(0);
    $mockQueryBuilder->shouldReceive('updateOrCreate')->andReturn(Mockery::mock(User::class));

    // Act
    $record = $action->execute();

    // Assert
    expect($record)->toBeInstanceOf(User::class);
});

// it does not apply transformation if optional field is missing
it('does not apply transformation if optional field is missing', function () {
    // Arrange
    $inputs = [
        'name' => 'John Doe',
        'email' => 'johndoe@example.com',
        'password' => 'secretpassword', // Required field to satisfy validation
    ];

    $action = new CreateUserAction($inputs);
    $action->setProperty('hashFields', ['security_answer']); // 'security_answer' is not present in inputs

    // Act
    $record = $action->execute();

    // Assert
    expect($record)->toBeInstanceOf(User::class); // Check the action executed successfully
    expect($action->inputs())->not->toHaveKey('security_answer'); // Ensure no transformation occurred on missing field
});
