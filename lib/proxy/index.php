<?php

$proxyList = [];
$proxyIndex = 0;

function updateProxy () {
    global $proxyList;
    $link = 'https://free-proxy-list.net/';
     
    $agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36';
     
    $ch = curl_init($link);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response_data = curl_exec($ch);
    if (curl_errno($ch) > 0) {
        die('Ошибка curl: ' . curl_error($ch));
    }
    curl_close($ch);
     
    preg_match_all('/<td>(\d+\.\d+\.\d+\.\d+)<\/td><td>(\d+)<\/td>/', $response_data, $rawlist);
     
    $cleanedList = str_replace('</td><td>', ':', $rawlist[0]);
    $cleanedList = str_replace('<td>', '', $cleanedList);
    $cleanedList = str_replace('</td>', '', $cleanedList);

    $proxyList = $cleanedList;
}

updateProxy();

function getProxy () {
    global $proxyIndex;
    global $proxyList;

    $tmp = explode(':', $proxyList[$proxyIndex]);

    $res = new stdClass();
    $res -> ip = $tmp[0];
    $res -> port = $tmp[1];

    return $res;
}
function getNextProxy () {
    global $proxyIndex;
    global $proxyList;

    $proxyIndex++;

    if ($proxyIndex > count($proxyList) - 1) $proxyList = updateProxy();

    return getProxy();
}

?>