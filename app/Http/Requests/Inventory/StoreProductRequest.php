<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('create', \App\Models\Inventory\Product::class);
    }

    public function rules(): array
    {
        $organizationId = auth()->user()->current_organization_id;

        return [
            'name' => 'required|string|max:500',
            'slug' => [
                'sometimes',
                'string',
                'max:500',
                Rule::unique('products')->where('organization_id', $organizationId)
            ],
            'short_description' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
            'sku' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('products')->where('organization_id', $organizationId)
            ],
            'barcode' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:simple,variable,bundle,digital',
            'status' => 'required|in:active,inactive,discontinued,draft',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'msrp' => 'nullable|numeric|min:0',
            'currency' => 'required|string|size:3',
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
            'published_at' => 'nullable|date',

            // Initial inventory data
            'initial_inventory' => 'nullable|array',
            'initial_inventory.*.location_id' => 'required|exists:inventory_locations,id',
            'initial_inventory.*.quantity' => 'required|integer|min:0',
            'initial_inventory.*.unit_cost' => 'nullable|numeric|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'sku.unique' => 'This SKU is already in use within your organization',
            'slug.unique' => 'This URL slug is already in use within your organization',
            'cost_price.required' => 'Cost price is required',
            'selling_price.required' => 'Selling price is required',
            'currency.size' => 'Currency must be a 3-letter ISO code (e.g., USD, EUR)',
        ];
    }
}

