<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Organization;
use App\Models\User;
use App\Traits\HasUuid;
use App\Traits\BelongsToOrganization;

class Supplier extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'email',
        'phone',
        'website',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'tax_id',
        'business_registration',
        'payment_terms',
        'currency',
        'minimum_order_amount',
        'lead_time_days',
        'performance_rating',
        'total_orders',
        'on_time_delivery_rate',
        'is_active',
        'is_preferred',
        'notes',
        'meta_data',
        'created_by'
    ];

    protected $casts = [
        'minimum_order_amount' => 'decimal:2',
        'performance_rating' => 'decimal:2',
        'on_time_delivery_rate' => 'decimal:2',
        'payment_terms' => 'integer',
        'lead_time_days' => 'integer',
        'total_orders' => 'integer',
        'is_active' => 'boolean',
        'is_preferred' => 'boolean',
        'meta_data' => 'array'
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function supplierProducts(): HasMany
    {
        return $this->hasMany(SupplierProduct::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->through('supplier_products');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePreferred($query)
    {
        return $query->where('is_preferred', true);
    }

    public function scopeByPerformance($query)
    {
        return $query->orderBy('performance_rating', 'desc');
    }

    // Helper methods
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country
        ]);

        return implode(', ', $parts);
    }

    public function updatePerformanceRating(): void
    {
        // This would calculate performance based on order history
        // Implementation depends on order management system
    }
}
