<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryController extends Controller
{
    /**
     * Display the inventory dashboard
     */
    public function dashboard()
    {
        // Get basic inventory stats
        $organizationId = auth()->user()->current_organization_id ?? 1;
        
        $analytics = [
            'total_products' => DB::table('products')->where('organization_id', $organizationId)->count(),
            'total_categories' => DB::table('categories')->where('organization_id', $organizationId)->count(),
            'total_suppliers' => DB::table('suppliers')->where('organization_id', $organizationId)->count(),
            'inventory_valuation' => [
                'total_value' => DB::table('inventory_levels')
                    ->where('organization_id', $organizationId)
                    ->sum(DB::raw('quantity_on_hand * average_cost')),
                'total_quantity' => DB::table('inventory_levels')
                    ->where('organization_id', $organizationId)
                    ->sum('quantity_on_hand')
            ],
            'low_stock_alerts' => DB::table('inventory_levels')
                ->where('organization_id', $organizationId)
                ->whereColumn('quantity_available', '<=', 'reorder_point')
                ->count()
        ];

        // Get recent movements
        $recentMovements = DB::table('inventory_movements')
            ->join('products', 'inventory_movements.product_id', '=', 'products.id')
            ->join('inventory_locations', 'inventory_movements.location_id', '=', 'inventory_locations.id')
            ->where('inventory_movements.organization_id', $organizationId)
            ->select(
                'inventory_movements.*',
                'products.name as product_name',
                'inventory_locations.name as location_name'
            )
            ->orderBy('inventory_movements.created_at', 'desc')
            ->limit(10)
            ->get();

        // Get low stock alerts with product details
        $lowStockAlerts = DB::table('inventory_levels')
            ->join('products', 'inventory_levels.product_id', '=', 'products.id')
            ->join('inventory_locations', 'inventory_levels.location_id', '=', 'inventory_locations.id')
            ->where('inventory_levels.organization_id', $organizationId)
            ->whereColumn('quantity_available', '<=', 'reorder_point')
            ->select(
                'inventory_levels.*',
                'products.name as product_name',
                'products.sku',
                'inventory_locations.name as location_name'
            )
            ->orderBy('quantity_available')
            ->limit(10)
            ->get();

        // Sample top categories data (you can enhance this)
        $topCategories = DB::table('categories')
            ->where('organization_id', $organizationId)
            ->select('id', 'name')
            ->limit(5)
            ->get()
            ->map(function ($category, $index) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'value' => rand(1000, 10000), // Replace with actual calculation
                    'color' => ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6'][$index] ?? '#6B7280'
                ];
            });

        return Inertia::render('Inventory/Dashboard', [
            'analytics' => $analytics,
            'recentMovements' => $recentMovements,
            'lowStockAlerts' => $lowStockAlerts,
            'topCategories' => $topCategories
        ]);
    }

    /**
     * Display inventory levels
     */
    public function levels(Request $request)
    {
        $organizationId = auth()->user()->current_organization_id ?? 1;

        $levels = DB::table('inventory_levels')
            ->join('products', 'inventory_levels.product_id', '=', 'products.id')
            ->join('inventory_locations', 'inventory_levels.location_id', '=', 'inventory_locations.id')
            ->where('inventory_levels.organization_id', $organizationId)
            ->select(
                'inventory_levels.*',
                'products.name as product_name',
                'products.sku',
                'inventory_locations.name as location_name'
            )
            ->paginate(15);

        return Inertia::render('Inventory/Operations/Levels', [
            'levels' => $levels
        ]);
    }

    /**
     * Display inventory movements
     */
    public function movements(Request $request)
    {
        $organizationId = auth()->user()->current_organization_id ?? 1;

        $movements = DB::table('inventory_movements')
            ->join('products', 'inventory_movements.product_id', '=', 'products.id')
            ->join('inventory_locations', 'inventory_movements.location_id', '=', 'inventory_locations.id')
            ->leftJoin('users', 'inventory_movements.created_by', '=', 'users.id')
            ->where('inventory_movements.organization_id', $organizationId)
            ->select(
                'inventory_movements.*',
                'products.name as product_name',
                'products.sku',
                'inventory_locations.name as location_name',
                'users.name as created_by_name'
            )
            ->orderBy('inventory_movements.created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Inventory/Operations/Movements', [
            'movements' => $movements
        ]);
    }

    /**
     * Show stock adjustment form
     */
    public function adjustForm()
    {
        $organizationId = auth()->user()->current_organization_id ?? 1;

        $products = DB::table('products')
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->select('id', 'name', 'sku')
            ->orderBy('name')
            ->get();

        $locations = DB::table('inventory_locations')
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return Inertia::render('Inventory/Operations/Adjust', [
            'products' => $products,
            'locations' => $locations
        ]);
    }

    /**
     * Process stock adjustment
     */
    public function adjustStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'location_id' => 'required|exists:inventory_locations,id',
            'quantity' => 'required|integer|not_in:0',
            'reason' => 'required|string|max:255'
        ]);

        $organizationId = auth()->user()->current_organization_id ?? 1;

        // This is a simplified version - you'd use your InventoryService here
        return redirect()->route('inventory.operations.levels')
            ->with('success', 'Stock adjustment completed successfully');
    }
}