<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Organization;
use App\Models\User;
use App\Traits\HasUuid;
use App\Traits\BelongsToOrganization;

class InventoryMovement extends Model
{
    use HasUuid, BelongsToOrganization;

    const UPDATED_AT = null; // Only track created_at

    protected $fillable = [
        'organization_id',
        'product_id',
        'product_variation_id',
        'location_id',
        'type',
        'quantity',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'reference_number',
        'reason',
        'notes',
        'batch_number',
        'lot_number',
        'expiry_date',
        'created_by',
        'balance_before',
        'balance_after'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'balance_before' => 'integer',
        'balance_after' => 'integer'
    ];

    // Movement types
    const TYPE_PURCHASE = 'purchase';
    const TYPE_SALE = 'sale';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_TRANSFER_IN = 'transfer_in';
    const TYPE_TRANSFER_OUT = 'transfer_out';
    const TYPE_RETURN = 'return';
    const TYPE_DAMAGED = 'damaged';
    const TYPE_LOST = 'lost';
    const TYPE_FOUND = 'found';

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeIncreases($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeDecreases($query)
    {
        return $query->where('quantity', '<', 0);
    }

    // Helper methods
    public function isIncrease(): bool
    {
        return $this->quantity > 0;
    }

    public function isDecrease(): bool
    {
        return $this->quantity < 0;
    }

    public function getMovementTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_PURCHASE => 'Purchase',
            self::TYPE_SALE => 'Sale',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_TRANSFER_IN => 'Transfer In',
            self::TYPE_TRANSFER_OUT => 'Transfer Out',
            self::TYPE_RETURN => 'Return',
            self::TYPE_DAMAGED => 'Damaged',
            self::TYPE_LOST => 'Lost',
            self::TYPE_FOUND => 'Found',
            default => ucfirst($this->type)
        };
    }

    public function getDisplayQuantity(): string
    {
        $prefix = $this->isIncrease() ? '+' : '';
        return $prefix . number_format($this->quantity);
    }
}