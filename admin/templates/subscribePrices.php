<?php

/** @global Database $db  */

use core\Database;

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
        if ($_GET['user_id']){
            \core\User::update($_GET['user_id'], ['is_subscribe' => 0]);
        }
        else{
            $db->delete('subscribe_prices', "`email` = '{$_GET['email']}'");
        }
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
		$params['page'] = $_GET['page'] ?: 1;
		$params['chunk'] = getChank($params['all'], $params['perPage'], $linkLimit, $params['page']);
		$start = $params['chunk'][$params['page']] ?: 0;
		if ($where) $where = "WHERE $where";
		$res_common_list = $db->query("
			SELECT 
			    '' as id,
			    email,
                title,
                phone
            FROM 
                tahos_subscribe_prices
            
            UNION
            
            SELECT
                u.id,                
                email,
                concat(
                   IF(
                       u.organization_name <> '',
                       CONCAT_WS(' ', ot.title, u.organization_name),
                       CONCAT_WS(' ', u.name_1, u.name_2, u.name_3)
                    ),
                    IF(
                        @date := (select created from tahos_orders where user_id = u.id order by created desc limit 1),
                        concat(
                            ' (заказ <b>',
                            @date,
                            '</b>)'
                        ),
                        ''
                    )
                ) as title,
                phone
            FROM tahos_users u
            LEFT JOIN tahos_organizations_types ot ON ot.id = u.organization_type
            WHERE u.is_subscribe = 1
            order by email
            LIMIT $start, {$params['perPage']}
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
				<tr class="edit <?=$item['id'] ? 'user_id' : ''?>">
					<td label="Email"><?=$item['email']?></td>
					<td label="Название"><?=$item['title']?></td>
					<td label="Телефон"><?=$item['phone']?></td>
					<td label="">
						<a tooltip="Удалить" class="delete" href="?view=subscribePrices&act=delete&email=<?=$item['email']?>&user_id=<?=$item['id']?>">
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
	<?pagination($params['chunk'], $params['page'], ceil($params['all'] / $params['perPage']), "?view=subscribePrices&search={$params['search']}&page=");?>
<?}?>