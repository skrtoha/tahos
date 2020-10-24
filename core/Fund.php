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
		return $GLOBALS['db']->insert(
			'funds',
			[
				'type_operation' => $type_operation,
				'sum' => $fields['sum'],
				'remainder' => $fields['remainder'],
				'user_id' => $fields['user_id'],
				'comment' => $fields['comment']
			]
		, '');
	}

	public static function getQueryListFunds($where = '', $having = ''){
		if ($where) $where = "WHERE $where";
		return  "
			SELECT
				f.*,
				IF(
					u.organization_name <> '',
					CONCAT_WS (' ', u.organization_name, ot.title),
					CONCAT_WS (' ', u.name_1, u.name_2, u.name_3)
				) AS full_name,
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
				#organizations_types ot ON ot.id=u.organization_type		
			$where			
			$having
			ORDER BY
				f.id DESC
		";
	}
}
