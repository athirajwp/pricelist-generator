<?php
// Script to generate high-resolution Goddess Lakshmi divine clipart image
$width = 600;
$height = 800;

$im = imagecreatetruecolor($width, $height);
imagealphablending($im, false);
imagesavealpha($im, true);

$transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
imagefill($im, 0, 0, $transparent);

imagealphablending($im, true);

// Colors
$goldDark   = imagecolorallocate($im, 180, 115, 15);
$goldMain   = imagecolorallocate($im, 245, 180, 25);
$goldLight  = imagecolorallocate($im, 255, 225, 100);
$goldWhite  = imagecolorallocate($im, 255, 245, 190);
$pinkMain   = imagecolorallocate($im, 235, 90, 150);
$pinkDark   = imagecolorallocate($im, 180, 40, 100);
$pinkLight  = imagecolorallocate($im, 255, 160, 200);
$redMain    = imagecolorallocate($im, 210, 30, 40);
$greenMain  = imagecolorallocate($im, 40, 160, 80);

// Draw Glowing Aura Halo in Background
for ($r = 180; $r > 0; $r -= 3) {
    $alpha = (int)(127 - (127 * ($r / 180) * 0.4));
    if ($alpha > 127) $alpha = 127;
    $haloCol = imagecolorallocatealpha($im, 255, 200, 50, $alpha);
    imagefilledellipse($im, 300, 320, $r * 2.2, $r * 2.2, $haloCol);
}

// Draw Pink Lotus Base (Petals)
for ($i = 0; $i < 12; $i++) {
    $angle = deg2rad($i * 30);
    $px = 300 + cos($angle) * 140;
    $py = 580 + sin($angle) * 50;
    imagefilledellipse($im, (int)$px, (int)$py, 110, 60, $pinkDark);
    imagefilledellipse($im, (int)$px, (int)$py - 4, 100, 52, $pinkMain);
    imagefilledellipse($im, (int)$px, (int)$py - 8, 85, 42, $pinkLight);
}
// Central Lotus Pad
imagefilledellipse($im, 300, 560, 220, 70, $goldMain);
imagefilledellipse($im, 300, 558, 200, 60, $goldLight);

// Goddess Lakshmi Crown (Kiritam)
$crownPoints = [
    300, 120, // top peak
    340, 200,
    350, 260,
    250, 260,
    260, 200
];
imagefilledpolygon($im, $crownPoints, $goldMain);
imagepolygon($im, $crownPoints, $goldDark);
imagefilledellipse($im, 300, 160, 30, 30, $redMain);
imagefilledellipse($im, 300, 220, 40, 20, $goldWhite);

// Goddess Face / Head Halo
imagefilledellipse($im, 300, 300, 140, 150, $goldLight);
imagefilledellipse($im, 300, 300, 125, 135, $goldWhite);
// Tilak
imagefilledellipse($im, 300, 275, 12, 24, $redMain);

// Goddess Body / Saree
$bodyPoints = [
    300, 340,
    420, 540,
    180, 540
];
imagefilledpolygon($im, $bodyPoints, $redMain);
imagefilledellipse($im, 300, 450, 200, 140, $goldMain);

// Four Upper & Lower Arms
// Upper Right Arm holding Lotus
imagefilledellipse($im, 400, 360, 50, 90, $goldMain);
imagefilledellipse($im, 430, 310, 45, 45, $pinkMain);
imagefilledellipse($im, 430, 310, 35, 35, $pinkLight);

// Upper Left Arm holding Lotus
imagefilledellipse($im, 200, 360, 50, 90, $goldMain);
imagefilledellipse($im, 170, 310, 45, 45, $pinkMain);
imagefilledellipse($im, 170, 310, 35, 35, $pinkLight);

// Lower Right Arm (Abhaya Mudra / Gold Coins Shower)
imagefilledellipse($im, 380, 450, 45, 80, $goldMain);

// Lower Left Arm (Varada Mudra)
imagefilledellipse($im, 220, 450, 45, 80, $goldMain);

// Gold Coins Falling from Hands
for ($k = 0; $k < 35; $k++) {
    $cx = rand(360, 440);
    $cy = rand(460, 680);
    $sz = rand(14, 22);
    imagefilledellipse($im, $cx, $cy, $sz, $sz, $goldDark);
    imagefilledellipse($im, $cx, $cy - 2, $sz - 3, $sz - 3, $goldMain);
    imagefilledellipse($im, $cx, $cy - 4, $sz - 8, $sz - 8, $goldWhite);
}

// Gold Coins Pile at Lotus Base
for ($p = 0; $p < 45; $p++) {
    $px = rand(220, 380);
    $py = rand(620, 720);
    $psz = rand(16, 26);
    imagefilledellipse($im, $px, $py, $psz, $psz * 0.7, $goldDark);
    imagefilledellipse($im, $px, $py - 2, $psz - 4, ($psz - 4) * 0.7, $goldMain);
}

// Save image
$destPath = __DIR__ . '/public/images/god_lakshmi.png';
imagepng($im, $destPath);
imagedestroy($im);

echo "Goddess Lakshmi divine clipart created at $destPath\n";
