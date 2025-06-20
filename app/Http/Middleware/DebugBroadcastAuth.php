<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DebugBroadcastAuth
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('🔍 DEBUG Broadcasting Auth', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'headers' => [
                'user-agent' => $request->header('user-agent'),
                'referer' => $request->header('referer'),
                'cookie' => $request->header('cookie') ? 'Present' : 'Missing',
                'x-csrf-token' => $request->header('x-csrf-token') ? 'Present' : 'Missing',
            ],
            'post_data' => $request->all(),
            'session_id' => session()->getId(),
            'authenticated' => Auth::check(),
            'user' => Auth::user() ? [
                'id' => Auth::user()->id,
                'email' => Auth::user()->email
            ] : null,
            'guards' => [
                'web' => Auth::guard('web')->check(),
                'default' => Auth::check(),
            ]
        ]);

        return $next($request);
    }
} 