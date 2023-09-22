<?php
/*
Plugin Name: Custom Parsing Plugin
Description: Плагин для выгрузки данных с сайтов..
Version: 1.0
Author: efimov-it
*/

add_action('wp_footer', 'importData');
function importData () {

    function writeCSV ($name = '', $data = []) {
        $file_name = plugin_dir_path(__FILE__) . 'imports/' . $name . '.csv';
        
        $file_handle = fopen($file_name, 'w');
        
        foreach ($data as $row) {
            fputcsv($file_handle, $row);
        }
        
        fclose($file_handle);
    }

    $folder_path = plugin_dir_path(__FILE__) . 'imports';
    if (!file_exists($folder_path)) {
        mkdir($folder_path, 0777, true);
    }

    require_once 'sites/tme.php';
}