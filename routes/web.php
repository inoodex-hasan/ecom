<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CourierController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FlashSaleController;
use App\Http\Controllers\Admin\FraudController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        if (request()->user()->can('dashboard.view')) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('dashboard');
    }

    return Inertia::render('Auth/Login', [
        'canResetPassword' => Route::has('password.request'),
        'status' => session('status'),
    ]);
});

Route::get('/dashboard', function () {
    if (request()->user()->can('dashboard.view')) {
        return redirect()->route('admin.dashboard');
    }

    return Inertia::render('Dashboard');
})->middleware(['auth'])->name('dashboard');

// Admin Protected Routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });

    // Analytics Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('can:dashboard.view');

    // Products Management
    Route::get('/products', [ProductController::class, 'index'])->name('products.index')->middleware('can:products.view');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export')->middleware('can:products.view');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store')->middleware('can:products.create');
    Route::post('/products/upload-image', [ProductController::class, 'uploadImage'])->name('products.upload-image')->middleware('can:products.create');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update')->middleware('can:products.edit');
    Route::patch('/products/{product}/quick-update', [ProductController::class, 'quickUpdate'])->name('products.quick-update')->middleware('can:products.edit');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy')->middleware('can:products.delete');

    // Inventory & Stock Management
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index')->middleware('can:inventory.view');
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust')->middleware('can:inventory.adjust');
    Route::get('/inventory/export', [InventoryController::class, 'export'])->name('inventory.export')->middleware('can:inventory.view');

    // Orders Management
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index')->middleware('can:orders.view');
    Route::get('/orders/export', [OrderController::class, 'export'])->name('orders.export')->middleware('can:orders.view');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show')->middleware('can:orders.view');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status')->middleware('can:orders.update_status');
    Route::patch('/orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])->name('orders.update-payment-status')->middleware('can:orders.edit');
    Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice')->middleware('can:orders.invoice');
    Route::post('/orders/{order}/courier-dispatch', [CourierController::class, 'dispatchOrder'])->name('orders.courier-dispatch')->middleware('can:orders.edit');
    Route::post('/orders/{order}/courier-sync', [CourierController::class, 'syncStatus'])->name('orders.courier-sync')->middleware('can:orders.edit');
    Route::get('/orders/{order}/courier-label', [CourierController::class, 'printLabel'])->name('orders.courier-label')->middleware('can:orders.view');

    // Categories Management
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index')->middleware('can:categories.view');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store')->middleware('can:categories.create');
    Route::post('/categories/upload-image', [CategoryController::class, 'uploadImage'])->name('categories.upload-image')->middleware('can:categories.create');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update')->middleware('can:categories.edit');
    Route::patch('/categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status')->middleware('can:categories.edit');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy')->middleware('can:categories.delete');

    // Banners & Sliders Management
    Route::get('/banners', [BannerController::class, 'index'])->name('banners.index')->middleware('can:banners.view');
    Route::post('/banners', [BannerController::class, 'store'])->name('banners.store')->middleware('can:banners.manage');
    Route::post('/banners/upload-image', [BannerController::class, 'uploadImage'])->name('banners.upload-image')->middleware('can:banners.manage');
    Route::put('/banners/{banner}', [BannerController::class, 'update'])->name('banners.update')->middleware('can:banners.manage');
    Route::patch('/banners/{banner}/toggle-status', [BannerController::class, 'toggleStatus'])->name('banners.toggle-status')->middleware('can:banners.manage');
    Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy')->middleware('can:banners.manage');

    // Marketing Campaigns & Promotional Events Management
    Route::get('/campaigns', [FlashSaleController::class, 'index'])->name('campaigns.index')->middleware('can:promotions.view');
    Route::post('/campaigns', [FlashSaleController::class, 'store'])->name('campaigns.store')->middleware('can:promotions.manage');
    Route::post('/campaigns/upload-image', [FlashSaleController::class, 'uploadImage'])->name('campaigns.upload-image')->middleware('can:promotions.manage');
    Route::put('/campaigns/{flashSale}', [FlashSaleController::class, 'update'])->name('campaigns.update')->middleware('can:promotions.manage');
    Route::patch('/campaigns/{flashSale}/toggle-status', [FlashSaleController::class, 'toggleStatus'])->name('campaigns.toggle-status')->middleware('can:promotions.manage');
    Route::delete('/campaigns/{flashSale}', [FlashSaleController::class, 'destroy'])->name('campaigns.destroy')->middleware('can:promotions.manage');

    // Legacy flash-sales redirects & aliases
    Route::get('/flash-sales', fn () => redirect()->route('admin.campaigns.index'))->name('flash-sales.index');
    Route::post('/flash-sales', [FlashSaleController::class, 'store'])->name('flash-sales.store')->middleware('can:promotions.manage');
    Route::post('/flash-sales/upload-image', [FlashSaleController::class, 'uploadImage'])->name('flash-sales.upload-image')->middleware('can:promotions.manage');
    Route::put('/flash-sales/{flashSale}', [FlashSaleController::class, 'update'])->name('flash-sales.update')->middleware('can:promotions.manage');
    Route::patch('/flash-sales/{flashSale}/toggle-status', [FlashSaleController::class, 'toggleStatus'])->name('flash-sales.toggle-status')->middleware('can:promotions.manage');
    Route::delete('/flash-sales/{flashSale}', [FlashSaleController::class, 'destroy'])->name('flash-sales.destroy')->middleware('can:promotions.manage');

    // Coupons & Promo Codes Engine
    Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index')->middleware('can:coupons.view');
    Route::post('/coupons', [CouponController::class, 'store'])->name('coupons.store')->middleware('can:coupons.manage');
    Route::put('/coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update')->middleware('can:coupons.manage');
    Route::patch('/coupons/{coupon}/toggle-status', [CouponController::class, 'toggleStatus'])->name('coupons.toggle-status')->middleware('can:coupons.manage');
    Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy')->middleware('can:coupons.manage');
    Route::post('/coupons/validate', [CouponController::class, 'validateApi'])->name('coupons.validate')->middleware(['can:coupons.view', 'throttle:60,1']);

    // Customer Reviews & Moderation Desk
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index')->middleware('can:reviews.view');
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store')->middleware('can:reviews.manage');
    Route::patch('/reviews/{review}/status', [ReviewController::class, 'updateStatus'])->name('reviews.update-status')->middleware('can:reviews.manage');
    Route::post('/reviews/{review}/reply', [ReviewController::class, 'reply'])->name('reviews.reply')->middleware('can:reviews.manage');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy')->middleware('can:reviews.manage');

    // Blog & Editorial Articles Management
    Route::get('/blogs', [BlogController::class, 'index'])->name('blogs.index')->middleware('can:blogs.view');
    Route::post('/blogs', [BlogController::class, 'store'])->name('blogs.store')->middleware('can:blogs.manage');
    Route::post('/blogs/upload-cover', [BlogController::class, 'uploadCover'])->name('blogs.upload-cover')->middleware('can:blogs.manage');
    Route::put('/blogs/{blogPost}', [BlogController::class, 'update'])->name('blogs.update')->middleware('can:blogs.manage');
    Route::patch('/blogs/{blogPost}/toggle-status', [BlogController::class, 'toggleStatus'])->name('blogs.toggle-status')->middleware('can:blogs.manage');
    Route::delete('/blogs/{blogPost}', [BlogController::class, 'destroy'])->name('blogs.destroy')->middleware('can:blogs.manage');

    // Customers Management
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index')->middleware('can:customers.view');
    Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export')->middleware('can:customers.view');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show')->middleware('can:customers.view');
    Route::patch('/customers/{customer}/status', [CustomerController::class, 'updateStatus'])->name('customers.update-status')->middleware('can:customers.edit');

    // Staff & Roles Management (RBAC)
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index')->middleware('can:staff.view');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create')->middleware('can:staff.manage');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store')->middleware('can:staff.manage');
    Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit')->middleware('can:staff.manage');
    Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update')->middleware('can:staff.manage');
    Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy')->middleware('can:staff.manage');
    Route::post('/roles/{role}/permissions', [StaffController::class, 'updateRolePermissions'])->name('roles.update-permissions')->middleware('can:staff.manage');
    Route::post('/roles/bulk-permissions', [StaffController::class, 'bulkUpdateRolePermissions'])->name('roles.bulk-update')->middleware('can:staff.manage');

    // Fraud Detection & Risk Shield (Bangladeshi E-commerce)
    Route::get('/fraud', [FraudController::class, 'index'])->name('fraud.index')->middleware('can:fraud.view');
    Route::post('/fraud/lookup', [FraudController::class, 'lookup'])->name('fraud.lookup')->middleware('can:fraud.view');
    Route::post('/fraud/orders/{order}/recheck', [FraudController::class, 'recheck'])->name('fraud.recheck')->middleware('can:fraud.manage');
    Route::post('/fraud/orders/{order}/verify', [FraudController::class, 'verify'])->name('fraud.verify')->middleware('can:fraud.manage');
    Route::post('/fraud/orders/{order}/request-advance', [FraudController::class, 'requestAdvance'])->name('fraud.request-advance')->middleware('can:fraud.manage');
    Route::post('/fraud/orders/{order}/confirm-advance', [FraudController::class, 'confirmAdvance'])->name('fraud.confirm-advance')->middleware('can:fraud.manage');
    Route::post('/fraud/orders/{order}/block', [FraudController::class, 'blockOrder'])->name('fraud.block')->middleware('can:fraud.manage');
    Route::post('/fraud/blacklist', [FraudController::class, 'storeBlacklist'])->name('fraud.blacklist.store')->middleware('can:fraud.manage');
    Route::delete('/fraud/blacklist/{blacklist}', [FraudController::class, 'destroyBlacklist'])->name('fraud.blacklist.destroy')->middleware('can:fraud.manage');
    Route::post('/fraud/settings', [FraudController::class, 'updateSettings'])->name('fraud.settings.update')->middleware('can:fraud.manage');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index')->middleware('can:settings.view');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update')->middleware('can:settings.edit');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
