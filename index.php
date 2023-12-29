<?php

$is_wp_plugin = file_exists(__DIR__."/../../plugins");

$folder_path = __DIR__ . '/imports';
if (!file_exists($folder_path)) {
    mkdir($folder_path, 0777, true);
}

function writeCSV ($name = '', $data = []) {
    $file_name = __DIR__ . '/imports/' . $name . '.csv';
    
    $file_handle = fopen($file_name, 'w');
    
    foreach ($data as $row) {
        fputcsv($file_handle, $row);
    }
    
    fclose($file_handle);
}

function writeCSVRow ($name = '', $row = [], $rewrite = false) {
    if ($rewrite) {
        $file_handle = fopen($name, 'w');
    }
    else {
        $file_handle = file_exists($name) ? fopen($name, 'a') : fopen($name, 'w');
    }
    
    foreach ($row as $cell) {
        if (gettype($cell) === 'array') {
            echo "\n\nDebug!\n";
            var_dump($row);
            echo "\n";
        }
    }
    fputcsv($file_handle, $row);
    fclose($file_handle);
}

function importToWPAllImport ($folder_path, $prefix = "", $uid_folders = [], $delete_origin = false) {
    global $is_wp_plugin;

    if (!$is_wp_plugin) return;

    $wp_all_import_path = __DIR__."/../../uploads/wpallimport/";
    
    $wpai_uploads_folder_path = $wp_all_import_path . "uploads";
    if (!file_exists($wpai_uploads_folder_path)) {
        mkdir($wpai_uploads_folder_path, 0777, true);
    }

    $wpai_files_folder_path = $wp_all_import_path . "files";
    if (!file_exists($wpai_files_folder_path)) {
        mkdir($wpai_files_folder_path, 0777, true);
    }

    if (strlen($prefix) > 0) $prefix .= "__";

    $files = scandir($folder_path);
    $delete = [];
    foreach ($files as $file) {

        if (in_array($file, array(".",".."))) continue;
        if (!isset($uid_folders[$file])) continue;

        $uid_folder = $uid_folders[$file] . '/';

        if (
            copy($folder_path.'/'.$file, $wpai_uploads_folder_path."/" . $uid_folder . $prefix . $file) &&
            copy($folder_path.'/'.$file, $wpai_files_folder_path."/" . $prefix . $file)
        ) {
            if ($delete_origin) $delete[] = $folder_path.'/'.$file;
        }
    }
    foreach ($delete as $file) {
        unlink($file);
    }
}