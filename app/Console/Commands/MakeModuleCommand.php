<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name}
       {--back : Remove the module}
       {--backup : Create backup before removing}
       {--interactive : Interactive mode}
       {--force : Force operation without confirmation}';

    protected $description = 'Create a new module with all necessary files or remove an existing module';

    protected $fileTypes = ['model', 'repository', 'service', 'controller', 'filter'];

    protected $progressBar;

    public function handle()
    {
        try {
            $name = $this->argument('name');
            $this->validateModuleName($name);

            if ($this->option('interactive')) {
                return $this->handleInteractiveMode($name);
            }

            if ($this->option('back')) {
                if (!$this->option('force')) {
                    if (!$this->confirm("Are you sure you want to remove {$name} module?")) {
                        $this->info('Operation cancelled.');
                        return 0;
                    }
                }

                if ($this->option('backup')) {
                    $this->backupModule($name);
                }
                return $this->removeModule($name);
            }

            return $this->createModule($name);

        } catch (\Exception $e) {
            $this->error($e->getMessage());
            Log::error('Module Command Error: ' . $e->getMessage());
            return 1;
        }
    }

    protected function migrationExists($tableName): bool
    {
        $migrationPattern = database_path('migrations/*_create_' . $tableName . '_table.php');
        return !empty(glob($migrationPattern));
    }

    protected function createModule($name): int
    {
        $pluralName = Str::plural($name);
        $tableName = Str::snake($pluralName);
        $migrationExists = $this->migrationExists($tableName);

        $steps = count($this->fileTypes) + 3;
        $this->startProgress($steps);

        try {
            if (!$migrationExists) {
                Artisan::call('make:migration', [
                    'name' => "create_{$tableName}_table",
                    '--create' => $tableName,
                ]);
                $this->logSuccess('Migration created successfully');
            } else {
                $this->logWarning("Migration already exists. Skipping.");
            }
            $this->advanceProgress();

            foreach ($this->fileTypes as $type) {
                $path = $this->getPath($name, $type);
                if (!File::exists($path) || $this->option('force')) {
                    $this->createFileFromStub($name, $type);
                    $this->logSuccess(ucfirst($type) . ' created successfully');
                } else {
                    $this->logWarning(ucfirst($type) . ' already exists. Skipping.');
                }
                $this->advanceProgress();
            }

            $this->addRoute($name);
            $this->logSuccess("Route added successfully");
            $this->advanceProgress();

            $this->addPermissions($name);
            $this->logSuccess("Permissions added successfully");
            $this->advanceProgress();

            $this->finishProgress();
            $this->logSuccess("Module {$name} created successfully!");

            Artisan::call('db:seed --class=PermissionSeeder');

            return 0;

        } catch (\Exception $e) {
            $this->finishProgress();
            throw $e;
        }
    }

    protected function removeModule($name): int
    {
        try {
            $migrationPattern = database_path('migrations/*_create_' . Str::snake(Str::plural($name)) . '_table.php');
            $migrationFiles = glob($migrationPattern);
            foreach ($migrationFiles as $migrationFile) {
                File::delete($migrationFile);
                $this->logSuccess('Migration file removed');
            }

            foreach ($this->fileTypes as $type) {
                if ($type === 'filter') {
                    $filterPath = $this->getPath($name, 'filter');
                    if (File::exists($filterPath)) {
                        File::delete($filterPath);
                        $this->logSuccess('Filter removed successfully');
                    }
                    continue;
                }

                $path = $this->getPath($name, $type);
                if (File::exists($path)) {
                    File::delete($path);
                    $this->logSuccess(ucfirst($type) . ' removed successfully');
                }
            }

            $this->removeRoute($name);
            $this->removePermissions($name);

            $this->removeEmptyDirectory(app_path("Repositories/Module"));
            $this->removeEmptyDirectory(app_path("Services/Module"));

            $this->logSuccess("Module {$name} removed successfully!");
            return 0;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function getPath($name, $type)
    {
        $paths = [
            'model' => app_path("Models/{$name}.php"),
            'repository' => app_path("Repositories/Module/{$name}Repository.php"),
            'service' => app_path("Services/Module/{$name}Service.php"),
            'controller' => app_path("Http/Controllers/Api/{$name}Controller.php"),
            'filter' => app_path("Services/Filter/{$name}Filter.php"),
        ];

        return $paths[$type];
    }

    protected function getNamespace($name, $type): string
    {
        $namespaces = [
            'model' => 'App\\Models',
            'repository' => 'App\\Repositories\\Module',
            'service' => 'App\\Services\\Module',
            'controller' => 'App\\Http\\Controllers\\Api',
            'filter' => 'App\\Services\\Filter',
        ];

        return $namespaces[$type];
    }

    protected function validateModuleName($name): void
    {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9]+$/', $name)) {
            throw new \InvalidArgumentException('Invalid module name. Use only alphanumeric characters.');
        }
    }

    // Progress bar methods
    protected function startProgress($steps): void
    {
        $this->progressBar = $this->output->createProgressBar($steps);
        $this->progressBar->start();
    }

    protected function advanceProgress(): void
    {
        if ($this->progressBar) {
            $this->progressBar->advance();
        }
    }

    protected function finishProgress(): void
    {
        if ($this->progressBar) {
            $this->progressBar->finish();
            $this->newLine();
        }
    }

    // Log methods
    protected function logSuccess($message): void
    {
        $this->info($message);
        Log::info("Module Command: {$message}");
    }

    protected function logWarning($message): void
    {
        $this->warn($message);
        Log::warning("Module Command: {$message}");
    }

    protected function logError($message): void
    {
        $this->error($message);
        Log::error("Module Command: {$message}");
    }

    // File operations
    protected function createFileFromStub($name, $type): void
    {
        $stub = file_get_contents(base_path("stubs/module/{$type}.stub"));

        $replacements = [
            '{{ class }}' => $type === 'filter' ? $name . 'Filter' : $name,
            '{{ module }}' => $name,
            '{{ permission }}' => strtolower($name),
            '{{ namespace }}' => $this->getNamespace($name, $type),
            '{{ table }}' => Str::snake(Str::plural($name))
        ];

        foreach ($replacements as $search => $replace) {
            $stub = str_replace($search, $replace, $stub);
        }

        $path = $this->getPath($name, $type);
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $stub);
    }

    protected function removeEmptyDirectory($path): void
    {
        if (is_dir($path) && count(scandir($path)) == 2) {
            rmdir($path);
            $this->logSuccess("Removed empty directory: {$path}");
        }
    }

    protected function formatModuleName($name, $type = 'default'): string
    {
        return match($type) {
            'route' => Str::kebab(Str::plural($name)),    // payment-services
            'permission' => Str::snake(Str::singular($name)), // payment_service
            default => $name
        };
    }

    // Route operations
    protected function addRoute($name): void
    {
        $routePath = base_path('routes/admin.php');
        $routeContent = file_get_contents($routePath);

        $useStatement = "use App\Http\Controllers\Api\\{$name}Controller;";
        $routeName = $this->formatModuleName($name, 'route');
        $newRoute = "// {$name}\nRoute::resource('" . $routeName . "', {$name}Controller::class);";

        if (!str_contains($routeContent, $useStatement)) {
            $lastUsePos = strrpos($routeContent, "use ");
            $insertPos = strpos($routeContent, ";", $lastUsePos) + 1;
            $routeContent = substr_replace($routeContent, "\n" . $useStatement, $insertPos, 0);
        }

        if (!str_contains($routeContent, $newRoute)) {
            $routeContent .= "\n\n" . $newRoute;
        }

        file_put_contents($routePath, $routeContent);
    }

    protected function removeRoute($name): void
    {
        $routePath = base_path('routes/admin.php');
        $routeContent = file_get_contents($routePath);

        $useStatement = "use App\Http\Controllers\Api\\{$name}Controller;";
        $routeName = $this->formatModuleName($name, 'route');
        $routeToRemove = "// {$name}\nRoute::resource('" . $routeName . "', {$name}Controller::class);";

        $routeContent = str_replace($useStatement . "\n", '', $routeContent);
        $routeContent = str_replace($routeToRemove, '', $routeContent);

        file_put_contents($routePath, $routeContent);
    }

    // Permission operations
    protected function addPermissions($name): void
    {
        $permissionsPath = config_path('permissions.php');
        $content = file_get_contents($permissionsPath);

        // Modul üçün icazələri yaradırıq
        $permissionName = $this->formatModuleName($name, 'permission');
        $newPermissions = [
            "{$permissionName}_create",
            "{$permissionName}_update",
            "{$permissionName}_delete",
            "{$permissionName}_read",
            "{$permissionName}_status",
        ];

        // Modul artıq varmı yoxlayırıq
        if (strpos($content, '"' . $permissionName . '"') !== false) {
            $this->logWarning("Permissions for '{$permissionName}' already exist. Skipping.");
            return;
        }

        // $permissionArr arrayinin sonunu tapırıq (yəni ]; ifadəsini)
        $arrayEndPos = strpos($content, '];');
        if ($arrayEndPos === false) {
            $this->logError("Could not find array end in permissions file.");
            return;
        }

        // Son elementin bitdiyi pozisiyanı tapırıq
        $contentBeforeEnd = substr($content, 0, $arrayEndPos);
        $lastElementEnd = strrpos($contentBeforeEnd, '],');

        // Yeni kod blokunu hazırlayırıq
        $newCode = "    \"$permissionName\" => [\n";
        foreach ($newPermissions as $permission) {
            $newCode .= "        \"$permission\",\n";
        }
        $newCode .= "    ],\n";

        // Array boşdursa
        if ($lastElementEnd === false || preg_match('/\$permissionArr\s*=\s*\[\s*\];/', $content)) {
            // Boş arraydırsa, sadəcə bizim kodu əlavə edirik, vergülsüz
            $newContent = preg_replace('/\$permissionArr\s*=\s*\[\s*\];/', "\$permissionArr = [\n$newCode];", $content);
        } else {
            // Son elementin bitdiyi yerdə, vergül olub-olmadığını yoxlayırıq
            $charsAfterLastElement = substr($contentBeforeEnd, $lastElementEnd + 2, 10);

            if (trim($charsAfterLastElement) === '') {
                // Əgər son elementdən sonra yalnız boşluqlar varsa, vergülün olduğundan əmin oluruq
                $newContent = substr_replace($content, $newCode, $arrayEndPos, 0);
            } else {
                // Əgər son elementdən sonra başqa simvollar varsa (yəni vergül yoxdursa)
                // Son elementin sonuna vergül əlavə edirik, sonra yeni kodu
                $positionAfterLastElement = $lastElementEnd + 2;
                $newContent = substr_replace($content, ",\n$newCode", $positionAfterLastElement, 0);
            }
        }

        file_put_contents($permissionsPath, $newContent);
        $this->logSuccess("Permissions for '{$permissionName}' added successfully.");
    }

    protected function removePermissions($name): void
    {
        $permissionsPath = config_path('permissions.php');
        $content = file_get_contents($permissionsPath);

        $permissionName = $this->formatModuleName($name, 'permission');

        // Elementi və vergülü silmək üçün pattern
        $pattern = '/\s*"' . preg_quote($permissionName, '/') . '"\s*=>\s*\[\s*(?:".*?",?\s*)*\],\s*/s';

        if (preg_match($pattern, $content, $matches)) {
            // Elementi silirik
            $newContent = preg_replace($pattern, "\n", $content);

            file_put_contents($permissionsPath, $newContent);
            $this->logSuccess("Permissions for '{$permissionName}' removed successfully.");
        } else {
            $this->logWarning("No permissions found for '{$permissionName}'. Skipping.");
        }
    }

    protected function varExport($expression, $indent = ""): ?string
    {
        switch (gettype($expression)) {
            case 'string':
                return '"' . addcslashes($expression, "\\\$\"\r\n\t\v\f") . '"';
            case 'array':
                $output = "[";
                $isSequential = array_keys($expression) === range(0, count($expression) - 1);
                if (!empty($expression)) {
                    $output .= "\n";
                    foreach ($expression as $key => $value) {
                        $output .= $indent . "    ";
                        if (!$isSequential) {
                            $output .= $this->varExport($key) . " => ";
                        }
                        $output .= $this->varExport($value, $indent . "    ");
                        $output .= ",\n";
                    }
                    $output .= $indent;
                }
                return $output . "]";
            case 'boolean':
                return $expression ? 'true' : 'false';
            case 'NULL':
                return 'null';
            case 'integer':
            case 'double':
                return $expression;
            default:
                return var_export($expression, true);
        }
    }
}
