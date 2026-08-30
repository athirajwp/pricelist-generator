<?php
$srcFile = 'C:/Users/Athi/.gemini/antigravity-ide/brain/3cb3a8f7-b27f-4f13-b021-93c300387831/media__1788028155385.jpg';

if (!file_exists($srcFile)) {
    die("Source file does not exist\n");
}

list($w, $h) = getimagesize($srcFile);
echo "Source Image Dimensions: $w x $h\n";

$srcImg = imagecreatefromjpeg($srcFile);

// Crop the deity mandala center portion (approx 15% to 85% X, 42% to 76% Y)
$cropX = (int)($w * 0.15);
$cropY = (int)($h * 0.41);
$cropW = (int)($w * 0.70);
$cropH = (int)($h * 0.35);

$cropRect = ['x' => $cropX, 'y' => $cropY, 'width' => $cropW, 'height' => $cropH];
$cropped = imagecrop($srcImg, $cropRect);

if ($cropped !== false) {
    // Save cropped image
    $dstBackend = __DIR__ . '/public/images/god_pic.png';
    $dstFrontend = __DIR__ . '/../frontend/public/images/god_pic.png';
    
    imagepng($cropped, $dstBackend);
    imagepng($cropped, $dstFrontend);
    imagedestroy($cropped);
    echo "Cropped god_pic.png successfully saved to both backend and frontend!\n";
} else {
    echo "Failed to crop image.\n";
}
imagedestroy($srcImg);
