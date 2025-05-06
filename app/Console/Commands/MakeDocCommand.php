<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeDocCommand extends Command
{
    protected $signature = 'make:doc {name}';
    protected $description = 'Create a new swagger JSON file for CRUD operations';

    public function handle(): int
    {
        try {
            $name = $this->argument('name');
            $path = base_path('api-docs');

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }

            $fileName = Str::plural(strtolower($name));
            $filePath = "{$path}/{$fileName}.json";

            if (File::exists($filePath)) {
                if (!$this->confirm("The file {$fileName}.json already exists. Do you want to overwrite it?")) {
                    return Command::FAILURE;
                }
            }

            $content = $this->getSwaggerContent($name);
            File::put($filePath, $content);

            $this->info("Swagger JSON file created successfully: {$fileName}.json");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function getSwaggerContent(string $name): string
    {
        $pluralName = Str::plural(strtolower($name));
        $singularName = Str::singular($name);
        $tag = ucfirst($pluralName);

        $content = [
            'tag' => $tag,
            'description' => "{$tag} ilə əlaqəli API endpoint-ləri",
            'endpoints' => [
                // GET list endpoint
                [
                    'path' => "/{$pluralName}",
                    'method' => 'get',
                    'summary' => "{$tag} siyahısı",
                    'description' => "Bütün {$pluralName} siyahısını qaytarır",
                    'authorization' => true,
                    'query' => [
                        'search' => [
                            'type' => 'text',
                            'description' => 'Axtarış'
                        ],
                        'status' => [
                            'type' => 'text',
                            'description' => 'Status filtri',
                            'default' => 'active'
                        ],
                        'page' => [
                            'type' => 'number',
                            'description' => 'Səhifə nömrəsi',
                            'default' => 1
                        ],
                        'limit' => [
                            'type' => 'number',
                            'description' => 'Səhifə limiti',
                            'default' => 20
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'data' => [
                                [
                                    'id' => 1,
                                    'name' => 'Example name',
                                    'status' => 'active',
                                    'created_at' => '2024-01-01 00:00:00'
                                ]
                            ],
                            'total' => 50
                        ]
                    ]
                ],
                // POST create endpoint
                [
                    'path' => "/{$pluralName}",
                    'method' => 'post',
                    'summary' => "Yeni {$singularName}",
                    'description' => "Yeni {$singularName} əlavə edir",
                    'authorization' => true,
                    'formBody' => [
                        'name' => [
                            'type' => 'text',
                            'required' => true,
                            'description' => 'Ad'
                        ],
                        'description' => [
                            'type' => 'text',
                            'description' => 'Açıqlama'
                        ],
                        'status' => [
                            'type' => 'text',
                            'description' => 'Status',
                            'default' => 'active'
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'id' => 1,
                            'name' => 'Example name',
                            'description' => 'Example description',
                            'status' => 'active',
                            'created_at' => '2024-01-01 00:00:00'
                        ],
                        '422' => [
                            'name' => 'Zəhmət olmasa boş buraxmayın'
                        ]
                    ]
                ],
                // GET single endpoint
                [
                    'path' => "/{$pluralName}/{id}",
                    'method' => 'get',
                    'summary' => "{$singularName} detalları",
                    'description' => "{$singularName} detallarını qaytarır",
                    'authorization' => true,
                    'responses' => [
                        '200' => [
                            'id' => 1,
                            'name' => 'Example name',
                            'description' => 'Example description',
                            'status' => 'active',
                            'created_at' => '2024-01-01 00:00:00'
                        ],
                        '404' => [
                            'message' => 'Məlumat tapılmadı'
                        ]
                    ]
                ],
                // PUT update endpoint
                [
                    'path' => "/{$pluralName}/{id}",
                    'method' => 'put',
                    'summary' => "{$singularName} yeniləmə",
                    'description' => "{$singularName} məlumatlarını yeniləyir",
                    'authorization' => true,
                    'formBody' => [
                        'name' => [
                            'type' => 'text',
                            'required' => true,
                            'description' => 'Ad'
                        ],
                        'description' => [
                            'type' => 'text',
                            'description' => 'Açıqlama'
                        ],
                        'status' => [
                            'type' => 'text',
                            'description' => 'Status',
                            'default' => 'active'
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'id' => 1,
                            'name' => 'Updated name',
                            'description' => 'Updated description',
                            'status' => 'active',
                            'created_at' => '2024-01-01 00:00:00'
                        ],
                        '422' => [
                            'name' => 'Ad mütləqdir'
                        ]
                    ]
                ],
                // DELETE endpoint
                [
                    'path' => "/{$pluralName}/{id}",
                    'method' => 'delete',
                    'summary' => "{$singularName} silmə",
                    'description' => "{$singularName} məlumatını silir",
                    'authorization' => true,
                    'responses' => [
                        '200' => [
                            'message' => 'Məlumat uğurla silindi'
                        ],
                        '404' => [
                            'message' => 'Məlumat tapılmadı'
                        ]
                    ]
                ]
            ]
        ];

        return json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
