<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUuid;

class ProductVariation extends Model
{
    use HasUuid;

    protected $fillable = [
        'parent_product_id',
        'sku',
        'variation_attributes',
        'cost_price',
        'selling_price',
        'msrp',
        'weight',
        'length',
        'width',
        'height',
        'stock_quantity',
        'low_stock_threshold',
        'featured_image',
        'gallery_images',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'variation_attributes' => 'array',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'msrp' => 'decimal:2',
        'weight' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'gallery_images' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Relationships
    public function parentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function inventoryLevels(): HasMany
    {
        return $this->hasMany(InventoryLevel::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function supplierProducts(): HasMany
    {
        return $this->hasMany(SupplierProduct::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Helper methods
    public function getEffectiveCostPrice(): float
    {
        return $this->cost_price ?? $this->parentProduct->cost_price;
    }

    public function getEffectiveSellingPrice(): float
    {
        return $this->selling_price ?? $this->parentProduct->selling_price;
    }

    public function getEffectiveWeight(): float
    {
        return $this->weight ?? $this->parentProduct->weight;
    }

    public function getVariationName(): string
    {
        $attributes = [];
        foreach ($this->variation_attributes as $key => $value) {
            $attributes[] = ucfirst($key) . ': ' . $value;
        }
        
        return $this->parentProduct->name . ' (' . implode(', ', $attributes) . ')';
    }

    public function isInStock(): bool
    {
        return $this->getAvailableStock() > 0;
    }

    public function getAvailableStock(): int
    {
        return $this->inventoryLevels()->sum('quantity_available');
    }
}