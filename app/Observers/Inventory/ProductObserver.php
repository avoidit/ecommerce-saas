<?php

namespace App\Observers\Inventory;

use App\Models\Inventory\Product;
use App\Jobs\Inventory\UpdateProductSearchIndex;
use Illuminate\Support\Str;

class ProductObserver
{
    public function creating(Product $product): void
    {
        // Generate slug if not provided
        if (empty($product->slug)) {
            $product->slug = $this->generateUniqueSlug($product->name, $product->organization_id);
        }

        // Generate SKU if not provided
        if (empty($product->sku)) {
            $product->sku = $this->generateUniqueSku($product->organization_id);
        }
    }

    public function created(Product $product): void
    {
        // Dispatch search index update
        UpdateProductSearchIndex::dispatch($product);
    }

    public function updated(Product $product): void
    {
        // Update search index if searchable fields changed
        if ($product->wasChanged(['name', 'description', 'search_keywords', 'status'])) {
            UpdateProductSearchIndex::dispatch($product);
        }
    }

    public function deleted(Product $product): void
    {
        // Remove from search index
        UpdateProductSearchIndex::dispatch($product, 'delete');
    }

    private function generateUniqueSlug(string $name, string $organizationId): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Product::where('organization_id', $organizationId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function generateUniqueSku(string $organizationId): string
    {
        do {
            $sku = 'PRD-' . strtoupper(Str::random(8));
        } while (Product::where('organization_id', $organizationId)->where('sku', $sku)->exists());

        return $sku;
    }
}
