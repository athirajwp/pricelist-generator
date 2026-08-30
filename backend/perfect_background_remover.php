<?php
function removeBackgroundFloodFill($srcPath, $destPath) {
    if (!file_exists($srcPath)) {
        echo "File not found: $srcPath\n";
        return false;
    }
    $raw = file_get_contents($srcPath);
    $src = @imagecreatefromstring($raw);
    if (!$src) return false;

    $w = imagesx($src);
    $h = imagesy($src);

    $dst = imagecreatetruecolor($w, $h);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);

    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefill($dst, 0, 0, $transparent);

    // Copy src pixels to dst
    for ($x = 0; $x < $w; $x++) {
        for ($y = 0; $y < $h; $y++) {
            $rgba = imagecolorat($src, $x, $y);
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            $a = ($rgba >> 24) & 0x7F;

            // Check if pixel is golden deity vs background/checkerboard/white halo
            // Golden deity has high Red and Green, but low Blue (Gold/Bronze: R:180-255, G:140-220, B:20-130)
            // Checkerboard/White halo has high Blue and low color saturation:
            $isGoldDeity = ($r > 90 && $g > 60 && ($r - $b) > 35 && ($g - $b) > 10);
            
            // Grayscale (checkerboard gray/white):
            $isGrayscale = (abs($r - $g) < 25 && abs($g - $b) < 25 && abs($r - $b) < 25);
            $isWhiteOrLightHalo = ($r > 160 && $g > 160 && $b > 150);

            if ($isGoldDeity && !$isGrayscale) {
                // Keep the golden deity pixel
                $color = imagecolorallocatealpha($dst, $r, $g, $b, 0);
                imagesetpixel($dst, $x, $y, $color);
            } else {
                // Make transparent
                imagesetpixel($dst, $x, $y, $transparent);
            }
        }
    }

    imagepng($dst, $destPath);
    imagedestroy($src);
    imagedestroy($dst);
    echo "Perfect background removal completed for $destPath\n";
    return true;
}

$artifactDir = 'C:/Users/Athi/.gemini/antigravity-ide/brain/3cb3a8f7-b27f-4f13-b021-93c300387831';
$destDir = __DIR__ . '/public/images';

removeBackgroundFloodFill($artifactDir . '/god_vinayagar_1788038904977.png', $destDir . '/god_vinayagar.png');
removeBackgroundFloodFill($artifactDir . '/god_murugan_1788038923200.png', $destDir . '/god_murugan.png');
removeBackgroundFloodFill($artifactDir . '/god_perumal_1788038953889.png', $destDir . '/god_perumal.png');
