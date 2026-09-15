<?php

namespace App\Security;

use Illuminate\Support\Facades\Cache;

class LoginAttemptGuard
{
    private const ATTEMPTS_PER_CYCLE = 3;
    private const BASE_DELAY_SECONDS = 10;
    private const STATE_TTL_SECONDS = 86400;

    public function status(string $email, string $ip): array
    {
        $state = $this->state($email, $ip);
        $retryAfter = max(0, (int) ($state['locked_until'] ?? 0) - now()->timestamp);

        return [
            'locked' => $retryAfter > 0,
            'retry_after' => $retryAfter,
        ];
    }

    public function failed(string $email, string $ip): array
    {
        $key = $this->key($email, $ip);
        $state = Cache::get($key, ['attempts' => 0, 'cycle' => 0, 'locked_until' => 0]);
        $state['attempts']++;

        $retryAfter = 0;
        if ($state['attempts'] >= self::ATTEMPTS_PER_CYCLE) {
            $state['attempts'] = 0;
            $state['cycle']++;
            $retryAfter = $state['cycle'] * self::BASE_DELAY_SECONDS;
            $state['locked_until'] = now()->addSeconds($retryAfter)->timestamp;
        }

        Cache::put($key, $state, self::STATE_TTL_SECONDS);

        return [
            'locked' => $retryAfter > 0,
            'retry_after' => $retryAfter,
            'remaining_attempts' => self::ATTEMPTS_PER_CYCLE - $state['attempts'],
        ];
    }

    public function clear(string $email, string $ip): void
    {
        Cache::forget($this->key($email, $ip));
    }

    private function state(string $email, string $ip): array
    {
        return Cache::get($this->key($email, $ip), ['attempts' => 0, 'cycle' => 0, 'locked_until' => 0]);
    }

    private function key(string $email, string $ip): string
    {
        return 'security:login:'.hash('sha256', mb_strtolower(trim($email)).'|'.$ip);
    }
}
