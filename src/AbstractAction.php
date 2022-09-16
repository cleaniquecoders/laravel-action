<?php

namespace App\Actions;

use Bekwoh\LaravelAction\Exceptions\ActionException;
use Bekwoh\LaravelContract\Contracts\Execute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

abstract class AbstractAction implements Execute
{
    abstract public function rules(): array;

    protected array $constrainedBy = [];

    protected array $hashFields = [];

    protected array $encryptFields = [];

    protected Model $record;

    protected $model;

    public function __construct(public array $inputs)
    {
    }

    public function setInputs(array $inputs): self
    {
        $this->inputs = $inputs;

        return $this;
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
        return count($this->getConstrainedBy()) > 0;
    }

    public function hashFields()
    {
        if ($this->hasHashFields()) {
            // get from constrainedBy
            if ($this->hasConstrained()) {
                $constrainedBy = $this->getConstrainedBy();
                foreach ($this->getHashFields() as $key => $value) {
                    if (isset($constrainedBy[$value])) {
                        $constrainedBy[$value] = Hash::make($constrainedBy[$value]);
                    }
                }
                $this->setConstrainedBy($constrainedBy);
            }
            // get from inputs
            $inputs = $this->inputs();
            foreach ($this->getHashFields() as $key => $value) {
                if (isset($inputs[$value])) {
                    $inputs[$value] = Hash::make($inputs[$value]);
                }
            }
            $this->setInputs($inputs);
        }
    }

    public function encryptFields()
    {
        if ($this->hasEncryptFields()) {
            // get from constrainedBy
            if ($this->hasConstrained()) {
                $constrainedBy = $this->getConstrainedBy();
                foreach ($this->getEncryptFields() as $key => $value) {
                    if (isset($constrainedBy[$value])) {
                        $constrainedBy[$value] = encrypt($constrainedBy[$value]);
                    }
                }
                $this->setConstrainedBy($constrainedBy);
            }
            // get from inputs
            $inputs = $this->inputs();
            foreach ($this->getEncryptFields() as $key => $value) {
                if (isset($inputs[$value])) {
                    $inputs[$value] = encrypt($inputs[$value]);
                }
            }
            $this->setInputs($inputs);
        }
    }

    public function removeConfirmationFields()
    {
        $inputs = $this->inputs();
        $_inputs = [];
        foreach ($inputs as $key => $value) {
            if (! str($key)->contains('_confirmation')) {
                $_inputs[$key] = $value;
            }
        }
        $this->setInputs($_inputs);
    }

    public function inputs(): array
    {
        return $this->inputs;
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

    public function prepare()
    {
    }

    public function execute()
    {
        $this->prepare();

        Validator::make(
            array_merge(
                $this->getConstrainedBy(),
                $this->inputs()
            ),
            $this->rules()
        )->validate();

        $this->hashFields();
        $this->encryptFields();
        $this->removeConfirmationFields();

        return $this->record = DB::transaction(function () {
            return $this->hasConstrained()
                ? $this->model::updateOrCreate($this->getConstrainedBy(), $this->inputs())
                : $this->model::create($this->inputs());
        });
    }

    public function getRecord(): Model
    {
        return $this->record;
    }
}
