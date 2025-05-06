<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiSignatureMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Test mühitində yoxlama keçid
        if (app()->environment('local', 'development', 'testing')) {
            return $next($request);
        }

        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');

        // Bu başlıqlar yoxdur?
        if (!$timestamp || !$signature) {
            return response()->json([
                'message' => 'Missing required headers'
            ], 403);
        }

        // Zaman aşımı yoxlaması (1 dəqiqə)
        $tolerance = config('api.timestamp_tolerance', 60); // saniyələr
        if (abs(time() - intval($timestamp)) > $tolerance) {
            return response()->json([
                'message' => 'Request timestamp expired'
            ], 403);
        }

        // İmza yoxlaması
        $secret = config('api.signing_secret');
        $payload = $timestamp . $request->getPathInfo();

        // POST və ya PUT istəklərisə, məlumatları da imzaya daxil et
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $payload .= json_encode($request->all());
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'message' => 'Invalid request signature'
            ], 403);
        }

        return $next($request);
    }
}
