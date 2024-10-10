<?php

namespace CleaniqueCoders\LaravelAction;

use CleaniqueCoders\LaravelAction\Exceptions\ActionException;
use CleaniqueCoders\LaravelContract\Contracts\Execute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

abstract class AbstractAction implements Execute
{
    protected string $model;

    protected array $inputs;

    protected array $constrainedBy = [];

    protected array $hashFields = [];

    protected array $encryptFields = [];

    protected Model $record;

    // Constructor and Initialization
    public function __construct(array $inputs)
    {
        $this->inputs = $inputs;
    }

    // Abstract Method for Rules
    abstract public function rules(): array;

    // Setters and Getters
    public function setInputs(array $inputs): self
    {
        $this->inputs = $inputs;

        return $this;
    }

    public function setConstrainedBy(array $constrainedBy): self
    {
        $this->constrainedBy = $constrainedBy;

        return $this;
    }

    public function getConstrainedBy(): array
    {
        return $this->constrainedBy;
    }

    public function hasConstrained(): bool
    {
        return count($this->constrainedBy) > 0;
    }

    public function setHashFields(array $hashFields): self
    {
        $this->hashFields = $hashFields;

        return $this;
    }

    public function getHashFields(): array
    {
        return $this->hashFields;
    }

    public function hasHashFields(): bool
    {
        return count($this->getHashFields()) > 0;
    }

    public function setEncryptFields(array $encryptFields): self
    {
        $this->encryptFields = $encryptFields;

        return $this;
    }

    public function getEncryptFields(): array
    {
        return $this->encryptFields;
    }

    public function hasEncryptFields(): bool
    {
        return count($this->getEncryptFields()) > 0;
    }

    public function getRecord(): Model
    {
        return $this->record;
    }

    public function inputs(): array
    {
        return $this->inputs;
    }

    // Field Processing
    public function hashFields(): void
    {
        if ($this->hasHashFields()) {
            $this->applyTransformationOnFields($this->getHashFields(), fn ($value) => Hash::make($value));
        }
    }

    public function encryptFields(): void
    {
        if ($this->hasEncryptFields()) {
            $this->applyTransformationOnFields($this->getEncryptFields(), fn ($value) => encrypt($value));
        }
    }

    public function removeConfirmationFields(): void
    {
        $this->inputs = array_filter($this->inputs, fn ($value, $key) => ! str($key)->contains('_confirmation'), ARRAY_FILTER_USE_BOTH);
    }

    // Validation
    protected function validateInputs(): void
    {
        Validator::make(
            array_merge($this->constrainedBy, $this->inputs),
            $this->rules()
        )->validate();
    }

    // Field Transformation Helper
    protected function applyTransformationOnFields(array $fields, callable $transformation): void
    {
        if ($this->hasConstrained()) {
            $constrainedBy = $this->constrainedBy;
            foreach ($fields as $field) {
                if (isset($constrainedBy[$field])) {
                    $constrainedBy[$field] = $transformation($constrainedBy[$field]);
                }
            }
            $this->constrainedBy = $constrainedBy;
        }

        $inputs = $this->inputs;
        foreach ($fields as $field) {
            if (isset($inputs[$field])) {
                $inputs[$field] = $transformation($inputs[$field]);
            }
        }
        $this->inputs = $inputs;
    }

    public function model(): string
    {
        if (! property_exists($this, 'model')) {
            throw ActionException::missingModelProperty(__CLASS__);
        }

        if (empty($this->model)) {
            throw ActionException::emptyModelProperty(__CLASS__);
        }

        return $this->model;
    }

    // Preparation
    public function prepare(): void
    {

    }

    // Execution
    public function execute()
    {
        $this->prepare();
        $this->validateInputs();
        $this->hashFields();
        $this->encryptFields();
        $this->removeConfirmationFields();

        return $this->record = DB::transaction(fn () => $this->hasConstrained()
            ? $this->model()::updateOrCreate($this->constrainedBy, $this->inputs)
            : $this->model()::create($this->inputs));
    }
}
