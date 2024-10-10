<?php

namespace CleaniqueCoders\LaravelAction\Tests\Stubs\Actions;

use CleaniqueCoders\LaravelAction\AbstractAction;
use CleaniqueCoders\LaravelAction\Tests\Stubs\Models\User;

class CreateUserAction extends AbstractAction
{
    protected string $model = User::class; // Assuming you're working with a `User` model.

    protected array $hashFields = [
        'password',
    ];

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ];
    }
}
