<?php

require_once __DIR__.'/../index.php';

$folder_path = __DIR__ . '/../imports/verical';
if (!file_exists($folder_path)) {
    mkdir($folder_path, 0777, true);
}

$ch = curl_init("https://www.verical.com/static/generated/master.json?format=json");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$mainResponse = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Ошибка Curl: ' . curl_error($ch);
}
curl_close($ch);

if (!$mainResponse) die();

$main = json_decode($mainResponse);

$categories = $main -> categoryTree -> categories;

require_once 'verical/categories.php';
vericalCategories($categories);

require_once 'verical/products.php';

echo 'Verical parsing done!';

?>