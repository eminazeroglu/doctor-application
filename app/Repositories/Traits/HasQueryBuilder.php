<?php

namespace App\Repositories\Traits;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use ReflectionFunction;

trait HasQueryBuilder
{
    public function baseQuery(): Builder
    {
        $query = $this->model->newQuery();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        if (!empty($this->withCount)) {
            $query->withCount($this->withCount);
        }

        return $query;
    }

    /**
     * Query-ni cache-ləmə imkanı ilə icra edir
     *
     * @param string $method Query metodu (get, first və s.)
     * @param callable|null $callback Query builder callback
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function executeQuery(string $method, ?callable $callback = null): mixed
    {
        // Cache üçün unique açar yaradırıq
        $cacheKey = $this->createCacheKey($method, $callback);

        return $this->remember($cacheKey, function() use ($method, $callback) {
            // Əsas query-ni alırıq
            $query = $this->baseQuery();

            // Callback vasitəsilə query-ni modifikasiya edirik
            if ($callback) {
                $query = $callback($query);
            }

            // Əgər artıq icra olunmuş query-dirsə
            if (!$query instanceof Builder) {
                return $query;
            }

            // Method-u yoxlayırıq və icra edirik
            $allowedMethods = ['get', 'first', 'firstOrFail', 'count', 'paginate', 'exists', 'value', 'sum', 'max', 'min', 'avg'];

            if (!in_array($method, $allowedMethods)) {
                throw new \InvalidArgumentException("Invalid query method: {$method}");
            }

            return $query->{$method}();
        });
    }

    /**
     * Cache üçün unique açar yaradır
     *
     * @param string $method
     * @param callable|null $callback
     * @return string
     */
    public function createCacheKey(string $method, ?callable $callback = null): string
    {
        // Request parametrlərini hash-ə çeviririk
        $requestHash = md5(json_encode(request()->all()));

        // Callback üçün hash yaradırıq
        $callbackIdentifier = 'base';
        if ($callback) {
            $callbackIdentifier = $this->generateCallbackIdentifier($callback);
        }

        // Cache açarını model, method və identifier-dən yaradırıq
        return sprintf(
            '%s_%s_%s_%s',
            $this->model->getTable(),
            $method,
            $callbackIdentifier,
            $requestHash
        );
    }

    /**
     * Callback üçün unikal identifikator yaradır
     * Closure üçün daxili parametrləri və use-da istifadə edilən dəyişənləri analiz edir
     *
     * @param callable $callback
     * @return string
     */
    protected function generateCallbackIdentifier(callable $callback): string
    {
        // Callback bir Closure olduqda
        if ($callback instanceof Closure) {
            try {
                // ReflectionFunction istifadə edərək closure haqqında məlumat alırıq
                $reflection = new ReflectionFunction($callback);

                // Closure-un müəyyən edildyi fayl və xətt nömrəsi
                $file = $reflection->getFileName();
                $startLine = $reflection->getStartLine();
                $endLine = $reflection->getEndLine();

                // Funksiyanın parametrlərini alırıq
                $parameters = [];
                foreach ($reflection->getParameters() as $param) {
                    $parameters[] = $param->getName();
                }

                // Closure üçün identifier yaradırıq
                $identifier = sprintf(
                    'closure_%s_lines_%d_%d_params_%s',
                    basename($file),
                    $startLine,
                    $endLine,
                    implode('_', $parameters)
                );

                // Use edilən dəyişənlər haqqında məlumat əldə edə bilərik
                // Amma burada closure içində istifadə edilən bütün dəyişənləri almaq mümkün olmaya bilər

                return md5($identifier);
            } catch (\Exception $e) {
                // Xəta baş versə, sadə bir hash qaytarırıq
                return md5('closure_' . spl_object_hash($callback));
            }
        }

        // Callback array olduqda, sinifdə olan method üçün
        if (is_array($callback) && count($callback) === 2) {
            $class = is_object($callback[0]) ? get_class($callback[0]) : $callback[0];
            $method = $callback[1];
            return md5($class . '::' . $method);
        }

        // Digər hallarda, sadəcə hash yaradırıq
        return md5('callback_' . mt_rand(1000, 9999));
    }
}
