<?php

ini_set('memory_limit', '-1');

$csv_data = file(__DIR__.'/../../imports/verical/GetCategories.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$paramsCount = 1;

$products_file = __DIR__.'/../../imports/verical/getProducts.csv';
$params_file = __DIR__.'/../../imports/verical/getParameters.csv';
$prices_file = __DIR__.'/../../imports/verical/getPrices.csv';
$stok_file = __DIR__.'/../../imports/verical/getStoks.csv';

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

function verical_rec_prod_get ($category, $startIndex = 0) {

    global $paramsCount;

    global $products_file;
    global $params_file;
    global $prices_file;
    global $stok_file;

    $queryData = [
        "parameters" => [
            [
                "key" => "search_term",
                "values" => [
                    "*"
                ]
            ],
            [
                "key" => "part_category_id",
                "values" => [
                    $category
                ]
            ],
            [
                "key" => "start_index",
                "values" => [
                    $startIndex
                ]
            ],
            [
                "key" => "quantity_min",
                "values" => [
                    1
                ]
            ],
            [
                "key" => "facet_field",
                "values" => [
                    "manufacturer_id",
                    "category_unique"
                ]
            ]
        ]
    ];

    $queryData = json_encode($queryData);

    $ch_p = curl_init('https://www.verical.com/server-webapp/api/rest/search/parametric?format=json');
    curl_setopt($ch_p, CURLOPT_POST, 1);
    curl_setopt($ch_p, CURLOPT_POSTFIELDS, $queryData);
    curl_setopt($ch_p, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_p, CURLOPT_ENCODING, 'UTF-8');
    
    curl_setopt($ch_p, CURLOPT_HTTPHEADER, array(
        'Accept: application/json, text/plain, */*',
        'Accept-Encoding: gzip, deflate, br',
        'Content-Type: application/json',
        'Content-Length: ' . strlen($queryData),
        // $cookie
    ));
    
    $response = curl_exec($ch_p);
    
    $http_code = curl_getinfo($ch_p)['http_code'];
       
    if (curl_errno($ch_p)) {
        echo "[" . date("H:i:s d.m.Y", time()) . "] Setevaya oshibka. CURL error number - " . curl_errno($ch_p) . ". Kategoriya $category.\n";
        curl_close($ch_p);
        return;
    }
    curl_close($ch_p);

    if ($http_code !== 200) {
        echo "[" . date("H:i:s d.m.Y", time()) . "] Setevaya oshibka. HTTP kod - $http_code. Kategoriya $category.\n";
        return;
    }

    $data = json_decode($response);

    if (!isset($data -> records)) return;

    foreach ($data -> records as $product) {

        $pageUrl = "https://www.verical.com/pd/";

        $mn = $product -> manufacturerName;
        $mn = str_replace("-", "", $mn);
        $mn = str_replace("  ", "-", $mn);
        $mn = str_replace(" ", "-", $mn);
        $mn = str_replace("/", "-", $mn);

        $pageUrl .= strtolower($mn) . "-" . strtolower($product -> mpn) . "-" . $product -> partId;

        $getCookieQuery = curl_init($pageUrl);

        curl_setopt($getCookieQuery, CURLOPT_HEADER, true);
        curl_setopt($getCookieQuery, CURLOPT_RETURNTRANSFER, true);
        
        $header = curl_exec($getCookieQuery);
        
        $headerLength = curl_getinfo($getCookieQuery)['header_size'];
        
        $header = substr($header, 0, $headerLength);
        
        $header = explode("\n", $header);
        $tmpHeader = [];
        foreach($header as $i => $param) {
            if ($i === 0) continue;
            $param = trim($param);
            if ($param) {
                $tmp = explode(": ", $param);
        
                if ($tmp[0] === 'set-cookie') {
                    $tmpString = $tmp[1];
                    
                    if (strpos($tmpString, 'bm_mi=') !== FALSE) continue;
            
                    $tmpString = substr($tmpString, 0, strpos($tmpString, 'Domain='));
            
                    // echo $tmpString . "\n\n";
        
                    if (isset($tmpHeader[$tmp[0]])) {
                        $tmpHeader[$tmp[0]] = $tmpHeader[$tmp[0]] . "" . $tmpString;
                    }
                    else {
                        $tmpHeader[$tmp[0]] = $tmpString;
                    }
                }
            }
        }
        
        $cookie = "Cookie: SERVERID=.30-81; " . $tmpHeader["set-cookie"];

        echo $cookie; die();

        $url = 'https://www.verical.com/server-webapp/api/getCatalogItems?' .
                                                        'includeAlternates=false&' .
                                                        'mpnIDs=' . $product -> partId . '&' .
                                                        'format=json&' .
                                                        'vipCacheBust=';

        $ch_p = curl_init($url);
        curl_setopt($ch_p, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_p, CURLOPT_ENCODING, 'UTF-8');
        
        curl_setopt($ch_p, CURLOPT_HTTPHEADER, array(
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br',
            'Content-Type: application/json',
            'Content-Length: 0',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36',
            // $cookie
            'Cookie: SERVERID=.30-81; bm_sz=3B65DD9683C06AFB6A65D48A90F8473B~YAAQJPlVaDNermuLAQAAGolvixV+2Wm39ssZVUICcnP5gbEs5WmhXFJu8oBm38aY4n+HReML8FF5lmKcl8WkRJM4fG87LFLhXgqEWt477RfVW4FwZJL+r0IwL1gwO9JWS1hDFA7QUw6q7OpTVuT1Vb4aAJfJK1roEYbLrgmj7mm9KcgGadlHYZIrHOsJ2MZgsY4zzIUuW2CvtKWwaX4TMN2zXIQG5G2cStKi4YUBlYMMNLoDVgUx4yFn3ZBkQWxW4aKHi/iyK7dE0r8jHn6SckKozU5Cc/Q5X8D8f3DP0CsPdRk3~4474160~4403270; _gid=GA1.2.872488372.1698851429; ga_cid=2030764787.1698851429; dfp=dc3c0ade99c95f8098261e946591310d; fidelius=64382a4e-ee26-4197-9136-840412d14a57; alohomora=57426c85-be6d-4249-b9a3-817d4da1e73e; _gcl_au=1.1.277370738.1698851435; gaVisitorType=new; _fbp=fb.1.1698851438512.619266797; liveagent_oref=https://www.verical.com/products/electromechanical-692/lamps-incandescent-702/lamps-761/; liveagent_sid=a0e6e85c-dbc0-4ea3-99e9-dbed24f2234d; liveagent_vc=2; liveagent_ptid=a0e6e85c-dbc0-4ea3-99e9-dbed24f2234d; JSESSIONID=4316C1C46DBCBC75D6CFF6ECFE3D4739; bm_mi=DD67F44E0C66245595E95B0D06C485EE~YAAQtjQQYC+nNGCLAQAA71aNixVj+pUebnqIHQ6em0tJED/q4Hr3kJIcf/Y2DYkBzy8FUIpXyvBsndLIys2hxnZ5PdP7SVj0xqwVZkmFI7+zDoWACdnPHfjUzXLTdmwZjzdeRvNN+gCG7qxExoAxCZumBHta5l/0+BLNmz56NjjH+8tzqFpL2ilwmsFl5BUAtN0OPfG+UmgOm1ShGmnu1dBIxommGWVWEy9esc5Yd5lUKRnz4lqR8H4jlQ5wYHjhZxqS/Ax2wjECcMX9ut6V8tD0WgTMLrRR56Ksk4YOH7Tq8smkKO+D4tBJ21hIwZkS0hmZlK9qJG9cE0nJb7hwYczM1oCLyC7wCktEdRDIS9qVhiGj/lYNv1OAOqWJb/bznNoW~1; ak_bmsc=0F1E2461FE9B471D0EB59E7316338479~000000000000000000000000000000~YAAQtjQQYEmnNGCLAQAA62WNixWEnkf5P3ScfddsXLGH9ad+Fj+aZVX2yGjER7eGFhGfbuNVHEOPyKXQEbKmg56R35PbUTwBVFqYezF3Eg0NQ/pKOyNHOe+kYJxRqxlbPBUNSxKR3mXD+/rxFij9NW1n7JZI8mTSlS/teRH+TsSeZggW2SdxkdMGAfzwz92wDX2c72dkDAoKPt0SlDaLvP/RgUnYa1hYdFF4awOP9Pf8A9lBClO5SnloAGh7xwn0LkL9rnlGou9J3g7oH7d/8QzQYVB609IFm3mPhDxmak/nLosqP84opB7vpmslFFYDsRQmyTkHrOLE6shy6OZLl/hhGpo9Cid6V0CoIpIGOXrBMo0FcbfrtoZ5nLgJop5RkfnSr6cvBkUFQNuf0dTI8r5en5spuVrCpRinILgIru/I/LAb/Tl2xFt43Z+fThABCgbhIy+zaTbG9mNvl7beqFkE5T4P1raZgmkVE3o2IyzJnL2JxbI5X7VYdFWBEUoUASHWfmjTSl6yFgkasEx+NndLDmCazKmtg1QHskhT9JuH7p3NKXfBQuNDGX/xHiutbAerZ1Gtx8Zq9sjqDZtKQb6rXQJXwX35fIE=; _abck=9BA3A5BAF996BDD6B8D81ABB88E9A088~0~YAAQJPlVaC22tmuLAQAAMFqgiwo7aqixyVXIwtl0LS59LqeZFGv9rAGKIh4YdJoRzDTd/OyNYQx+t0oR7a7pHNdvxfSu+fNdO1y63uT41kHLHEvOqlTuvizuFdeQ8k9VBPqM7YAJZfxzAHqUW8+mEvVqHPJWaksvixteuNf51fSRrRQVFggYzb3L7YimXznGVML9WPTiKqS5kz5YJTGgz+tQ4It0RhYojXL7fa32THag4tiSzl1EBcLzU6IXLGrohSjkUh1HuCLPLFmmZfwjUinrndIt/z9x31K3dkmBhOjAgfRWBSiZUdijFUJnOrWCSZqhDILpk8EhI9I2SLWnoqolvmRihF4zQg8UXPIWXTI9NFBDNvAHC0rxFtytBzzXvvPPDCKT5Qz7rUyOnBMF85Ju89T9PcT+OQ==~-1~-1~-1; _gat_coreTracker=1; _gat_UA-26803780-8=1; _gat_UA-26803780-11=1; pageviewCount=9; _ga=GA1.1.2030764787.1698851429; _uetsid=939fd550788611eeb3ce01ab87bd5549; _uetvid=e73ca3a0628611eeb0dce11450b8c518; _ga_GQQZK61FEJ=GS1.1.1698853298.2.1.1698854834.0.0.0; bm_sv=4A3E31AF904EE2F2B649B03AF566881A~YAAQtjQQYGi5NGCLAQAAl5ejixWH37NZFxMifT9wY6WwotcK2vJmLCXJz+CumwjUqX8BaJEnrQ0qit+ICrYtnaW5UQn1mLMXtq5MMVJZEHggrRIIhotptx2vp2zV7yCZXgB9UFeDfbOX4teTE7/remijU+jFKWZNQWDKkSC/7vX6eSexZ3njDdLR0sf9ZVe0dQGP41qazSispsYBZ+YoZxnV/ptc4BxtdiVwBfiQsr6vEaHasBTnvCmJMV5CqxqtqvU=~1'
        ));
        
        // echo $cookie . "\n\n";die();

        $response = curl_exec($ch_p);
        
        $http_code = curl_getinfo($ch_p)['http_code'];
        
        if (curl_errno($ch_p)) {
            echo "[" . date("H:i:s d.m.Y", time()) . "] Setevaya oshibka. CURL error number - " . curl_errno($ch_p) . ". Product " . $product -> partId . ".\n";
            curl_close($ch_p);
            return;
        }
        curl_close($ch_p);

        if ($http_code !== 200) {
            echo "[" . date("H:i:s d.m.Y", time()) . "] Setevaya oshibka. HTTP kod - $http_code. Product " . $product -> partId . ".\n";
            return;
        }

        $productData = json_decode($response);
        $productData = $productData -> itemsViewList[0];

        $minCount = 1;

        if (isset($productData -> priceTiers)) {
            $minCount = $productData -> priceTiers[0] -> minimumOrderQuantity;
        }
        else {
            $minCount = $productData -> minimumOrderQuantity;
        }

        writeCSVRow($products_file, [
            $product -> mpn,                                                          // 'Symbol',
            '',                                                                           // 'CustomerSymbol',
            '',                                                                           // 'OriginalSymbol',
            $product -> manufacturerName,                                             // 'Producer',
            '',                                                                           // 'Description',
            $category,                                                                    // 'CategoryId',
            $product -> partDescription,                                                  // 'Category',
            isset($product -> partLargeImageUrl) ? $product -> partLargeImageUrl : '',    // 'Photo',
            isset($product -> partSmallImageUrl) ? $product -> partSmallImageUrl : '',    // 'Thumbnail',
            '',                                                                           // 'Weight',
            '',                                                                           // 'WeightUnit',
            '',                                                                           // 'SuppliedAmount',
            $minCount,                                                                    // 'MinAmount',
            '',                                                                           // 'Multiples',
            '',                                                                           // 'Unit',
            $pageUrl,                                                                     // 'ProductInformationPage',
            '',                                                                           // 'Guarantee',
            ''                                                                            // 'OfferId'
        ]);

        echo "File has written\n";
        
        continue;
        
        writeCSVRow($params_file, [
            'Symbol',
            'key',
            'value'
        ]);
        
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
        ]);
        
        writeCSVRow($stok_file, [
            'Symbol',
            'Unit',
            'Amount'
        ]);
    }

    if ($data -> startIndex + $data -> numResultsDisplayed < $data -> numResultsFound) {
        verical_rec_prod_get($category, $data -> startIndex + $data -> numResultsDisplayed);
    }
}

foreach ($csv_data as $i => $category) {
    if ($i === 0) continue;

    $category = str_getcsv($category);

    verical_rec_prod_get($category[1]);

    if ($i === 5) break;
}

?>