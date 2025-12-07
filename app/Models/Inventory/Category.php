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

class Category extends Model
{
    use HasUuid, BelongsToOrganization, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
        'path',
        'lft',
        'rgt',
        'depth',
        'sort_order',
        'is_active',
        'seo_title',
        'seo_description',
        'meta_data',
        'image_url',
        'created_by'
    ];

    protected $casts = [
        'meta_data' => 'array',
        'is_active' => 'boolean',
        'depth' => 'integer',
        'lft' => 'integer',
        'rgt' => 'integer',
        'sort_order' => 'integer'
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

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(CategoryAttribute::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->where('depth', 0);
    }

    // Helper methods for nested set operations
    public function getDescendants()
    {
        return static::where('organization_id', $this->organization_id)
            ->where('lft', '>', $this->lft)
            ->where('rgt', '<', $this->rgt)
            ->orderBy('lft');
    }

    public function getAncestors()
    {
        return static::where('organization_id', $this->organization_id)
            ->where('lft', '<', $this->lft)
            ->where('rgt', '>', $this->rgt)
            ->orderBy('lft');
    }

    public function getSiblings()
    {
        return static::where('organization_id', $this->organization_id)
            ->where('depth', $this->depth)
            ->where('id', '!=', $this->id);
    }

    public function isLeaf(): bool
    {
        return ($this->rgt - $this->lft) === 1;
    }

    public function isRoot(): bool
    {
        return $this->depth === 0;
    }

    // Get breadcrumb trail
    public function getBreadcrumb(): array
    {
        return $this->getAncestors()
            ->get()
            ->pluck('name', 'slug')
            ->toArray();
    }
}