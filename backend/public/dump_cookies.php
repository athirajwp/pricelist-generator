<?php
$logData = sprintf(
    "[%s] COOKIES: %s\nHEADERS: %s\n\n",
    date('Y-m-d H:i:s'),
    json_encode($_COOKIE),
    json_encode(getallheaders())
);
file_put_contents(__DIR__ . '/../storage/logs/dump_cookies_debug.log', $logData, FILE_APPEND);

header('Content-Type: application/json');
echo json_encode([
    'cookies' => $_COOKIE,
    'headers' => getallheaders(),
    'session' => session_status()
]);
