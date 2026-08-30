<?php
$srcFile = 'C:/Users/Athi/.gemini/antigravity-ide/brain/3cb3a8f7-b27f-4f13-b021-93c300387831/media__1788028155385.jpg';

if (!file_exists($srcFile)) {
    die("Source file does not exist\n");
}

list($w, $h) = getimagesize($srcFile);
echo "Source Image Dimensions: $w x $h\n";

$srcImg = imagecreatefromjpeg($srcFile);

// Left crackers illustration (Flowerpot + Bombs at bottom-left of card: approx 0% to 32% X, 77% to 92% Y)
$cropLeft = [
    'x' => (int)($w * 0.02),
    'y' => (int)($h * 0.77),
    'width' => (int)($w * 0.28),
    'height' => (int)($h * 0.14)
];
$croppedLeft = imagecrop($srcImg, $cropLeft);

if ($croppedLeft !== false) {
    imagepng($croppedLeft, __DIR__ . '/public/images/left_crackers.png');
    imagepng($croppedLeft, __DIR__ . '/../frontend/public/images/left_crackers.png');
    imagedestroy($croppedLeft);
    echo "Saved left_crackers.png successfully!\n";
}

// Right rockets illustration (Rocket at bottom-right of card: approx 74% to 98% X, 76% to 93% Y)
$cropRight = [
    'x' => (int)($w * 0.74),
    'y' => (int)($h * 0.76),
    'width' => (int)($w * 0.24),
    'height' => (int)($h * 0.15)
];
$croppedRight = imagecrop($srcImg, $cropRight);

if ($croppedRight !== false) {
    imagepng($croppedRight, __DIR__ . '/public/images/right_crackers.png');
    imagepng($croppedRight, __DIR__ . '/../frontend/public/images/right_crackers.png');
    imagedestroy($croppedRight);
    echo "Saved right_crackers.png successfully!\n";
}

imagedestroy($srcImg);
