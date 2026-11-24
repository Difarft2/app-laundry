<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLandingApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('landing.api_key');
        $provided = $request->header('X-Landing-Api-Key');

        abort_unless(
            is_string($expected) && $expected !== ''
                && is_string($provided) && hash_equals($expected, $provided),
            401,
            'Kunci API landing tidak valid.'
        );

        return $next($request);
    }
}
