<?php

namespace CleaniqueCoders\LaravelAction;

use CleaniqueCoders\LaravelAction\Exceptions\ActionException;
use CleaniqueCoders\LaravelContract\Contracts\Execute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
     * Generic setter for properties.
     *
     * @param string $property
     * @param array $value
     * @return $this
     * @throws ActionException
     */
    public function setProperty(string $property, array $value): self
    {
        if (!property_exists($this, $property)) {
            throw new ActionException("Property {$property} does not exist.");
        }

        $this->{$property} = $value;
        return $this;
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
    protected function transformFields(): void
    {
        $this->applyTransformationOnFields($this->hashFields, fn($value) => Hash::make($value));
        $this->applyTransformationOnFields($this->encryptFields, fn($value) => encrypt($value));
    }

    /**
     * Remove confirmation fields from inputs.
     *
     * @return void
     */
    public function removeConfirmationFields(): void
    {
        $this->inputs = array_filter($this->inputs, fn($value, $key) => !Str::contains($key, '_confirmation'), ARRAY_FILTER_USE_BOTH);
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
        $transformFields = function (&$value, $key) use ($fields, $transformation) {
            if (in_array($key, $fields, true)) {
                $value = $transformation($value);
            }
        };

        array_walk_recursive($this->inputs, $transformFields);
        array_walk_recursive($this->constrainedBy, $transformFields);
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
     * Validates the inputs against the defined rules.
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
     * Execute the action with preparation, validation, and data processing.
     *
     * @return Model
     * @throws \Illuminate\Validation\ValidationException
     */
    public function execute(): Model
    {
        $this->prepare();
        $this->validateInputs();
        $this->transformFields();
        $this->removeConfirmationFields();

        return $this->record = DB::transaction(function () {
            return !empty($this->constrainedBy)
                ? $this->model()::updateOrCreate($this->constrainedBy, $this->inputs)
                : $this->model()::create($this->inputs);
        });
    }
}