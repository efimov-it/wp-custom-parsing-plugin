<?php

$categoriesUrls = [];

writeCSVRow(__DIR__.'/../../imports/farnell/GetCategories.csv', [
    'TotalProducts',
    'Id',
    'Depth',
    'ParentId',
    'Name',
    'SubTreeCount'
], true);

$i = 0;

do {
    if ($i === 0) {
        $proxy = getProxy();
    }
    else {
        $proxy = getNextProxy();
    }

    if ($i === 10) {
        echo "[" . date("H:i:s d.m.Y", time()) . "] Не удалось загрузить данные. Обновление прокси.\n\n";

        $i = 0;
        updateProxy();
        continue;
    }

    $i++;
    
    echo "[" . date("H:i:s d.m.Y", time()) . "] GetCategories | Попытка подключения #$i\n";
    echo "[" . date("H:i:s d.m.Y", time()) . "] Proxy: " . $proxy -> ip . ":" . $proxy -> port . "\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://uk.farnell.com/browse-for-products");
    curl_setopt($ch, CURLOPT_PROXY, $proxy -> ip);
    curl_setopt($ch, CURLOPT_PROXYPORT, $proxy -> port);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
    $mainResponse = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "[" . date("H:i:s d.m.Y", time()) . "] Ошибка Curl: " . curl_error($ch) . "\n\n";
    }
    curl_close($ch);
    
} while (!$mainResponse);

$document = str_get_html($mainResponse);

$categories = $document -> find('.categoryList');

foreach ($categories as $category) {
    $name = $category -> find('.catHeader a')[0];
    $productsCount = trim($category -> find('.catHeader span')[0] -> plaintext);

    $productsCount = str_replace('(', '', $productsCount);
    $productsCount = str_replace(')', '', $productsCount);
    $productsCount = str_replace(',', '', $productsCount);
    
    writeCSVRow(__DIR__.'/../../imports/farnell/GetCategories.csv', [
        $productsCount,
        '',
        '',
        '',
        trim($name -> plaintext),
        count($category -> find('.filterCategoryLevelOne ul'))
    ]);

    $categoriesUrls[] = $name -> href;

    $subCategories = $category -> find('.filterCategoryLevelOne ul li');

    foreach ($subCategories as $subCategory) {
        $tmpName = $subCategory -> find('a')[0];
        $tmpCount = trim($subCategory -> find('span')[0] -> plaintext);
        
        $tmpCount = str_replace('(', '', $tmpCount);
        $tmpCount = str_replace(')', '', $tmpCount);
        $tmpCount = str_replace(',', '', $tmpCount);

        writeCSVRow(__DIR__.'/../../imports/farnell/GetCategories.csv', [
            $tmpCount,
            '',
            '',
            '',
            trim($tmpName -> plaintext),
            0
        ]);

        $categoriesUrls[] = $tmpName -> href;
    }
}

?>