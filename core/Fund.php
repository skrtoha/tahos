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
}
