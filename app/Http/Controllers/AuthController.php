<?php

namespace App\Http\Controllers;

use App\Security\LoginAttemptGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('staff_id')) return redirect()->route('dashboard');
        return view('login');
    }

    public function login(Request $request, LoginAttemptGuard $guard)
    {
        $credentials = $request->validate(['email'=>'required|email','password'=>'required|string']);
        $ip = (string) $request->ip();
        $status = $guard->status($credentials['email'], $ip);
        if ($status['locked']) {
            return back()->withErrors(['email'=>"Demasiados intentos. Vuelve a intentarlo en {$status['retry_after']} segundos."])
                ->with('retry_after', $status['retry_after'])->onlyInput('email');
        }

        $staff = DB::table('employees')->where('email',$credentials['email'])->where('active',true)->first();
        if (!$staff || !Hash::check($credentials['password'],$staff->password)) {
            $failure = $guard->failed($credentials['email'], $ip);
            Log::notice('security.login_failed', ['request_id'=>$request->attributes->get('request_id'), 'ip'=>$ip, 'email_hash'=>hash('sha256', mb_strtolower($credentials['email'])), 'locked'=>$failure['locked']]);
            $message = $failure['locked']
                ? "Demasiados intentos. El acceso se bloqueó por {$failure['retry_after']} segundos."
                : 'El correo o la contraseña no son correctos.';
            return back()->withErrors(['email'=>$message])->with('retry_after', $failure['retry_after'])->onlyInput('email');
        }
        $guard->clear($credentials['email'], $ip);
        $request->session()->regenerate();
        $request->session()->put(['staff_id'=>$staff->id,'staff_role'=>$staff->role,'staff_branch_id'=>$staff->branch_id]);
        Log::info('security.login_succeeded', ['request_id'=>$request->attributes->get('request_id'), 'ip'=>$ip, 'staff_id'=>$staff->id]);
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
