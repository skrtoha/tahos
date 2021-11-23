<?php
use core\Managers;
use core\Item;
use core\Timer;

if (isset($_GET['item_id'])){
	$item = Item::getByID($_GET['item_id']);
}

if (isset($_GET['act'])){
	switch ($_GET['act']){
		case 'getCoincidences':
			$res = Item::getByArticle($_GET['article']);
			if (!$res->num_rows) break;
			$output = [];
			foreach($res as $item) $output[] = $item;
			echo json_encode($output);
			break;
		case 'getResultApi':
			// debug($_GET);
			Timer::start();
			switch($_GET['providerApiTitle']){
				case 'Abcp':
					$abcp = new core\Provider\Abcp($_GET['item_id'], $db);
					$abcp->render($_GET['provider_id']); 
					break;
				case 'Armtek':
					$armtek = new core\Provider\Armtek($db);
					$armtek->setArticle($item['brend'], $item['article']);
					break;
				case 'Mikado':
					$mikado = new core\Provider\Mikado($db);
					$mikado->setArticle($item['brend'], $item['article']);
					break;
				case 'Rossko':
					$rossko = new core\Provider\Rossko($db);
					$rossko->execute("{$item['article']} {$item['brend']}");
					break;
				case 'ForumAuto':
					core\Provider\ForumAuto::setArticle($_GET['item_id'], $item['brend'], $item['article']);
					break;
				case 'Autoeuro':
                    $autoeuro = new \core\Provider\Autoeuro($_GET['item_id']);
					$autoeuro->setArticle($item['brend'], $item['article']);
					break;
				case 'Autokontinent':
					core\Provider\Autokontinent::setArticle($item['brend'], $item['article'], $_GET['item_id']);
					break;
			}
			echo Timer::end();
			break;
	}
	exit();
}

$page_title = 'Тестирование API поставщиков';
$status = "<a href='/'>Главная </a> > Администрирование > $page_title";


$res_providers = $db->query("
	SELECT
		p.id,
		p.title,
		p.api_title
	FROM
		#providers p
	WHERE
		p.api_title IS NOT NULL
", '');
?>
<div id="articles">
	<input class="intuitive_search" type="text" name="items" placeholder="Артикул">
</div>
<div id="tests">
	<p class="title">
		<?if (isset($item)){?>
			<?=$item['title']?> <?=$item['brand']?> <?=$item['article']?>
		<?}?>
	</p>
	<button class="<?=isset($_GET['item_id']) ? '' : 'hidden'?>" id="processTest">Запустить тест</button>
	<input type="hidden" name="item_id" value="<?=$_GET['item_id']?>">
	<form>
		<table style="margin-top: 10px " border="1">
			<tr>
				<th>Поставщик</th>
				<th>Время</th>
				<th><input type="checkbox" name="checkAll" checked></th>
			</tr>
			<?foreach($res_providers as $provider){?>
				<tr>
					<td><?=$provider['title']?></td>
					<td class="removable"></td>
					<td>
						<input checked type="checkbox" name="<?=$provider['id']?>" value="<?=$provider['api_title']?>">
					</td>
				</tr>
			<?}?>
			<tr>
				<td style="text-align: right;" colspan="3">
					Всего: <span class="removable total"></span>
				</td>
			</tr>
		</table>
	</form>
</div>
