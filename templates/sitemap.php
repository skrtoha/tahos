<?php
/** @global $db \core\Database */
/** @var $view string */

use core\Category;

$title = 'Карта сайта';
?>
<ul class="sitemap">
    <li><a href="/">Главная</a></li>
    <li>
        <a href="/original-catalogs">Оригинальные каталоги</a>
        <?$vehicles = $db->select('vehicles', '*', '', 'pos');?>
        <ul>
            <?foreach($vehicles as $v){?>
                <li>
                    <a href="/original-catalogs/<?=$v['href']?>"><?=$v['title']?></a>
                </li>
            <?}?>
        </ul>

    </li>
    <?
    $categories = Category::getAll();
    foreach($categories as $title => $category){?>
        <li>
            <a href="/category/<?=$category['href']?>"><?=$title?></a>
            <?if (isset($category['subcategories'])){?>
                <ul>
                    <?foreach($category['subcategories'] as $c){?>
                        <li>
                            <a href="/category/<?=$category['href']?>/<?=$c['href']?>"><?=$c['title']?></a>
                        </li>
                    <?}?>
                </ul>
            <?}?>
        </li>
    <?}?>
    <?$articles = $db->select('text_articles', '*');?>
    <li>
        <a href="#">Информация</a>
        <ul>
            <?foreach($articles as $row){?>
                <li>
                    <a href="/page/<?=$row['href']?>"><?=$row['title']?></a>
                </li>
            <?}?>
        </ul>
    </li>
    <?$rubrics = $db->select('text_rubrics', '*');?>
    <li>
        <a href="#">Интернет-магазин</a>
        <ul>
            <li>
                <a href="/help">Помощь</a>
            </li>
            <?foreach($rubrics as $row){?>
                <li>
                    <a href="/help/<?=$row['id']?>"><?=$row['title']?></a>
                </li>
            <?}?>
        </ul>
    </li>
</ul>