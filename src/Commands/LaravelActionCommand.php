<?php

namespace CleaniqueCoders\LaravelAction\Commands;

use Illuminate\Console\GeneratorCommand;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class LaravelActionCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:action';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new action class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Action';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('menu')) {
            return $this->resolveStubPath('/stubs/action-menu.stub');
        }

        if ($this->option('api')) {
            return $this->resolveStubPath('/stubs/action-api.stub');
        }

        if ($this->option('resource')) {
            return $this->resolveStubPath('/stubs/action-resource.stub');
        }

        return $this->resolveStubPath('/stubs/action.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
                        ? $customPath
                        : __DIR__.'/../../'.$stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        if ($this->option('menu')) {
            return $rootNamespace.'\Actions\Builder\Menu';
        }

        if ($this->option('api')) {
            return $rootNamespace.'\Actions\Api';
        }

        return $rootNamespace.'\Actions';
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)
            ->replaceModel($stub)
            ->replaceClass($stub, $name);
    }

    protected function replaceModel(string &$stub): self
    {
        $this->throwExceptionIfMissingModel();

        if (! $this->option('model')) {
            return $this;
        }

        $stub = str_replace(
            ['{{ model_namespace }}', '{{ model }}'],
            [$this->getModelNamespace(), $this->getModel()],
            $stub
        );

        return $this;
    }

    protected function getModelNamespace(): string
    {
        $namespace = $this->option('namespace')
            ? $this->option('namespace')
            : config('action.model_namespace');

        return $namespace.$this->getModel();
    }

    public function throwExceptionIfMissingModel(): void
    {
        if (! $this->option('resource')) {
            return;
        }

        if (empty($this->option('model'))) {
            throw new RuntimeException('Missing model option.');
        }
    }

    /**
     * Retrieves the model option or throws an exception if it's missing.
     */
    public function getModel(): string
    {
        $model = $this->option('model');

        if (is_string($model) && $model !== '') {
            return $model;
        }

        throw new RuntimeException('The model option must be provided and cannot be empty.');
    }

    /**
     * Get the console command arguments.
     *
     * @return array<int, array<int|string, mixed>>
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the class'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array<int|string, mixed>>
     */
    protected function getOptions(): array
    {
        return [
            ['namespace', '', InputOption::VALUE_REQUIRED, 'The model namespace'],
            ['model', '', InputOption::VALUE_REQUIRED, 'The name of the model'],
            ['menu', '', InputOption::VALUE_NONE, 'Create a menu action'],
            ['api', '', InputOption::VALUE_NONE, 'Create an API action'],
            ['resource', 'r', InputOption::VALUE_NONE, 'Create a resource action'],
        ];
    }
}
