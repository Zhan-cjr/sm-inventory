<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class DynamicDatabaseSwitch
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $dbName = null;

        if ($request->hasSession()) {
            $dbName = session('active_database');
        }

        if (!$dbName) {
            $dbName = $request->header('X-Active-Database');
        }

        if ($dbName && preg_match('/^[a-zA-Z0-9_-]+$/', $dbName)) {
            // Ubah koneksi mysql default
            config(['database.connections.mysql.database' => $dbName]);
            DB::purge('mysql');
            DB::reconnect('mysql');
        }

        return $next($request);
    }
}
