<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'subdomain',
        'email',
        'phone',
        'description',
        'settings',
        'branding',
        'timezone',
        'currency',
        'status',
        'trial_ends_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'branding' => 'array',
        'trial_ends_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($organization) {
            if (empty($organization->slug)) {
                $organization->slug = Str::slug($organization->name);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // Scope helpers
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBySubdomain($query, $subdomain)
    {
        return $query->where('subdomain', $subdomain);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function getDefaultSettings(): array
    {
        return [
            'features' => [
                'inventory_management' => true,
                'order_processing' => true,
                'multi_platform_sync' => true,
                'analytics' => true,
                'api_access' => false,
            ],
            'limits' => [
                'max_users' => 10,
                'max_products' => 1000,
                'max_orders_per_month' => 500,
            ],
            'notifications' => [
                'email_enabled' => true,
                'sms_enabled' => false,
                'slack_webhook' => null,
            ],
        ];
    }
}