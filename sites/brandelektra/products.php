<?php

writeCSVRow(__DIR__.'/../../imports/brandelektra/getProducts.csv', [
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

writeCSVRow(__DIR__.'/../../imports/brandelektra/getPrices.csv', [
    'Symbol',
    'Language',
    'Currency',
    'PriceType',
    'Unit',
    'VatRate',
    'VatType',
    'Amount',
    'PriceValue',
    'Special'
], true);

writeCSVRow(__DIR__.'/../../imports/brandelektra/getStoks.csv', [
    'Symbol',
    'Unit',
    'Amount'
], true);


function getElektraProducts ($products = []) {
    global $cookie;

    foreach ($products as $product) {
        $query = curl_init("https://b2b.brandelektra.com/api/item?query=" . $product -> Symbol . "&page=0&localeType=EN");
        curl_setopt($query, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($query, CURLOPT_HTTPHEADER, ["Cookie: $cookie"]);
    
        $response = curl_exec($query);
    
        if (curl_errno($query)) {
            echo "[" . date("H:i:s d.m.Y", time()) . "] Ошибка cURL - запрос товаров: " . curl_error($query);
        }
        curl_close($query);
        $response = json_decode($response);

        foreach($response -> items as $product) {
            if ($product -> price && $product -> stock -> availableQuantity) {
                writeCSVRow(__DIR__.'/../../imports/brandelektra/getProducts.csv', [
                    $product -> sku,
                    '',
                    '',
                    $product -> manufacturer,
                    $product -> title,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $product -> minimumOrderQuantity,
                    $product -> minimumOrderQuantity,
                    '',
                    $product -> unit,
                    'https://b2b.brandelektra.com/item/' . $product -> id,
                    '',
                    ''
                ]);
                
                writeCSVRow(__DIR__.'/../../imports/brandelektra/getPrices.csv', [
                    $product -> sku,
                    'EN',
                    'EUR',
                    'ExVAT',
                    '',
                    '',
                    'ExVAT',
                    1,
                    floatval($product -> price),
                    ''
                ]);
                
                writeCSVRow(__DIR__.'/../../imports/brandelektra/getStoks.csv', [
                    $product -> sku,
                    $product -> unit,
                    $product -> stock -> availableQuantity
                ]);
            }
        }
    }
}

?>