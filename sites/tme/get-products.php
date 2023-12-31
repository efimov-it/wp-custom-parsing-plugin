<?php

function TME_get_products ($symbols = []) {
    require_once 'tme_api.php';

    $country = 'GB';
    $currency = 'EUR';
    $lang = 'EN';

    $startTime = time();

    $symbolsCount = count($symbols);

    if ($symbolsCount === 0) return [];

    $file_products          = __DIR__ . '/../../imports/tme/getProducts.csv';
    $file_ProductStatusList = __DIR__ . '/../../imports/tme/getProducts-ProductStatusList.csv';
    $file_Guarantee         = __DIR__ . '/../../imports/tme/getProducts-Guarantee.csv';
    $file_deliveryTime      = __DIR__ . '/../../imports/tme/getDeliveryTime.csv';
    $file_parameters        = __DIR__ . '/../../imports/tme/getParameters.csv';
    $file_pricesAndStok     = __DIR__ . '/../../imports/tme/getPricesAndStocks.csv';
    $file_prices            = __DIR__ . '/../../imports/tme/getPrices.csv';
    $file_stoks             = __DIR__ . '/../../imports/tme/getStoks.csv';
    
    writeCSVRow($file_products, [
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

    writeCSVRow($file_ProductStatusList, [
        'Symbol',
        'ProductStatusList'
    ], true);
    
    writeCSVRow($file_Guarantee, [
        'Symbol',
        'Type',
        'Period'
    ], true);

    writeCSVRow($file_deliveryTime, [
        'Symbol',
        'Amount',
        'Status',
        'Week',
        'Year',
        'Date'
    ], true);

    writeCSVRow($file_parameters, [
        'Symbol',
        'ParameterId',
        'ParameterName',
        'ParameterValueId',
        'ParameterValue'
    ], true);

    writeCSVRow($file_pricesAndStok, [
        'Symbol',
        'Language',
        'Currency',
        'PriceType',
        'Unit',
        'VatRate',
        'VatType',
        'Amount',
        'PriceAmount',
        'PriceValue',
        'Special'
    ], true);

    writeCSVRow($file_prices, [
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
    
    writeCSVRow($file_stoks, [
        'Symbol',
        'Unit',
        'Amount'
    ], true);

    $paramCounts = 0;

    for ($i = 0; $i < $symbolsCount + 1; $i+=50) {

        $limitedSymbolsList = [];
        $ignoreSymbols = [];
        $amountList = [];

        for ($j = $i; $j < $i + 50; $j++) {
            if ($i === 0) continue;
            if (!isset($symbols[$j])) break;
            $limitedSymbolsList[] = $symbols[$j][0];
            $ignoreSymbols[$symbols[$j][0]] = count($limitedSymbolsList) - 1;
            $amountList[] = 1;
        }

        if (count($limitedSymbolsList) === 0) continue;

        // Наличие товаров
        $params = array(
            'SymbolList' => $limitedSymbolsList,
            'Country'    => $country,
            'Currency'   => $currency,
            'Language'   => $lang,
        );
        $response = api_call('Products/GetStocks', $params);
        $result = json_decode($response, true);
        if (!$result) {
            echo "[" . date("H:i:s d.m.Y", time()) . "] Oshibka - ne udalos zagruzit informaciu o nalichie tovarov.\n";
            continue;
        }
        if (!isset($result['Status'])) {
            echo "[" . date("H:i:s d.m.Y", time()) . "] Oshibka - ne udalos zagruzit informaciu o nalichie tovarov.\n";
            continue;
        }
        if ($result['Status'] !== 'OK') {
            echo "[" . date("H:i:s d.m.Y", time()) . "] Oshibka - ne udalos zagruzit informaciu o nalichie tovarov.\n";
            continue;
        }
        foreach ($result['Data']['ProductList'] as $product) {
            if ($product['Amount'] == 0) {
                unset($limitedSymbolsList[$ignoreSymbols[$product['Symbol']]]);
                unset($amountList[count($amountList) - 1]);
                continue;
            }
            writeCSVRow($file_stoks, [
                $product['Symbol'],
                $product['Unit'],
                $product['Amount']
            ]);
        }
        
        // Основная информация о продукте
        $params = array(
            'SymbolList' => $limitedSymbolsList,
            'Country'    => $country,
            'Currency'   => $currency,
            'Language'   => $lang,
        );
        $response = api_call('Products/GetProducts', $params);
        $result = json_decode($response, true);
        if ($result) {
            if (isset($result['Status'])) {
                if ($result['Status'] === 'OK') {
                    foreach ( $result['Data']['ProductList'] as $product) {
                        writeCSVRow($file_products, [
                            $product['Symbol'],
                            $product['CustomerSymbol'],
                            $product['OriginalSymbol'],
                            $product['Producer'],
                            $product['Description'],
                            $product['CategoryId'],
                            $product['Category'],
                            $product['Photo'],
                            $product['Thumbnail'],
                            $product['Weight'],
                            $product['WeightUnit'],
                            $product['SuppliedAmount'],
                            $product['MinAmount'],
                            $product['Multiples'],
                            $product['Unit'],
                            $product['ProductInformationPage'],
                            '',
                            $product['OfferId']
                        ]);

                        if (isset($product['ProductStatusList'])) {
                            foreach ($product['ProductStatusList'] as $psl) {
                                writeCSVRow($file_ProductStatusList, [
                                    $product['Symbol'],
                                    $psl
                                ]);
                            }
                        }

                        if (isset($product['Guarantee'])) {
                            writeCSVRow($file_Guarantee, [
                                $product['Symbol'],
                                $product['Guarantee']['Type'],
                                $product['Guarantee']['Period']
                            ]);
                        }
                    }
                }
            }
        }


        // Время доставки
        $params = array(
            'SymbolList' => $limitedSymbolsList,
            'AmountList' => $amountList,
            'Country'    => $country,
            'Currency'   => $currency,
            'Language'   => $lang,
        );
        $response = api_call('Products/GetDeliveryTime', $params);
        $result = json_decode($response, true);
        if ($result) {
            if (isset($result['Status'])) {
                if ($result['Status'] === 'OK') {
                    foreach ( $result['Data']['ProductList'] as $product) {
                        foreach ($product['DeliveryList'] as $delivery) {
                            writeCSVRow($file_deliveryTime, [
                                'Symbol' => $product['Symbol'],
                                'Amount' => $delivery['Amount'],
                                'Status' => $delivery['Status'],
                                'Week' => $delivery['Week'],
                                'Year' => isset($delivery['Year']) ? $delivery['Year'] : '',
                                'Date' => isset($delivery['Date']) ? $delivery['Date'] : ''
                            ]);
                        }
                    }
                }
            }
        }

        // Параметры товаров
        $params = array(
            'SymbolList' => $limitedSymbolsList,
            'Country'    => $country,
            'Currency'   => $currency,
            'Language'   => $lang,
        );
        $response = api_call('Products/GetParameters', $params);
        $result = json_decode($response, true);
        if ($result) {
            if (isset($result['Status'])) {
                if ($result['Status'] === 'OK') {
                    $needToRewriteHeaders = false;
                    foreach ($result['Data']['ProductList'] as $product) {
                        $tmpCount = 0;
                        $row = [$product['Symbol']];
                        foreach ($product['ParameterList'] as $parameter) {
                            $row[] = $parameter['ParameterId'];
                            $row[] = $parameter['ParameterName'];
                            $row[] = $parameter['ParameterValueId'];
                            $row[] = $parameter['ParameterValue'];
                            $tmpCount++;
                        }
                        if ($tmpCount > $paramCounts) {
                            $paramCounts = $tmpCount;
                            $needToRewriteHeaders = true;
                        }

                        writeCSVRow($file_parameters, $row);
                    }

                    if ($needToRewriteHeaders) {
                        $headerCells = [
                            'Symbol'
                        ];
                        for ($ip = 0; $ip < $paramCounts; $ip++) {
                            $headerCells = array_merge($headerCells, [
                                'ParameterId' . ($ip + 1),
                                'ParameterName' . ($ip + 1),
                                'ParameterValueId' . ($ip + 1),
                                'ParameterValue' . ($ip + 1)
                            ]);
                        }
                        
                        $csv_data = file($file_parameters, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        $readed_file = [];
                        foreach ($csv_data as $row) {
                            $readed_file[] = str_getcsv($row);
                        }

                        $readed_file[0] = $headerCells;
                        writeCSV('tme/getParameters', $readed_file);
                    }
                }
            }
        }


        // Цены и наличие товаров
        $params = array(
            'SymbolList' => $limitedSymbolsList,
            'Country'    => $country,
            'Currency'   => $currency,
            'Language'   => $lang,
        );
        $response = api_call('Products/GetPricesAndStocks', $params);
        $result = json_decode($response, true);
        if ($result) {
            if (isset($result['Status'])) {
                if ($result['Status'] === 'OK') {
                    foreach ($result['Data']['ProductList'] as $product) {
                        foreach ($product['PriceList'] as $key => $price) {
                            if ($key === 0) {
                                writeCSVRow($file_pricesAndStok, [
                                    $product['Symbol'],
                                    $result['Data']['Language'],
                                    $result['Data']['Currency'],
                                    $result['Data']['PriceType'],
                                    $product['Unit'],
                                    $product['VatRate'],
                                    $product['VatType'],
                                    $product['Amount'],
                                    $price['Amount'],
                                    ceil(floatval($price['PriceValue'])),
                                    $price['Special']
                                ]);
                            }
                        }
                    }
                }
            }
        }


        // Цены товаров
        $params = array(
            'SymbolList' => $limitedSymbolsList,
            'Country'    => $country,
            'Currency'   => $currency,
            'Language'   => $lang,
        );
        $response = api_call('Products/GetPrices', $params);
        $result = json_decode($response, true);
        if ($result) {
            if (isset($result['Status'])) {
                if ($result['Status'] === 'OK') {
                    foreach ($result['Data']['ProductList'] as $product) {
                        foreach ($product['PriceList'] as $price) {
                            writeCSVRow($file_prices, [
                                $product['Symbol'],
                                $result['Data']['Language'],
                                $result['Data']['Currency'],
                                $result['Data']['PriceType'],
                                $product['Unit'],
                                $product['VatRate'],
                                $product['VatType'],
                                $price['Amount'],
                                $price['PriceValue'],
                                $price['Special']
                            ]);
                        }
                    }
                }
            }
        }

        // if ($i > 100) return;
    }
}