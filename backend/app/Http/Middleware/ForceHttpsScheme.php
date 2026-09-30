<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ForceHttpsScheme
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appUrl = (string) config('app.url');
        $isHttpsAppUrl = str_starts_with($appUrl, 'https://');
        $protoHeader = (string) $request->header('X-Forwarded-Proto');
        $serverProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';

        if (
            $isHttpsAppUrl ||
            $protoHeader === 'https' ||
            $serverProto === 'https' ||
            $request->isSecure()
        ) {
            $request->headers->set('X-Forwarded-Proto', 'https');
            $request->server->set('HTTP_X_FORWARDED_PROTO', 'https');
            $request->server->set('HTTPS', 'on');
            $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
            $_SERVER['HTTPS'] = 'on';
            URL::forceScheme('https');
        }

        return $next($request);
    }
}
