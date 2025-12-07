<?php

namespace App\Guards;

use App\Models\LoginAttempt;
use App\Models\UserSession;
use Illuminate\Auth\SessionGuard as BaseSessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;

class SessionGuard extends BaseSessionGuard
{
    public function attempt(array $credentials = [], $remember = false)
    {
        $this->logLoginAttempt($credentials['email'] ?? '', false, 'attempt_started');
        
        // Check for too many failed attempts
        if ($this->hasTooManyLoginAttempts($credentials['email'] ?? '')) {
            $this->logLoginAttempt($credentials['email'] ?? '', false, 'too_many_attempts');
            return false;
        }
        
        $result = parent::attempt($credentials, $remember);
        
        if ($result) {
            $this->logLoginAttempt($credentials['email'] ?? '', true);
            $this->createUserSession();
            $this->updateUserLoginInfo();
        } else {
            $this->logLoginAttempt($credentials['email'] ?? '', false, 'invalid_credentials');
        }
        
        return $result;
    }

    public function logout()
    {
        if ($this->user()) {
            $this->logLoginAttempt($this->user()->email, true, 'logout');
            $this->clearUserSession();
        }
        
        parent::logout();
    }

    private function logLoginAttempt(string $email, bool $successful, ?string $reason = null): void
    {
        LoginAttempt::create([
            'email' => $email,
            'ip_address' => request()->ip(),
            'successful' => $successful,
            'failure_reason' => $successful ? null : $reason,
            'user_agent' => request()->userAgent(),
            'attempted_at' => now(),
        ]);
    }

    private function hasTooManyLoginAttempts(string $email): bool
    {
        $maxAttempts = 5;
        $timeWindow = 15; // minutes
        
        $recentFailedAttempts = LoginAttempt::forEmail($email)
            ->failed()
            ->recent($timeWindow)
            ->count();
        
        return $recentFailedAttempts >= $maxAttempts;
    }

    private function createUserSession(): void
    {
        if ($this->user()) {
            $session = UserSession::create([
                'id' => session()->getId(),
                'user_id' => $this->user()->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'payload' => base64_encode(serialize(session()->all())),
                'last_activity' => time(),
                'expires_at' => now()->addMinutes(config('session.lifetime')),
            ]);
            
            $session->markAsCurrent();
        }
    }

    private function clearUserSession(): void
    {
        UserSession::where('id', session()->getId())->delete();
    }

    private function updateUserLoginInfo(): void
    {
        if ($this->user()) {
            $this->user()->updateLastLogin();
        }
    }
}
