<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

$conn = DB::getDefaultConnection();
$dbName = config("database.connections.{$conn}.database");

echo "Default Conn: " . $conn . "\n";
echo "Database Name: " . $dbName . "\n";
echo "Category Count: " . Category::count() . "\n";
echo "Product Count: " . Product::count() . "\n";

// Test storefront API response logic
$categories = Category::with(['products' => function ($query) {
    $query->where('status', 'active')->orderBy('sort_order', 'asc');
}])
->where('status', 'active')
->orderBy('sort_order', 'asc')
->get();

$totalProdsInCategories = 0;
foreach ($categories as $cat) {
    $totalProdsInCategories += $cat->products->count();
}

echo "Total Products returned by Category relation: " . $totalProdsInCategories . "\n";
