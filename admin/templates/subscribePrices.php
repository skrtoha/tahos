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
		$where_u = 'u.is_subscribe = 1 AND ';
        $where_sp = '1 AND ';
		$params['search'] = '';
		if (isset($_GET['search']) && $_GET['search']){
			$params['search'] = $_GET['search'];
			$string = "(email LIKE '%{$_GET['search']}%' OR title LIKE '%{$_GET['search']}%' OR phone LIKE '%{$_GET['search']}%') AND ";
            $where_u .= $string;
            $where_sp .= $string;
		}

        $where_sp = substr($where_sp, 0, -5);
        $where_u = substr($where_u, 0, -5);
        $db->query("SET sql_mode = ''");

        $resAll = $db->query("
            SELECT 
			    email,
                title,
                phone,
                count(*) as cnt
            FROM 
                tahos_subscribe_prices
            WHERE $where_sp
            
            UNION
            
            SELECT
                email,
                IF(
                   u.organization_name <> '',
                   CONCAT_WS(' ', ot.title, u.organization_name),
                   CONCAT_WS(' ', u.name_1, u.name_2, u.name_3)
                ) as title,
                phone,
                count(*) as cnt
            FROM tahos_users u
            LEFT JOIN tahos_organizations_types ot ON ot.id = u.organization_type
            WHERE $where_u 
        ");
        $params['all'] = 0;
        foreach($resAll as $row) $params['all'] += $row['cnt'];
		$params['perPage'] = 30;
		$linkLimit = 10;
		$params['page'] = $_GET['page'] ?: 1;
		$params['chunk'] = getChank($params['all'], $params['perPage'], $linkLimit, $params['page']);
		$start = $params['chunk'][$params['page']] ?: 0;
        $params['dir'] = $_GET['dir'] ?? 'ASC';

        $orderBy = '';
        if (isset($_GET['sort']) && $_GET['sort']){
            $orderBy = "ORDER BY {$_GET['sort']} {$params['dir']}";
        }
		$res_common_list = $db->query("
			SELECT 
			    '' as id,
			    email,
                title,
                phone,
                '' as date
            FROM 
                tahos_subscribe_prices
            WHERE $where_sp
            
            UNION
            
            SELECT
                u.id,                
                email,
                IF(
                   u.organization_name <> '',
                   CONCAT_WS(' ', ot.title, u.organization_name),
                   CONCAT_WS(' ', u.name_1, u.name_2, u.name_3)
                ) as title,
                phone,
                (select created from tahos_orders where user_id = u.id order by created desc limit 1) as date
            FROM tahos_users u
            LEFT JOIN tahos_organizations_types ot ON ot.id = u.organization_type
            WHERE $where_u 
            $orderBy
            LIMIT $start, {$params['perPage']}
		", '');

        $params['rotated'] = $params['dir'] == 'DESC' ? 'rotated' : '';
        if ($params['dir'] == 'DESC') $params['dir'] = 'ASC';
        else $params['dir'] = 'DESC';

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
			<td>
                <a href="/admin/?view=subscribePrices&sort=email&search=<?=$params['search']?>&dir=<?=$params['dir']?>">
                    Email
                </a>
                <?if (isset($_GET['sort']) && $_GET['sort'] == 'email'){?>
                    <span class="icon-arrow-up2 <?=$params['rotated']?>"></span>
                <?}?>
            </td>
			<td>
                <a href="/admin/?view=subscribePrices&sort=title&search=<?=$params['search']?>&dir=<?=$params['dir']?>">
                    Название
                </a>
                <?if (isset($_GET['sort']) && $_GET['sort'] == 'title'){?>
                    <span class="icon-arrow-up2 <?=$params['rotated']?>"></span>
                <?}?>
            </td>
			<td>Телефон</td>
			<td>
                <a href="/admin/?view=subscribePrices&sort=date&search=<?=$params['search']?>&dir=<?=$params['dir']?>">
                    Дата заказа
                </a>
                <?if (isset($_GET['sort']) && $_GET['sort'] == 'date'){?>
                    <span class="icon-arrow-up2 <?=$params['rotated']?>"></span>
                <?}?>
            </td>
			<td></td>
		</tr>
		<?if ($res_common_list->num_rows){
			foreach($res_common_list as $item){?>
				<tr class="edit <?=$item['id'] ? 'user_id' : ''?>">
					<td label="Email"><?=$item['email']?></td>
					<td label="Название"><?=$item['title']?></td>
					<td label="Телефон"><?=$item['phone']?></td>
					<td label="Дата"><?=$item['date']?></td>
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