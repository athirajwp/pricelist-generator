<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminApiController extends Controller
{
    /**
     * Check if admin is authenticated.
     */
    public function authCheck()
    {
        $company = view()->shared('currentCompany');
        $companyCode = $company ? $company->code : 'default';

        if (session()->has('admin_logged_in_' . $companyCode)) {
            return response()->json(['logged_in' => true]);
        }
        return response()->json(['logged_in' => false], 401);
    }

    /**
     * Handle login authentication.
     */
    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $adminPassword = Setting::get('admin_password');
        if (!$adminPassword) {
            $adminPassword = env('ADMIN_PASSWORD', 'admin123');
        }

        $isMatch = false;
        if (str_starts_with($adminPassword, '$2y$') || str_starts_with($adminPassword, '$2a$')) {
            $isMatch = Hash::check($request->password, $adminPassword);
        } else {
            $isMatch = ($request->password === $adminPassword);
        }

        if ($isMatch) {
            $company = view()->shared('currentCompany');
            $companyCode = $company ? $company->code : 'default';
            session(['admin_logged_in_' . $companyCode => true]);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid password!'], 422);
    }

    /**
     * Handle logout.
     */
    public function logout()
    {
        $company = view()->shared('currentCompany');
        $companyCode = $company ? $company->code : 'default';
        session()->forget('admin_logged_in_' . $companyCode);
        return response()->json(['success' => true]);
    }

    /**
     * Admin Dashboard Statistics.
     */
    public function dashboard()
    {
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('order_status', 'pending')->count(),
            'shipped_orders' => Order::where('order_status', 'shipped')->count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('net_amount'),
            'pending_revenue' => Order::where('payment_status', 'pending')->sum('net_amount'),
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
        ];

        $recentOrders = Order::orderBy('created_at', 'desc')->limit(5)->get();

        return response()->json([
            'stats' => $stats,
            'recentOrders' => $recentOrders,
        ]);
    }

    /**
     * Categories API list & CRUD.
     */
    public function categories()
    {
        $categories = Category::orderBy('sort_order', 'asc')->get();
        return response()->json(['categories' => $categories]);
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'sort_order' => $request->sort_order ?? 0,
            'status' => $request->status,
        ]);

        return response()->json(['success' => true, 'category' => $category]);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'sort_order' => $request->sort_order ?? 0,
            'status' => $request->status,
        ]);

        return response()->json(['success' => true, 'category' => $category]);
    }

    public function destroyCategory($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Products API list & CRUD.
     */
    public function products()
    {
        $products = Product::with('category')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->orderBy('categories.sort_order', 'asc')
            ->orderBy('categories.id', 'asc')
            ->orderBy('products.sort_order', 'asc')
            ->orderBy('products.id', 'asc')
            ->select('products.*')
            ->get();
        $categories = Category::orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();

        return response()->json([
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'product_code' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'pack_size' => 'required|string|max:255',
            'mrp' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $company = view()->shared('currentCompany');
            $companyCode = $company ? $company->code : 'default';
            $companyCodeClean = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $companyCode));
            $uploadDir = "uploads/companies/{$companyCodeClean}/products";
            if (!is_dir(public_path($uploadDir))) mkdir(public_path($uploadDir), 0755, true);

            $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
            $request->image->move(public_path($uploadDir), $imageName);
            $imagePath = $uploadDir . '/' . $imageName;
        }

        // Auto-heal missing columns if tenant database schema has not executed recent migrations
        if (!Schema::hasColumn('products', 'product_code')) {
            try {
                Schema::table('products', function ($table) {
                    $table->string('product_code')->nullable();
                });
            } catch (\Exception $e) {}
        }
        if (!Schema::hasColumn('products', 'is_bestseller')) {
            try {
                Schema::table('products', function ($table) {
                    $table->boolean('is_bestseller')->default(false);
                });
            } catch (\Exception $e) {}
        }
        if (!Schema::hasColumn('products', 'sort_order')) {
            try {
                Schema::table('products', function ($table) {
                    $table->integer('sort_order')->nullable()->default(999);
                });
            } catch (\Exception $e) {}
        }

        $productData = [
            'category_id' => $request->category_id,
            'product_code' => $request->product_code,
            'name' => $request->name,
            'pack_size' => $request->pack_size,
            'mrp' => $request->mrp,
            'selling_price' => $request->selling_price,
            'image' => $imagePath,
            'status' => $request->status,
        ];

        if (Schema::hasColumn('products', 'sort_order')) {
            $productData['sort_order'] = $request->filled('sort_order') ? (int) $request->sort_order : 999;
        }

        if (Schema::hasColumn('products', 'is_bestseller') && $request->has('is_bestseller')) {
            $productData['is_bestseller'] = filter_var($request->is_bestseller, FILTER_VALIDATE_BOOLEAN);
        }

        $product = Product::create($productData);

        return response()->json(['success' => true, 'product' => $product]);
    }

    public function quickUpdateProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $fields = ['name', 'pack_size', 'mrp', 'selling_price', 'product_code', 'category_id', 'status', 'is_bestseller', 'sort_order'];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $product->$field = $request->input($field);
            }
        }
        $product->save();
        return response()->json(['success' => true, 'product' => $product]);
    }

    public function quickUpdateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        if ($request->has('name')) {
            $category->name = $request->input('name');
            $category->save();
        }
        return response()->json(['success' => true, 'category' => $category]);
    }

    public function updateProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'product_code' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'pack_size' => 'required|string|max:255',
            'mrp' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        $imagePath = $product->image;
        if ($request->hasFile('image')) {
            if ($product->image && file_exists(public_path($product->image))) {
                @unlink(public_path($product->image));
            }

            $company = view()->shared('currentCompany');
            $companyCode = $company ? $company->code : 'default';
            $companyCodeClean = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $companyCode));
            $uploadDir = "uploads/companies/{$companyCodeClean}/products";
            if (!is_dir(public_path($uploadDir))) mkdir(public_path($uploadDir), 0755, true);

            $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
            $request->image->move(public_path($uploadDir), $imageName);
            $imagePath = $uploadDir . '/' . $imageName;
        }

        // Auto-heal missing columns if tenant database schema has not executed recent migrations
        if (!Schema::hasColumn('products', 'product_code')) {
            try {
                Schema::table('products', function ($table) {
                    $table->string('product_code')->nullable();
                });
            } catch (\Exception $e) {}
        }
        if (!Schema::hasColumn('products', 'is_bestseller')) {
            try {
                Schema::table('products', function ($table) {
                    $table->boolean('is_bestseller')->default(false);
                });
            } catch (\Exception $e) {}
        }
        if (!Schema::hasColumn('products', 'sort_order')) {
            try {
                Schema::table('products', function ($table) {
                    $table->integer('sort_order')->nullable()->default(999);
                });
            } catch (\Exception $e) {}
        }

        $updateData = [
            'category_id' => $request->category_id,
            'product_code' => $request->product_code,
            'name' => $request->name,
            'pack_size' => $request->pack_size,
            'mrp' => $request->mrp,
            'selling_price' => $request->selling_price,
            'image' => $imagePath,
            'status' => $request->status,
        ];

        if (Schema::hasColumn('products', 'sort_order') && $request->filled('sort_order')) {
            $updateData['sort_order'] = (int) $request->sort_order;
        }

        if (Schema::hasColumn('products', 'is_bestseller') && $request->has('is_bestseller')) {
            $updateData['is_bestseller'] = filter_var($request->is_bestseller, FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->has('stock_quantity')) {
            $updateData['stock_quantity'] = (int) $request->stock_quantity;
        }
        if ($request->has('min_stock_alert')) {
            $updateData['min_stock_alert'] = (int) $request->min_stock_alert;
        }
        if ($request->has('manage_stock')) {
            $updateData['manage_stock'] = $request->manage_stock;
        }

        $product->update($updateData);
        $product->updateStockStatus();
        $product->save();

        return response()->json(['success' => true, 'product' => $product]);
    }

    public function inventory(Request $request)
    {
        if (!Schema::hasColumn('products', 'stock_quantity')) {
            try {
                Schema::table('products', function ($table) {
                    $table->integer('stock_quantity')->default(100);
                    $table->integer('min_stock_alert')->default(10);
                    $table->string('manage_stock')->default('yes');
                    $table->string('stock_status')->default('in_stock');
                });
            } catch (\Exception $e) {}
        }

        $query = Product::with('category');

        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        $allProducts = (clone $query)->get();

        foreach ($allProducts as $p) {
            $oldStatus = $p->stock_status;
            $p->updateStockStatus();
            if ($oldStatus !== $p->stock_status) {
                $p->save();
            }
        }

        $totalProducts = $allProducts->count();
        $inStockCount = $allProducts->where('stock_status', 'in_stock')->count();
        $lowStockCount = $allProducts->where('stock_status', 'low_stock')->count();
        $outOfStockCount = $allProducts->where('stock_status', 'out_of_stock')->count();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('stock_status', $request->status);
        }

        $products = $query->orderBy('category_id', 'asc')
                          ->orderBy('sort_order', 'asc')
                          ->get();

        $categories = Category::orderBy('sort_order', 'asc')->get();

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_products' => $totalProducts,
                'in_stock_count' => $inStockCount,
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
            ],
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function updateInventory(Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
            'updates.*.id' => 'required|exists:products,id',
            'updates.*.stock_quantity' => 'required|integer|min:0',
            'updates.*.min_stock_alert' => 'nullable|integer|min:0',
            'updates.*.manage_stock' => 'nullable|in:yes,no',
        ]);

        foreach ($request->updates as $item) {
            $product = Product::find($item['id']);
            if ($product) {
                $product->stock_quantity = (int) $item['stock_quantity'];
                if (isset($item['min_stock_alert'])) {
                    $product->min_stock_alert = (int) $item['min_stock_alert'];
                }
                if (isset($item['manage_stock'])) {
                    $product->manage_stock = $item['manage_stock'];
                }
                $product->updateStockStatus();
                $product->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Inventory stock levels updated successfully!'
        ]);
    }

    public function toggleBestsellerProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        if (!Schema::hasColumn('products', 'is_bestseller')) {
            try {
                Schema::table('products', function ($table) {
                    $table->boolean('is_bestseller')->default(false);
                });
            } catch (\Exception $e) {}
        }

        $product->is_bestseller = !$product->is_bestseller;
        $product->save();

        return response()->json([
            'success' => true,
            'is_bestseller' => (bool) $product->is_bestseller,
            'message' => $product->is_bestseller ? 'Added to Most Sold Products!' : 'Removed from Most Sold Products!',
        ]);
    }

    public function destroyProduct($id)
    {
        $product = Product::findOrFail($id);
        if ($product->image && file_exists(public_path($product->image))) {
            @unlink(public_path($product->image));
        }
        $product->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Delete all products from the database.
     */
    public function deleteAllProducts()
    {
        try {
            $products = Product::all();
            foreach ($products as $product) {
                if ($product->image && file_exists(public_path($product->image)) && !str_contains($product->image, 'img/')) {
                    @unlink(public_path($product->image));
                }
            }
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                Product::truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Exception $ex) {
                Product::query()->delete();
            }
            return response()->json([
                'success' => true,
                'message' => 'All products deleted successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Delete All Products Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete products: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Import products from an Excel file (.xlsx, .xls, .csv).
     * Expected columns: Category, Product Name, Pack Size, MRP, Selling Price, Sort Order, Status
     */
    public function importProducts(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            $realPath = $file->getRealPath();

            if ($extension === 'csv' || str_contains($file->getMimeType() ?: '', 'csv')) {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                $spreadsheet = $reader->load($realPath);
            } else {
                if (!class_exists('ZipArchive')) {
                    return response()->json([
                        'error' => 'ZipArchive extension is disabled in PHP. Please enable extension=zip in php.ini or upload a .csv file.'
                    ], 422);
                }
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($realPath);
            }
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray(null, true, true, true);

            if (count($rows) < 2) {
                return response()->json(['error' => 'The file appears to be empty or has no data rows.'], 422);
            }

            // Parse header row (first row)
            $headerRow = array_shift($rows);
            $headerMap = [];
            foreach ($headerRow as $col => $value) {
                if ($value !== null) {
                    $headerMap[strtolower(trim($value))] = $col;
                }
            }

            // Alias map for required fields matching template headers: Category, S.No / Code, Product Name, Unit, Rate (MRP), Offer Rate
            $aliasMap = [
                'Category' => ['category', 'category name', 'cat'],
                'Product Name' => ['product name', 'product', 'name', 'item'],
                'Unit' => ['unit', 'pack size', 'unit / pack size', 'size', 'contents'],
                'Rate (MRP)' => ['rate (mrp)', 'mrp', 'rate', 'rate (₹)', 'price'],
                'Offer Rate' => ['offer rate', 'selling price', 'offer price', 'offer rate (₹)', 'discounted price'],
            ];

            $missingColumns = [];
            foreach ($aliasMap as $label => $aliases) {
                $found = false;
                foreach ($aliases as $alias) {
                    if (isset($headerMap[strtolower(trim($alias))])) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $missingColumns[] = $label;
                }
            }

            if (!empty($missingColumns)) {
                return response()->json([
                    'error' => 'Missing required columns: ' . implode(', ', $missingColumns) . '. Required columns are: Category, Product Name, Unit, Rate (MRP), Offer Rate.'
                ], 422);
            }

            // Helper function to find column value by key aliases
            $getColVal = function($row, $headerMap, $aliases, $default = '') {
                foreach ($aliases as $alias) {
                    $key = strtolower(trim($alias));
                    if (isset($headerMap[$key]) && isset($row[$headerMap[$key]])) {
                        return trim($row[$headerMap[$key]]);
                    }
                }
                return $default;
            };

            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            // Pre-fill category cache from DB to prevent duplicate categories or undefined variable errors
            $categoryCache = [];
            foreach (Category::all() as $existingCat) {
                $categoryCache[strtolower(trim($existingCat->name))] = $existingCat;
            }

            $categoryOrderCounter = 1;

            foreach ($rows as $rowIndex => $row) {
                $rowNum = $rowIndex + 1; // 1-based for user-friendly error messages (header was row 1)

                $categoryName = $getColVal($row, $headerMap, ['category', 'category name', 'cat']);
                $productCode  = $getColVal($row, $headerMap, ['s.no / code', 's.no', 'sno', 'product code', 'code', 's.no.']);
                $productName  = $getColVal($row, $headerMap, ['product name', 'product', 'name', 'item']);
                $packSize     = $getColVal($row, $headerMap, ['unit', 'pack size', 'unit / pack size', 'size', 'contents']);
                $mrpVal       = $getColVal($row, $headerMap, ['rate (mrp)', 'mrp', 'rate', 'rate (₹)', 'price'], '0');
                $sellingVal   = $getColVal($row, $headerMap, ['offer rate', 'selling price', 'offer price', 'offer rate (₹)', 'discounted price'], '0');

                $mrp = floatval(preg_replace('/[^0-9.]/', '', $mrpVal));
                $sellingPrice = floatval(preg_replace('/[^0-9.]/', '', $sellingVal));

                // Skip completely empty rows
                if (empty($categoryName) && empty($productName)) {
                    continue;
                }

                // Validate row data
                if (empty($categoryName)) {
                    $errors[] = "Row {$rowNum}: Category is empty, skipped.";
                    $skipped++;
                    continue;
                }
                if (empty($productName)) {
                    $errors[] = "Row {$rowNum}: Product Name is empty, skipped.";
                    $skipped++;
                    continue;
                }

                // Find or create category safely
                $catKey = strtolower(trim($categoryName));
                if (!isset($categoryCache[$catKey])) {
                    $baseSlug = \Illuminate\Support\Str::slug($categoryName) ?: 'category';
                    $existingBySlug = Category::where('slug', $baseSlug)->first();
                    if ($existingBySlug) {
                        $existingBySlug->update(['sort_order' => $categoryOrderCounter]);
                        $categoryCache[$catKey] = $existingBySlug;
                    } else {
                        $newCat = Category::create([
                            'name' => $categoryName,
                            'slug' => $baseSlug,
                            'sort_order' => $categoryOrderCounter,
                            'status' => 'active',
                        ]);
                        $categoryCache[$catKey] = $newCat;
                    }
                    $categoryOrderCounter++;
                }
                $category = $categoryCache[$catKey];

                $excelRowPosition = $rowIndex + 1; // 1-based exact row order in Excel file

                // Check if product already exists in same category with same name
                $existingProduct = Product::where('name', $productName)
                    ->where('category_id', $category->id)
                    ->first();

                if ($existingProduct) {
                    $existingProduct->update([
                        'product_code' => ($productCode !== null && $productCode !== '') ? $productCode : $existingProduct->product_code,
                        'pack_size' => $packSize ?: $existingProduct->pack_size,
                        'mrp' => (float) $mrp,
                        'selling_price' => (float) $sellingPrice,
                        'sort_order' => $excelRowPosition,
                    ]);
                    $updated++;
                } else {
                    Product::create([
                        'category_id' => $category->id,
                        'product_code' => ($productCode !== null && $productCode !== '') ? $productCode : null,
                        'name' => $productName,
                        'pack_size' => $packSize ?: '-',
                        'mrp' => (float) $mrp,
                        'selling_price' => (float) $sellingPrice,
                        'sort_order' => $excelRowPosition,
                        'status' => 'active',
                    ]);
                    $imported++;
                }
            }

            return response()->json([
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            Log::error('Product Import Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process file: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export current products as an Excel file (also serves as a template).
     */
    public function exportProductTemplate(Request $request)
    {
        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Products');

            // Set headers strictly matching the Price List Table Format: Category, S.No / Code, Product Name, Unit, Rate (MRP), Offer Rate
            $headers = ['Category', 'S.No / Code', 'Product Name', 'Unit', 'Rate (MRP)', 'Offer Rate'];
            foreach ($headers as $colIndex => $header) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                $sheet->setCellValue($colLetter . '1', $header);

                // Style the header row
                $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
                $sheet->getStyle($colLetter . '1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE8532A');
                $sheet->getStyle($colLetter . '1')->getFont()->getColor()->setARGB('FFFFFFFF');
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            $includeData = $request->query('include_data', 'false') === 'true';

            if ($includeData) {
                // Export existing products
                $products = Product::with('category')
                    ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                    ->orderBy('categories.sort_order', 'asc')
                    ->orderBy('products.name', 'asc')
                    ->select('products.*')
                    ->get();

                $row = 2;
                foreach ($products as $idx => $product) {
                    $sheet->setCellValue('A' . $row, $product->category->name ?? 'Uncategorized');
                    $sheet->setCellValue('B' . $row, $product->product_code ?: ($idx + 1));
                    $sheet->setCellValue('C' . $row, $product->name);
                    $sheet->setCellValue('D' . $row, $product->pack_size);
                    $sheet->setCellValue('E' . $row, (float) $product->mrp);
                    $sheet->setCellValue('F' . $row, (float) $product->selling_price);
                    $row++;
                }
            } else {
                // Add sample rows for template matching table format
                $sampleData = [
                    ['SPARKLERS', '1', '7cm Electric Sparklers', '1 Packet (6 Pcs)', 3570, 892.50],
                    ['SPARKLERS', '2', '7cm Green Sparklers', '1 Box (2 Pcs)', 2660, 665.00],
                    ['GROUND CHAKKARS', '20', 'Ground Chakkars Baby', '1 Box (8 Pcs)', 4100, 1025.00],
                ];
                $row = 2;
                foreach ($sampleData as $sample) {
                    foreach ($sample as $colIndex => $value) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                        $sheet->setCellValue($colLetter . $row, $value);
                    }
                    $row++;
                }
            }

            // Add a Categories reference sheet
            $catSheet = $spreadsheet->createSheet();
            $catSheet->setTitle('Categories (Reference)');
            $catSheet->setCellValue('A1', 'Existing Categories');
            $catSheet->getStyle('A1')->getFont()->setBold(true);
            $catSheet->getStyle('A1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF4CAF50');
            $catSheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFFFFFF');
            $catSheet->getColumnDimension('A')->setAutoSize(true);

            $categories = Category::orderBy('sort_order', 'asc')->get();
            $catRow = 2;
            foreach ($categories as $cat) {
                $catSheet->setCellValue('A' . $catRow, $cat->name);
                $catRow++;
            }

            $catSheet->setCellValue('B1', 'Note');
            $catSheet->setCellValue('B2', 'Use these exact category names in the Products sheet.');
            $catSheet->setCellValue('B3', 'New category names will be auto-created during import.');
            $catSheet->getColumnDimension('B')->setAutoSize(true);
            $catSheet->getStyle('B1')->getFont()->setBold(true);

            // Set the active sheet back to Products
            $spreadsheet->setActiveSheetIndex(0);

            $fileName = $includeData ? 'products_export.xlsx' : 'products_template.xlsx';

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'products_') . '.xlsx';
            $writer->save($tempFile);

            return response()->download($tempFile, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Product Export Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate file: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate pixel-perfect Vector PDF via Puppeteer in backend.
     */
    public function generatePuppeteerPdf(Request $request)
    {
        @set_time_limit(120);
        try {
            $outputFilename = 'price_list_vector_' . time() . '.pdf';
            $outputPath = storage_path('app/public/' . $outputFilename);
            $scriptPath = base_path('scripts/generate-pdf.js');

            $scheme = $request->getScheme();
            $host = $request->getHttpHost(); // Dynamically gets 127.0.0.1:9000 or active server host:port
            $targetUrl = "{$scheme}://{$host}/price-list?print_pdf=1";

            $cmd = "node \"" . addslashes($scriptPath) . "\" --url \"" . addslashes($targetUrl) . "\" --output \"" . addslashes($outputPath) . "\" --delay 1500";
            
            Log::info("Executing Puppeteer PDF Command: " . $cmd);
            exec($cmd . " 2>&1", $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($outputPath)) {
                Log::error("Puppeteer PDF Execution Failed. Code: {$returnCode}. Output: " . implode("\n", $output));
                return response()->json([
                    'error' => 'Puppeteer PDF generation failed.',
                    'details' => implode("\n", $output)
                ], 500);
            }

            return response()->download($outputPath, 'Sivakasi_Fireworks_PriceList_A4.pdf', [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error("Puppeteer Controller Error: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Orders API list & CRUD.
     */
    public function orders(Request $request)
    {
        $status = $request->input('status');
        $query = Order::orderBy('created_at', 'desc');

        if ($status) {
            $query->where('order_status', $status);
        }

        $orders = $query->get();
        return response()->json(['orders' => $orders]);
    }

    public function createBillingOrder(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) continue;

                $price = floatval($item['price']);
                $qty = intval($item['qty']);
                $mrp = floatval($product->mrp);
                $subtotal += ($mrp > 0 ? $mrp : $price) * $qty;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'pack_size' => $product->pack_size ?? '',
                    'price' => $price,
                    'quantity' => $qty,
                    'total_price' => $price * $qty,
                ];
            }

            $discountAmount = floatval($request->discount_amount ?? 0);
            $netAmount = floatval($request->net_amount ?? 0);
            if ($netAmount <= 0) {
                $itemTotalSum = array_sum(array_column($itemsData, 'total_price'));
                $netAmount = max(0, $itemTotalSum - $discountAmount);
            }

            $paymentMethod = $request->payment_method ?? 'Cash';
            $paymentStatus = $request->payment_status ?? 'paid';
            $orderStatus = $request->order_status ?? 'confirmed';
            $notes = $request->notes ?? '';
            if ($paymentMethod) {
                $notes = trim("Payment Mode: {$paymentMethod}\n" . $notes);
            }

            $phone = !empty($request->phone) ? $request->phone : 'Counter Sale';
            $city = !empty($request->city) ? $request->city : 'Sivakasi';
            $address = !empty($request->address) ? $request->address : 'Counter Billing';
            $state = !empty($request->state) ? $request->state : 'Tamil Nadu';
            $pincode = !empty($request->pincode) ? $request->pincode : '626123';

            $order = Order::create([
                'name' => $request->name ?: 'Counter Customer',
                'phone' => $phone,
                'whatsapp' => $request->whatsapp ?: $phone,
                'email' => $request->email ?: '',
                'address' => $address,
                'landmark' => $request->landmark ?: '',
                'city' => $city,
                'state' => $state,
                'pincode' => $pincode,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'net_amount' => $netAmount,
                'payment_status' => $paymentStatus,
                'order_status' => $orderStatus,
                'notes' => $notes,
            ]);

            $now = now();
            foreach ($itemsData as &$iData) {
                $iData['order_id'] = $order->id;
                $iData['created_at'] = $now;
                $iData['updated_at'] = $now;
            }
            OrderItem::insert($itemsData);

            DB::commit();

            // Dispatch emails asynchronously in detached background process (<10ms non-blocking)
            CheckoutController::dispatchOrderEmailsAsync($order->id);

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'message' => 'Bill created successfully!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Billing Order Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create bill: ' . $e->getMessage()], 500);
        }
    }

    public function order($id)
    {
        $order = Order::with('items')->findOrFail($id);
        
        $categories = Category::active()
            ->with(['products' => function ($query) {
                $query->active()->orderBy('sort_order', 'asc');
            }])
            ->orderBy('sort_order', 'asc')
            ->get();

        $settings = [
            'store_name' => Setting::get('store_name', 'Cracker Demo'),
            'store_logo' => Setting::get('store_logo', ''),
            'store_address' => Setting::get('store_address', 'Virudhunagar to Sivakasi Main Road, Sivakasi'),
            'store_phone' => Setting::get('store_phone', '+91 9998887776'),
            'store_email' => Setting::get('store_email', 'store@example.com'),
        ];

        return response()->json([
            'order' => $order,
            'categories' => $categories,
            'settings' => $settings
        ]);
    }

    public function destroyOrder($id)
    {
        try {
            $order = Order::findOrFail($id);
            $order->items()->delete();
            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Order Destroy Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $request->validate([
            'order_status' => 'required|in:pending,approved,processing,shipped,delivered,cancelled',
            'payment_status' => 'required|in:pending,paid,verified',
            'transport_name' => 'nullable|string|max:255',
            'lr_number' => 'nullable|string|max:255',
        ]);

        $order->update([
            'order_status' => $request->order_status,
            'payment_status' => $request->payment_status,
            'transport_name' => $request->transport_name,
            'lr_number' => $request->lr_number,
        ]);

        return response()->json(['success' => true, 'order' => $order]);
    }

    public function updateOrderItems(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $request->validate([
            'items' => 'required|array',
            'items.*' => 'required|integer|min:0',
        ]);

        $submittedItems = $request->input('items');
        $subtotal = 0;
        $netAmount = 0;
        $validatedItems = [];

        foreach ($submittedItems as $productId => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) continue;

            $product = Product::find($productId);
            if (!$product) {
                return response()->json(['error' => "Product #{$productId} not found."], 422);
            }

            $itemSubtotal = $product->mrp * $qty;
            $itemNet = $product->selling_price * $qty;

            $subtotal += $itemSubtotal;
            $netAmount += $itemNet;

            $validatedItems[] = [
                'product' => $product,
                'qty' => $qty,
                'price' => $product->selling_price,
                'total_price' => $itemNet,
            ];
        }

        if (empty($validatedItems)) {
            return response()->json(['error' => 'The order must contain at least one item.'], 422);
        }

        $enableTaxDelivery = Setting::get('enable_tax_delivery', 'no') === 'yes';
        $taxPercent = (float) Setting::get('tax_percent', 18);
        $deliveryCharge = (float) Setting::get('delivery_charge', 150);

        $taxAmount = 0;
        $deliveryChargeVal = 0;
        if ($enableTaxDelivery) {
            $taxAmount = $netAmount * ($taxPercent / 100);
            $deliveryChargeVal = $deliveryCharge;
        }

        $finalNet = $netAmount + $taxAmount + $deliveryChargeVal;
        $discountAmount = $subtotal - $netAmount;

        try {
            DB::beginTransaction();
            $order->items()->delete();

            foreach ($validatedItems as $vItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $vItem['product']->id,
                    'product_name' => $vItem['product']->name,
                    'pack_size' => $vItem['product']->pack_size,
                    'price' => $vItem['price'],
                    'quantity' => $vItem['qty'],
                    'total_price' => $vItem['total_price'],
                ]);
            }

            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'net_amount' => $finalNet,
            ]);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('Admin Order Update Items API failed: ' . $exception->getMessage());
            return response()->json(['error' => 'Something went wrong: ' . $exception->getMessage()], 500);
        }
    }

    /**
     * Settings configuration APIs.
     */
    public function settings()
    {
        $settings = [
            'store_name' => Setting::get('store_name', 'Cracker Demo'),
            'min_order_value' => Setting::get('min_order_value', 3800),
            'discount_percent' => Setting::get('discount_percent', 60),
            'store_whatsapp' => Setting::get('store_whatsapp', '919998887776'),
            'store_phone' => Setting::get('store_phone', '+91 9998887776'),
            'store_phone_2' => Setting::get('store_phone_2', ''),
            'store_phone_3' => Setting::get('store_phone_3', ''),
            'store_phone_4' => Setting::get('store_phone_4', ''),
            'store_email' => Setting::get('store_email', 'crackerdemo@gmail.com'),
            'store_address' => Setting::get('store_address', 'Virudhunagar to Sivakasi Main Road, Sivakasi'),
            'store_map_iframe' => Setting::get('store_map_iframe', ''),
            'license_name' => Setting::get('license_name', 'Fireworks Factory License'),
            'license_no' => Setting::get('license_no', 'LE-4/SIVAKASI/2024'),
            'store_experience' => Setting::get('store_experience', '10+'),
            'instagram_link' => Setting::get('instagram_link', ''),
            'facebook_link' => Setting::get('facebook_link', ''),
            'youtube_link' => Setting::get('youtube_link', ''),
            'whatsapp_link' => Setting::get('whatsapp_link', ''),
            'twitter_link' => Setting::get('twitter_link', ''),
            'store_upi' => Setting::get('store_upi', 'aathishacrackers@okaxis'),
            'store_upi_qr' => Setting::get('store_upi_qr', ''),
            'bank_name' => Setting::get('bank_name', 'State Bank of India'),
            'bank_acc_no' => Setting::get('bank_acc_no', '1234567890'),
            'bank_ifsc' => Setting::get('bank_ifsc', 'SBIN0000123'),
            'bank_holder' => Setting::get('bank_holder', 'Cracker Demo'),
            
            // Feature flags & AiSensy WhatsApp config
            'enable_min_order' => Setting::get('enable_min_order', 'yes'),
            'enable_promo_codes' => Setting::get('enable_promo_codes', 'yes'),
            'enable_tax_delivery' => Setting::get('enable_tax_delivery', 'no'),
            'enable_legal_notice' => Setting::get('enable_legal_notice', 'yes'),
            'enable_fireworks' => Setting::get('enable_fireworks', 'yes'),
            'enable_aos' => Setting::get('enable_aos', 'yes'),
            'enable_most_sold' => Setting::get('enable_most_sold', 'yes'),
            'show_mrp' => Setting::get('show_mrp', 'yes'),
            'card_bg_color' => Setting::get('card_bg_color', '#FFFFFF'),
            'default_view_mode' => Setting::get('default_view_mode', 'flex'),
            'tax_percent' => Setting::get('tax_percent', 18),
            'delivery_charge' => Setting::get('delivery_charge', 150),
            'enable_aisensy' => Setting::get('enable_aisensy', 'no'),
            'aisensy_api_key' => Setting::get('aisensy_api_key', ''),
            'aisensy_campaign_name' => Setting::get('aisensy_campaign_name', ''),
        ];

        return response()->json(['settings' => $settings]);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'store_name' => 'required|string|max:255',
            'min_order_value' => 'required|numeric|min:0',
            'discount_percent' => 'required|numeric|min:0|max:100',
            'store_whatsapp' => 'required|string|max:20',
            'store_phone' => 'required|string|max:20',
            'store_phone_2' => 'nullable|string|max:20',
            'store_phone_3' => 'nullable|string|max:20',
            'store_phone_4' => 'nullable|string|max:20',
            'store_email' => 'required|email|max:255',
            'store_address' => 'required|string',
            'store_map_iframe' => 'nullable|string',
            'license_name' => 'nullable|string|max:255',
            'license_no' => 'nullable|string|max:255',
            'store_experience' => 'nullable|string|max:50',
            'instagram_link' => 'nullable|string|max:500',
            'facebook_link' => 'nullable|string|max:500',
            'youtube_link' => 'nullable|string|max:500',
            'whatsapp_link' => 'nullable|string|max:500',
            'twitter_link' => 'nullable|string|max:500',
            'store_upi' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_acc_no' => 'nullable|string|max:255',
            'bank_ifsc' => 'nullable|string|max:255',
            'bank_holder' => 'nullable|string|max:255',
            'enable_min_order' => 'required|in:yes,no',
            'enable_promo_codes' => 'required|in:yes,no',
            'enable_tax_delivery' => 'required|in:yes,no',
            'enable_legal_notice' => 'nullable|in:yes,no',
            'enable_fireworks' => 'required|in:yes,no',
            'enable_aos' => 'nullable|in:yes,no',
            'enable_most_sold' => 'nullable|in:yes,no',
            'show_mrp' => 'nullable|in:yes,no',
            'card_bg_color' => 'nullable|string|max:50',
            'default_view_mode' => 'nullable|in:flex,grid',
            'tax_percent' => 'required|numeric|min:0|max:100',
            'delivery_charge' => 'required|numeric|min:0',
            'enable_aisensy' => 'nullable|in:yes,no',
            'aisensy_api_key' => 'nullable|string',
            'aisensy_campaign_name' => 'nullable|string',
            'store_upi_qr' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:20480',
        ]);

        Setting::set('store_name', $request->store_name, 'text');
        Setting::set('min_order_value', $request->min_order_value, 'number');

        $newDiscountPercent = (float) $request->discount_percent;
        $oldDiscountPercent = (float) Setting::get('discount_percent', 60);
        Setting::set('discount_percent', $newDiscountPercent, 'number');

        // Automatically update all product prices if catalog discount percentage changed or was submitted
        if ($newDiscountPercent !== $oldDiscountPercent || $request->has('discount_percent')) {
            $products = Product::all();
            foreach ($products as $prod) {
                if ($prod->mrp > 0) {
                    $prod->selling_price = round($prod->mrp * (1 - ($newDiscountPercent / 100)), 2);
                    $prod->save();
                }
            }
        }

        Setting::set('store_whatsapp', $request->store_whatsapp, 'text');
        Setting::set('store_phone', $request->store_phone, 'text');
        Setting::set('store_phone_2', $request->store_phone_2 ?? '', 'text');
        Setting::set('store_phone_3', $request->store_phone_3 ?? '', 'text');
        Setting::set('store_phone_4', $request->store_phone_4 ?? '', 'text');
        Setting::set('store_gpay', $request->store_gpay ?? '', 'text');
        Setting::set('table_row_height', $request->table_row_height ?? '22', 'text');
        Setting::set('table_col_padding', $request->table_col_padding ?? '4', 'text');
        Setting::set('store_email', $request->store_email, 'text');
        Setting::set('store_address', $request->store_address, 'textarea');
        Setting::set('store_map_iframe', $request->store_map_iframe ?? '', 'textarea');
        Setting::set('license_name', $request->license_name ?? '', 'text');
        Setting::set('license_no', $request->license_no ?? '', 'text');
        Setting::set('store_experience', $request->store_experience ?? '10+', 'text');
        Setting::set('instagram_link', $request->instagram_link ?? '', 'text');
        Setting::set('facebook_link', $request->facebook_link ?? '', 'text');
        Setting::set('youtube_link', $request->youtube_link ?? '', 'text');
        Setting::set('whatsapp_link', $request->whatsapp_link ?? '', 'text');
        Setting::set('twitter_link', $request->twitter_link ?? '', 'text');
        Setting::set('enable_aisensy', $request->enable_aisensy ?? 'no', 'text');
        Setting::set('aisensy_api_key', $request->aisensy_api_key ?? '', 'text');
        Setting::set('aisensy_campaign_name', $request->aisensy_campaign_name ?? '', 'text');
        Setting::set('store_upi', $request->store_upi ?? '', 'text');
        Setting::set('bank_name', $request->bank_name ?? '', 'text');
        Setting::set('bank_acc_no', $request->bank_acc_no ?? '', 'text');
        Setting::set('bank_ifsc', $request->bank_ifsc ?? '', 'text');
        Setting::set('bank_holder', $request->bank_holder ?? '', 'text');
        Setting::set('enable_min_order', $request->enable_min_order, 'text');
        Setting::set('enable_promo_codes', $request->enable_promo_codes, 'text');
        Setting::set('enable_tax_delivery', $request->enable_tax_delivery, 'text');
        Setting::set('enable_legal_notice', $request->enable_legal_notice ?? 'yes', 'text');
        Setting::set('enable_fireworks', $request->enable_fireworks, 'text');
        Setting::set('enable_aos', $request->enable_aos ?? 'yes', 'text');
        Setting::set('enable_most_sold', $request->enable_most_sold ?? 'yes', 'text');
        Setting::set('show_mrp', $request->show_mrp ?? 'yes', 'text');
        Setting::set('card_bg_color', $request->card_bg_color ?? '#FFFFFF', 'text');
        Setting::set('default_view_mode', $request->default_view_mode ?? 'flex', 'text');
        Setting::set('tax_percent', $request->tax_percent, 'number');
        Setting::set('delivery_charge', $request->delivery_charge, 'number');

        if ($request->has('store_upi_qr') && !$request->hasFile('store_upi_qr')) {
            Setting::set('store_upi_qr', $request->store_upi_qr, 'text');
        }

        if ($request->hasFile('store_upi_qr')) {
            $company = view()->shared('currentCompany');
            $companyCode = $company ? $company->code : 'default';
            $companyCodeClean = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $companyCode));
            $uploadDir = "uploads/companies/{$companyCodeClean}/settings";
            if (!is_dir(public_path($uploadDir))) mkdir(public_path($uploadDir), 0755, true);

            $fileName = time() . '_store_upi_qr_' . uniqid() . '.' . $request->file('store_upi_qr')->extension();
            $request->file('store_upi_qr')->move(public_path($uploadDir), $fileName);
            Setting::set('store_upi_qr', $uploadDir . '/' . $fileName, 'text');
        }

        if ($request->has('store_name_font')) {
            Setting::set('store_name_font', $request->store_name_font, 'text');
        }
        if ($request->has('store_deity_preset')) {
            Setting::set('store_deity_preset', $request->store_deity_preset, 'text');
        }
        if ($request->has('store_deity_image')) {
            Setting::set('store_deity_image', $request->store_deity_image, 'text');
        }
        if ($request->has('store_cover_bg') && !$request->hasFile('store_cover_bg')) {
            Setting::set('store_cover_bg', $request->store_cover_bg, 'text');
        }
        if ($request->has('store_logo') && !$request->hasFile('store_logo')) {
            Setting::set('store_logo', $request->store_logo, 'text');
        }

        if ($request->hasFile('store_cover_bg')) {
            $company = view()->shared('currentCompany');
            $companyCode = $company ? $company->code : 'default';
            $companyCodeClean = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $companyCode));
            $uploadDir = "uploads/companies/{$companyCodeClean}/settings";
            if (!is_dir(public_path($uploadDir))) mkdir(public_path($uploadDir), 0755, true);

            $fileName = time() . '_bg_' . uniqid() . '.' . $request->file('store_cover_bg')->extension();
            $request->file('store_cover_bg')->move(public_path($uploadDir), $fileName);
            Setting::set('store_cover_bg', $uploadDir . '/' . $fileName, 'text');
        }

        if ($request->hasFile('store_logo')) {
            $company = view()->shared('currentCompany');
            $companyCode = $company ? $company->code : 'default';
            $companyCodeClean = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $companyCode));
            $uploadDir = "uploads/companies/{$companyCodeClean}/settings";
            if (!is_dir(public_path($uploadDir))) mkdir(public_path($uploadDir), 0755, true);

            $fileName = time() . '_logo_' . uniqid() . '.' . $request->file('store_logo')->extension();
            $request->file('store_logo')->move(public_path($uploadDir), $fileName);
            Setting::set('store_logo', $uploadDir . '/' . $fileName, 'text');
        }
        if ($request->has('store_invocation_symbol')) {
            Setting::set('store_invocation_symbol', $request->store_invocation_symbol, 'text');
        }
        if ($request->has('store_invocation')) {
            Setting::set('store_invocation', $request->store_invocation, 'text');
        }

        if ($request->hasFile('store_deity_image')) {
            $company = view()->shared('currentCompany');
            $companyCode = $company ? $company->code : 'default';
            $companyCodeClean = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $companyCode));
            $uploadDir = "uploads/companies/{$companyCodeClean}/settings";
            if (!is_dir(public_path($uploadDir))) mkdir(public_path($uploadDir), 0755, true);

            $fileName = time() . '_deity_' . uniqid() . '.png';
            $fullPath = public_path($uploadDir . '/' . $fileName);
            $request->file('store_deity_image')->move(public_path($uploadDir), $fileName);

            // Auto-remove light background & checkerboard pattern using GD
            try {
                $raw = @file_get_contents($fullPath);
                if ($raw) {
                    $src = @imagecreatefromstring($raw);
                    if ($src) {
                        $w = imagesx($src);
                        $h = imagesy($src);
                        $dst = imagecreatetruecolor($w, $h);
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                        $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                        imagefill($dst, 0, 0, $trans);
                        for ($x = 0; $x < $w; $x++) {
                            for ($y = 0; $y < $h; $y++) {
                                $rgba = imagecolorat($src, $x, $y);
                                $r = ($rgba >> 16) & 0xFF;
                                $g = ($rgba >> 8) & 0xFF;
                                $b = $rgba & 0xFF;
                                $a = ($rgba >> 24) & 0x7F;
                                $isGrayscale = (abs($r - $g) < 15 && abs($g - $b) < 15);
                                $isLightBg = ($r > 170 && $g > 170 && $b > 170 && $isGrayscale);
                                $isWhite = ($r > 215 && $g > 215 && $b > 215);
                                if ($isLightBg || $isWhite) {
                                    imagesetpixel($dst, $x, $y, $trans);
                                } else {
                                    imagesetpixel($dst, $x, $y, imagecolorallocatealpha($dst, $r, $g, $b, $a));
                                }
                            }
                        }
                        imagepng($dst, $fullPath);
                        imagedestroy($src);
                        imagedestroy($dst);
                    }
                }
            } catch (\Exception $e) {}

            Setting::set('store_deity_image', $uploadDir . '/' . $fileName, 'text');
            Setting::set('store_deity_preset', 'custom', 'text');
        }

        // Sync back to central Company record if available
        $company = view()->shared('currentCompany');
        if ($company) {
            try {
                $company->update([
                    'name' => $request->store_name,
                    'contact_1' => $request->store_phone,
                    'contact_2' => $request->store_phone_2 ?: $request->store_whatsapp,
                    'contact_3' => $request->store_phone_3,
                    'contact_4' => $request->store_phone_4,
                    'email_1' => $request->store_email,
                    'address' => $request->store_address,
                    'address_1' => $request->store_address,
                    'bank_name_1' => $request->bank_name,
                    'bank_acc_1' => $request->bank_acc_no,
                    'bank_ifsc_1' => $request->bank_ifsc,
                    'bank_holder_1' => $request->bank_holder,
                    'upi_id_1' => $request->store_upi,
                ]);
            } catch (\Exception $ex) {
                Log::error('Company central sync error: ' . $ex->getMessage());
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Branding configuration APIs.
     */
    public function branding()
    {
        $keys = [
            'instagram_link', 'whatsapp_link', 'youtube_link', 'twitter_link', 'facebook_link',
            'promo_code_1', 'promo_value_1',
            'promo_code_2', 'promo_value_2',
            'promo_code_3', 'promo_value_3',
            'promo_code_4', 'promo_value_4',
            'promo_code_5', 'promo_value_5',
            'admin_theme', 'banner_scroller',
            'terms_conditions', 'about_us',
            'about_us_badge', 'about_us_title',
            'about_us_est_tag', 'about_us_expert_title', 'about_us_expert_desc',
            'about_us_feat_1', 'about_us_feat_2', 'about_us_feat_3', 'about_us_feat_4',
            'about_us_why_subtitle', 'about_us_why_title',
            'about_us_card1_title', 'about_us_card1_desc',
            'about_us_card2_title', 'about_us_card2_desc',
            'about_us_card3_title', 'about_us_card3_desc',
            'slider_image_1', 'slider_image_2', 'slider_image_3',
            'quick_order_banner_1', 'quick_order_banner_2', 'quick_order_banner_3',
            'aboutus_image_1', 'page_header_banner', 
            'about_banner', 'about_banner_1', 'about_banner_2', 'about_banner_3',
            'contact_banner', 'contact_banner_1', 'contact_banner_2', 'contact_banner_3',
            'store_logo', 'store_favicon',
            'license_name', 'license_no', 'store_map_iframe',
            'marquee_alert_1', 'marquee_alert_2', 'marquee_alert_3', 'marquee_alert_4', 'marquee_alert_5', 'marquee_alert_6',
        ];

        for ($i = 1; $i <= 10; $i++) {
            $keys[] = "gallery_image_{$i}";
        }

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = Setting::get($key, '');
        }

        $company = view()->shared('currentCompany');
        if (empty($settings['store_logo']) && $company && !empty($company->logo_path)) {
            $settings['store_logo'] = $company->logo_path;
        }
        if ($settings['store_logo'] === 'img/crackers logo.jpg' || $settings['store_logo'] === '/img/crackers logo.jpg') {
            $settings['store_logo'] = '';
        }

        if (empty($settings['store_favicon']) && $company && !empty($company->favicon_path)) {
            $settings['store_favicon'] = $company->favicon_path;
        }

        if (empty($settings['admin_theme'])) $settings['admin_theme'] = 'gold';
        if (empty($settings['about_us_badge'])) $settings['about_us_badge'] = 'A DECADE OF QUALITY';
        if (empty($settings['about_us_title'])) $settings['about_us_title'] = 'WE PROVIDE PREMIUM QUALITY FIREWORKS';
        if (empty($settings['about_us_est_tag'])) $settings['about_us_est_tag'] = 'EST. 1999 • Sivakasi';
        if (empty($settings['about_us_expert_title'])) $settings['about_us_expert_title'] = 'EXPERT TEAM';
        if (empty($settings['about_us_expert_desc'])) $settings['about_us_expert_desc'] = 'We have an experienced pyro technicians team';
        if (empty($settings['about_us_feat_1'])) $settings['about_us_feat_1'] = 'Branded Crackers at reasonable price';
        if (empty($settings['about_us_feat_2'])) $settings['about_us_feat_2'] = '100% Safe & Certified Standard';
        if (empty($settings['about_us_feat_3'])) $settings['about_us_feat_3'] = 'High Quality & Timely Delivery';
        if (empty($settings['about_us_feat_4'])) $settings['about_us_feat_4'] = '100% Satisfaction Guaranteed';

        return response()->json(['settings' => $settings]);
    }

    public function updateBranding(Request $request)
    {
        $imageFields = [
            'store_logo', 'store_favicon', 'slider_image_1', 'slider_image_2', 'slider_image_3',
            'quick_order_banner_1', 'quick_order_banner_2', 'quick_order_banner_3',
            'aboutus_image_1', 'page_header_banner', 
            'about_banner', 'about_banner_1', 'about_banner_2', 'about_banner_3',
            'contact_banner', 'contact_banner_1', 'contact_banner_2', 'contact_banner_3'
        ];
        for ($i = 1; $i <= 10; $i++) {
            $imageFields[] = "gallery_image_{$i}";
        }

        // Handle removing image slots
        if ($request->has('remove_image_key')) {
            $removeKey = $request->input('remove_image_key');
            if (in_array($removeKey, $imageFields)) {
                $oldPath = Setting::get($removeKey);
                if ($oldPath && !str_starts_with($oldPath, 'data:') && file_exists(public_path($oldPath))) {
                    @unlink(public_path($oldPath));
                }
                Setting::set($removeKey, '', 'text');

                $company = view()->shared('currentCompany');
                if ($company) {
                    try {
                        if ($removeKey === 'store_logo') {
                            $company->update(['logo_path' => null]);
                        } elseif ($removeKey === 'store_favicon') {
                            $company->update(['favicon_path' => null]);
                        }
                    } catch (\Throwable $th) {}
                }

                return response()->json(['success' => true, 'removed' => $removeKey]);
            }
        }

        $excludeFields = [
            'store_logo', 'store_favicon', 'slider_image_1', 'slider_image_2', 'slider_image_3',
            'quick_order_banner_1', 'quick_order_banner_2', 'quick_order_banner_3',
            'aboutus_image_1', 'page_header_banner', 
            'about_banner', 'about_banner_1', 'about_banner_2', 'about_banner_3',
            'contact_banner', 'contact_banner_1', 'contact_banner_2', 'contact_banner_3'
        ];
        for ($i = 1; $i <= 10; $i++) {
            $excludeFields[] = "gallery_image_{$i}";
        }

        $fields = $request->except($excludeFields);

        foreach ($fields as $key => $value) {
            $type = 'text';
            if (in_array($key, ['terms_conditions', 'about_us', 'store_map_iframe'])) {
                $type = 'textarea';
            }
            Setting::set($key, $value ?? '', $type);
        }

        if ($request->has('admin_theme')) {
            $themeVal = strtolower($request->admin_theme);
            Setting::set('admin_theme', $themeVal, 'text');
            try {
                $company = view()->shared('currentCompany');
                if ($company) {
                    $company->update(['theme' => $themeVal]);
                }
            } catch (\Throwable $th) {
                // Ignore if multi-tenant company table is not present
            }
        }

        $imageFields = [
            'store_logo', 'store_favicon', 'slider_image_1', 'slider_image_2', 'slider_image_3',
            'quick_order_banner_1', 'quick_order_banner_2', 'quick_order_banner_3',
            'aboutus_image_1', 'page_header_banner', 
            'about_banner', 'about_banner_1', 'about_banner_2', 'about_banner_3',
            'contact_banner', 'contact_banner_1', 'contact_banner_2', 'contact_banner_3'
        ];
        for ($i = 1; $i <= 10; $i++) {
            $imageFields[] = "gallery_image_{$i}";
        }
        $company = view()->shared('currentCompany');
        $companyCode = $company ? $company->code : 'default';
        $companyCodeClean = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $companyCode));
        $uploadDir = "uploads/companies/{$companyCodeClean}/branding";
        if (!is_dir(public_path($uploadDir))) mkdir(public_path($uploadDir), 0755, true);

        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $request->validate([
                    $field => 'file|max:20480'
                ]);

                $file = $request->file($field);
                $ext = strtolower($file->getClientOriginalExtension());
                if (!$ext) {
                    $ext = strtolower($file->extension()) ?: 'png';
                }
                
                $fileName = time() . '_' . $field . '_' . uniqid() . '.' . $ext;
                $file->move(public_path($uploadDir), $fileName);
                $filePath = $uploadDir . '/' . $fileName;

                // Clean up old file from disk if it existed
                $oldPath = Setting::get($field);
                if ($oldPath && !str_starts_with($oldPath, 'data:') && file_exists(public_path($oldPath))) {
                    @unlink(public_path($oldPath));
                }

                Setting::set($field, $filePath, 'text');

                if ($company) {
                    try {
                        if ($field === 'store_logo') {
                            $company->update(['logo_path' => $filePath]);
                        } elseif ($field === 'store_favicon') {
                            $company->update(['favicon_path' => $filePath]);
                        }
                    } catch (\Throwable $compEx) {
                        Log::error("Company model logo/favicon update error: " . $compEx->getMessage());
                    }
                }
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Update admin profile credentials/password.
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed|different:current_password',
        ]);

        $currentActivePassword = Setting::get('admin_password');
        if (!$currentActivePassword) {
            $currentActivePassword = env('ADMIN_PASSWORD', 'admin123');
        }

        $isMatch = false;
        if (str_starts_with($currentActivePassword, '$2y$') || str_starts_with($currentActivePassword, '$2a$')) {
            $isMatch = Hash::check($request->current_password, $currentActivePassword);
        } else {
            $isMatch = ($request->current_password === $currentActivePassword);
        }

        if (!$isMatch) {
            return response()->json(['error' => 'The provided current password does not match our records.'], 422);
        }

        Setting::set('admin_password', Hash::make($request->password), 'text');
        return response()->json(['success' => true]);
    }

    /**
     * Sales Reports API (Specific Day, Daily, Monthly, Yearly).
     */
    public function reportsSales(Request $request)
    {
        $period = $request->query('period', 'monthly');
        $reqDate = $request->query('date');
        $reqYear = $request->query('year');
        $reqMonth = $request->query('month');
        $status = $request->query('status', 'all');

        // 1. Load ALL orders (with items) — filter in PHP for SQLite+MySQL compatibility
        $allOrders = Order::with('items')->whereNotNull('created_at')->get();

        // 1a. Get available years from PHP
        $availableYears = $allOrders
            ->map(fn($o) => (int) $o->created_at->format('Y'))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [(int) date('Y')];
        }

        // 1b. Determine active year
        if ($reqYear === 'all') {
            $year = 'all';
        } elseif ($reqYear && in_array((int) $reqYear, $availableYears)) {
            $year = (int) $reqYear;
        } else {
            $year = reset($availableYears);
        }

        // 1c. Determine active month
        $month = $reqMonth ? (int) $reqMonth : (int) date('m');

        // 2. Filter orders in PHP
        $orders = $allOrders->filter(function ($o) use ($status, $period, $reqDate, $year, $month) {
            // Status filter
            if ($status === 'paid' && $o->payment_status !== 'paid') return false;
            if ($status === 'pending' && $o->payment_status !== 'paid') {
                // keep non-paid only
            } elseif ($status === 'pending') return false;

            $dt = $o->created_at;

            if ($period === 'specific_date' && !empty($reqDate)) {
                return $dt->format('Y-m-d') === $reqDate;
            } elseif ($period === 'daily') {
                if ($year !== 'all' && (int) $dt->format('Y') !== $year) return false;
                if ($month && (int) $dt->format('m') !== $month) return false;
            } elseif ($period === 'monthly') {
                if ($year !== 'all' && (int) $dt->format('Y') !== $year) return false;
            }
            // yearly: no filter, show all years

            return true;
        })->values();

        // 3. Group breakdown in PHP
        $grouped = [];
        foreach ($orders as $o) {
            $dt = $o->created_at;
            if ($period === 'specific_date') {
                $key   = $dt->format('Y-m-d H:00');
                $label = $dt->format('h:00 A');
            } elseif ($period === 'daily') {
                $key   = $dt->format('Y-m-d');
                $label = $dt->format('d M Y (D)');
            } elseif ($period === 'yearly') {
                $key   = $dt->format('Y');
                $label = 'Year ' . $dt->format('Y');
            } else {
                $key   = $dt->format('Y-m');
                $label = $dt->format('F Y');
            }

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'label'            => $label,
                    'raw_key'          => $key,
                    'total_orders'     => 0,
                    'total_revenue'    => 0.0,
                    'verified_revenue' => 0.0,
                    'pending_revenue'  => 0.0,
                ];
            }

            $amount = (float) $o->net_amount;
            $grouped[$key]['total_orders']++;
            $grouped[$key]['total_revenue'] += $amount;
            if ($o->payment_status === 'paid') {
                $grouped[$key]['verified_revenue'] += $amount;
            } else {
                $grouped[$key]['pending_revenue'] += $amount;
            }
        }

        $breakdown = array_values($grouped);

        // 4. Overall summary
        $totalRev    = $orders->sum('net_amount');
        $verifiedRev = $orders->where('payment_status', 'paid')->sum('net_amount');
        $pendingRev  = $orders->where('payment_status', '!=', 'paid')->sum('net_amount');
        $totalCount  = $orders->count();

        $summary = [
            'total_orders'    => $totalCount,
            'total_revenue'   => (float) $totalRev,
            'verified_revenue'=> (float) $verifiedRev,
            'pending_revenue' => (float) $pendingRev,
            'avg_order_value' => $totalCount > 0 ? round((float) $totalRev / $totalCount, 2) : 0,
        ];

        // 5. Top products
        $productSales = [];
        foreach ($orders as $o) {
            foreach ($o->items as $item) {
                $pName = $item->product_name ?: 'Unnamed Product';
                if (!isset($productSales[$pName])) {
                    $productSales[$pName] = ['product_name' => $pName, 'total_qty' => 0, 'total_sales' => 0.0];
                }
                $productSales[$pName]['total_qty']   += (int) $item->qty;
                $productSales[$pName]['total_sales'] += (float) $item->total_price;
            }
        }
        usort($productSales, fn($a, $b) => $b['total_sales'] <=> $a['total_sales']);
        $topProducts = array_slice($productSales, 0, 5);

        return response()->json([
            'period'          => $period,
            'date'            => $reqDate,
            'year'            => $year,
            'month'           => $month,
            'summary'         => $summary,
            'breakdown'       => $breakdown,
            'top_products'    => $topProducts,
            'available_years' => $availableYears,
            'orders'          => $orders->map(fn($o) => [
                'id'             => $o->id,
                'order_number'   => $o->order_number,
                'name'           => $o->name,
                'phone'          => $o->phone,
                'net_amount'     => (float) $o->net_amount,
                'payment_status' => $o->payment_status,
                'order_status'   => $o->order_status,
                'created_at'     => $o->created_at ? $o->created_at->format('d M Y, h:i A') : '',
            ]),
        ]);
    }


    /**
     * Customer Directory & Order History Insights.
     */
    public function customers(Request $request)
    {
        $search = trim($request->query('search', ''));

        $orders = Order::orderBy('created_at', 'desc')->get();

        $grouped = [];
        foreach ($orders as $o) {
            $phoneKey = trim($o->phone ?: ($o->email ?: ('id_' . $o->id)));
            
            if (!isset($grouped[$phoneKey])) {
                $grouped[$phoneKey] = [
                    'customer_key' => $phoneKey,
                    'name' => $o->name,
                    'phone' => $o->phone,
                    'email' => $o->email,
                    'city' => $o->city ?: ($o->town ?: 'N/A'),
                    'district' => $o->district,
                    'state' => $o->state,
                    'address' => $o->address,
                    'total_orders' => 0,
                    'total_spent' => 0,
                    'verified_spent' => 0,
                    'last_order_date' => $o->created_at ? $o->created_at->format('Y-m-d H:i') : 'N/A',
                    'last_order_number' => $o->order_number,
                    'orders' => [],
                ];
            }

            $grouped[$phoneKey]['total_orders'] += 1;
            $grouped[$phoneKey]['total_spent'] += (float)$o->net_amount;
            if ($o->payment_status === 'paid') {
                $grouped[$phoneKey]['verified_spent'] += (float)$o->net_amount;
            }

            $grouped[$phoneKey]['orders'][] = [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'created_at' => $o->created_at ? $o->created_at->format('d M Y, h:i A') : 'N/A',
                'net_amount' => (float)$o->net_amount,
                'order_status' => $o->order_status,
                'payment_status' => $o->payment_status,
            ];
        }

        $customersList = array_values($grouped);

        if (!empty($search)) {
            $searchLower = mb_strtolower($search);
            $customersList = array_filter($customersList, function ($cust) use ($searchLower) {
                return str_contains(mb_strtolower($cust['name'] ?? ''), $searchLower) ||
                       str_contains(mb_strtolower($cust['phone'] ?? ''), $searchLower) ||
                       str_contains(mb_strtolower($cust['email'] ?? ''), $searchLower) ||
                       str_contains(mb_strtolower($cust['city'] ?? ''), $searchLower);
            });
            $customersList = array_values($customersList);
        }

        $totalCustomers = count($grouped);
        $repeatCustomers = count(array_filter($grouped, fn($c) => $c['total_orders'] > 1));
        $totalCustomerSpent = array_sum(array_column($grouped, 'total_spent'));

        return response()->json([
            'customers' => $customersList,
            'metrics' => [
                'total_customers' => $totalCustomers,
                'repeat_customers' => $repeatCustomers,
                'total_lifetime_spent' => $totalCustomerSpent,
                'avg_spent_per_customer' => $totalCustomers > 0 ? round($totalCustomerSpent / $totalCustomers, 2) : 0,
            ],
        ]);
    }
}
