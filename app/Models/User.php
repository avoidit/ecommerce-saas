<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'organization_id',
        'employee_id',
        'phone',
        'department',
        'job_title',
        'hire_date',
        'preferences',
        'notification_settings',
        'is_active',
        'password_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'mfa_secret',
        'mfa_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'hire_date' => 'date',
        'preferences' => 'array',
        'notification_settings' => 'array',
        'last_login_at' => 'datetime',
        'password_expires_at' => 'datetime',
        'mfa_enabled' => 'boolean',
        'mfa_recovery_codes' => 'array',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
                    ->withPivot(['organization_id', 'team_id', 'expires_at'])
                    ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // Role and permission methods
    public function hasRole(string $roleSlug, ?int $organizationId = null): bool
    {
        $query = $this->roles()->where('slug', $roleSlug);
        
        if ($organizationId) {
            $query->wherePivot('organization_id', $organizationId);
        }
        
        return $query->exists();
    }

    public function hasPermission(string $permission, ?int $organizationId = null): bool
    {
        $query = $this->roles();
        
        if ($organizationId) {
            $query->wherePivot('organization_id', $organizationId);
        }
        
        $roles = $query->get();
        
        foreach ($roles as $role) {
            if (in_array($permission, $role->permissions)) {
                return true;
            }
        }
        
        return false;
    }

    public function assignRole(Role $role, ?int $organizationId = null, ?int $teamId = null): void
    {
        $this->roles()->attach($role->id, [
            'organization_id' => $organizationId,
            'team_id' => $teamId,
        ]);
    }

    public function removeRole(Role $role, ?int $organizationId = null): void
    {
        $query = $this->roles()->wherePivot('role_id', $role->id);
        
        if ($organizationId) {
            $query->wherePivot('organization_id', $organizationId);
        }
        
        $query->detach();
    }

    // Scope helpers
    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public function getDefaultPreferences(): array
    {
        return [
            'theme' => 'light',
            'language' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => '24h',
            'dashboard_layout' => 'default',
        ];
    }

    public function getDefaultNotificationSettings(): array
    {
        return [
            'email' => [
                'system_updates' => true,
                'order_notifications' => true,
                'inventory_alerts' => true,
                'weekly_reports' => false,
            ],
            'browser' => [
                'enabled' => true,
                'order_notifications' => true,
                'inventory_alerts' => true,
            ],
            'mobile' => [
                'enabled' => false,
                'order_notifications' => false,
                'inventory_alerts' => false,
            ],
        ];
    }

    public function updateLastLogin(): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
    }

    public function isMfaEnabled(): bool
    {
        return $this->mfa_enabled && !empty($this->mfa_secret);
    }

    public function isPasswordExpired(): bool
    {
        return $this->password_expires_at && $this->password_expires_at->isPast();
    }
}