<?php
function removeCheckerboardAndHalo($srcPath, $destPath) {
    if (!file_exists($srcPath)) return false;
    $data = file_get_contents($srcPath);
    $src = @imagecreatefromstring($data);
    if (!$src) return false;

    $w = imagesx($src);
    $h = imagesy($src);

    $dst = imagecreatetruecolor($w, $h);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);

    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefill($dst, 0, 0, $transparent);

    for ($x = 0; $x < $w; $x++) {
        for ($y = 0; $y < $h; $y++) {
            $rgba = imagecolorat($src, $x, $y);
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            $a = ($rgba >> 24) & 0x7F;

            // Detect checkerboard (gray/white squares) & background glow:
            // Checkerboard gray is ~ (180-210, 180-210, 180-210), white is ~ (>230, >230, >230)
            // Golden deity pixels have high Red and Green (yellow/gold/orange) and LOWER Blue! (r > b + 20)
            $isGrayscale = (abs($r - $g) < 15 && abs($g - $b) < 15);
            $isLightBackground = ($r > 170 && $g > 170 && $b > 170 && $isGrayscale);
            $isVeryLightOrWhite = ($r > 215 && $g > 215 && $b > 215);

            // If it's part of the background / checkerboard / white halo:
            if ($isLightBackground || $isVeryLightOrWhite) {
                imagesetpixel($dst, $x, $y, $transparent);
            } else {
                // Keep rich gold/brown/red deity colors
                $color = imagecolorallocatealpha($dst, $r, $g, $b, $a);
                imagesetpixel($dst, $x, $y, $color);
            }
        }
    }

    imagepng($dst, $destPath);
    imagedestroy($src);
    imagedestroy($dst);
    echo "Cleaned image saved to $destPath\n";
    return true;
}

$artifactDir = 'C:/Users/Athi/.gemini/antigravity-ide/brain/3cb3a8f7-b27f-4f13-b021-93c300387831';
$destDir = __DIR__ . '/public/images';

removeCheckerboardAndHalo($artifactDir . '/god_vinayagar_1788038904977.png', $destDir . '/god_vinayagar.png');
removeCheckerboardAndHalo($artifactDir . '/god_murugan_1788038923200.png', $destDir . '/god_murugan.png');
removeCheckerboardAndHalo($artifactDir . '/god_perumal_1788038953889.png', $destDir . '/god_perumal.png');
removeCheckerboardAndHalo($destDir . '/god.png', $destDir . '/god_lakshmi.png');
removeCheckerboardAndHalo($destDir . '/god_pic.png', $destDir . '/god_default.png');
