<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Debug Script Started...<br/>";

$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorAutoload)) {
    die("<h2 style='color:red;'>FATAL ERROR: vendor/autoload.php does not exist! Please upload the vendor folder.</h2>");
}
echo "Step 2: vendor/autoload.php exists.<br/>";

try {
    require $vendorAutoload;
    echo "Step 3: vendor/autoload.php loaded successfully.<br/>";
} catch (Throwable $e) {
    die("<h2 style='color:red;'>FATAL ERROR loading vendor/autoload.php: " . $e->getMessage() . "</h2>");
}

$bootstrapApp = __DIR__ . '/../bootstrap/app.php';
if (!file_exists($bootstrapApp)) {
    die("<h2 style='color:red;'>FATAL ERROR: bootstrap/app.php does not exist!</h2>");
}
echo "Step 4: bootstrap/app.php exists.<br/>";

try {
    $app = require_once $bootstrapApp;
    echo "Step 5: Laravel App Bootstrapped successfully!<br/>";
} catch (Throwable $e) {
    die("<h2 style='color:red;'>FATAL ERROR bootstrapping app: " . $e->getMessage() . "</h2><pre>" . $e->getTraceAsString() . "</pre>");
}

try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "Step 6: Http Kernel instantiated successfully.<br/>";
    
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );
    echo "Step 7: Request handled successfully.<br/>";
    $response->send();
    $kernel->terminate($request, $response);
} catch (Throwable $e) {
    echo "<h2 style='color:red;'>FATAL ERROR during Request Handling: " . htmlspecialchars($e->getMessage()) . "</h2>";
    echo "<p><strong>File:</strong> " . $e->getFile() . " (Line " . $e->getLine() . ")</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
