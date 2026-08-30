<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StorefrontApiController;
use App\Http\Controllers\AdminApiController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. API Route Group
Route::get('/clear-cache', function () {
    try {
        @\Illuminate\Support\Facades\Artisan::call('config:clear');
        @\Illuminate\Support\Facades\Artisan::call('cache:clear');
        @\Illuminate\Support\Facades\Artisan::call('route:clear');
        $files = glob(storage_path('framework/views/*.php'));
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        return response("SUCCESS: All configuration, application, Blade view, and route caches cleared!");
    } catch (\Exception $e) {
        return response("Cache clear note: " . $e->getMessage());
    }
});

Route::prefix('api')->group(function () {
    Route::get('/storefront', [StorefrontApiController::class, 'index']);
    Route::get('/pricelist/download-pdf', [StorefrontApiController::class, 'downloadPricelistPdf']);
    Route::get('/pricelist/generate-puppeteer-pdf', [AdminApiController::class, 'generatePuppeteerPdf']);
    Route::get('/track', [StorefrontApiController::class, 'track']);
    Route::get('/checkout/success/{order_number}', [StorefrontApiController::class, 'successDetails']);
    Route::post('/checkout', [CheckoutController::class, 'store']);
    Route::get('/test-email', function (\Illuminate\Http\Request $request) {
        try {
            $orderId = $request->query('order_id');
            $order = $orderId ? \App\Models\Order::find($orderId) : \App\Models\Order::orderBy('id', 'desc')->first();
            if (!$order) {
                return response("No orders found to test with.", 404)->header('Content-Type', 'text/plain');
            }
            
            $adminEmail = \App\Models\Setting::get('store_email', config('mail.from.address'));
            $output = "Starting email test for order ID {$order->id} (Number: {$order->order_number})...\n";
            $output .= "SMTP Host: " . config('mail.mailers.smtp.host') . "\n";
            $output .= "SMTP Port: " . config('mail.mailers.smtp.port') . "\n";
            $output .= "SMTP Username: " . config('mail.mailers.smtp.username') . "\n";
            $output .= "Admin Email: {$adminEmail}\n";
            
            if (!empty($adminEmail)) {
                $output .= "Sending Admin Invoice Mail...\n";
                \Illuminate\Support\Facades\Mail::to($adminEmail)->send(new \App\Mail\AdminInvoiceMail($order));
                $output .= "SUCCESS: Admin email sent!\n";
            }
            
            if (!empty($order->email)) {
                $output .= "Sending Customer Order Mail to {$order->email}...\n";
                \Illuminate\Support\Facades\Mail::to($order->email)->send(new \App\Mail\CustomerOrderMail($order));
                $output .= "SUCCESS: Customer email sent!\n";
            } else {
                $output .= "Skipped Customer Mail (No email provided for this order).\n";
            }
            
            return response($output)->header('Content-Type', 'text/plain');
        } catch (\Exception $e) {
            return response("ERROR ENCOUNTERED:\n" . $e->getMessage() . "\n\nTrace:\n" . $e->getTraceAsString(), 500)->header('Content-Type', 'text/plain');
        }
    });
    Route::get('/checkout/invoice/{order_number}', [CheckoutController::class, 'downloadInvoice'])->name('checkout.invoice');
    Route::get('/view-logs', function () {
        try {
            $logFile = storage_path('logs/email_background.log');
            $laravelLog = storage_path('logs/laravel.log');
            
            $output = "=== ACTIVE MAIL CONFIG ===\n";
            $output .= "Default: " . config('mail.default') . "\n";
            $output .= "Host: " . config('mail.mailers.smtp.host') . "\n";
            $output .= "Port: " . config('mail.mailers.smtp.port') . "\n";
            $output .= "Encryption: " . config('mail.mailers.smtp.encryption') . "\n";
            $output .= "Username: " . config('mail.mailers.smtp.username') . "\n";
            $output .= "From Address: " . config('mail.from.address') . "\n\n";
            
            $output = $output . "=== EMAIL BACKGROUND LOG (storage/logs/email_background.log) ===\n";
            if (file_exists($logFile)) {
                $output .= file_get_contents($logFile) . "\n";
            } else {
                $output .= "File does not exist.\n";
            }
            
            $output .= "\n=== LARAVEL LOG LAST 50 LINES (storage/logs/laravel.log) ===\n";
            if (file_exists($laravelLog)) {
                $lines = file($laravelLog);
                $lastLines = array_slice($lines, -50);
                $output .= implode("", $lastLines) . "\n";
            } else {
                $output .= "File does not exist.\n";
            }
            
            return response($output)->header('Content-Type', 'text/plain');
        } catch (\Exception $e) {
            return response("Error reading logs: " . $e->getMessage(), 500)->header('Content-Type', 'text/plain');
        }
    });
});

