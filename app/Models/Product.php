<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Product extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'organization_id',
        'category_id',
        'brand_id',
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
        'created_by',
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
        'allow_backorders' => 'boolean',
        'requires_shipping' => 'boolean',
        'attributes' => 'array',
        'gallery_images' => 'array',
        'average_rating' => 'decimal:2',
        'published_at' => 'datetime',
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

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('is_primary', true)->limit(1);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getDiscountPercentageAttribute(): ?float
    {
        if ($this->msrp && $this->msrp > $this->selling_price) {
            return round((($this->msrp - $this->selling_price) / $this->msrp) * 100);
        }
        return null;
    }

    public function getIsInStockAttribute(): bool
    {
        if (!$this->manage_stock) {
            return true;
        }
        return $this->stock_quantity > 0 || $this->allow_backorders;
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->msrp && $this->msrp > $this->selling_price;
    }

    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->selling_price, 2);
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        // First try the relationship
        $primaryImage = $this->primaryImage->first();
        if ($primaryImage) {
            return $primaryImage->url;
        }
        
        // Fall back to featured_image
        return $this->featured_image;
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('manage_stock', false)
              ->orWhere('stock_quantity', '>', 0)
              ->orWhere('allow_backorders', true);
        });
    }

    public function scopePriceRange(Builder $query, $min, $max): Builder
    {
        return $query->whereBetween('selling_price', [$min, $max]);
    }

    public function scopeForOrganization(Builder $query, $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->whereRaw(
            "to_tsvector('english', name || ' ' || COALESCE(description, '') || ' ' || COALESCE(search_keywords, '')) @@ plainto_tsquery('english', ?)",
            [$term]
        );
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory(Builder $query, $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByBrand(Builder $query, $brandId): Builder
    {
        return $query->where('brand_id', $brandId);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('attributes->featured', true);
    }

    // Methods
    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    public function incrementSales(int $quantity = 1): void
    {
        $this->increment('total_sales', $quantity);
        
        if ($this->manage_stock) {
            $this->decrement('stock_quantity', $quantity);
        }
    }

    public function isLowStock(): bool
    {
        if (!$this->manage_stock) {
            return false;
        }
        return $this->stock_quantity <= $this->low_stock_threshold;
    }
}