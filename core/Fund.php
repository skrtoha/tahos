<?php
namespace core;
class Fund{
    /**
     * Для получения последнего id
     * @var integer
     */
    public static $last_id;

    /**
     * inserts fund into funds
     * @param $type_operation
     * @param $fields
     * @return true [boolean] true if inserted successfully
     * @throws \Exception
     */
	public static function insert($type_operation, $fields): bool
    {
		$insert = [
			'type_operation' => $type_operation,
			'sum' => $fields['sum'],
			'remainder' => $fields['remainder'],
			'user_id' => $fields['user_id'],
            'paid' => $fields['paid'] ?? 0,
            'issue_id' => $fields['issue_id'] ?? null,
			'overdue' => $fields['overdue'] ?? 0,
			'comment' => $fields['comment'],
            'bill_type' => $fields['bill_type']
		];
		if ($type_operation == 1) $insert['is_new'] = 1;

        $result = Database::getInstance()->insert('funds', $insert);
        if ($result !== true) throw new \Exception("Ошибка вставки funds: $result");

        self::$last_id = Database::getInstance()->last_id();

		return true;
	}

    /**
     * @param string $where
     * @param string $having
     * @param string $order
     * @return string
     */
	public static function getQueryListFunds(string $where = '', string $having = '', string $order = ''): string
    {
		if ($where) $where = "WHERE $where";
		if (!$order) $order = "f.id DESC";
		if ($having) $having = "HAVING $having";
		return  "
			SELECT
				f.*,
				" . User::getUserFullNameForQuery() . " AS full_name,
				DATE_FORMAT(
					DATE_ADD(
						f.created, Interval u.defermentOfPayment DAY
					), '%d.%m.%Y'
				) AS date_payment
			FROM
				#funds f
			LEFT JOIN
				#users u ON u.id = f.user_id
            LEFT JOIN
                #order_issues oi ON f.issue_id = oi.id
			LEFT JOIN 
				#organizations_types ot ON ot.id=u.organization_type		
			$where			
			$having
			ORDER BY $order
		";
	}

    public static function setFundDistribution($debit_id, $replenishment_id, $sum){
        Database::getInstance()->insert(
            'fund_distribution',
            [
                'debit_id' => $debit_id,
                'replenishment_id' => $replenishment_id,
                'sum' => $sum
            ],
            ['duplicate' => [
                'sum' => $sum
            ]]
        );
    }
}
