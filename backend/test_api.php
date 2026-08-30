<?php
$res = file_get_contents('http://127.0.0.1:9000/api/pricelist/generate-puppeteer-pdf');
echo "API SUCCESS! PDF Bytes: " . strlen($res);
