<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ApiStaffAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (!$plainToken) {
            return response()->json(['ok'=>false, 'message'=>'Token de acceso requerido.'], 401);
        }

        $token = DB::table('staff_api_tokens as t')
            ->join('employees as e', 'e.id', '=', 't.employee_id')
            ->leftJoin('branches as b', 'b.id', '=', 'e.branch_id')
            ->where('t.token_hash', hash('sha256', $plainToken))
            ->where('t.expires_at', '>', now())
            ->where('e.active', true)
            ->select('t.id as api_token_id', 't.expires_at', 'e.id', 'e.branch_id', 'e.full_name', 'e.email', 'e.phone', 'e.role', 'b.name as branch_name')
            ->first();

        if (!$token) {
            return response()->json(['ok'=>false, 'message'=>'El token no es válido o ha expirado.'], 401);
        }

        DB::table('staff_api_tokens')->where('id', $token->api_token_id)->update(['last_used_at'=>now()]);
        $request->attributes->set('staff', $token);
        $request->attributes->set('api_token_id', $token->api_token_id);

        return $next($request);
    }
}
