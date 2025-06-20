<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogBroadcastingAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('🔐 BROADCASTING AUTH REQUEST', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'has_session' => $request->hasSession(),
            'session_id' => $request->session()->getId(),
            'auth_check' => Auth::check(),
            'user_id' => Auth::id(),
            'headers' => [
                'x-csrf-token' => $request->header('X-CSRF-TOKEN'),
                'x-requested-with' => $request->header('X-Requested-With'),
                'accept' => $request->header('Accept'),
                'content-type' => $request->header('Content-Type'),
                'cookie' => $request->header('Cookie') ? 'present' : 'missing',
            ],
            'body' => $request->all(),
        ]);

        $response = $next($request);

        Log::info('🔐 BROADCASTING AUTH RESPONSE', [
            'status' => $response->getStatusCode(),
            'content' => $response->getContent(),
        ]);

        return $response;
    }
} 