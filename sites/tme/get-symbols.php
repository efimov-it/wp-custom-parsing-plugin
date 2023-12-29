<?php

function TME_get_symbols () {
    require_once 'tme_api.php';
    ini_set('memory_limit', '-1');
    $params = array(
        'Country'    => 'US',
        'Currency'   => 'EUR',
        'Language'   => 'EN',
    );

    // Get TME Prices and stoks
    $response = api_call('Products/GetSymbols', $params);
    $result = json_decode($response, true);

    if ($result['Status'] === 'OK') {
        $array = [];
        
        $array[] = [
            'SymbolList'
        ];

        foreach ( $result['Data']['SymbolList'] as $symbol) {
            $array[] = [$symbol];
        }

        return $array;
    }
    ini_set('memory_limit', '134217728');
    return [];
}