<?php

namespace App\Services\Doc;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DocGeneratorService
{
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @throws \Exception
     */
    public function generate(): array
    {
        $this->createDefaultConfig();

        $documentation = [
            "config" => $this->loadJson('config.json'),
            "elements" => []
        ];

        // Process each module JSON file
        foreach (glob($this->basePath . '/*.json') as $file) {
            if (!Str::endsWith($file, 'config.json')) {
                $moduleData = $this->loadJson(basename($file));

                // Transform the new endpoint array format back to the nested format if needed
                if (isset($moduleData['endpoints']) && is_array($moduleData['endpoints']) && !empty($moduleData['endpoints']) && isset($moduleData['endpoints'][0])) {
                    $moduleData['endpoints'] = $this->convertEndpointsToNestedFormat($moduleData['endpoints']);
                }

                $documentation['elements'][] = $moduleData;
            }
        }

        return $documentation;
    }

    /**
     * Convert flat array of endpoints to nested object format
     *
     * @param array $endpoints
     * @return array
     */
    protected function convertEndpointsToNestedFormat(array $endpoints): array
    {
        $nestedEndpoints = [];

        foreach ($endpoints as $endpoint) {
            $path = $endpoint['path'];
            $method = $endpoint['method'];

            // Initialize the path if it doesn't exist
            if (!isset($nestedEndpoints[$path])) {
                $nestedEndpoints[$path] = [];
            }

            // Remove path and method from the endpoint as they're now used as keys
            $endpointData = $endpoint;
            unset($endpointData['path']);
            unset($endpointData['method']);

            // Add the endpoint data under the path and method
            $nestedEndpoints[$path][$method] = $endpointData;
        }

        return $nestedEndpoints;
    }

    /**
     * @throws \Exception
     */
    protected function loadJson(string $file): array
    {
        $path = $this->basePath . '/' . $file;

        if (!File::exists($path)) {
            throw new \Exception("File not found: $path");
        }

        $content = File::get($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in file {$file}: " . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new \Exception("Invalid JSON structure in file {$file}");
        }

        return $data;
    }

    protected function createDefaultConfig(): void
    {
        $configPath = $this->basePath . '/config.json';

        if (File::exists($configPath)) {
            return;
        }

        $config = [
            'info' => [
                'title' => config('app.name', 'API') . ' Documentation',
                'version' => '1.0.0',
                'description' => 'API Documentation'
            ],
            'servers' => [
                [
                    'url' => config('app.url') . '/api',
                    'description' => 'API Server'
                ]
            ],
            'authorization' => 'bearerToken'
        ];

        File::put(
            $configPath,
            json_encode($config, JSON_PRETTY_PRINT)
        );
    }
}
