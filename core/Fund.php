<?php
namespace core;
class Fund{
	/**
	 * inserts fund into funds
	 * @param  [interger] $type_operation type_operation
	 * @param  [array] $fields sum, remainder, user_id, comment
	 * @return [boolean] true if inserted successfully 
	 */
	public static function insert($type_operation, $fields){
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
		return $GLOBALS['db']->insert('funds', $insert);
	}

    /**
     * @param $where
     * @param $having
     * @param $order
     * @return string
     */
	public static function getQueryListFunds($where = '', $having = '', $order = ''){
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
}
