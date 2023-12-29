<?php

ini_set('memory_limit', '-1');

foreach ($categoriesUrls as $i => $categoryUrl) {
    writeCSVRow(__DIR__.'/../../imports/farnell/getProducts.csv', [
        'Symbol',
        'CustomerSymbol',
        'OriginalSymbol',
        'Producer',
        'Description',
        'CategoryId',
        'Category',
        'Photo',
        'Thumbnail',
        'Weight',
        'WeightUnit',
        'SuppliedAmount',
        'MinAmount',
        'Multiples',
        'Unit',
        'ProductInformationPage',
        'Guarantee',
        'OfferId'
    ], true);
    
    $i = 0;
    $pageCount = 1;
    $curPage = 0;
    
    do {
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
            
            echo "[" . date("H:i:s d.m.Y", time()) . "] GetProducts | Попытка подключения #$i\n";
            echo "[" . date("H:i:s d.m.Y", time()) . "] Proxy: " . $proxy -> ip . ":" . $proxy -> port . "\n";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $categoryUrl);
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

    } while ($curPage < $pageCount);


}

?>