<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Security\LoginAttemptGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request, LoginAttemptGuard $guard)
    {
        $credentials = $request->validate([
            'email'=>'required|string|max:150',
            'password'=>'required|string',
            'device_name'=>'nullable|string|max:100',
        ]);

        $ip = (string) $request->ip();
        $status = $guard->status($credentials['email'], $ip);
        if ($status['locked']) {
            return response()->json([
                'ok'=>false,
                'message'=>"Demasiados intentos. Vuelve a intentarlo en {$status['retry_after']} segundos.",
                'retry_after'=>$status['retry_after'],
            ], 429)->header('Retry-After', (string) $status['retry_after']);
        }

        $identifier = mb_strtolower(trim($credentials['email']));
        $staff = DB::table('employees')
            ->where('active', true)
            ->where(function ($query) use ($identifier) {
                $query->whereRaw('LOWER(email) = ?', [$identifier])
                    ->orWhereRaw('LOWER(username) = ?', [$identifier]);
            })
            ->first();

        if (!$staff || !Hash::check($credentials['password'], $staff->password)) {
            $failure = $guard->failed($credentials['email'], $ip);
            Log::notice('security.api_login_failed', ['request_id'=>$request->attributes->get('request_id'), 'ip'=>$ip, 'email_hash'=>hash('sha256', mb_strtolower($credentials['email'])), 'locked'=>$failure['locked']]);
            if ($failure['locked']) {
                return response()->json([
                    'ok'=>false,
                    'message'=>"Demasiados intentos. El acceso se bloqueó por {$failure['retry_after']} segundos.",
                    'retry_after'=>$failure['retry_after'],
                ], 429)->header('Retry-After', (string) $failure['retry_after']);
            }
            return response()->json(['ok'=>false, 'message'=>'El correo o la contraseña no son correctos.'], 422);
        }

        $guard->clear($credentials['email'], $ip);

        $plainToken = Str::random(80);
        $expiresAt = now()->addDays(30);

        DB::table('staff_api_tokens')->insert([
            'employee_id'=>$staff->id,
            'name'=>$credentials['device_name'] ?? 'flutter',
            'token_hash'=>hash('sha256', $plainToken),
            'expires_at'=>$expiresAt,
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        Log::info('security.api_login_succeeded', ['request_id'=>$request->attributes->get('request_id'), 'ip'=>$ip, 'staff_id'=>$staff->id]);

        return response()->json([
            'ok'=>true,
            'message'=>'Sesión iniciada correctamente.',
            'data'=>[
                'access_token'=>$plainToken,
                'token_type'=>'Bearer',
                'expires_at'=>$expiresAt->toIso8601String(),
                'staff'=>$this->staffData($staff),
            ],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(['ok'=>true, 'data'=>$this->staffData($request->attributes->get('staff'))]);
    }

    public function logout(Request $request)
    {
        DB::table('staff_api_tokens')->where('id', $request->attributes->get('api_token_id'))->delete();

        return response()->json(['ok'=>true, 'message'=>'Sesión cerrada correctamente.']);
    }

    private function staffData(object $staff): array
    {
        return [
            'id'=>$staff->id,
            'full_name'=>$staff->full_name,
            'username'=>$staff->username,
            'email'=>$staff->email,
            'phone'=>$staff->phone,
            'role'=>$staff->role,
            'branch_id'=>$staff->branch_id,
            'branch_name'=>$staff->branch_name ?? DB::table('branches')->where('id', $staff->branch_id)->value('name'),
        ];
    }
}
