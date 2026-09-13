<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyDeviceApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.device.key');

        if (! $expected || ! hash_equals((string) $expected, (string) $request->header('X-Device-Key'))) {
            return response()->json(['message' => 'Invalid device key.'], 401);
        }

        return $next($request);
    }
}
