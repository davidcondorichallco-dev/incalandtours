<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class RejectUnsafeInput
{
    private const SENSITIVE_FIELDS = ['password', 'password_confirmation', 'token', 'branch_token'];

    public function handle(Request $request, Closure $next): Response
    {
        $unsafeField = $this->findUnsafeField($request->all());

        if ($unsafeField !== null) {
            Log::warning('security.unsafe_input_rejected', [
                'request_id' => $request->attributes->get('request_id'),
                'route' => $request->path(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'field' => $unsafeField,
            ]);

            if ($request->isMethod('GET') && !$request->expectsJson()) {
                return redirect()->route('login')->withErrors([
                    'email' => 'La URL contiene datos no permitidos. Inicia sesión nuevamente.',
                ]);
            }

            throw ValidationException::withMessages([
                $unsafeField => 'El campo contiene etiquetas o símbolos peligrosos y no puede almacenarse.',
            ]);
        }

        return $next($request);
    }

    private function findUnsafeField(array $input, string $prefix = ''): ?string
    {
        foreach ($input as $key => $value) {
            $field = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (in_array((string) $key, self::SENSITIVE_FIELDS, true)) continue;

            if (is_array($value)) {
                $nested = $this->findUnsafeField($value, $field);
                if ($nested !== null) return $nested;
                continue;
            }

            if (is_string($value) && $this->isUnsafe($value)) return $field;
        }

        return null;
    }

    private function isUnsafe(string $value): bool
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_match('/[<>`\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $decoded) === 1
            || preg_match('/(?:javascript\s*:|data\s*:\s*text\/html|on[a-z]+\s*=|expression\s*\()/iu', $decoded) === 1;
    }
}
