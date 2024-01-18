<?php
ini_set('memory_limit', '-1');

$paramsColumnCount = 1;

$products_file = __DIR__ . '/../../imports/rs-online/getProducts.csv';
$prices_file   = __DIR__ . '/../../imports/rs-online/getPrices.csv';
$stok_file     = __DIR__ . '/../../imports/rs-online/getStoks.csv';
$params_file   = __DIR__ . '/../../imports/rs-online/getParameters.csv';


function updateParametrsHeaders () {
    global $paramsColumnCount;

    global $folder_path;
    global $params_file;

    $headerCells = [
        'Symbol'
    ];
    for ($i = 0; $i < $paramsColumnCount; $i++) {
        $headerCells = array_merge($headerCells, [
            'key' . ($i + 1),
            'value' . ($i + 1)
        ]);
    }
    
    $csv_data = file($params_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $readed_file = [];
    foreach ($csv_data as $row) {
        $readed_file[] = str_getcsv($row);
    }

    $readed_file[0] = $headerCells;
    writeCSV('rs-online/GetParameters', $readed_file);
    unset($readed_file);
    unset($headerCells);
    unset($csv_data);
    
    $wpai_uid_folders = [
        'getStoks.csv'      => '7d75d0c30cd161fe5938a7bef4e20cbc',
        'getProducts.csv'   => '121df5910752e2bbaa3e82211b932dd1',
        'getPrices.csv'     => '345afd1cdc48d742e5cc0d83efe3dffe',
        'getParameters.csv' => '96c45e9429be33582bc8f38bf5ebec28'
    ];

    importToWPAllImport($folder_path, 'rs', $wpai_uid_folders);
}

function rs_get_products ($categories) {
    global $paramsColumnCount;

    global $products_file;
    global $prices_file;
    global $stok_file;
    global $params_file;

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


    function rs_getProductsPage ($cat, $page = 1) {
        global $paramsColumnCount;
        // if ($page === 2) return;

        global $products_file;
        global $prices_file;
        global $stok_file;
        global $params_file;

        $query = array(
            'query' => '
            query getTerminalNodeFilterResultsQuery(
            $seoUrl: String!
            $locale: String!
            $searchTerm: String
            $searchType: SearchType
            $filters: [String]
            $page: Int
            $limit: Int
            $sortBy: String
            $sortType: SortType
            $displayCategoryContent: Boolean
            $newProducts: Boolean
            $soldTo: String
            ) {
            terminalNode(
                seoUrl: $seoUrl
                locale: $locale
                searchTerm: $searchTerm
                searchType: $searchType
                filters: $filters
                page: $page
                limit: $limit
                sortBy: $sortBy
                sortType: $sortType
                displayCategoryContent: $displayCategoryContent
                newProducts: $newProducts
                soldTo: $soldTo)
            {
                resultsList {
                pagination {
                    count
                    limit
                    page
                    lastPage
                }
                columnHeaders
                records {
                    id
                    name
                    image
                    productUrl
                    brandUrl
                    stockNumber
                    mpn
                    newProduct
                    breakPrice
                    rawBreakPrice
                    breakQuantity
                    uomMessage
                    specificationAttributes {
                    key
                    value
                    }
                }
                }
                appliedFilters {
                a_u_id: id
                a_u_label: label
                a_u_binCount: binCount
                a_u_type: type
                children {
                    a_u_c_id: id
                    a_u_c_label: label
                    a_u_c_binCount: binCount
                    a_u_c_type: type
                }
                }
                refinements {
                r_u_id: id
                r_u_label: label
                r_u_type: type
                children {
                    r_u_c_id: id
                    r_u_c_label: label
                    r_u_c_binCount: binCount
                    r_u_c_type: type
                }
                }
            }
        
            }
        ',
            "variables" => [
                "displayCategoryContent" => false,
                "filters" => [],
                "limit" => 100,
                "locale" => "uk",
                "newProducts" => false,
                "page" => $page,
                "searchTerm" => "",
                "searchType" => "Default",
                "seoUrl" => $cat['url'],
                "sortBy" => "",
                "sortType" => "ASC"
            ]
        );

        $products = rs_api($query);

        if (!isset($products -> data)) {
            // echo "[" . date("H:i:s d.m.Y", time()) . "] Oshibka - ne udalos zagruzit informaciu o tovarah.\n";
            return;
        }

        foreach ($products -> data -> terminalNode -> resultsList -> records as $product) {

            // Finding brand name
            $brand = "";
            foreach($product -> specificationAttributes as $param) {
                if ($param -> key === "P_brand") {
                    $brand = $param -> value;
                }
            }

            $tmpProduct = [
                $product -> stockNumber,
                '',
                '',
                $brand,
                $product -> name, // Description
                $cat['id'],
                $cat['name'],
                'https://media.rs-online.com/t_large,f_auto/' . $product -> image,
                'https://media.rs-online.com/t_medium,f_auto/' . $product -> image,
                '',
                '',
                '',
                '',
                '',
                '',
                'https://uk.rs-online.com' . $product -> productUrl,
                '',
                ''
            ];

            $tmpPrice = [
                $product -> stockNumber,
                'EN',
                'EUR',
                'NET',
                '',
                '',
                'VAT',
                1,
                0,
                ''
            ];

            $tmpStok = [
                $product -> stockNumber,
                '',
                0
            ];

            // Получаем цену в евро с немецкого сайта
            $deDataQuery = curl_init('https://de.rs-online.com' . $product -> productUrl);
            curl_setopt($deDataQuery, CURLOPT_RETURNTRANSFER, true);
            $document = curl_exec($deDataQuery);

            if (curl_errno($deDataQuery)) {
                curl_close($deDataQuery);
                continue;
            }

            $deQueryInfo = curl_getinfo($deDataQuery);

            if ($deQueryInfo["http_code"] === 404) {
                curl_close($deDataQuery);
                continue;
            }
            
            if ($deQueryInfo["http_code"] === 301) {
                $deDataQuery = curl_init($deQueryInfo["redirect_url"]);
                curl_setopt($deDataQuery, CURLOPT_RETURNTRANSFER, true);
                $document = curl_exec($deDataQuery);

                if (curl_errno($deDataQuery)) {
                    curl_close($deDataQuery);
                    continue;
                }

                $deQueryInfo = curl_getinfo($deDataQuery);
            }

            curl_close($deDataQuery);

            if ($deQueryInfo["http_code"] !== 200) {
                continue;
            }

            if ($document !== false) {
                if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $document, $matches)) {
                    unset($document);
                    $jsonStr = $matches[1];
                    $data = json_decode($jsonStr, true);
                    
                    $tmp = isset($data['props'])             ? $data['props']             : (object)[];
                    $tmp = isset($tmp['pageProps'])          ? $tmp['pageProps']          : (object)[];
                    $tmp = isset($tmp['articleResult'])      ? $tmp['articleResult']      : (object)[];
                    $tmp = isset($tmp['data'])               ? $tmp['data']               : (object)[];
                    $tmp = isset($tmp['article'])            ? $tmp['article']            : (object)[];
                    $tmpS = $tmp;

                    $tmp = isset($tmp['prices'])             ? $tmp['prices']             : (object)[];
                    $tmp = isset($tmp['priceBreaks'])        ? $tmp['priceBreaks']        : [];
                    $tmp = isset($tmp[0])                    ? $tmp[0]                    : (object)[];
                    $tmp = isset($tmp['roundedVatIncPrice']) ? $tmp['roundedVatIncPrice'] : null;

                    $tmpS = isset($tmpS['productAvailability']) ? $tmpS['productAvailability'] : (object)[];
                    $tmpS = isset($tmpS['productPageStockVolume']) ? $tmpS['productPageStockVolume'] : null;

                    if (!is_null($tmp)) {
                        $tmpPrice[8] = ceil(floatval($tmp));
                    }
    
                    if (!is_null($tmpS)) {
                        $tmpStok[2] = $tmpS;
                    }
                }
            }
            
            if ($tmpPrice[8] == 0) continue;
            if ($tmpStok[2] == 0) continue;

            writeCSVRow($products_file, $tmpProduct); unset($tmpProduct);
            writeCSVRow($prices_file, $tmpPrice); unset($tmpPrice);
            writeCSVRow($stok_file, $tmpStok); unset($tmpStok);

            $tmpColumnCount = 0;
            $paramRow = [
                $product -> stockNumber
            ];

            foreach ($product -> specificationAttributes as $param) {
                $paramRow[] = $param -> key;
                $paramRow[] = $param -> value;
                $tmpColumnCount++;
            }

            if ($paramsColumnCount < $tmpColumnCount) {
                $paramsColumnCount = $tmpColumnCount;
            }

            writeCSVRow($params_file, $paramRow);unset($paramRow);
        }

        updateParametrsHeaders();

        $pagination = $products -> data -> terminalNode -> resultsList -> pagination;
        unset($products);

        if ($pagination -> page < $pagination -> lastPage) {
            unset($pagination);
            rs_getProductsPage($cat, $page + 1);
        }
    }

    echo "[" . date("H:i:s d.m.Y", time()) . "] Parsing has start.\n";

    foreach ($categories as $i => $category) {
        // if ($i === 3) {
        //     echo "end\n";
        //     die();
        // }
        
        if (!($category['stockCount'] > 0)) {
            echo "[" . date("H:i:s d.m.Y", time()) . "] Category #$i is empty. $i of " . count($categories) . " done.\n";
            continue;
        }
        
        rs_getProductsPage($category);

        echo "[" . date("H:i:s d.m.Y", time()) . "] $i of " . count($categories) . " done.\n";
    }
}