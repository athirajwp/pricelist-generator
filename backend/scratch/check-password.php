<?php

$hash = '$2y$12$s0yVTYHJUWmcWL5l6mn/IOxIsIvhpCY2ua8oURdV2Zx9yRz1l6ykq';

$passwords = [
    'admin123',
    'admin',
    'superadmin',
    'superadmin123',
    'password',
    'crackers',
    'crackers123',
    'demo',
    'demo123',
    'athiraj',
    'athiraj123',
    'crackersdemo',
    'crackerdemo',
    'crackerdemo123'
];

foreach ($passwords as $password) {
    if (password_verify($password, $hash)) {
        echo "Match found: '{$password}' matches the hash!\n";
        exit(0);
    }
}

echo "No match found among the common list.\n";
