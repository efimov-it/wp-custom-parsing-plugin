<?php

function conradGetCategories () {
    
    $categoriesFile = __DIR__.'/../../imports/conrad/GetCategories.csv';

    writeCSVRow($categoriesFile, [
        'title',
        'url',
        'imageIrl',
        'parentUrl'
    ], true);

    $response = file_get_contents('https://api.conrad.com/category/1/service/HP_COM_B2B/tree?' .
                                  'levels=3&' .
                                  'language=en&' .
                                  'showCategoryRelations=true&' .
                                  'showNewAtConrad=true&' .
                                  'apikey=WhxAHslieDwHoZ99q0MSVToyP7Ew2YFVBugCJNFdleOKyIkG');

    if ($response) {
        $categories = json_decode($response);
        
        // if ($categories -> statusCode !== 'SUCCESS') {
        //     return false;
        // }

        // $data = $categories -> body;

        function recWrite ($categories, $parentUrl = '') {
            $categoriesFile = __DIR__.'/../../imports/conrad/GetCategories.csv';

            foreach ($categories as $category) {
                $tmpPath = $category -> path[count($category -> path) - 1];
                $tmpUrl = 'https://www.conrad.com/en/' . $tmpPath -> id[0] . '/' . str_replace(' ', '-', (strtolower($tmpPath -> name))) . '-' . substr($tmpPath -> id, 1) . '.html';
                
                // var_dump($category);

                if (isset($category -> childs)) {
                    if (count($category -> childs) > 0) {
                        recWrite($category -> childs, $tmpUrl);
                    }
                }
                else {
                    writeCSVRow($categoriesFile, [
                        $category -> name,
                        $tmpUrl,
                        isset($category -> image) ? $category -> image -> imageUrl : '',
                        $parentUrl,
                    ]);
                }
            }
        }

        recWrite($categories);
    }
}
?>