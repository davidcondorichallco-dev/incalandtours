<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        return $request->session()->has('staff_id') ? $next($request) : redirect()->guest(route('login'));
    }
}
