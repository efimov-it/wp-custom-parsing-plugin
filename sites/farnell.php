<?php

require_once __DIR__.'/../lib/simplehtmldom/simple_html_dom.php';
require_once __DIR__.'/../lib/proxy/index.php';
require_once __DIR__.'/../index.php';

$folder_path = __DIR__ . '/../imports/farnell';
if (!file_exists($folder_path)) {
    mkdir($folder_path, 0777, true);
}

require_once 'farnell/categories.php';
echo 'Farnell parsing: Categories parsing done!';

require_once 'farnell/products.php';

echo 'Farnell parsing done!';

?>