<?php

use core\Category;
use core\Config;
use core\Database;

require_once ('core/Config.php');
require_once ('core/Database.php');
require_once ('core/Category.php');

$db = new Database();

function setUrl($urlTo){
    global $dom, $urlset;
    $url = $dom->createElement('url');
    $loc = $dom->createElement('loc');
    $text = $dom->createTextNode(
        htmlentities(Config::HOST.$urlTo, ENT_QUOTES)
    );
    $loc->appendChild($text);
    $url->appendChild($loc);
    $urlset->appendChild($url);
}

$dom = new DOMDocument('1.0', 'utf-8');
$urlset = $dom->createElement('urlset');
$urlset->setAttribute('xmlns','http://www.sitemaps.org/schemas/sitemap/0.9');

setUrl('/original-catalogs');

$vehicles = $db->select('vehicles', '*', '', 'pos');
foreach($vehicles as $v) setUrl('/original-catalogs/'.$v['href']);

$categories = Category::getAll();
foreach($categories as $title => $category){
    setUrl('/category/'.$category['href']);
    if (isset($category['subcategories'])){
        foreach($category['subcategories'] as $c){
            setUrl('/category/'.$category['href'].'/'.$c['href']);
        }
    }
}

$articles = $db->select('text_articles', '*');
foreach($articles as $row){
    setUrl('/page/'.$row['href']);
}

$rubrics = $db->select('text_rubrics', '*');
setUrl('/help');
foreach($rubrics as $row){
    setUrl('/help/'.$row['id']);
}

$dom->appendChild($urlset);

header('Content-Type: text/xml');
echo $dom->saveXML();
exit();
