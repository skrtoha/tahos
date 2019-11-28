<?
class Issues{
	public $user_id;
	function __construct($user_id = null, $db){
		if ($user_id) $this->user_id = $user_id;
		$this->db = $db;
	}
	function getUser(){
		return $this->db->select_one('users', ['name_1', 'name_2', 'name_3', 'reserved_funds', 'bill'], "`id`=$this->user_id");
	}
	function getOrderValues(){
		$query = "
			SELECT
				ps.cipher,
				b.title AS brend,
				i.article,
				i.id AS item_id,
				IF (i.title_full<>'', i.title_full, i.title) AS title_full,
				ov.issued,
				ov.price,
				ov.ordered,
				ov.arrived,
				ov.comment,
				o.id AS order_id,
				DATE_FORMAT(o.created, '%d.%m.%Y %H:%i') as created,
				os.title AS status,
				os.class AS class
			FROM
				#orders_values ov
			LEFT JOIN #orders o ON o.id=ov.order_id
			LEFT JOIN #provider_stores ps ON ps.id=ov.store_id
			LEFT JOIN #items i ON i.id=ov.item_id
			LEFT JOIN #brends b ON i.brend_id=b.id
			LEFT JOIN #orders_statuses os ON os.id=ov.status_id
			WHERE
				o.user_id=$this->user_id AND
				ov.status_id=3
			ORDER BY o.created DESC
		";
		return $this->db->query($query, '');
	}
	protected function getTitleForFund($item_id){
		$item = $this->db->select_unique("
			SELECT
				i.article,
				i.title_full,
				b.title as brend
			FROM
				#items i
			LEFT JOIN
				#brends b ON b.id=i.brend_id
			WHERE
				i.id={$item_id}
		", '');
		$item = $item[0];
		return '<a href="/admin/?view=item&id='.$item_id.'">'.$item['brend'].' - '.$item['article'].' - '.$item['title_full'].'</a>';
	}
	protected function getOrderValue($order_id, $item_id){
		return $this->db->select_one('orders_values', '*', "`order_id`={$order_id} AND `item_id`={$item_id}");
	}
	function setIncome(){
		// debug($_POST); exit();
		$insert_order_issue = $this->db->insert('order_issues', ['user_id' => $this->user_id], ['print_query' => false]);
		if ($insert_order_issue !== true) die("Ошибка: $this->last_query | $insert_order_issue");
		$issue_id = $this->db->last_id();
		foreach($_POST['income'] as $key => $issued){
			$a = explode(':', $key);
			$user = $this->getUser();
			$title = $this->getTitleForFund($a[1]);
			$ov = $this->getOrderValue($a[0], $a[1]);
			$insert_order_issue_values = $this->db->insert(
				'order_issue_values',
				[
					'issue_id' => $issue_id,
					'order_id' => $a[0],
					'item_id' => $a[1],
					'issued' => $issued,
					'comment' => $_POST['comment'][$key]
				]
			);
			if ($insert_order_issue_values !== true) die("Ошибка: $this->db->$last_query | $insert_order_issue_values");
			//debug($title); debug($ov); //continue;
			$status_id = ($ov['issued'] + $issued < $ov['arrived']) ? 3 : 1;
			$res_1 = $this->db->query("
				UPDATE 
					#orders_values 
				SET 
					`issued` = `issued` + {$issued},
					`status_id` = $status_id
				WHERE 
					`order_id`={$a[0]} AND
					`item_id`={$a[1]}
			", '');
			// exit();
			$remainder = $user['bill'] - $ov['price'] * $ov['arrived'];
			$res_2 = $this->db->insert(
				'funds',
				[
					'type_operation' => 2,
					'sum' => $ov['price'] * $ov['arrived'],
					'remainder' => $remainder,
					'user_id' => $this->user_id,
					'comment' => addslashes('Списание средств на оплату "'.$title.'"')
				],
				['print_query' => false]
			);
			if ($user['bonus_program']){
				$bonus_size = $this->db->getField('settings', 'bonus_size', 'id', 1);
				$bonus_count = floor($ov['price'] * $ov['arrived'] * $bonus_size / 100);
				$res_4 = $this->db->insert(
					'funds',
					[
						'type_operation' => 3,
						'sum' => $bonus_count,
						'remainder' => $user['bonus_count'] + $bonus_count,
						'user_id' => $this->user_id,
						'comment' => addslashes('Начисление бонусов за "'.$title.'"'),
					],
					['print_query' => false]
				);
			}
			$res_3 = $this->db->query("
				UPDATE
					#users
				SET
					`reserved_funds`=`reserved_funds` - {$ov['price']} * {$ov['arrived']},
					`bill`= `bill` - {$ov['price']} * {$ov['arrived']}
				WHERE
					`id`={$this->user_id}
			", '');
		}
		if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
			echo $issue_id;
			exit();
		}
		message('Успешно сохранено');
		header("Location: /admin/?view=order_issues&issue_id={$issue_id}");
	}
	protected function getOrderIssues(){
		if ($_GET['user_id']) $where = "WHERE oi.user_id={$_GET['user_id']}";
		else $where = null;
		$start = ($_GET['pageNumber'] - 1) * $_GET['pageSize'];
		$res = $this->db->select_unique("
			SELECT
				oiv.issue_id,
				oi.user_id,
				CONCAT_WS(' ', u.name_1, u.name_2, u.name_3) AS user,
				SUM(ov.price * oiv.issued) AS sum,
				DATE_FORMAT(oi.created, '%d.%m.%Y') AS created
			FROM
				#order_issue_values oiv
			LEFT JOIN #order_issues oi ON oi.id = oiv.issue_id
			LEFT JOIN #orders_values ov ON ov.order_id = oiv.order_id AND ov.item_id = oiv.item_id
			LEFT JOIN #users u ON oi.user_id=u.id
			$where
			GROUP BY oiv.issue_id
			ORDER BY	
				oi.created DESC
			LIMIT $start, {$_GET['pageSize']}
		", '');
		return $res;
	}
	protected function commonList(){
		echo json_encode($this->getOrderIssues());
	}
	protected function getIssueValues($user_id){
		$start = ($_GET['pageNumber'] - 1) * $_GET['pageSize'];
		return $this->db->select_unique("
			SELECT
				oiv.issue_id,
				oiv.order_id,
				oiv.item_id,
				i.brend_id,
				b.title AS brend,
				i.article,
				i.title_full,
				oiv.issued,
				oiv.comment
			FROM
				#order_issue_values oiv
			LEFT JOIN
				#order_issues oi ON oi.id=oiv.issue_id
			LEFT JOIN
				#items i ON oiv.item_id=i.id
			LEFT JOIN
				#brends b ON b.id=i.brend_id
			WHERE
				oi.user_id=$user_id
			ORDER BY
				oi.created DESC
			LIMIT $start, {$_GET['pageSize']}
		", '');
	}
	function getAjax(){
		// debug($_GET);
		switch($_GET['ajax']){
			case 'common_list': $this->commonList(); break;
			case 'user_issue_values':
				$issue_values = $this->getIssueValues($_GET['user_id']);
				echo json_encode($issue_values);
				break;
		}
		exit();
	}
	function getIssueWithUser($issue_id){
		$res_issue = $this->db->query("
			SELECT
				oiv.order_id,
				oi.user_id,
				CONCAT_WS(' ', u.name_1, u.name_2, u.name_3) AS user_name,
				u.bill - u.reserved_funds AS user_available,
				oiv.item_id,
				i.brend_id,
				b.title AS brend,
				i.article,
				i.title_full,
				ov.price,
				oiv.issued,
				ov.comment,
				DATE_FORMAT(oi.created, '%d.%m.%Y') AS created
			FROM
				#order_issue_values oiv
			LEFT JOIN
				#order_issues oi ON oi.id = oiv.issue_id
			LEFT JOIN
				#orders_values ov ON ov.order_id=oiv.order_id AND ov.item_id=oiv.item_id
			LEFT JOIN
				#items i ON i.id=oiv.item_id
			LEFT JOIN
				#brends b ON b.id=i.brend_id
			LEFT JOIN
				#users u ON u.id=oi.user_id
			WHERE
				oi.id=$issue_id
		", '');
		$summ = 0;
		while($row = $res_issue->fetch_assoc()){
			$issue_values[] = [
				'order_id' => $row['order_id'],
				'item_id' => $row['item_id'],
				'brend' => $row['brend'],
				'article' => $row['article'],
				'title_full' => $row['title_full'],
				'price' => $row['price'],
				'issued' => $row['issued'],
				'comment' => $row['comment']
			];
			$summ += $row['price'] * $row['issued'];
			$created = $row['created'];
			$user = [
				'id' => $row['user_id'],
				'name' => $row['user_name'],
				'available' => $row['user_available']
			];
		};
		return [
			'issue_values' => $issue_values,
			'summ' => $summ,
			'created' => $created,
			'user' => $user
		];
	}
	function print($issue_id){
		$array = $this->getIssueWithUser($issue_id);
		// debug($array);
		?>
		<head>
			<title></title>
			<link rel="stylesheet" type="text/css" href="/admin/css/order_issues.css">
			<link rel="shortcut icon" href="/img/favicon/favicon.png" type="image/x-icon">
			<link rel="apple-touch-icon" href="/img/favicon/apple-touch-icon.png">
			<link rel="apple-touch-icon" sizes="72x72" href="/img/favicon/apple-touch-icon-72x72.png">
			<link rel="apple-touch-icon" sizes="114x114" href="/img/favicon/apple-touch-icon-114x114.png">
			<script src="/js/libs.min.js"></script>
		</head>
		<div id="issue_print">
			<script>
				window.onload =	 function () {window.print();}
			</script>
			<h1>Договор-выдача №<?=$_GET['issue_id']?> от <?=$array['created']?></h1>
			<p>Баранов В.В. (в дальнейшем ПОСТАВЩИК) с одной стороны и <?=$array['user']['name']?> (именуемый в дальнейшем ЗАКАЗЧИК), заключили настоящий договор о нижеследующем:</p>
			<p><b>ПОСТАВЩИК</b> обязуется выдать товары:</p>
			<table cellspacing="0">
				<tr>
					<th>Бренд</th>
					<th>Артикул</th>
					<th>Наименование</th>
					<th>Цена</th>
					<th>Кол-во</th>
					<th>Сумма</th>
					<th>Комментарий</th>
				</tr>
				<? $i = 1;
				$max = 0;
				foreach($array['issue_values'] as $value){?>
					<tr>
						<td><?=$value['brend']?></td>
						<td><?=$value['article']?></td>
						<td><?=$value['title_full']?></td>
						<td class="td_center"><?=$value['price']?></td>
						<td class="td_center"><?=$value['issued']?></td>
						<td class="td_center"><?=$value['issued'] * $value['price']?></td>
						<td><?=$value['comment']?></td>
					</tr>
					<?$total += $value['issued'] * $value['price'];?>
				<?}?>
				<tr>
					<td style="text-align: right" colspan="8">Итого: <?=$total?> руб.</td>
				</tr>
			</table>
			<p style="white-space: nowrap">
				<span>Поставщик  ______________</span>
				<span><strong>Клиент: <span id="square"></span></strong></span>
				<span><?=$array['user']['name']?></span>
			</p>
		</div>
	<?exit();
	
	}
}