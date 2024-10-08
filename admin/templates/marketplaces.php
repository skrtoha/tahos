<?php
/** @global \core\Database $db */

use core\Marketplaces\Avito;
use core\Marketplaces\Ozon;

$page_title = "Маркетплейсы";
$status = '<a href="">Главная</a> > Маркетплейсы';

if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
    switch($_POST['tab']){
        case 'avito':
            $output = [
                'items' => Avito::getCommonList(),
                'totalCount' => $db->found_rows()
            ];
            break;
        case 'ozon':
            $output = [
                'items' => Ozon::getProductList(),
                'totalCount' => Ozon::$countProduct
            ];
            break;
    }
    echo json_encode($output);
    exit();
}?>
<form class="search" action="">
    <input placeholder="Поиск" type="text" name="search">
</form>
<div class="ionTabs" id="tabs_1" data-name="marketplaces">
	<ul class="ionTabs__head">
		<li class="ionTabs__tab" data-target="avito">Авито</li>
		<li class="ionTabs__tab" data-target="ozon">Озон</li>
	</ul>
	<div class="ionTabs__body">
		<div class="ionTabs__item" data-name="avito">
			<div class="actions">
                <input style="width: 264px;" type="text" name="itemsForAdding" value="" class="intuitive_search" placeholder="Поиск для добавления">
            </div>
			<span class="total">
				Всего: <span></span>
			</span>
			<table class="t_table" cellspacing="1">
				<thead class="head">
					<tr class="head">
						<th>Бренд</th>
						<th>Артикул</th>
						<th>Наименование</th>
						<th>Категория</th>
						<th></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
			<div class="pagination-container"></div>
		</div>
        <div class="ionTabs__item" data-name="ozon">
            <div class="actions">
                <input style="width: 264px;" type="text" name="itemsForAdding" value="" class="intuitive_search" placeholder="Поиск для добавления">
                <input type="button" value="Изменить склад" name="ozon_change_store">
                <input type="button" value="Сопоставить категории" name="ozon_match_categories">
            </div>
            <span class="total">
				Всего: <span></span>
			</span>
            <table class="t_table" cellspacing="1">
                <thead class="head">
                <tr class="head">
                    <th>Бренд</th>
                    <th>Артикул</th>
                    <th>Наименование</th>
                    <th></th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
            <div class="pagination-container"></div>
        </div>
		<div class="ionTabs__preloader"></div>
	</div>
</div>
