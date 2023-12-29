<?php

function rs_get_categories () {
    $document = file_get_contents('https://uk.rs-online.com/web/');
    $data = [];
    
    if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $document, $matches)) {
        $jsonStr = $matches[1];
        $data = json_decode($jsonStr, true);

        function rec ($category, $parentId) {

            $current = [[
                'id'         => $category['id'],
                'name'       => $category['name'],
                'url'        => isset($category['url']) ? $category['url'] : '',
                'parentId'   => $parentId,
                'stockCount' => isset($category['stockCount']) ? $category['stockCount'] : ''
            ]];

            if (isset($category['subCategories'])) {
                $sub = [];
                foreach ($category['subCategories'] as $subCat) {
                    $sub = array_merge($sub, rec($subCat, $category['id']));
                }
                return array_merge($current, $sub);
            }
            else {
                return $current;
            }
        }

        $catalogArray = [];

        foreach($data['props']['pageProps']['catalogue'] as $item) {
            $catalogArray = array_merge($catalogArray, rec($item, null));
        }

        return $catalogArray;
    }
    else {
        return [];
    }
}

?>