<?php

$folder_path = plugin_dir_path(__FILE__) . '/../imports/tme';
if (!file_exists($folder_path)) {
    mkdir($folder_path, 0777, true);
}

require_once 'tme/categories.php';
writeCSV('tme/categories', TME_categories());