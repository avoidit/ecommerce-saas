<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreProductRequest;
use App\Http\Requests\Inventory\UpdateProductRequest;
use App\Http\Resources\Inventory\ProductResource;
use App\Http\Resources\Inventory\ProductCollection;
use App\Models\Inventory\Product;
use App\Services\Inventory\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Product::class, 'product');
    }

    /**
     * Get category with products
     */
    public function products(Category $category, Request $request): JsonResponse
    {
        $products = $category->products()
            ->with(['inventoryLevels', 'variations'])
            ->active()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $products
        ]);
    }
}
