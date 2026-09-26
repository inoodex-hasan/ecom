<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('admin.dashboard');
    }

    return Inertia::render('Auth/Login', [
        'canResetPassword' => Route::has('password.request'),
        'status' => session('status'),
    ]);
});

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth'])->name('dashboard');

// Admin Protected Routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });

    // Analytics Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Products Management
    Route::get('/products', [ProductController::class, 'index'])->name('products.index')->middleware('can:products.view');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export')->middleware('can:products.view');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store')->middleware('can:products.create');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update')->middleware('can:products.edit');
    Route::patch('/products/{product}/quick-update', [ProductController::class, 'quickUpdate'])->name('products.quick-update')->middleware('can:products.edit');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy')->middleware('can:products.delete');

    // Orders Management
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index')->middleware('can:orders.view');
    Route::get('/orders/export', [OrderController::class, 'export'])->name('orders.export')->middleware('can:orders.view');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show')->middleware('can:orders.view');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status')->middleware('can:orders.update_status');
    Route::patch('/orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])->name('orders.update-payment-status')->middleware('can:orders.edit');
    Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice')->middleware('can:orders.invoice');

    // Categories Management
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index')->middleware('can:categories.view');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store')->middleware('can:categories.create');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update')->middleware('can:categories.edit');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy')->middleware('can:categories.delete');

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
