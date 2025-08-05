<?php

use App\Enums\ActivityLogActionEnum;
use App\Services\Module\ActivityLogService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            registerApiRoutes();
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        registerCustomMiddleware($middleware);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        if (Env::get('APP_ENV') === 'local') {
            return $exceptions;
        }

        if (shouldSkipExceptionHandling()) {
            return;
        }

        registerAuthExceptions($exceptions);
        registerDatabaseExceptions($exceptions);
        registerHttpExceptions($exceptions);
        registerGeneralExceptions($exceptions);
    })
    ->withSchedule(function ($schedule) {
        // Telescope və Activity Log təmizləmə
        $schedule->command('telescope:prune')->daily();
        $schedule->command('log:clear --days=30')->daily();
    })
    ->create();

function registerApiRoutes(): void
{
    $defaultMiddleware = ['api', 'api.protection'];
    $authMiddleware = array_merge($defaultMiddleware, ['auth:sanctum', 'auth.user_control']);

    // Admin marşrutları
    Route::prefix('api/admin')
        ->as('admin.')
        ->middleware($authMiddleware)
        ->group(base_path('routes/admin.php'));

    // Common marşrutları
    Route::prefix('api/common')
        ->as('common.')
        ->middleware($defaultMiddleware)
        ->group(base_path('routes/common.php'));

    // Reference data marşrutları
    Route::prefix('api/reference-data')
        ->as('reference-data.')
        ->middleware($defaultMiddleware)
        ->group(base_path('routes/reference-data.php'));

    // App marşrutları
    Route::prefix('api/app')
        ->as('app.')
        ->middleware($defaultMiddleware)
        ->group(base_path('routes/app.php'));

    // Auth marşrutları
    Route::prefix('api/auth')
        ->as('auth.')
        ->middleware($defaultMiddleware)
        ->group(base_path('routes/auth.php'));
}

function registerCustomMiddleware(Middleware $middleware): void
{
    // Core middleware-lər
    $middleware->alias([
        'auth.user_control' => \App\Http\Middleware\UserControl::class,
        'auth.admin_control' => \App\Http\Middleware\AdminControl::class,
        'auth.optional' => \App\Http\Middleware\OptionalAuthSanctum::class,
        'check.blocked' => \App\Http\Middleware\CheckBlockedCredentials::class,
        'api.logging' => \App\Http\Middleware\ApiLoggingMiddleware::class,
        'api.protection' => \App\Http\Middleware\ApiProtectionMiddleware::class,
        'api.signature' => \App\Http\Middleware\ApiSignatureMiddleware::class,
    ]);

    // CSRF qoruması istisnaları
    $middleware->validateCsrfTokens(except: [
        'api/payment/*',
        'api/webhook/*'
    ]);
}

function shouldSkipExceptionHandling(): bool
{
    $app = app();
    return $app->runningInConsole() ||
        !$app->hasBeenBootstrapped() ||
        !$app->make('request')->is('api/*');
}

function registerAuthExceptions(Exceptions $exceptions): void
{
    // Authentication xətaları
    $exceptions->renderable(function (AuthenticationException $e) {
        logExceptionToActivityLog($e, ActivityLogActionEnum::UNAUTHORIZED);
        return createApiResponse('Unauthenticated', 401);
    });

    $exceptions->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e) {
        logExceptionToActivityLog($e, ActivityLogActionEnum::FORBIDDEN);
        return createApiResponse('Forbidden', 403);
    });
}

function registerDatabaseExceptions(Exceptions $exceptions): void
{
    $exceptions->renderable(function (QueryException $e) {
        return handleDatabaseException($e);
    });
}

function registerHttpExceptions(Exceptions $exceptions): void
{
    // Not Found xətaları
    $exceptions->renderable(function (NotFoundHttpException $e) {
        logExceptionToActivityLog($e, ActivityLogActionEnum::NOT_FOUND);
        return createApiResponse(
            Env::get('APP_ENV') === 'local' ? $e->getMessage() : 'Resource not found',
            404
        );
    });

    $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e) {
        logExceptionToActivityLog($e, ActivityLogActionEnum::VALIDATION_ERROR);
        return createApiResponse(
            'Validation failed',
            422,
            ['errors' => $e->errors()]
        );
    });
}

