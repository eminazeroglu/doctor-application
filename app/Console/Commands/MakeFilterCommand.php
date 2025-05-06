<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeFilterCommand extends GeneratorCommand
{
    protected $name = 'make:filter';
    protected $description = 'Create a new filter class';
    protected $type = 'Filter';

    protected function getStub(): string
    {
        return base_path('stubs/module/filter.stub');
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Services\Filter';
    }
}
