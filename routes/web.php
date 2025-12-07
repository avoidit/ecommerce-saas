<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Import Inventory Controllers
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\CategoryController;
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\Inventory\SupplierController;
use App\Http\Controllers\Inventory\LocationController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    
    // Main Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // ================================
    // INVENTORY MANAGEMENT ROUTES
    // ================================
    
    Route::prefix('inventory')->name('inventory.')->group(function () {
        
        // Inventory Dashboard
        Route::get('/', [InventoryController::class, 'dashboard'])->name('dashboard');
        
        // Product Management
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('index');
            Route::get('/create', [ProductController::class, 'create'])->name('create');
            Route::post('/', [ProductController::class, 'store'])->name('store');
            Route::get('/{product}', [ProductController::class, 'show'])->name('show');
            Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
            Route::put('/{product}', [ProductController::class, 'update'])->name('update');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
            
            // Additional product actions
            Route::post('/{product}/duplicate', [ProductController::class, 'duplicate'])->name('duplicate');
            Route::get('/{product}/analytics', [ProductController::class, 'analytics'])->name('analytics');
            Route::post('/bulk-import', [ProductController::class, 'bulkImport'])->name('bulk-import');
        });
        
        // Category Management
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::get('/create', [CategoryController::class, 'create'])->name('create');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::get('/{category}', [CategoryController::class, 'show'])->name('show');
            Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
            Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
            
            // Category specific actions
            Route::post('/{category}/move', [CategoryController::class, 'move'])->name('move');
            Route::get('/{category}/products', [CategoryController::class, 'products'])->name('products');
        });
        
        // Supplier Management
        Route::prefix('suppliers')->name('suppliers.')->group(function () {
            Route::get('/', [SupplierController::class, 'index'])->name('index');
            Route::get('/create', [SupplierController::class, 'create'])->name('create');
            Route::post('/', [SupplierController::class, 'store'])->name('store');
            Route::get('/{supplier}', [SupplierController::class, 'show'])->name('show');
            Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->name('edit');
            Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
            Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
            
            // Supplier analytics
            Route::get('/{supplier}/performance', [SupplierController::class, 'performance'])->name('performance');
        });
        
        // Inventory Location Management
        Route::prefix('locations')->name('locations.')->group(function () {
            Route::get('/', [LocationController::class, 'index'])->name('index');
            Route::get('/create', [LocationController::class, 'create'])->name('create');
            Route::post('/', [LocationController::class, 'store'])->name('store');
            Route::get('/{location}', [LocationController::class, 'show'])->name('show');
            Route::get('/{location}/edit', [LocationController::class, 'edit'])->name('edit');
            Route::put('/{location}', [LocationController::class, 'update'])->name('update');
            Route::delete('/{location}', [LocationController::class, 'destroy'])->name('destroy');
            
            // Location analytics
            Route::get('/{location}/analytics', [LocationController::class, 'analytics'])->name('analytics');
        });
        
        // Inventory Operations
        Route::prefix('operations')->name('operations.')->group(function () {
            // Stock Management
            Route::get('/adjust', [InventoryController::class, 'adjustForm'])->name('adjust.form');
            Route::post('/adjust', [InventoryController::class, 'adjustStock'])->name('adjust.store');
            
            Route::get('/transfer', [InventoryController::class, 'transferForm'])->name('transfer.form');
            Route::post('/transfer', [InventoryController::class, 'transferStock'])->name('transfer.store');
            
            Route::get('/reserve', [InventoryController::class, 'reserveForm'])->name('reserve.form');
            Route::post('/reserve', [InventoryController::class, 'reserveStock'])->name('reserve.store');
            
            // Inventory Reports
            Route::get('/levels', [InventoryController::class, 'levels'])->name('levels');
            Route::get('/movements', [InventoryController::class, 'movements'])->name('movements');
            Route::get('/valuation', [InventoryController::class, 'valuation'])->name('valuation');
            Route::get('/low-stock', [InventoryController::class, 'lowStockAlerts'])->name('low-stock');
        });
        
        // Inventory Analytics & Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/analytics', [InventoryController::class, 'analytics'])->name('analytics');
            Route::get('/valuation', [InventoryController::class, 'valuationReport'])->name('valuation');
            Route::get('/movement-summary', [InventoryController::class, 'movementSummary'])->name('movement-summary');
            Route::get('/low-stock-report', [InventoryController::class, 'lowStockReport'])->name('low-stock-report');
            Route::get('/abc-analysis', [InventoryController::class, 'abcAnalysis'])->name('abc-analysis');
        });
    });
});

// ================================
// API ROUTES (for AJAX/Vue.js calls)
// ================================

Route::middleware(['auth:sanctum'])->prefix('api/v1')->name('api.')->group(function () {
    
    // Product API Routes
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'apiIndex'])->name('index');
        Route::post('/', [ProductController::class, 'apiStore'])->name('store');
        Route::get('/{product}', [ProductController::class, 'apiShow'])->name('show');
        Route::put('/{product}', [ProductController::class, 'apiUpdate'])->name('update');
        Route::delete('/{product}', [ProductController::class, 'apiDestroy'])->name('destroy');
        Route::post('/{product}/duplicate', [ProductController::class, 'apiDuplicate'])->name('duplicate');
        Route::post('/bulk-import', [ProductController::class, 'apiBulkImport'])->name('bulk-import');
    });
    
    // Category API Routes
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'apiIndex'])->name('index');
        Route::post('/', [CategoryController::class, 'apiStore'])->name('store');
        Route::get('/{category}', [CategoryController::class, 'apiShow'])->name('show');
        Route::put('/{category}', [CategoryController::class, 'apiUpdate'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'apiDestroy'])->name('destroy');
        Route::post('/{category}/move', [CategoryController::class, 'apiMove'])->name('move');
    });
    
    // Supplier API Routes
    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/', [SupplierController::class, 'apiIndex'])->name('index');
        Route::post('/', [SupplierController::class, 'apiStore'])->name('store');
        Route::get('/{supplier}', [SupplierController::class, 'apiShow'])->name('show');
        Route::put('/{supplier}', [SupplierController::class, 'apiUpdate'])->name('update');
        Route::delete('/{supplier}', [SupplierController::class, 'apiDestroy'])->name('destroy');
    });
    
    // Inventory API Routes
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/levels', [InventoryController::class, 'apiLevels'])->name('levels');
        Route::get('/movements', [InventoryController::class, 'apiMovements'])->name('movements');
        Route::get('/analytics', [InventoryController::class, 'apiAnalytics'])->name('analytics');
        Route::get('/low-stock-alerts', [InventoryController::class, 'apiLowStockAlerts'])->name('low-stock-alerts');
        
        Route::post('/adjust-stock', [InventoryController::class, 'apiAdjustStock'])->name('adjust-stock');
        Route::post('/transfer-stock', [InventoryController::class, 'apiTransferStock'])->name('transfer-stock');
        Route::post('/reserve-stock', [InventoryController::class, 'apiReserveStock'])->name('reserve-stock');
        Route::post('/release-stock', [InventoryController::class, 'apiReleaseStock'])->name('release-stock');
    });
    
    // Location API Routes
    Route::prefix('locations')->name('locations.')->group(function () {
        Route::get('/', [LocationController::class, 'apiIndex'])->name('index');
        Route::post('/', [LocationController::class, 'apiStore'])->name('store');
        Route::get('/{location}', [LocationController::class, 'apiShow'])->name('show');
        Route::put('/{location}', [LocationController::class, 'apiUpdate'])->name('update');
        Route::delete('/{location}', [LocationController::class, 'apiDestroy'])->name('destroy');
    });
});