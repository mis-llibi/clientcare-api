<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PortalRequestApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('CORPORATE-API-KEY');

        if($apiKey !== config('app.corporate_api_key')){
            return response()->json([
                'message' => 'Unauthorized'
            ]);
        }

        return $next($request);
    }
}
