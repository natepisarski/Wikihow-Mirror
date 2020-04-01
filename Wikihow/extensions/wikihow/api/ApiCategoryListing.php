<?php

$wgExtensionCredits['api'][] = array(
    'path' => __FILE__,
    'name' => 'Category Listing API',
    'description' => 'An API extension to list available top-level categories'
                   . ' or subcategories and their contents',
    'version' => 1,
    'author' => 'George Bahij',
);

$wgAutoloadClasses['ApiCategoryListing'] =
    __DIR__ . '/ApiCategoryListing.body.php';
$wgAutoloadClasses['CategoryLister'] =
    __DIR__ . '/ApiCategoryListing.body.php';
$wgAPIModules['categorylisting'] = 'ApiCategoryListing';
