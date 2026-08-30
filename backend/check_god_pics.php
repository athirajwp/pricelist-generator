<?php
$files = ['god.png', 'god_pic.png'];
foreach ($files as $file) {
    $path = __DIR__ . '/public/images/' . $file;
    if (file_exists($path)) {
        list($w, $h) = getimagesize($path);
        echo "$file: {$w}x{$h}\n";
    }
}
