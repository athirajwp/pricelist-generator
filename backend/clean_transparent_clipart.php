<?php
function makeBackgroundTransparent($inputFile, $outputBackend, $outputFrontend) {
    if (!file_exists($inputFile)) return;
    
    $img = imagecreatefrompng($inputFile);
    if (!$img) return;

    $w = imagesx($img);
    $h = imagesy($img);

    // Create a new truecolor image with alpha support
    $transparentImg = imagecreatetruecolor($w, $h);
    imagealphablending($transparentImg, false);
    imagesavealpha($transparentImg, true);
    $transparentColor = imagecolorallocatealpha($transparentImg, 255, 255, 255, 127);
    imagefill($transparentImg, 0, 0, $transparentColor);

    // Copy non-white / non-border pixels
    for ($x = 0; $x < $w; $x++) {
        for ($y = 0; $y < $h; $y++) {
            $rgba = imagecolorat($img, $x, $y);
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;

            // If color is near white or top brown crop bar (y < 12 and r > 100), skip it
            if (($r > 235 && $g > 235 && $b > 235) || ($y < 10 && $r > 120 && $g < 100)) {
                // Keep transparent
            } else {
                $color = imagecolorallocatealpha($transparentImg, $r, $g, $b, 0);
                imagesetpixel($transparentImg, $x, $y, $color);
            }
        }
    }

    imagepng($transparentImg, $outputBackend);
    imagepng($transparentImg, $outputFrontend);
    imagedestroy($img);
    imagedestroy($transparentImg);
}

makeBackgroundTransparent(
    __DIR__ . '/public/images/left_crackers.png',
    __DIR__ . '/public/images/left_crackers_clean.png',
    __DIR__ . '/../frontend/public/images/left_crackers_clean.png'
);

makeBackgroundTransparent(
    __DIR__ . '/public/images/right_crackers.png',
    __DIR__ . '/public/images/right_crackers_clean.png',
    __DIR__ . '/../frontend/public/images/right_crackers_clean.png'
);

echo "Cleaned left and right transparent clipart images successfully!\n";
