<?php

require_once __DIR__.'/../index.php';

$folder_path = __DIR__ . '/../imports/conrad';
if (!file_exists($folder_path)) {
    mkdir($folder_path, 0777, true);
}

require_once 'conrad/categories.php';
conradGetCategories();

require_once 'conrad/products.php';

$wpai_uid_folders = [
    'getStoks.csv'      => '8401c67c7dc3b97ce2104a286000b64b',
    'getProducts.csv'   => '2a6aebc9ffcf16b77fa40d8f2ba1c7c6',
    'getPrices.csv'     => '9c691212dcef12e8610f23b16b885e05',
    'getParameters.csv' => '69864a25a0fed21055e0fa6c8787e7af'
];

importToWPAllImport($folder_path, 'conrad', $wpai_uid_folders, true);

echo 'Conrad parsing done!';

?>