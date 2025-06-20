<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugBroadcastingAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('🔐 BROADCASTING AUTH DEBUG', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'has_session' => $request->hasSession(),
            'session_id' => $request->session() ? $request->session()->getId() : 'no-session',
            'auth_check' => Auth::check(),
            'user_id' => Auth::id(),
            'user' => Auth::user() ? [
                'id' => Auth::user()->id,
                'email' => Auth::user()->email,
                'name' => Auth::user()->firstname . ' ' . Auth::user()->lastname,
            ] : null,
            'headers' => [
                'x-csrf-token' => $request->header('X-CSRF-TOKEN'),
                'x-requested-with' => $request->header('X-Requested-With'),
                'accept' => $request->header('Accept'),
                'content-type' => $request->header('Content-Type'),
                'cookie' => $request->header('Cookie') ? 'present' : 'missing',
                'authorization' => $request->header('Authorization'),
            ],
            'cookies' => $request->cookies->all(),
            'body' => $request->all(),
        ]);

        $response = $next($request);

        Log::info('🔐 BROADCASTING AUTH RESPONSE', [
            'status' => $response->getStatusCode(),
            'content' => $response->getContent(),
            'headers' => $response->headers->all(),
        ]);

        return $response;
    }
} 