<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->get('staff')?->role !== 'admin') {
            return response()->json(['ok'=>false, 'message'=>'Esta acción requiere permisos de administrador.'], 403);
        }

        return $next($request);
    }
}
