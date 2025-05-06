<?php

namespace App\Console\Commands;

use App\Services\Doc\DocGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateDocCommand extends Command
{
    protected $signature = 'doc:generate';
    protected $description = 'Generate Swagger documentation from JSON files';

    public function handle(): int
    {
        try {
            $this->info('Starting Swagger documentation generation...');

            $basePath = base_path('api-docs');

            if (!File::exists($basePath)) {
                File::makeDirectory($basePath, 0755, true);
            }

            $generator = new DocGeneratorService($basePath);
            $documentation = $generator->generate();

            $this->saveDocumentation($documentation);
            $this->showSummary($documentation);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error generating documentation: ' . $e->getMessage());
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    protected function saveDocumentation(array $documentation): void
    {
        $path = storage_path('api-docs');

        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        File::put(
            $path . '/api-docs.json',
            json_encode($documentation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        $this->info('Documentation saved: ' . $path . '/api-docs.json');
    }

    protected function showSummary(array $documentation): void
    {
        $this->newLine();
        $this->info('Documentation Summary:');

        // Count total endpoints and tags from the elements array
        $endpoints = 0;
        $tags = count($documentation['elements'] ?? []);

        // Count all endpoints across all modules
        foreach ($documentation['elements'] as $module) {
            if (isset($module['endpoints'])) {
                // Handle both formats - array format and object format
                if (is_array($module['endpoints']) && isset($module['endpoints'][0])) {
                    // Array format (new style)
                    $endpoints += count($module['endpoints']);
                } else {
                    // Object format (old style)
                    foreach ($module['endpoints'] as $path => $methods) {
                        $endpoints += count($methods);
                    }
                }
            }
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Endpoints', $endpoints],
                ['Tags', $tags],
            ]
        );

        $this->newLine();
        $this->info('Generated file:');
        $this->line('- ' . storage_path('api-docs/api-docs.json'));
    }
}
