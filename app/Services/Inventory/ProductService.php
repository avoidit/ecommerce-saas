<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariation;
use App\Models\Inventory\Category;
use App\Exceptions\ProductException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        private InventoryService $inventoryService
    ) {}

    /**
     * Create a new product
     */
    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $data['organization_id']);
            }

            // Generate SKU if not provided
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateUniqueSku($data['organization_id'], $data['category_id'] ?? null);
            }

            $product = Product::create($data);

            // Create initial inventory levels if locations are provided
            if (!empty($data['initial_inventory'])) {
                $this->createInitialInventory($product, $data['initial_inventory']);
            }

            return $product;
        });
    }

    /**
     * Create product variations
     */
    public function createVariations(Product $product, array $variationsData): \Illuminate\Database\Eloquent\Collection
    {
        return DB::transaction(function () use ($product, $variationsData) {
            $variations = collect();

            foreach ($variationsData as $variationData) {
                // Generate SKU for variation
                if (empty($variationData['sku'])) {
                    $variationData['sku'] = $this->generateVariationSku(
                        $product->sku,
                        $variationData['variation_attributes']
                    );
                }

                $variationData['parent_product_id'] = $product->id;
                $variation = ProductVariation::create($variationData);
                $variations->push($variation);
            }

            // Update product type to variable
            $product->update(['type' => 'variable']);

            return $variations;
        });
    }

    /**
     * Bulk import products
     */
    public function bulkImport(array $productsData, string $organizationId): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        DB::transaction(function () use ($productsData, $organizationId, &$results) {
            foreach ($productsData as $index => $productData) {
                try {
                    $productData['organization_id'] = $organizationId;
                    $this->createProduct($productData);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'row' => $index + 1,
                        'error' => $e->getMessage()
                    ];
                }
            }
        });

        return $results;
    }

    /**
     * Search products with advanced filtering
     */
    public function searchProducts(array $filters, string $organizationId): \Illuminate\Database\Eloquent\Builder
    {
        $query = Product::where('organization_id', $organizationId);

        // Text search
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $category = Category::find($filters['category_id']);
            if ($category) {
                // Include all descendants of the category
                $categoryIds = $category->getDescendants()->pluck('id')->push($category->id);
                $query->whereIn('category_id', $categoryIds);
            }
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Price range
        if (!empty($filters['min_price'])) {
            $query->where('selling_price', '>=', $filters['min_price']);
        }
        if (!empty($filters['max_price'])) {
            $query->where('selling_price', '<=', $filters['max_price']);
        }

        // Stock status
        if (!empty($filters['stock_status'])) {
            switch ($filters['stock_status']) {
                case 'in_stock':
                    $query->inStock();
                    break;
                case 'low_stock':
                    $query->lowStock();
                    break;
                case 'out_of_stock':
                    $query->where('stock_quantity', 0);
                    break;
            }
        }

        // Attribute filters
        if (!empty($filters['attributes'])) {
            foreach ($filters['attributes'] as $key => $value) {
                $query->whereJsonContains('attributes->' . $key, $value);
            }
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query;
    }

    /**
     * Generate unique slug
     */
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

    /**
     * Generate unique SKU
     */
    private function generateUniqueSku(string $organizationId, ?string $categoryId = null): string
    {
        $prefix = 'PRD';
        
        if ($categoryId) {
            $category = Category::find($categoryId);
            if ($category) {
                $prefix = strtoupper(substr($category->name, 0, 3));
            }
        }

        do {
            $sku = $prefix . '-' . strtoupper(Str::random(8));
        } while (Product::where('organization_id', $organizationId)->where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * Generate variation SKU
     */
    private function generateVariationSku(string $parentSku, array $attributes): string
    {
        $suffix = '';
        foreach ($attributes as $key => $value) {
            $suffix .= '-' . strtoupper(substr($key, 0, 1)) . strtoupper(substr($value, 0, 2));
        }

        return $parentSku . $suffix;
    }

    /**
     * Create initial inventory
     */
    private function createInitialInventory(Product $product, array $inventoryData): void
    {
        foreach ($inventoryData as $locationInventory) {
            if ($locationInventory['quantity'] > 0) {
                $this->inventoryService->adjustStock(
                    $product->id,
                    $locationInventory['location_id'],
                    $locationInventory['quantity'],
                    'Initial stock',
                    null,
                    $locationInventory['unit_cost'] ?? $product->cost_price
                );
            }
        }
    }
}