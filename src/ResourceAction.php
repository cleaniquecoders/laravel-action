<?php

namespace CleaniqueCoders\LaravelAction;

use CleaniqueCoders\LaravelAction\Exceptions\ActionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

abstract class ResourceAction
{
    use AsAction;

    /**
     * The model class the action operates on.
     */
    protected string $model;

    /**
     * Input data for the action.
     */
    protected array $inputs = [];

    /**
     * Fields to use for constraint-based operations.
     */
    protected array $constrainedBy = [];

    /**
     * Fields to hash before saving.
     */
    protected array $hashFields = [];

    /**
     * Fields to encrypt before saving.
     */
    protected array $encryptFields = [];

    /**
     * The Eloquent model record.
     */
    protected ?Model $record = null;

    /**
     * Constructor to initialize input data.
     */
    public function __construct(array $inputs = [])
    {
        $this->inputs = $inputs;
    }

    /**
     * Generic setter for properties.
     *
     * @return $this
     *
     * @throws ActionException
     */
    public function setProperty(string $property, array $value): self
    {
        if (! property_exists($this, $property)) {
            throw new ActionException("Property {$property} does not exist.");
        }

        $this->{$property} = $value;

        return $this;
    }

    /**
     * Retrieve the current record.
     */
    public function getRecord(): ?Model
    {
        return $this->record;
    }

    /**
     * Retrieve the inputs.
     */
    public function inputs(): array
    {
        return $this->inputs;
    }

    /**
     * Main action execution logic.
     */
    public function handle(): Model
    {
        $this->validateInputs();
        $this->transformFields();
        $this->removeConfirmationFields();

        return $this->record = DB::transaction(function () {
            return ! empty($this->constrainedBy)
                ? $this->model()::updateOrCreate($this->constrainedBy, $this->inputs)
                : $this->model()::create($this->inputs);
        });
    }

    /**
     * Conditionally validate inputs if rules are defined.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateInputs(): void
    {
        if (method_exists($this, 'rules') && ! empty($this->rules())) {
            Validator::make($this->inputs, $this->rules())->validate();
        }
    }

    /**
     * Transform specified fields in the inputs.
     */
    protected function transformFields(): void
    {
        $this->applyTransformationOnFields($this->hashFields, fn ($value) => Hash::make($value));
        $this->applyTransformationOnFields($this->encryptFields, fn ($value) => encrypt($value));
    }

    /**
     * Remove confirmation fields from inputs.
     */
    public function removeConfirmationFields(): void
    {
        $this->inputs = array_filter($this->inputs, fn ($value, $key) => ! Str::contains($key, '_confirmation'), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Apply transformation to specified fields in inputs and constraints.
     */
    protected function applyTransformationOnFields(array $fields, callable $transformation): void
    {
        foreach ($fields as $field) {
            if (isset($this->inputs[$field])) {
                $this->inputs[$field] = $transformation($this->inputs[$field]);
            }
        }
    }

    /**
     * Retrieve the model class for the action.
     *
     * @throws ActionException
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
}
