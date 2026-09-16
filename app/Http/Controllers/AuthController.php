<?php

namespace App\Http\Controllers;

use App\Security\LoginAttemptGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('staff_id')) return redirect()->route('dashboard');
        return view('login');
    }

    public function login(Request $request, LoginAttemptGuard $guard)
    {
        $credentials = $request->validate(['email'=>'required|string|max:150','password'=>'required|string']);
        $ip = (string) $request->ip();
        $status = $guard->status($credentials['email'], $ip);
        if ($status['locked']) {
            return back()->withErrors(['email'=>"Demasiados intentos. Vuelve a intentarlo en {$status['retry_after']} segundos."])
                ->with('retry_after', $status['retry_after'])->onlyInput('email');
        }

        $identifier = mb_strtolower(trim($credentials['email']));
        $staff = DB::table('employees')
            ->where('active', true)
            ->where(function ($query) use ($identifier) {
                $query->whereRaw('LOWER(email) = ?', [$identifier])
                    ->orWhereRaw('LOWER(username) = ?', [$identifier]);
            })
            ->first();
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

    public function updateProfile(Request $request)
    {
        $staffId = (int) $request->session()->get('staff_id');
        $staff = DB::table('employees')->where('id', $staffId)->where('role', 'admin')->firstOrFail();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('employees', 'username')->ignore($staffId)],
            'email' => ['required', 'email', 'max:150', Rule::unique('employees', 'email')->ignore($staffId)],
            'current_password' => ['required', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'username.regex' => 'El nombre de usuario solo puede contener letras, números, puntos, guiones y guiones bajos.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ]);

        if (!Hash::check($data['current_password'], $staff->password)) {
            return response()->json([
                'message' => 'La contraseña actual no es correcta.',
                'errors' => ['current_password' => ['La contraseña actual no es correcta.']],
            ], 422);
        }

        $changes = [
            'full_name' => trim($data['full_name']),
            'username' => mb_strtolower(trim($data['username'])),
            'email' => mb_strtolower(trim($data['email'])),
            'updated_at' => now(),
        ];
        if (!empty($data['password'])) {
            $changes['password'] = Hash::make($data['password']);
        }

        DB::transaction(function () use ($staffId, $changes, $data) {
            DB::table('employees')->where('id', $staffId)->update($changes);
            if (!empty($data['password'])) {
                DB::table('staff_api_tokens')->where('employee_id', $staffId)->delete();
            }
        });

        Log::info('security.admin_profile_updated', [
            'request_id' => $request->attributes->get('request_id'),
            'staff_id' => $staffId,
            'password_changed' => !empty($data['password']),
        ]);

        return response()->json([
            'ok' => true,
            'message' => !empty($data['password'])
                ? 'Perfil y contraseña actualizados. Los tokens API anteriores fueron revocados.'
                : 'Perfil actualizado correctamente.',
        ]);
    }
}
