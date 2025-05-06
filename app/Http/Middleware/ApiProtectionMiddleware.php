<?php

namespace App\Http\Middleware;

use App\Enums\ActivityLogActionEnum;
use App\Services\Module\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiProtectionMiddleware
{
    protected ActivityLogService $activityLogService;

    // Request counter və son vaxt üçün cache açarları
    private const CACHE_REQUEST_COUNT = 'api_request_count_';
    private const CACHE_LAST_NONCE = 'api_last_nonce_';

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Etibarlı frontend domainlər
        $validOrigins = config('api.allowed_origins', []);

        // Origin və ya Referer yoxla
        $origin = $request->headers->get('origin');
        $referer = $request->headers->get('referer');

        // 1. DOMAIN YOXLAMASI
        $validOriginCheck = $this->checkOrigin($origin, $referer, $validOrigins);

        // 2. BROWSER FINGERPRINT YOXLAMASI
        $validBrowserCheck = $this->checkBrowserFingerprint($request);

        // 3. NONCE VƏ TIMESTAMP YOXLAMASI
        $validNonceCheck = $this->checkNonceAndTimestamp($request);

        // 4. REQUEST PATTERN YOXLAMASI
        $validPatternCheck = $this->checkRequestPattern($request);

        // DEV MÜHİTİNDƏ YOXLAMA BYPASS
        // Təhlükəsizlik! Bu kod yalnız development zamanı aktiv olmalıdır
        if (app()->environment('local')) {
            return $next($request);
        }

        // Etibarsız sorğu - hər hansı yoxlama uğursuz olubsa
        if (!$validOriginCheck || !$validBrowserCheck || !$validNonceCheck || !$validPatternCheck) {
            // Loq qeyd et
            $this->activityLogService->log(
                action: ActivityLogActionEnum::UNAUTHORIZED_API_ACCESS,
                oldData: [
                    'origin' => $origin,
                    'referer' => $referer,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'checks' => [
                        'origin' => $validOriginCheck,
                        'browser' => $validBrowserCheck,
                        'nonce' => $validNonceCheck,
                        'pattern' => $validPatternCheck
                    ]
                ]
            );

            // Random gecikmə (DOS qarşısını almaq üçün)
            usleep(random_int(500000, 2000000)); // 0.5-2 saniyə

            return response()->json([
                'message' => 'Unauthorized request'
            ], 403);
        }

        // Uğurlu keçid - sorğu davam edir
        return $next($request);
    }

    /**
     * Domain yoxlaması
     */
    private function checkOrigin(?string $origin, ?string $referer, array $validOrigins): bool
    {
        // Əgər origin və referer ikisi də yoxdursa
        if (empty($origin) && empty($referer)) {
            return false;
        }

        // Origin yoxlaması
        if (!empty($origin)) {
            if ($this->matchesAnyAllowedOrigin($origin, $validOrigins)) {
                return true;
            }
        }

        // Referer yoxlaması
        if (!empty($referer)) {
            if ($this->matchesAnyAllowedOrigin($referer, $validOrigins)) {
                return true;
            }
        }

        return false;
    }

    /**
     * URL-in icazəli mənbələrdən birinə uyğun olub-olmadığını yoxlayır
     */
    private function matchesAnyAllowedOrigin(string $url, array $validOrigins): bool
    {
        // URL-i parse edirik
        $parsedUrl = parse_url($url);

        // Mütləq host olmalıdır
        if (!isset($parsedUrl['host'])) {
            return false;
        }

        $urlHost = $parsedUrl['host'];
        $urlPort = $parsedUrl['port'] ?? null;

        foreach ($validOrigins as $allowedOrigin) {
            $parsedAllowed = parse_url($allowedOrigin);

            if (!isset($parsedAllowed['host'])) {
                continue;
            }

            $allowedHost = $parsedAllowed['host'];
            $allowedPort = $parsedAllowed['port'] ?? null;

            // Host eyni olmalıdır
            if ($urlHost !== $allowedHost) {
                continue;
            }

            // Port yoxlaması
            // Əgər icazəli domain-də port varsa və URL-də də varsa, onlar uyğun olmalıdır
            if ($allowedPort !== null && $urlPort !== null) {
                if ($allowedPort == $urlPort) {
                    return true;
                }
            }
            // Əgər icazəli domain-də port yoxdursa, port nəzərə alınmır
            elseif ($allowedPort === null) {
                return true;
            }
            // Əgər URL-də port yoxdursa, amma icazəli domain-də varsa
            elseif ($urlPort === null && $allowedPort !== null) {
                // Standart HTTP və HTTPS portları üçün yoxlama (80 və 443)
                $scheme = $parsedUrl['scheme'] ?? '';
                $standardPort = ($scheme === 'https') ? 443 : 80;

                if ($allowedPort == $standardPort) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Browser barmaq izi yoxlaması - Postman üçün çətin
     */
    private function checkBrowserFingerprint(Request $request): bool
    {
        $userAgent = $request->header('User-Agent', '');
        $acceptHeader = $request->header('Accept', '');
        $acceptLanguage = $request->header('Accept-Language', '');
        $acceptEncoding = $request->header('Accept-Encoding', '');

        // Postman kimi alətlər tipik olaraq bu başlıqları düzgün təyin etmir

        // 1. User-Agent brauzer kimi görünür?
        $browserUA = preg_match('/(Mozilla|AppleWebKit|Chrome|Safari|Firefox|Edge)/i', $userAgent);

        // 2. Accept-Language başlığı ümumiyyətlə browserlərdə olur
        $hasAcceptLanguage = !empty($acceptLanguage);

        // 3. Accept başlığı browserlərdə daha geniş olur
        $hasAccept = stripos($acceptHeader, 'text/html') !== false;

        // 4. Accept-Encoding adətən brauzerlərdə gzip və digər formatları dəstəkləyir
        $hasEncoding = stripos($acceptEncoding, 'gzip') !== false;

        // 5. Sec-CH başlıqları yalnız modern brauzerlərdə olur
        $hasSecHeaders = $request->hasHeader('Sec-CH-UA') || $request->hasHeader('Sec-Fetch-Site');

        // Əgər əksər browser xarakteristikaları yoxdursa
        $score = ($browserUA ? 1 : 0) +
            ($hasAcceptLanguage ? 1 : 0) +
            ($hasAccept ? 1 : 0) +
            ($hasEncoding ? 1 : 0) +
            ($hasSecHeaders ? 2 : 0);

        // Ən azı 3 xarakteristika uyğun olmalıdır
        return $score >= 3;
    }

    /**
     * Nonce və timestamp yoxlaması
     */
    private function checkNonceAndTimestamp(Request $request): bool
    {
        // API nonce - təkrarlanmayan, tək dəfə istifadə olunan token
        $nonce = $request->header('X-API-Nonce');
        $timestamp = $request->header('X-API-Timestamp');

        // Bütün tələb olunan başlıqlar var?
        if (empty($nonce) || empty($timestamp)) {
            return false;
        }

        // Timestamp yoxlaması - 2 dəqiqə keçibsə, sorğu köhnədir
        $now = time();
        $requestTime = (int) $timestamp;

        if (abs($now - $requestTime) > 120) {
            return false;
        }

        // Nonce təkrarlanma yoxlaması
        $cacheKey = self::CACHE_LAST_NONCE . md5($nonce);

        // Bu nonce daha əvvəl istifadə edilib?
        if (Cache::has($cacheKey)) {
            return false;
        }

        // Bu nonce-i 5 dəqiqə üçün yadda saxla (replay hücumlarını əngəlləmək üçün)
        Cache::put($cacheKey, $now, 300);

        return true;
    }

    /**
     * Sorğu patternlərini yoxlayır (insan davranışı kimi görünürmü)
     */
    private function checkRequestPattern(Request $request): bool
    {
        $ipAddress = $request->ip();
        $requestKey = self::CACHE_REQUEST_COUNT . md5($ipAddress);

        // Son 60 saniyədə bu IP-dən neçə sorğu gəlib
        $requestCount = Cache::get($requestKey, 0);

        // Sorğu sayını artır
        Cache::put($requestKey, $requestCount + 1, 60);

        // Əgər 1 dəqiqədə 60-dan çox sorğu varsa (saniyədə 1-dən çox)
        // bu çox ehtimal bot davranışıdır
        if ($requestCount > 60) {
            return false;
        }

        return true;
    }

    /**
     * Domeni yoxlayır
     */
    private function isValidDomain($host, $validDomains): bool
    {
        if (empty($host)) {
            return false;
        }

        // Host-da port olub-olmadığını yoxlayaq
        $hostParts = explode(':', $host);
        $hostName = $hostParts[0];
        $hostPort = $hostParts[1] ?? null;

        foreach ($validDomains as $domain) {
            $parsedDomain = parse_url($domain);
            $domainHost = $parsedDomain['host'] ?? '';
            $domainPort = $parsedDomain['port'] ?? null;

            // Əgər host adları uyğundursa
            if ($hostName === $domainHost) {
                // Port yoxlaması - əgər domendə port varsa, host-da da eyni port olmalıdır
                if ($domainPort !== null && $hostPort !== null) {
                    if ($domainPort == $hostPort) {
                        return true;
                    }
                }
                // Əgər domendə port yoxdursa, host port-u önəmli deyil
                elseif ($domainPort === null) {
                    return true;
                }
                // Əgər host-da port yoxdursa, amma domendə varsa, uyğun deyil
                elseif ($hostPort === null && $domainPort !== null) {
                    continue;
                }
                // Əgər heç birində port yoxdursa, uyğundur
                else {
                    return true;
                }
            }
        }

        return false;
    }
}
