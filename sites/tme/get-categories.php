<?php

function TME_get_categories () {
    require_once 'tme_api.php';

    $params = array(
        'SymbolList' => ['NE555D', '1N4007-DIO'],
        'Country'    => 'US',
        'Currency'   => 'EUR',
        'Language'   => 'EN',
    );

    // Get TME Categories
    $response = api_call('Products/GetCategories', $params);
    $result = json_decode($response, true);

    if ($result['Status'] === 'OK') {
        function recGetCat ($categories, $array = []) {

            if (count($array) === 0) {
                $array[] = [
                    'TotalProducts',
                    'Id',
                    'Depth',
                    'ParentId',
                    'Name',
                    'SubTreeCount'
                ];
            }

            foreach ($categories as $category) {
                $array[] = [
                    $category['TotalProducts'],
                    $category['Id'],
                    $category['Depth'],
                    $category['ParentId'],
                    $category['Name'],
                    $category['SubTreeCount']
                ];

                if ($category['SubTree']) {
                    $array = recGetCat($category['SubTree'], $array);
                }
            }

            return $array;
        }

        return recGetCat($result['Data']['CategoryTree']['SubTree']);
    }

    return [];
}