<?php
namespace core;
require_once ($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/admin/templates/functions.php');
class Index{
	public static function getHtmlOrderFunds($from, $to, $user_id){
		$resOrderFundsIssued = self::getOrderFundsIssued($from, $to, $user_id);
		if (!$resOrderFundsIssued->num_rows) return false;
		$resOrderFundsInWork = self::getOrderFundsInWork($from, $to, $user_id);
		$orderFundsInWork = $resOrderFundsInWork->fetch_assoc();
		$output = '';
		foreach($resOrderFundsIssued as $value){
			$output .= "
				<tr>
					<td label=\"Выдано\">{$value['common']}</td>
					<td label=\"В работе\">{$orderFundsInWork['common']}</td>
					<td label=\"Закупка\">{$value['spent']}</td>
					<td label=\"Прибыль\">{$value['difference']}</td>
					<td label=\"Процент\">{$value['percent']}</td>
				</tr>
			";
		}
		return $output;
	}
	public static function getOrderFundsIssued($from, $to, $user_id): \mysqli_result
	{
		$from = Connection::getTimestamp($from);
		$to = Connection::getTimestamp($to);
		if ($user_id){
			$whereUser = " AND ov.user_id = $user_id";
		} 
		$res = $GLOBALS['db']->query("
			SELECT
				SUM(ov.price * ov.issued) AS common,
				ROUND(SUM((ov.price - ov.price * ps.percent / 100) * ov.issued)) AS spent,
				ROUND(SUM(ov.price * ov.issued) - SUM((ov.price - ov.price * ps.percent / 100) * ov.issued)) AS difference,
				ROUND((SUM(ov.price * ov.issued) - SUM((ov.price - ov.price * ps.percent / 100) * ov.issued)) / (SUM(ov.price * ov.issued)) * 100, 2) AS percent
			FROM
				#orders_values ov
			LEFT JOIN
				#provider_stores ps ON ps.id = ov.store_id
			WHERE
				ov.status_id = 1 AND ov.updated BETWEEN '$from' AND '$to' $whereUser
		", '');
		return $res;
	}
	public static function getOrderFundsInWork($from, $to, $user_id): \mysqli_result
	{
		$from = Connection::getTimestamp($from);
		$to = Connection::getTimestamp($to);
		if ($user_id){
			$whereUser = " AND ov.user_id = $user_id";
		} 
		$res = $GLOBALS['db']->query("
			SELECT
				SUM(ov.price * ov.quan) AS common
			FROM
				#orders_values ov
			WHERE
				ov.status_id IN (7, 11) AND ov.updated BETWEEN '$from' AND '$to' $whereUser
		", '');
		return $res;
	}
}
