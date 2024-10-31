<?php

namespace CleaniqueCoders\LaravelAction;

use CleaniqueCoders\LaravelAction\Exceptions\ActionException;
use CleaniqueCoders\LaravelContract\Contracts\Execute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

abstract class ResourceAction implements Execute
{
    /**
     * The model class the action operates on.
     *
     * @var string
     */
    protected string $model;

    /**
     * Input data for the action.
     *
     * @var array
     */
    protected array $inputs;

    /**
     * Fields to use for constraint-based operations.
     *
     * @var array
     */
    protected array $constrainedBy = [];

    /**
     * Fields to hash before saving.
     *
     * @var array
     */
    protected array $hashFields = [];

    /**
     * Fields to encrypt before saving.
     *
     * @var array
     */
    protected array $encryptFields = [];

    /**
     * The Eloquent model record.
     *
     * @var Model
     */
    protected Model $record;

    /**
     * Constructor to initialize input data.
     *
     * @param array $inputs
     */
    public function __construct(array $inputs = [])
    {
        $this->inputs = $inputs;
    }

    /**
     * Abstract method to define validation rules for the action.
     *
     * @return array
     */
    abstract public function rules(): array;

    /**
     * Set input data.
     *
     * @param array $inputs
     * @return $this
     */
    public function setInputs(array $inputs): self
    {
        $this->inputs = $inputs;
        return $this;
    }

    /**
     * Set fields for constraint-based operations.
     *
     * @param array $constrainedBy
     * @return $this
     */
    public function setConstrainedBy(array $constrainedBy): self
    {
        $this->constrainedBy = $constrainedBy;
        return $this;
    }

    /**
     * Get constrained fields.
     *
     * @return array
     */
    public function getConstrainedBy(): array
    {
        return $this->constrainedBy;
    }

    /**
     * Check if constraints are applied.
     *
     * @return bool
     */
    public function hasConstrained(): bool
    {
        return count($this->constrainedBy) > 0;
    }

    /**
     * Set fields to hash before saving.
     *
     * @param array $hashFields
     * @return $this
     */
    public function setHashFields(array $hashFields): self
    {
        $this->hashFields = $hashFields;
        return $this;
    }

    /**
     * Get fields to hash.
     *
     * @return array
     */
    public function getHashFields(): array
    {
        return $this->hashFields;
    }

    /**
     * Check if any fields are set to be hashed.
     *
     * @return bool
     */
    public function hasHashFields(): bool
    {
        return count($this->getHashFields()) > 0;
    }

    /**
     * Set fields to encrypt before saving.
     *
     * @param array $encryptFields
     * @return $this
     */
    public function setEncryptFields(array $encryptFields): self
    {
        $this->encryptFields = $encryptFields;
        return $this;
    }

    /**
     * Get fields to encrypt.
     *
     * @return array
     */
    public function getEncryptFields(): array
    {
        return $this->encryptFields;
    }

    /**
     * Check if any fields are set to be encrypted.
     *
     * @return bool
     */
    public function hasEncryptFields(): bool
    {
        return count($this->getEncryptFields()) > 0;
    }

    /**
     * Retrieve the current record.
     *
     * @return Model
     */
    public function getRecord(): Model
    {
        return $this->record;
    }

    /**
     * Retrieve the inputs.
     *
     * @return array
     */
    public function inputs(): array
    {
        return $this->inputs;
    }

    /**
     * Hash specified fields in the inputs or constraints.
     *
     * @return void
     */
    public function hashFields(): void
    {
        if ($this->hasHashFields()) {
            $this->applyTransformationOnFields($this->getHashFields(), fn ($value) => Hash::make($value));
        }
    }

    /**
     * Encrypt specified fields in the inputs or constraints.
     *
     * @return void
     */
    public function encryptFields(): void
    {
        if ($this->hasEncryptFields()) {
            $this->applyTransformationOnFields($this->getEncryptFields(), fn ($value) => encrypt($value));
        }
    }

    /**
     * Remove confirmation fields from inputs.
     *
     * @return void
     */
    public function removeConfirmationFields(): void
    {
        $this->inputs = array_filter($this->inputs, fn ($value, $key) => ! str($key)->contains('_confirmation'), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Validate inputs against defined rules.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @return void
     */
    protected function validateInputs(): void
    {
        Validator::make(
            array_merge($this->constrainedBy, $this->inputs),
            $this->rules()
        )->validate();
    }

    /**
     * Apply transformation to specified fields in inputs and constraints.
     *
     * @param array $fields
     * @param callable $transformation
     * @return void
     */
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

    /**
     * Retrieve the model class for the action.
     *
     * @throws ActionException
     * @return string
     */
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

    /**
     * Preparation method for the action.
     *
     * @return void
     */
    public function prepare(): void
    {
        // Placeholder for child classes to implement custom preparation.
    }

    /**
     * Execute the action with preparation, validation, and data processing.
     *
     * @return Model
     * @throws \Illuminate\Validation\ValidationException
     */
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