function registerGeneralExceptions(Exceptions $exceptions): void
{
    // Ümumi xətalar
    $exceptions->renderable(function (\Throwable $e) {
        logExceptionToActivityLog($e, ActivityLogActionEnum::SERVER_ERROR);

        return createApiResponse(
            Env::get('APP_ENV') === 'production' ? 'Server error' : $e->getMessage(),
            getHttpStatusCode($e),
            getDebugData($e)
        );
    });
}

function handleDatabaseException(QueryException $e): JsonResponse
{
    $errorCode = $e->getCode();
    $message = $e->getMessage();

    // Foreign key constraint xətası
    if (isForeignKeyViolation($errorCode, $message)) {
        logExceptionToActivityLog($e, ActivityLogActionEnum::FOREIGN_KEY_ERROR);
        return handleForeignKeyViolation($message);
    }

    // Duplicate entry xətası
    if (isDuplicateEntry($errorCode, $message)) {
        logExceptionToActivityLog($e, ActivityLogActionEnum::DUPLICATE_ENTRY);
        return handleDuplicateEntry($message);
    }

    logExceptionToActivityLog($e, ActivityLogActionEnum::DATABASE_ERROR);

    return createApiResponse(
        Env::get('APP_ENV') === 'production' ? 'Database error' : $message,
        500,
        ['code' => $errorCode]
    );

}

function logExceptionToActivityLog(\Throwable $e, string $action): void
{
    $activityLogService = app(ActivityLogService::class);

    $context = [
        'exception_class' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'request_method' => request()->method(),
        'request_url' => request()->fullUrl(),
        'user_id' => auth()->id(),
    ];

    $activityLogService->logError($action, $e->getMessage(), null, $context);
}

function createApiResponse(string $message, int $status, array $extra = []): JsonResponse
{
    $response = ['message' => $message];

    if (Env::get('APP_ENV') === 'local' && !empty($extra)) {
        $response['debug'] = $extra;
    }

    return response()->json($response, $status);
}

function getHttpStatusCode(\Throwable $e): int
{
    return method_exists($e, 'getStatusCode')
        ? $e->getStatusCode()
        : 500;
}

function getDebugData(\Throwable $e): array
{
    if (Env::get('APP_ENV') !== 'local') {
        return [];
    }

    return [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
}

// Database exception helpers
function isForeignKeyViolation(string $errorCode, string $message): bool
{
    return $errorCode == 23000 && str_contains($message, 'a foreign key constraint fails');
}

function isDuplicateEntry(string $errorCode, string $message): bool
{
    return $errorCode == 23000 && str_contains($message, 'Duplicate entry');
}

function isDatabaseStructureError(string $errorCode): bool
{
    return in_array($errorCode, ['42S02', '42S22']);
}

function handleForeignKeyViolation(string $message): JsonResponse
{
    preg_match('/FOREIGN KEY \(`(\w+)`\) REFERENCES `(\w+)`/', $message, $matches);
    if (isset($matches[1], $matches[2])) {
        return createApiResponse(
            "Bu məlumat silinə bilməz. {$matches[2]} cədvəlində istifadə olunur.",
            409
        );
    }
    return createApiResponse('Foreign key constraint violation', 409);
}

function handleDuplicateEntry(string $message): JsonResponse
{
    preg_match('/Duplicate entry \'(.*?)\' for key/', $message, $matches);
    if (isset($matches[1])) {
        return createApiResponse(
            $message,
            409
        );
    }
    return createApiResponse('Duplicate entry', 409);
}

function handleDatabaseStructureError(string $errorCode): JsonResponse
{
    $message = $errorCode === '42S02' ? 'Cədvəl tapılmadı.' : 'Sütun tapılmadı.';
    return createApiResponse($message, 404);
}
