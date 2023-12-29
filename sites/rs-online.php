<?php

set_time_limit(0);

require_once __DIR__.'/../index.php';

$folder_path = __DIR__ . '/../imports/rs-online';
if (!file_exists($folder_path)) {
    mkdir($folder_path, 0777, true);
}

function rs_api ($query = [], $url = "https://uk.rs-online.com/web/services/aggregation/search-and-browse/graphql") {
    $data_json = json_encode($query);
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_json)
    ));
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return false;
    }
    
    curl_close($ch);

    return json_decode($response);
}


require_once 'rs-online/categories.php';
$categories = rs_get_categories();
writeCSV('rs-online/GetCategories', array_merge([[
    'id',
    'name',
    'url',
    'parentId',
    'stockCount'
]], $categories));

require_once 'rs-online/products.php';
rs_get_products($categories);

echo 'RS-Online parsing done!';

?>