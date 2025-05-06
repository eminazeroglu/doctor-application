<?php

namespace App\Http\Middleware;

use App\Enums\ActivityLogActionEnum;
use App\Enums\ActivityLogStatusEnum;
use App\Services\Module\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ApiLoggingMiddleware
{
    /**
     * Activity Log xidməti
     */
    protected ActivityLogService $activityLogService;

    /**
     * Log edilməyəcək route-lar
     */
    protected array $excludedPaths = [
        'api/health-check',
        'api/ping',
        'api/metrics'
    ];

    /**
     * Request-dən təmizlənəcək həssas məlumatlar
     */
    protected array $sensitiveData = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'secret'
    ];

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * API request-lərini emal edir və loglayır.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Request başlama vaxtını qeyd edirik
        $startTime = microtime(true);

        // Əgər loqlanmamalı olan route-dursa, keçid edirik
        if ($this->shouldSkipLogging($request)) {
            return $next($request);
        }

        // Request məlumatlarını qeyd edirik
        $requestData = $this->captureRequestData($request);

        // Response-u əldə edirik
        $response = $next($request);

        // Response məlumatlarını qeyd edirik
        $responseData = $this->captureResponseData($response);

        // Request müddətini hesablayırıq (millisaniyələrlə)
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        // Log yazırıq
        $this->logApiCall(
            $request,
            $response,
            $requestData,
            $responseData,
            $duration
        );

        return $response;
    }

    /**
     * Request-in loglanıb-loglanmayacağını yoxlayır
     */
    protected function shouldSkipLogging(Request $request): bool
    {
        // Excluded path-ları yoxlayırıq
        foreach ($this->excludedPaths as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Request məlumatlarını təhlükəsiz şəkildə əldə edir
     */
    protected function captureRequestData(Request $request): array
    {
        return [
            'headers' => $this->filterHeaders($request->headers->all()),
            'query' => $request->query(),
            'body' => $this->filterSensitiveData($request->except($this->sensitiveData)),
            'files' => $this->captureFileData($request->allFiles())
        ];
    }

    /**
     * Response məlumatlarını əldə edir
     */
    protected function captureResponseData(Response $response): array
    {
        return [
            'status' => $response->getStatusCode(),
            'headers' => $this->filterHeaders($response->headers->all()),
            'body' => $this->shouldCaptureResponseBody($response)
                ? json_decode($response->getContent(), true)
                : '[BINARY CONTENT]'
        ];
    }

    /**
     * API çağırışını loglayır
     */
    protected function logApiCall(
        Request $request,
        Response $response,
        array $requestData,
        array $responseData,
        float $duration
    ): void {
        $status = $response->getStatusCode() >= 400
            ? ActivityLogStatusEnum::ERROR
            : ActivityLogStatusEnum::SUCCESS;

        $action = $this->determineAction($request, $response);

        // Meta məlumatları hazırlayırıq
        $metaData = [
            'duration_ms' => $duration,
            'request' => $requestData,
            'response' => $responseData,
            'browser' => $request->header('User-Agent'),
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
            'route' => $request->route() ? [
                'name' => $request->route()->getName(),
                'action' => $request->route()->getActionName()
            ] : null
        ];

        // Logu yazırıq
        $this->activityLogService->log(
            action: $action,
            oldData: null,
            newData: null,
            additionalData: [
                'status' => $status,
                'meta_data' => $metaData,
                'error_message' => $status === ActivityLogStatusEnum::ERROR
                    ? $responseData['body']['message'] ?? null
                    : null
            ]
        );
    }

    /**
     * Request və response əsasında action təyin edir
     */
    protected function determineAction(Request $request, Response $response): string
    {
        // Xəta varsa
        if ($response->getStatusCode() >= 400) {
            return match ($response->getStatusCode()) {
                401 => ActivityLogActionEnum::UNAUTHORIZED,
                403 => ActivityLogActionEnum::FORBIDDEN,
                404 => ActivityLogActionEnum::NOT_FOUND,
                422 => ActivityLogActionEnum::VALIDATION_ERROR,
                default => ActivityLogActionEnum::API_ERROR
            };
        }

        // Method əsasında action təyin edirik
        return match ($request->method()) {
            'GET' => ActivityLogActionEnum::API_READ,
            'POST' => ActivityLogActionEnum::API_CREATE,
            'PUT', 'PATCH' => ActivityLogActionEnum::API_UPDATE,
            'DELETE' => ActivityLogActionEnum::API_DELETE,
            default => ActivityLogActionEnum::API_OTHER
        };
    }

    /**
     * Həssas məlumatları request body-dən təmizləyir
     */
    protected function filterSensitiveData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->sensitiveData)) {
                $data[$key] = '[FILTERED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->filterSensitiveData($value);
            }
        }
        return $data;
    }

    /**
     * Həssas header-ləri təmizləyir
     */
    protected function filterHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-xsrf-token'];

        foreach ($headers as $key => $value) {
            if (in_array(Str::lower($key), $sensitiveHeaders)) {
                $headers[$key] = ['[FILTERED]'];
            }
        }

        return $headers;
    }

    /**
     * Fayl məlumatlarını qeyd edir
     */
    protected function captureFileData(array $files): array
    {
        $fileData = [];

        foreach ($files as $key => $file) {
            if (is_array($file)) {
                $fileData[$key] = $this->captureFileData($file);
            } else {
                $fileData[$key] = [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType()
                ];
            }
        }

        return $fileData;
    }

    /**
     * Response body-nin qeyd edilib-edilməyəcəyini yoxlayır
     */
    protected function shouldCaptureResponseBody(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        return Str::contains($contentType, ['/json', '+json']);
    }
}
