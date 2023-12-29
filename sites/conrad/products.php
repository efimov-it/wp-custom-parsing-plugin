<?php

    $csv_data = file(__DIR__.'/../../imports/conrad/GetCategories.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    $products_file = __DIR__.'/../../imports/conrad/getProducts.csv';
    $params_file = __DIR__.'/../../imports/conrad/getParameters.csv';
    $prices_file = __DIR__.'/../../imports/conrad/getPrices.csv';
    $stok_file = __DIR__.'/../../imports/conrad/getStoks.csv';

    $paramsColumnCount = 1;

    writeCSVRow($products_file, [
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
    
    writeCSVRow($params_file, [
        'Symbol',
        'key',
        'value'
    ], true);
    
    writeCSVRow($prices_file, [
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
    
    writeCSVRow($stok_file, [
        'Symbol',
        'Unit',
        'Amount'
    ], true);

    function conradGetProducts ($category, $categoryId, $index = 0) {
        global $paramsColumnCount;

        $products_file = __DIR__.'/../../imports/conrad/getProducts.csv';
        $params_file = __DIR__.'/../../imports/conrad/getParameters.csv';
        $prices_file = __DIR__.'/../../imports/conrad/getPrices.csv';
        $stok_file = __DIR__.'/../../imports/conrad/getStoks.csv';

        $queryData = [
            "facetFilter" => [],
            "from" => $index,
            "globalFilter" => [
                [
                    "field" => "categoryId",
                    "type" => "TERM_OR",
                    "values" => [
                        $categoryId
                    ]
                ]
            ],
            "query" => "",
            "size" =>  200,
            "sort" => [],
            "disabledFeatures" => [
                "FIRST_LEVEL_CATEGORIES_ONLY"
            ],
            "enabledFeatures" => [
                "and_filters",
                "query_relaxation"
            ]
        ];

        $queryData = json_encode($queryData);
        $ch = curl_init();
        
        $apiKey = 'f3wpPFFGqzMPZuPRBFYFbYOjLpTFdUZAXT2bWUfN4sqZ3Jee';

        curl_setopt($ch, CURLOPT_URL, 'https://api.conrad.com/search/1/v3/facetSearch/com/en/b2b?apikey=' . $apiKey);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $queryData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($queryData)
        ));
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            return false;
        }
        curl_close($ch);

        // echo "Query status: " . curl_getinfo($ch)['http_code'] . ".\n\n";
        $data = json_decode($response);

        $priceAndStockQueryData = [
            "ns:inputArticleItemList" => [
                "#namespaces" => [
                    "ns" => "http://www.conrad.de/ccp/basit/service/article/priceandavailabilityservice/api"
                ],
                "articles" => []
            ]
        ];
        
        if (count($data -> hits) > 0) {
    
            foreach ($data -> hits as $product) {
                $priceAndStockQueryData["ns:inputArticleItemList"]["articles"][] = [
                    "articleID" => str_pad($product -> productId, 18, '0', STR_PAD_LEFT),
                    "calculatePrice" => true,
                    "checkAvailability" => true,
                    "findExclusions" => true,
                    "insertCode" => "62"
                ];
            }
    
            $priceAndStockQuery = curl_init('https://api.conrad.com/price-availability/4/HP_COM_B2B/facade?' .
                                            'apikey=' . $apiKey . '&' .
                                            'forceStorePrice=false&' .
                                            'overrideCalculationSchema=GROSS');
            curl_setopt($priceAndStockQuery, CURLOPT_POST, true);
            curl_setopt($priceAndStockQuery, CURLOPT_POSTFIELDS, json_encode($priceAndStockQueryData));
            curl_setopt($priceAndStockQuery, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($priceAndStockQuery, CURLOPT_HTTPHEADER, array(
                'Accept: application/json, text/plain, */*',
                'Content-Type: application/json'
            ));
    
            $priceAndStok = curl_exec($priceAndStockQuery);
            
            if ($priceAndStok) {
                $priceAndStok = json_decode($priceAndStok);

                if ($priceAndStok -> priceAndAvailabilityFacadeResponse -> globalStatus === 'SUCCESS') {
                    foreach ($data -> hits as $i => $product) {
                        $tmpPriceAndStok = NULL;
    
                        foreach($priceAndStok -> priceAndAvailabilityFacadeResponse -> priceAndAvailability as $ps) {
                            if (gettype($ps) === 'string') continue;
                            if ($product -> productId != intval($ps -> articleID)) continue;
                            $tmpPriceAndStok = $ps;
                            break;
                        }
    
                        if ($tmpPriceAndStok === NULL) continue;
                        if (!isset($tmpPriceAndStok -> availabilityStatus)) continue;
                        if ($tmpPriceAndStok -> availabilityStatus -> stockQuantity < 1) continue;
            
                        writeCSVRow($products_file, [
                            $product -> productId,
                            $product -> manufacturerId,
                            '',
                            $product -> brand -> name,
                            str_replace('<br>', ' ', $product -> title),
                            $categoryId,
                            $category[0],
                            $product -> image,
                            $product -> image,
                            '',
                            '',
                            '',
                            '',
                            '',
                            $product -> orderUnit,
                            '',
                            '',
                            ''
                        ]);
            
                        $tmpParams = [
                            $product -> productId
                        ];
            
                        $paramCount = 0;
                
                        foreach ($product -> technicalDetails as $param) {
                            if (isset($param -> values[0])) {
                                $tmpParams[] = $param -> displayName;
                                $tmpParams[] = $param -> values[0];
                                $paramCount++;
                            }
                        }
                        if ($paramCount > 0) writeCSVRow($params_file, $tmpParams);
    
                        if ($paramCount > $paramsColumnCount) $paramsColumnCount = $paramCount;
                        
                        writeCSVRow($prices_file, [
                            $product -> productId,
                            'EN',
                            $tmpPriceAndStok -> price -> currency,
                            '',
                            '',
                            $tmpPriceAndStok -> price -> vatPercentage,
                            'VAT',
                            $tmpPriceAndStok -> price -> price,
                            '',
                            ''
                        ]);
                        
                        writeCSVRow($stok_file, [
                            $product -> productId,
                            '',
                            $tmpPriceAndStok -> availabilityStatus -> stockQuantity
                        ]);
                    }
                }
            }
        }

        if ($index + 200 < $data -> meta -> total) {
            conradGetProducts($category, $categoryId, $index + 200);
        }
    }

    ini_set('memory_limit', '-1');

    foreach ($csv_data as $i => $row) {
        if ($i < 1) continue;

        $category = str_getcsv($row);
        
        if (strpos($category[1], 'https://www.conrad.com/en/') !== 0) {
            continue;
        }

        if (preg_match("/(\d+)\.html$/", $category[1], $matches)) {
            $categoryId = $category[1][26] . $matches[1];
        } else {
            continue;
        }

        // echo $i . " - " . $category[1] . "\n";

        conradGetProducts($category, $categoryId);

        // if ($i === 110) break;
    }
    
    $paramsHeaderCells = [
        'Symbol'
    ];
    for ($i = 0; $i < $paramsColumnCount; $i++) {
        $paramsHeaderCells[] = 'key' . ($i + 1);
        $paramsHeaderCells[] = 'value' . ($i + 1);
    }
    
    $csv_data = file($params_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $readed_file = [];
    foreach ($csv_data as $row) {
        $readed_file[] = str_getcsv($row);
    }

    $readed_file[0] = $paramsHeaderCells;
    writeCSV('conrad/getParameters', $readed_file);
?>