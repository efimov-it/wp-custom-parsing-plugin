<?php

require_once __DIR__.'/../index.php';
require_once __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$folder_path = __DIR__ . '/../imports/brandelektra';
if (!file_exists($folder_path)) {
    mkdir($folder_path, 0777, true);
}

$loginQueryData = [
    'username' => 'Awatera@VV',
    'password' => 'HolaBrand71'
];
$loginQueryData = json_encode($loginQueryData);

$loginQuery = curl_init('https://b2b.brandelektra.com/api/public/login');
curl_setopt($loginQuery, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($loginQuery, CURLOPT_HEADER, 1);
curl_setopt($loginQuery, CURLOPT_POST, 1);
curl_setopt($loginQuery, CURLOPT_POSTFIELDS, $loginQueryData);
curl_setopt($loginQuery, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

$response = curl_exec($loginQuery);

if (curl_errno($loginQuery)) {
    echo "[" . date("H:i:s d.m.Y", time()) . "] Ошибка Curl: " . curl_error($loginQuery) . "\n\n";
}

$loginQueryInfo = curl_getinfo($loginQuery);

$responseHeader = substr($response, 0, $loginQueryInfo['header_size']);
$response       = substr($response, $loginQueryInfo['header_size']);

$tmpHeaders = explode("\n", $responseHeader);
$responseHeader = [];
foreach($tmpHeaders as $h) {
    if (strpos($h, ": ") > 0) {
        $tmp = explode(": ", $h);
        $responseHeader[$tmp[0]] = $tmp[1];
    }
}

if ($loginQueryInfo["http_code"] !== 204) {
    echo "[" . date("H:i:s d.m.Y", time()) . "] Ошибка авторизации: " . curl_error($loginQuery) . "\n\n";
    die();
}
curl_close($loginQuery);

$cookie = $responseHeader["Set-Cookie"];

$getBrandPrice = curl_init("https://b2b.brandelektra.com/api/stock/report");

$tmpFileName = __DIR__.'/../imports/brandelektra/tmp.xlsx';

$tmpFile = fopen($tmpFileName, 'wb');
curl_setopt($getBrandPrice, CURLOPT_FILE, $tmpFile);
curl_setopt($getBrandPrice, CURLOPT_HTTPHEADER, [
    'Cookie: ' . $cookie
]);
curl_exec($getBrandPrice);
curl_close($getBrandPrice);
fclose($tmpFile);

$spreadsheet = IOFactory::load($tmpFileName);

$worksheet = $spreadsheet->getActiveSheet();

$totalRows = $worksheet->getHighestRow();

$rawProductsArray = [];

for ($row = 2; $row <= $totalRows; $row++) {
    $tmp = new StdClass();

    $tmp -> Symbol = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
    $tmp -> Description = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
    $tmp -> Producer = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
    $tmp -> Stock = $worksheet->getCellByColumnAndRow(4, $row)->getValue();

    $rawProductsArray[] = $tmp;
}

require_once 'brandelektra/products.php';
getElektraProducts($rawProductsArray);

$logOutQuery = curl_init('https://b2b.brandelektra.com/api/public/logout');
curl_setopt($logOutQuery, CURLOPT_POST, 1);
curl_setopt($logOutQuery, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($logOutQuery, CURLOPT_HTTPHEADER, [
    'Cookie: ' . $cookie
]);

curl_exec($logOutQuery);

curl_close($logOutQuery);

unlink($tmpFileName);

$wpai_uid_folders = [
    'getPrices.csv'   => 'caba6b23f5723e00e01779735563e68d',
    'getProducts.csv' => '9865c65c2493197f5c514ba235612739',
    'getStoks.csv'    => '274f461fb234fdd51e4811335b99bbb8'
];

importToWPAllImport($folder_path, 'brandelektra', $wpai_uid_folders, true);

echo 'Brandelektra parsing done!';

?>