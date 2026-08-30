<?php
function makeWhiteTransparent($srcPath, $destPath) {
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
            
            // If near white, make transparent
            if ($r > 220 && $g > 220 && $b > 220) {
                imagesetpixel($dst, $x, $y, $transparent);
            } else {
                $color = imagecolorallocatealpha($dst, $r, $g, $b, 0);
                imagesetpixel($dst, $x, $y, $color);
            }
        }
    }
    
    imagepng($dst, $destPath);
    imagedestroy($src);
    imagedestroy($dst);
    echo "Successfully converted to transparent PNG: $destPath\n";
    return true;
}

$artifactDir = 'C:/Users/Athi/.gemini/antigravity-ide/brain/3cb3a8f7-b27f-4f13-b021-93c300387831';
$destDir = __DIR__ . '/public/images';

makeWhiteTransparent($artifactDir . '/god_vinayagar_1788038904977.png', $destDir . '/god_vinayagar.png');
makeWhiteTransparent($artifactDir . '/god_murugan_1788038923200.png', $destDir . '/god_murugan.png');
makeWhiteTransparent($artifactDir . '/god_perumal_1788038953889.png', $destDir . '/god_perumal.png');
makeWhiteTransparent($destDir . '/god.png', $destDir . '/god_lakshmi.png');
makeWhiteTransparent($destDir . '/god_pic.png', $destDir . '/god_default.png');