Route::get('/api/attach-all-images', function () {
    $imgDir = public_path('img');
    if (!file_exists($imgDir)) {
        return response("Directory backend/public/img does not exist.", 404);
    }

    $files = scandir($imgDir);
    $imageMap = [];

    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || is_dir($imgDir . '/' . $file)) continue;
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp', 'gif'])) continue;

        $baseName = pathinfo($file, PATHINFO_FILENAME);
        $cleanKey = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', str_replace('colour', 'color', $baseName)));
        $imageMap[$cleanKey] = 'img/' . $file;
    }

    $products = \App\Models\Product::all();
    $matched = 0;

    foreach ($products as $product) {
        $prodCleanKey = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', str_replace('colour', 'color', $product->name)));
        
        $matchedPath = null;
        if (isset($imageMap[$prodCleanKey])) {
            $matchedPath = $imageMap[$prodCleanKey];
        } else {
            foreach ($imageMap as $key => $path) {
                if ($key === $prodCleanKey || str_contains($prodCleanKey, $key) || str_contains($key, $prodCleanKey)) {
                    $matchedPath = $path;
                    break;
                }
            }
        }

        if ($matchedPath) {
            $product->image = $matchedPath;
            $product->save();
            $matched++;
        }
    }

    if (file_exists($imgDir . '/slider img/20631.jpg')) {
        \App\Models\Setting::set('slider_image_1', 'img/slider img/20631.jpg', 'text');
    }

    if (file_exists($imgDir . '/about us/about_showcase.png')) {
        \App\Models\Setting::set('aboutus_image_1', 'img/about us/about_showcase.png', 'text');
    }

    $galleryDir = $imgDir . '/gallery';
    if (file_exists($galleryDir)) {
        $gFiles = scandir($galleryDir);
        $gIdx = 1;
        foreach ($gFiles as $gFile) {
            if ($gFile === '.' || $gFile === '..' || is_dir($galleryDir . '/' . $gFile)) continue;
            if ($gIdx > 10) break;
            
            $gPath = 'img/gallery/' . $gFile;
            \App\Models\Setting::set("gallery_image_$gIdx", $gPath, 'text');
            $gIdx++;
        }
    }

    return response("SUCCESS: Matched $matched / " . count($products) . " product images, and set Slider, About Us & Gallery images!");
});

Route::get('/view-logs', function () {
    try {
        $laravelLog = storage_path('logs/laravel.log');
        $output = "=== LARAVEL LOG LAST 100 LINES (storage/logs/laravel.log) ===\n";
        if (file_exists($laravelLog)) {
            $lines = file($laravelLog);
            $lastLines = array_slice($lines, -100);
            $output .= implode("", $lastLines) . "\n";
        } else {
            $output .= "File does not exist.\n";
        }
        return response($output)->header('Content-Type', 'text/plain');
    } catch (\Exception $e) {
        return response("Error reading logs: " . $e->getMessage(), 500)->header('Content-Type', 'text/plain');
    }
});

// 3. Public Storefront & Booking Routes (handled by React Client-side routing)
Route::get('/', function () { return view('react'); })->name('home');
Route::get('/home', function () { return view('react'); });
Route::get('/quick-order', function () { return view('react'); });
Route::get('/checkout/success/{order_number}', function () { return view('react'); })->name('checkout.success');
Route::get('/about', function () { return view('react'); })->name('about');
Route::get('/terms', function () { return view('react'); })->name('terms');
Route::get('/contact', function () { return view('react'); })->name('contact');
Route::get('/price_list', function () { return view('react'); })->name('price_list');
Route::get('/price-list', function () { return view('react'); });
Route::get('/track', function () { return view('react'); })->name('track.index');

