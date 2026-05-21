<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\{
    DashboardController,
    SubHeadOfAccController,
    COAController,
    ProductionController,
    PurchaseInvoiceController,
    PurchaseReturnController,
    ProductController,
    UserController,
    RoleController,
    AttributeController,
    ProductCategoryController,
    ProductionReceivingController,
    VoucherController,
    InventoryReportController,
    PurchaseReportController,
    ProductionReportController,
    AccountsReportController,
    PermissionController,
    ProductionReturnController,
    PurchaseOrderController,
    PurchaseOrderReceivingController,
    VendorController,
};

Auth::routes();

// ── Unauthorized ──────────────────────────────────────────────────────────────
Route::view('/unauthorized', 'unauthorized')->name('unauthorized');

Route::middleware(['auth'])->group(function () {

    // ── Dashboard ─────────────────────────────────────────────────────
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── User helpers ──────────────────────────────────────────────────
    Route::put('/users/{id}/change-password', [UserController::class, 'changePassword'])->name('users.changePassword');
    Route::put('/users/{id}/toggle-active',   [UserController::class, 'toggleActive'])->name('users.toggleActive');

    // ── Product helpers ───────────────────────────────────────────────
    Route::get('/products/details',                     [ProductController::class, 'details'])->name('products.receiving');
    Route::get('/products/barcode-selection',           [ProductController::class, 'barcodeSelection'])->name('products.barcode.selection');
    Route::post('/products/generate-multiple-barcodes', [ProductController::class, 'generateMultipleBarcodes'])->name('products.generateBarcodes');
    Route::get('/get-product-by-code/{barcode}',        [ProductController::class, 'getByBarcode'])->name('product.byBarcode');
    Route::get('/product/{product}/variations',         [ProductController::class, 'getVariations'])->name('product.variations');
    Route::get('/product/{product}/variations2',        [ProductController::class, 'getVariations2'])->name('product.variations2');
    Route::get('/product/{product}/productions',        [ProductionController::class, 'getProductProductions'])->name('product.productions');

    Route::get('/products/bulk-upload/template', [ProductController::class, 'bulkUploadTemplate'])->name('products.bulk-upload.template')->middleware('check.permission:products.create');
    Route::get('/products/bulk-export',          [ProductController::class, 'bulkExport'])->name('products.bulk-export')->middleware('check.permission:products.create');
    Route::post('/products/bulk-import',         [ProductController::class, 'bulkImport'])->name('products.bulk-import')->middleware('check.permission:products.create');

    // ── Purchase helpers ──────────────────────────────────────────────
    Route::get('/product/{product}/invoices', [PurchaseInvoiceController::class, 'getProductInvoices'])->name('product.invoices');

    // ── Production helpers ────────────────────────────────────────────
    Route::get('/production-summary/{id}',  [ProductionController::class, 'summary'])->name('production.summary');
    Route::get('/production-gatepass/{id}', [ProductionController::class, 'printGatepass'])->name('production.gatepass');
    Route::get('/production/raw-stock', [ProductionController::class, 'getRawStock'])->name('production.raw-stock');
    Route::get('/production/fg-stock',  [ProductionController::class, 'getFgStock'])->name('production.fg-stock');

    // ── Vouchers (single tabbed page) ─────────────────────────────────
    Route::get('vouchers', [VoucherController::class, 'index'])->middleware('check.permission:vouchers.index')->name('vouchers.all');

    // ── Common Modules ────────────────────────────────────────────────
    $modules = [
        // User Management
        'roles'       => ['controller' => RoleController::class,      'permission' => 'user_roles'],
        'permissions' => ['controller' => PermissionController::class, 'permission' => 'user_roles'],
        'users'       => ['controller' => UserController::class,       'permission' => 'users'],

        // Accounts
        'coa'     => ['controller' => COAController::class,          'permission' => 'coa'],
        'shoa'    => ['controller' => SubHeadOfAccController::class, 'permission' => 'shoa'],
        'vendors' => ['controller' => VendorController::class,       'permission' => 'vendors'],

        // Products
        'products'           => ['controller' => ProductController::class,         'permission' => 'products'],
        'product_categories' => ['controller' => ProductCategoryController::class, 'permission' => 'product_categories'],
        'attributes'         => ['controller' => AttributeController::class,       'permission' => 'attributes'],

        // Purchases
        'purchase_invoices' => ['controller' => PurchaseInvoiceController::class, 'permission' => 'purchase_invoices'],
        'purchase_return'   => ['controller' => PurchaseReturnController::class,  'permission' => 'purchase_return'],

        // Vouchers
        'vouchers' => ['controller' => VoucherController::class, 'permission' => 'vouchers'],

        // Production
        'production'           => ['controller' => ProductionController::class,          'permission' => 'production'],
        'production_receiving' => ['controller' => ProductionReceivingController::class, 'permission' => 'production_receiving'],
        'production_return'    => ['controller' => ProductionReturnController::class,    'permission' => 'production_return'],
    ];

    foreach ($modules as $uri => $config) {
        $controller = $config['controller'];
        $permission = $config['permission'];
        $param      = $uri === 'roles' ? '{role}' : '{id}';

        // ── Vouchers: special prefix routing ──────────────────────────
        if ($uri === 'vouchers') {
            Route::prefix("$uri/{type}")->group(function () use ($controller, $permission) {
                Route::get('/',           [$controller, 'index'])->middleware("check.permission:$permission.index")->name('vouchers.index');
                Route::get('/create',     [$controller, 'create'])->middleware("check.permission:$permission.create")->name('vouchers.create');
                Route::post('/',          [$controller, 'store'])->middleware("check.permission:$permission.create")->name('vouchers.store');
                Route::get('/{id}',       [$controller, 'show'])->middleware("check.permission:$permission.index")->name('vouchers.show');
                Route::get('/{id}/edit',  [$controller, 'edit'])->middleware("check.permission:$permission.edit")->name('vouchers.edit');
                Route::put('/{id}',       [$controller, 'update'])->middleware("check.permission:$permission.edit")->name('vouchers.update');
                Route::delete('/{id}',    [$controller, 'destroy'])->middleware("check.permission:$permission.delete")->name('vouchers.destroy');
                Route::get('/{id}/print', [$controller, 'print'])->middleware("check.permission:$permission.print")->name('vouchers.print');
            });
            continue;
        }

        // ── Standard CRUD ─────────────────────────────────────────────
        Route::get("$uri",              [$controller, 'index'])  ->middleware("check.permission:$permission.index") ->name("$uri.index");
        Route::get("$uri/create",       [$controller, 'create']) ->middleware("check.permission:$permission.create")->name("$uri.create");
        Route::post("$uri",             [$controller, 'store'])  ->middleware("check.permission:$permission.create")->name("$uri.store");
        Route::get("$uri/$param",       [$controller, 'show'])   ->middleware("check.permission:$permission.index") ->name("$uri.show");
        Route::get("$uri/$param/edit",  [$controller, 'edit'])   ->middleware("check.permission:$permission.edit")  ->name("$uri.edit");
        Route::put("$uri/$param",       [$controller, 'update']) ->middleware("check.permission:$permission.edit")  ->name("$uri.update");
        Route::delete("$uri/$param",    [$controller, 'destroy'])->middleware("check.permission:$permission.delete")->name("$uri.destroy");
        Route::get("$uri/$param/print", [$controller, 'print'])  ->middleware("check.permission:$permission.print") ->name("$uri.print");
    }

    // ── Purchase Orders ───────────────────────────────────────────────
    Route::prefix('purchase_orders')->name('purchase_orders.')->group(function () {

        Route::get('/',           [PurchaseOrderController::class, 'index'])  ->middleware('check.permission:purchase_orders.index') ->name('index');
        Route::get('/create',     [PurchaseOrderController::class, 'create']) ->middleware('check.permission:purchase_orders.create')->name('create');
        Route::post('/',          [PurchaseOrderController::class, 'store'])  ->middleware('check.permission:purchase_orders.create')->name('store');
        Route::get('/{id}',       [PurchaseOrderController::class, 'show'])   ->middleware('check.permission:purchase_orders.index') ->name('show');
        Route::get('/{id}/edit',  [PurchaseOrderController::class, 'edit'])   ->middleware('check.permission:purchase_orders.edit')  ->name('edit');
        Route::put('/{id}',       [PurchaseOrderController::class, 'update']) ->middleware('check.permission:purchase_orders.edit')  ->name('update');
        Route::delete('/{id}',    [PurchaseOrderController::class, 'destroy'])->middleware('check.permission:purchase_orders.delete')->name('destroy');
        Route::get('/{id}/print', [PurchaseOrderController::class, 'print'])  ->middleware('check.permission:purchase_orders.print') ->name('print');

        Route::post('/{id}/convert', [PurchaseOrderController::class, 'convertToInvoice'])
            ->middleware('check.permission:purchase_orders.edit')
            ->name('convert');

        Route::get('/ajax/po_numbers_for_product', [PurchaseOrderController::class, 'getPoNumbersForProduct'])->name('ajax.po_numbers');
        Route::get('/ajax/item_detail',            [PurchaseOrderController::class, 'getItemDetail'])->name('ajax.item_detail');
    });

    // ── Purchase Order Receivings (GRN) ───────────────────────────────
    Route::prefix('purchase_order_receivings')->name('purchase_order_receivings.')->group(function () {

        Route::get('/',                         [PurchaseOrderReceivingController::class, 'index'])  ->middleware('check.permission:purchase_invoices.index') ->name('index');
        Route::get('/create/{purchaseOrderId}', [PurchaseOrderReceivingController::class, 'create']) ->middleware('check.permission:purchase_invoices.create')->name('create');
        Route::post('/',                        [PurchaseOrderReceivingController::class, 'store'])  ->middleware('check.permission:purchase_invoices.create')->name('store');
        Route::get('/{id}/edit',                [PurchaseOrderReceivingController::class, 'edit'])   ->middleware('check.permission:purchase_invoices.edit')  ->name('edit');
        Route::put('/{id}',                     [PurchaseOrderReceivingController::class, 'update']) ->middleware('check.permission:purchase_invoices.edit')  ->name('update');
        Route::delete('/{id}',                  [PurchaseOrderReceivingController::class, 'destroy'])->middleware('check.permission:purchase_invoices.delete')->name('destroy');
        Route::get('/{id}/print',               [PurchaseOrderReceivingController::class, 'print'])  ->middleware('check.permission:purchase_invoices.print') ->name('print');
    });

    // ── Reports ───────────────────────────────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('inventory',  [InventoryReportController::class,  'inventoryReports'])->name('inventory');
        Route::get('purchase',   [PurchaseReportController::class,   'purchaseReports'])->name('purchase');
        Route::get('production', [ProductionReportController::class, 'productionReports'])->name('production');
        Route::get('accounts',   [AccountsReportController::class,   'accounts'])->name('accounts');
    });
});