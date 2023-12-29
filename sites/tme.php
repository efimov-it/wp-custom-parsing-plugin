<?php

set_time_limit(0);

echo "[" . date("H:i:s d.m.Y", time()) . "] TME parsing has started...\n";

require_once __DIR__.'/../index.php';

$folder_path = __DIR__ . '/../imports/tme';
if (!file_exists($folder_path)) {
    mkdir($folder_path, 0777, true);
}

require_once 'tme/get-categories.php';
writeCSV('tme/GetCategories', TME_get_categories());

// Получаем актуальные артикулы
require_once 'tme/get-symbols.php';
$symbolsList = TME_get_symbols();
writeCSV('tme/GetSymbols', $symbolsList);

// Выгрузка продуктов
require_once 'tme/get-products.php';
TME_get_products($symbolsList);

$wpai_uid_folders = [
    'getPricesAndStocks.csv' => '4a756b2d8072834955005db3c06329fa',
    'getProducts.csv'        => 'a5c931d58e851c742a5ac14fff72fada',
    'getParameters.csv'      => '728a7997ff23383de79386af4611786c'
];

importToWPAllImport($folder_path, '', $wpai_uid_folders, true);

echo "[" . date("H:i:s d.m.Y", time()) . "] TME parsing done!\n";