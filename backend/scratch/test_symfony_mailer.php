<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

echo "--- Direct Symfony Mailer Test (587 TLS) ---\n";
try {
    $transport = new EsmtpTransport('smtp.gmail.com', 587, false);
    $transport->setUsername('athiraj.vnr@gmail.com');
    $transport->setPassword('qbvg pfmj urol tsvv');
    
    $mailer = new Mailer($transport);
    
    $email = (new Email())
        ->from('athiraj.vnr@gmail.com')
        ->to('athiraj.vnr@gmail.com')
        ->subject('Test Email 587')
        ->text('Testing 587 TLS from Symfony Mailer');
        
    echo "Sending via 587...\n";
    $mailer->send($email);
    echo "SUCCESS via 587!\n";
} catch (\Throwable $e) {
    echo "ERROR via 587: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n--- Direct Symfony Mailer Test (465 SSL) ---\n";
try {
    $transport = new EsmtpTransport('smtp.gmail.com', 465, true);
    $transport->setUsername('athiraj.vnr@gmail.com');
    $transport->setPassword('qbvg pfmj urol tsvv');
    
    $mailer = new Mailer($transport);
    
    $email = (new Email())
        ->from('athiraj.vnr@gmail.com')
        ->to('athiraj.vnr@gmail.com')
        ->subject('Test Email 465')
        ->text('Testing 465 SSL from Symfony Mailer');
        
    echo "Sending via 465...\n";
    $mailer->send($email);
    echo "SUCCESS via 465!\n";
} catch (\Throwable $e) {
    echo "ERROR via 465: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
