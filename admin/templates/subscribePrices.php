<?php
$page_title = "Рассылка прайсов";
$status = "<a href='/'>Главная</a> > Администрирование > ";
require_once('pagination.php');

switch($_GET['act']){
	case 'change':
		$res_insert = $db->insert('subscribe_prices', $_POST, ['duplicate' => [
			'email' => $_POST['email'],
			'title' => $_POST['title'],
			'phone' => $_POST['phone']
		]]);
		header("Location: {$_SERVER['HTTP_REFERER']}");
		break;
	case 'delete':
		$db->delete('subscribe_prices', "`email` = '{$_GET['email']}'");
		message('Успешно удалено');
		header("Location: {$_SERVER['HTTP_REFERER']}");
		break;
	default:
		$status .= "Рассылка прайсов";
		$where = '';
		$params['search'] = '';
		if (isset($_GET['search'])){
			$params['search'] = $_GET['search'];
			$where = "email LIKE '%{$_GET['search']}%' OR title LIKE '%{$_GET['search']}%' OR phone LIKE '%{$_GET['search']}%'";
		}
		$params['all'] = $db->getCount('subscribe_prices', $where);
		$params['perPage'] = 30;
		$linkLimit = 10;
		$params['page'] = $_GET['page'] ? $_GET['page'] : 1;
		$params['chank'] = getChank($params['all'], $params['perPage'], $linkLimit, $params['page']);
		$start = $params['chank'][$params['page']] ? $params['chank'][$params['page']] : 0;
		if ($where) $where = "WHERE $where";
		$res_common_list = $db->query("
			SELECT
				*
			FROM
				#subscribe_prices
			$where
			ORDER BY
				created DESC
			LIMIT
				$start, {$params['perPage']}
		", '');
		commonList($res_common_list, $params);
}
function commonList($res_common_list, $params){?>
	<div id="actions">
		<a id="add">Добавить</a>
		<form method="get">
			<input type="text" name="search" value="<?=$params['search']?>">
			<input type="hidden" name="view" value="subscribePrices">
			<input type="submit" name="" value="Искать">
		</form>
	</div>
	<table class="t_table" cellspacing="1">
		<tr class="head">
			<td>Email</td>
			<td>Название</td>
			<td>Телефон</td>
			<td></td>
		</tr>
		<?if ($res_common_list->num_rows){
			foreach($res_common_list as $item){?>
				<tr class="edit">
					<td label="Email"><?=$item['email']?></td>
					<td label="Название"><?=$item['title']?></td>
					<td label="Телефон"><?=$item['phone']?></td>
					<td label="">
						<a tooltip="Удалить" class="delete" href="?view=subscribePrices&act=delete&email=<?=$item['email']?>">
							<span class="icon-cancel-circle1"></span>
						</a>
						<a target="_blank" email="<?=$item['email']?>" tooltip="Отправить вручную" class="subscribeHandy" href="#">
							<span class="icon-envelop"></span>
						</a>
					</td>
				</tr>
			<?}
		}
		else{?>
			<tr>
				<td colspan="3">Ничего не найдено</td>
			</tr>
		<?}?>
	</table>
	<?pagination($params['chank'], $params['page'], ceil($params['all'] / $params['perPage']), "?view=subscribePrices&search={$params['search']}&page=");?>
<?}?>