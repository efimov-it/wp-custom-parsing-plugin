<?php

writeCSVRow(__DIR__.'/../../imports/verical/GetCategories.csv', [
    'TotalProducts',
    'Id',
    'Depth',
    'ParentId',
    'Name',
    'SubTreeCount'
], true);

function vericalCategories ($categories, $parentId = 1) {
    foreach ($categories as $key => $category) {
        writeCSVRow(__DIR__.'/../../imports/verical/GetCategories.csv', [
            $category -> numberOfMpns,
            $category -> privateId,
            '',
            $parentId,
            $category -> name,
            ''
        ]);
        
        if (isset($category -> categories)) {
            vericalCategories($category -> categories, $category -> privateId);
        }
    }
}

?>