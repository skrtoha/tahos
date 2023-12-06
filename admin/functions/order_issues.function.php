<?
use core\Database;
use core\User;
class Issues{
	public $user_id;
	function __construct($db, $user_id = null){
		if ($user_id) $this->user_id = $user_id;
		$this->db = $db;
	}
    //todo метод модернизирован только при использовании из админки, также нужна модифмкация для 1С
	function setIncome($incomeList, $isRequestFrom1C = false): array
    {
        Database::getInstance()->startTransaction();
        $output = [
            User::BILL_CASH => 0,
            User::BILL_CASHLESS => 0
        ];
		foreach($incomeList as $bill_type => $income){
            $insert_order_issue = Database::getInstance()->insert(
                'order_issues',
                [
                    'user_id' => $this->user_id,
                    'bill_type' => $bill_type
                ]
            );
            if ($insert_order_issue !== true) die("Ошибка: {$this->db->last_query} | $insert_order_issue");
            $issue_id = Database::getInstance()->last_id();

            $titles = [];
            $totalSumm = 0;
            foreach($income as $key => $issued){
                $a = explode(':', $key);
                $insert_order_issue_values = Database::getInstance()->insert(
                    'order_issue_values',
                    [
                        'issue_id' => $issue_id,
                        'order_id' => $a[0],
                        'store_id' => $a[2],
                        'item_id' => $a[1],
                        'issued' => $issued
                    ]
                );

                if ($insert_order_issue_values !== true){
                    die("Ошибка: {$this->db->last_query} | $insert_order_issue_values");
                }

                $res_orderValue = core\OrderValue::get([
                    'order_id' => $a[0],
                    'store_id' => $a[2],
                    'item_id' => $a[1],
                ]);
                $orderValue = $res_orderValue->fetch_assoc();
                $titles[] = "<b>{$orderValue['brend']} {$orderValue['article']}</b>";

                if (!$orderValue['is_payed']){
                    $totalSumm += $issued * $orderValue['price'];
                }

                $array = [
                    'order_id' => $a[0],
                    'store_id' => $a[2],
                    'item_id' => $a[1],
                    'issued' => $issued
                ];
                if ($isRequestFrom1C) $array['synchronized'] = 1;

                core\OrderValue::changeStatus(1, $array, true);
            }

            core\OrderValue::setFunds([
                'user_id' => $this->user_id,
                'issue_id' => $issue_id,
                'titles' => $titles,
                'totalSumm' => $totalSumm,
                'bill_type' => $bill_type
            ]);

            core\User::setBonusProgram($this->user_id, $titles, $totalSumm);

            if ($bill_type == User::BILL_CASH) $pay_type = 'Наличный';
            if ($bill_type == User::BILL_CASHLESS) $pay_type = 'Безналичный';
            User::updateReservedFunds($this->user_id, $totalSumm, 'minus', $pay_type);

            if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
                Database::getInstance()->commit();
                echo $issue_id;
                exit();
            }

            /*//если запрос пришел с 1С тогда проводить товар там не нужно
            if (!$isRequestFrom1C){
                $nonSynchronizedOrders = core\Synchronization::getNoneSynchronizedOrders();
                core\Synchronization::sendRequest('orders/write_orders', $nonSynchronizedOrders);
            }*/

            $output[$bill_type] = $issue_id;

        }
        Database::getInstance()->commit();
        return $output;

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
    public static function getQueryIssueValues($fields){
        $where = '';
        if (isset($fields['user_id'])) $where .= "oi.user_id = {$fields['user_id']} AND ";
        if (isset($fields['issue_id'])) $where .= "oiv.issue_id = {$fields['issue_id']} AND ";
        if ($where){
            $where = substr($where, 0, -5);
            $where = "WHERE $where";
        }
        return "
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
			$where
			ORDER BY
				oi.created DESC
        ";
    }
	protected function getIssueValues($user_id){
		$start = ($_GET['pageNumber'] - 1) * $_GET['pageSize'];
        $query = self::getQueryIssueValues(['user_id' => $user_id]);
        $query .= " LIMIT $start, {$_GET['pageSize']}";
		return $this->db->select_unique($query, '');
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
	public function getIssueWithUser($issue_id){
		$res_issue = $this->db->query("
			SELECT
			    oiv.issue_id,
				oiv.order_id,
				oi.user_id,
				CONCAT_WS(' ', u.name_1, u.name_2, u.name_3) AS user_name,
				IF(
				    u.user_type = 'private',
				    u.bill_cash - u.reserved_cash,
				    u.bill_cashless - u.reserved_cashless
				) AS user_available,
				oiv.item_id,
				i.brend_id,
				b.title AS brend,
				i.article,
				i.title_full,
				ov.price,
				oiv.issued,
				ov.comment,
				DATE_FORMAT(oi.created, '%d.%m.%Y') AS created,
				oi.bill_type
			FROM
				#order_issue_values oiv
			LEFT JOIN
				#order_issues oi ON oi.id = oiv.issue_id
			LEFT JOIN
				#orders_values ov ON ov.order_id=oiv.order_id AND ov.item_id=oiv.item_id AND ov.store_id=oiv.store_id
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
                'issue_id' => $row['issue_id'],
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
            $bill_type = $row['bill_type'];
			$user = [
				'id' => $row['user_id'],
				'name' => $row['user_name'],
				'available' => $row['user_available']
			];
		};
		return [
			'issue_values' => $issue_values,
			'summ' => $summ,
            'bill_type' => $bill_type,
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
