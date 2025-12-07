<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Organization;
use App\Models\User;
use App\Traits\HasUuid;
use App\Traits\BelongsToOrganization;

class Product extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'sku',
        'barcode',
        'type',
        'status',
        'cost_price',
        'selling_price',
        'msrp',
        'currency',
        'weight',
        'length',
        'width',
        'height',
        'track_inventory',
        'manage_stock',
        'stock_quantity',
        'low_stock_threshold',
        'allow_backorders',
        'tax_class',
        'requires_shipping',
        'shipping_class',
        'seo_title',
        'seo_description',
        'search_keywords',
        'attributes',
        'featured_image',
        'gallery_images',
        'average_rating',
        'review_count',
        'total_sales',
        'view_count',
        'published_at',
        'created_by'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'msrp' => 'decimal:2',
        'weight' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'track_inventory' => 'boolean',
        'manage_stock' => 'boolean',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'allow_backorders' => 'boolean',
        'requires_shipping' => 'boolean',
        'attributes' => 'array',
        'gallery_images' => 'array',
        'average_rating' => 'decimal:2',
        'review_count' => 'integer',
        'total_sales' => 'integer',
        'view_count' => 'integer',
        'published_at' => 'datetime'
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class, 'parent_product_id');
    }

    public function activeVariations(): HasMany
    {
        return $this->variations()->where('is_active', true);
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

    public function suppliers(): HasManyThrough
    {
        return $this->hasManyThrough(Supplier::class, SupplierProduct::class);
    }

    public function bundleComponents(): HasMany
    {
        return $this->hasMany(ProductBundle::class, 'bundle_product_id');
    }

    public function bundleParents(): HasMany
    {
        return $this->hasMany(ProductBundle::class, 'component_product_id');
    }

    // Scopes
    public function scopeActive($query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePublished($query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeInStock($query): Builder
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeLowStock($query): Builder
    {
        return $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    public function scopeByCategory($query, $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch($query, string $term): Builder
    {
        return $query->whereRaw(
            "to_tsvector('english', name || ' ' || COALESCE(description, '') || ' ' || COALESCE(search_keywords, '')) @@ plainto_tsquery('english', ?)",
            [$term]
        );
    }

    public function scopeByType($query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // Helper methods
    public function isVariable(): bool
    {
        return $this->type === 'variable';
    }

    public function isBundle(): bool
    {
        return $this->type === 'bundle';
    }

    public function isDigital(): bool
    {
        return $this->type === 'digital';
    }

    public function isInStock(): bool
    {
        if (!$this->manage_stock) {
            return true;
        }

        return $this->getAvailableStock() > 0;
    }

    public function isLowStock(): bool
    {
        if (!$this->manage_stock) {
            return false;
        }

        return $this->getAvailableStock() <= $this->low_stock_threshold;
    }

    public function getAvailableStock(): int
    {
        if ($this->isVariable()) {
            return $this->variations()->sum('stock_quantity');
        }

        return $this->inventoryLevels()->sum('quantity_available');
    }

    public function getTotalInventoryValue(): float
    {
        return $this->inventoryLevels()
            ->sum(\DB::raw('quantity_on_hand * average_cost'));
    }

    public function getMarginPercent(): float
    {
        if ($this->cost_price <= 0) {
            return 0;
        }

        return (($this->selling_price - $this->cost_price) / $this->cost_price) * 100;
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function updateAverageRating(): void
    {
        // This would calculate rating based on reviews
        // Implementation depends on review system
    }
}