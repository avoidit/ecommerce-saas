<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            
            // Pricing
            'cost_price' => $this->cost_price,
            'selling_price' => $this->selling_price,
            'msrp' => $this->msrp,
            'currency' => $this->currency,
            'margin_percent' => $this->getMarginPercent(),
            
            // Physical attributes
            'weight' => $this->weight,
            'dimensions' => [
                'length' => $this->length,
                'width' => $this->width,
                'height' => $this->height
            ],
            
            // Inventory
            'track_inventory' => $this->track_inventory,
            'manage_stock' => $this->manage_stock,
            'stock_quantity' => $this->stock_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'allow_backorders' => $this->allow_backorders,
            'available_stock' => $this->getAvailableStock(),
            'is_in_stock' => $this->isInStock(),
            'is_low_stock' => $this->isLowStock(),
            
            // SEO & Marketing
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'search_keywords' => $this->search_keywords,
            
            // Media
            'featured_image' => $this->featured_image,
            'gallery_images' => $this->gallery_images,
            
            // Custom attributes
            'attributes' => $this->attributes,
            
            // Analytics
            'average_rating' => $this->average_rating,
            'review_count' => $this->review_count,
            'total_sales' => $this->total_sales,
            'view_count' => $this->view_count,
            
            // Relationships
            'category' => new CategoryResource($this->whenLoaded('category')),
            'variations' => ProductVariationResource::collection($this->whenLoaded('variations')),
            'inventory_levels' => InventoryLevelResource::collection($this->whenLoaded('inventoryLevels')),
            'supplier_products' => SupplierProductResource::collection($this->whenLoaded('supplierProducts')),
            'bundle_components' => ProductBundleResource::collection($this->whenLoaded('bundleComponents')),
            'creator' => $this->whenLoaded('creator', fn() => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email
            ]),
            
            // Timestamps
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString()
        ];
    }
}
