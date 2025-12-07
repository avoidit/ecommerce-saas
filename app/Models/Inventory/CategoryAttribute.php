<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasUuid;

class CategoryAttribute extends Model
{
    use HasUuid;

    protected $fillable = [
        'category_id',
        'name',
        'label',
        'type',
        'is_required',
        'is_variant',
        'sort_order',
        'options',
        'validation_rules'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_variant' => 'boolean',
        'sort_order' => 'integer',
        'options' => 'array',
        'validation_rules' => 'array'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeVariant($query)
    {
        return $query->where('is_variant', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}