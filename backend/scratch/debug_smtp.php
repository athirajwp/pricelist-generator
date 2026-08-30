<?php
echo "1. Testing fsockopen to smtp.gmail.com:587...\n";
$fp = @fsockopen("smtp.gmail.com", 587, $errno, $errstr, 10);
if (!$fp) {
    echo "FAILED: $errstr ($errno)\n";
} else {
    echo "SUCCESS: Connected to smtp.gmail.com:587\n";
    $response = fgets($fp, 512);
    echo "Response: " . trim($response) . "\n";
    fclose($fp);
}

echo "\n2. Testing fsockopen to smtp.gmail.com:465 (SSL)...\n";
$fp2 = @fsockopen("ssl://smtp.gmail.com", 465, $errno, $errstr, 10);
if (!$fp2) {
    echo "FAILED: $errstr ($errno)\n";
} else {
    echo "SUCCESS: Connected to ssl://smtp.gmail.com:465\n";
    $response = fgets($fp2, 512);
    echo "Response: " . trim($response) . "\n";
    fclose($fp2);
}

echo "\n3. Testing Symfony Mailer DSN construction & connection...\n";
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "MAIL_MAILER: " . config('mail.default') . "\n";
echo "MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
echo "MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
echo "MAIL_USERNAME: " . config('mail.mailers.smtp.username') . "\n";
echo "MAIL_ENCRYPTION: " . config('mail.mailers.smtp.scheme') . " / " . config('mail.mailers.smtp.encryption') . "\n";