Route::prefix('api/admin')->group(function () {
    Route::post('/auth/login', [AdminApiController::class, 'login']);
    Route::post('/auth/logout', [AdminApiController::class, 'logout']);
    Route::get('/auth/check', [AdminApiController::class, 'authCheck']);

    // Admin API Routes (Accessible directly without auth middleware)
    Route::group([], function () {
        Route::get('/dashboard', [AdminApiController::class, 'dashboard']);
        Route::get('/categories', [AdminApiController::class, 'categories']);
        Route::post('/categories/store', [AdminApiController::class, 'storeCategory']);
        Route::post('/categories/{id}/update', [AdminApiController::class, 'updateCategory']);
        Route::post('/categories/{id}/quick-update', [AdminApiController::class, 'quickUpdateCategory']);
        Route::delete('/categories/{id}/destroy', [AdminApiController::class, 'destroyCategory']);
        
        Route::get('/products', [AdminApiController::class, 'products']);
        Route::get('/products/export', [AdminApiController::class, 'exportProductTemplate']);
        Route::post('/products/store', [AdminApiController::class, 'storeProduct']);
        Route::post('/products/{id}/update', [AdminApiController::class, 'updateProduct']);
        Route::post('/products/{id}/quick-update', [AdminApiController::class, 'quickUpdateProduct']);
        Route::post('/products/{id}/toggle-bestseller', [AdminApiController::class, 'toggleBestsellerProduct']);
        Route::delete('/products/{id}/destroy', [AdminApiController::class, 'destroyProduct']);
        Route::post('/products/delete-all', [AdminApiController::class, 'deleteAllProducts']);
        Route::delete('/products/delete-all', [AdminApiController::class, 'deleteAllProducts']);
        Route::post('/products/import', [AdminApiController::class, 'importProducts']);
        Route::get('/inventory', [AdminApiController::class, 'inventory']);
        Route::post('/inventory/update', [AdminApiController::class, 'updateInventory']);

        Route::get('/orders', [AdminApiController::class, 'orders']);
        Route::post('/orders/create-billing', [AdminApiController::class, 'createBillingOrder']);
        Route::get('/orders/{id}', [AdminApiController::class, 'order']);
        Route::post('/orders/{id}/status', [AdminApiController::class, 'updateOrderStatus']);
        Route::post('/orders/{id}/items', [AdminApiController::class, 'updateOrderItems']);
        Route::delete('/orders/{id}', [AdminApiController::class, 'destroyOrder']);
        Route::delete('/orders/{id}/destroy', [AdminApiController::class, 'destroyOrder']);
        
        Route::get('/settings', [AdminApiController::class, 'settings']);
        Route::post('/settings/update', [AdminApiController::class, 'updateSettings']);
        
        Route::get('/branding', [AdminApiController::class, 'branding']);
        Route::post('/branding/update', [AdminApiController::class, 'updateBranding']);
        
        Route::get('/reports/sales', [AdminApiController::class, 'reportsSales']);
        Route::get('/customers', [AdminApiController::class, 'customers']);
        
        Route::post('/profile/update', [AdminApiController::class, 'updateProfile']);
    });
});

// React routing fallback for Admin panel UI
Route::get('/admin/{any?}', function () {
    return view('react');
})->where('any', '.*');

// 4. Super Admin API Routes & React Fallback
Route::prefix('api/admin_sys')->group(function () {
    Route::post('/auth/login', [\App\Http\Controllers\AdminSysApiController::class, 'login']);
    Route::post('/auth/logout', [\App\Http\Controllers\AdminSysApiController::class, 'logout']);
    Route::get('/auth/check', [\App\Http\Controllers\AdminSysApiController::class, 'checkAuth']);

    Route::middleware([\App\Http\Middleware\SuperAdminAuth::class])->group(function () {
        Route::get('/companies', [\App\Http\Controllers\AdminSysApiController::class, 'companies']);
        Route::post('/companies/store', [\App\Http\Controllers\AdminSysApiController::class, 'storeCompany']);
        Route::post('/companies/{id}/update', [\App\Http\Controllers\AdminSysApiController::class, 'updateCompany']);
        Route::post('/companies/{id}/toggle-status', [\App\Http\Controllers\AdminSysApiController::class, 'toggleCompanyStatus']);
        Route::delete('/companies/{id}/destroy', [\App\Http\Controllers\AdminSysApiController::class, 'destroyCompany']);

        Route::get('/profile', [\App\Http\Controllers\AdminSysApiController::class, 'profile']);
        Route::post('/profile/update', [\App\Http\Controllers\AdminSysApiController::class, 'updateProfile']);
        Route::post('/companies/{id}/reset-admin-password', [\App\Http\Controllers\AdminSysApiController::class, 'resetCompanyAdminPassword']);
    });
});

// React routing fallback for Super Admin panel UI
Route::get('/admin_sys/{any?}', function () {
    return view('react');
})->where('any', '.*');

// React routing fallback for public storefront UI routes (quick-order, price-list, about, contact, track, etc.)
Route::fallback(function () {
    return view('react');
});
