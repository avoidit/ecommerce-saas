<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index()
    {
        return Inertia::render('Inventory/Products/Index', [
            'products' => [
                'data' => [],
                'links' => [],
                'total' => 0,
                'meta' => [
                    'in_stock_count' => 0,
                    'low_stock_count' => 0,
                    'out_of_stock_count' => 0
                ]
            ],
            'categories' => [],
            'filters' => []
        ]);
    }

    public function create()
    {
        return Inertia::render('Inventory/Products/Create', [
            'categories' => [],
            'locations' => []
        ]);
    }

    public function store(Request $request)
    {
        return redirect()->route('inventory.products.index')->with('success', 'Product created successfully!');
    }

    public function show(Product $product)
    {
        return Inertia::render('Inventory/Products/Show', [
            'product' => $product
        ]);
    }

    public function edit(Product $product)
    {
        return Inertia::render('Inventory/Products/Edit', [
            'product' => $product,
            'categories' => [],
            'locations' => []
        ]);
    }

    public function update(Request $request, Product $product)
    {
        return redirect()->route('inventory.products.index')->with('success', 'Product updated successfully!');
    }

    public function destroy(Product $product)
    {
        return redirect()->route('inventory.products.index')->with('success', 'Product deleted successfully!');
    }
}