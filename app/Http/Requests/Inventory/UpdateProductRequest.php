<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('update', $this->route('product'));
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $organizationId = auth()->user()->current_organization_id;

        return [
            'name' => 'sometimes|string|max:500',
            'slug' => [
                'sometimes',
                'string',
                'max:500',
                Rule::unique('products')->where('organization_id', $organizationId)->ignore($product->id)
            ],
            'short_description' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
            'sku' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('products')->where('organization_id', $organizationId)->ignore($product->id)
            ],
            'barcode' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'sometimes|in:simple,variable,bundle,digital',
            'status' => 'sometimes|in:active,inactive,discontinued,draft',
            'cost_price' => 'sometimes|numeric|min:0',
            'selling_price' => 'sometimes|numeric|min:0',
            'msrp' => 'nullable|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'track_inventory' => 'boolean',
            'manage_stock' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'allow_backorders' => 'boolean',
            'tax_class' => 'nullable|string|max:100',
            'requires_shipping' => 'boolean',
            'shipping_class' => 'nullable|string|max:100',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'search_keywords' => 'nullable|string',
            'attributes' => 'nullable|array',
            'featured_image' => 'nullable|url|max:500',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'url|max:500',
            'published_at' => 'nullable|date'
        ];
    }
}