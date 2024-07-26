<?php
namespace core;
class Brend{
	private static $defaultFields = ['id', 'title', 'parent_id'];
	private static $additionalFields = array();
	/**
	 * gets brends by coditions
	 * @param  array  $conditions 
	 *         provider_id - adds table provider_brends and search through it
	 * @param  array  $additionalFields fields that have to be added to output results
	 * @param string for debugging (result, print)
	 * @return mixed false in no results, else mysqli object
	 */
	public static function get($conditions = array(), $additionalFields = array(), $flag = '')
    {
		if ($additionalFields) self::$additionalFields = $additionalFields;
		if (isset($conditions['provider_id'])) self::$additionalFields[] = 'provider_id';
		$fields = array_merge(self::$defaultFields, self::$additionalFields);
		$where = '';
		$limit = '';
		$joins = array();
		if (!empty($conditions)){
			foreach ($conditions as $field => $value){
				switch($field){
					case 'id': 
						if (isset($conditions['provider_id'])){
							$where .= "(b.id = {$value} OR (pb.brend_id = $value AND pb.provider_id = {$conditions['provider_id']})) AND ";
						}
						else  $where .= "b.id = {$value} AND "; 
					break;
					case 'parent_id':
						$where .= "b.parent_id = $value AND ";
						break;
					case 'title': 
						if (isset($conditions['provider_id'])) $where .= "(b.title = '$value' OR (pb.title = '$value' AND pb.provider_id = {$conditions['provider_id']})) AND ";
						else $where .= "b.title LIKE '$value%' AND ";
						break;
					case 'provider_id':
						$joins[] = "LEFT JOIN #provider_brends pb ON pb.brend_id = b.id";
						break;
					case 'limit':
						$limit .= "LIMIT 0, $value";
				}
			}
		}
		if ($where){
			$where = substr($where, 0, -4);
			$where = "WHERE $where";
		} 
		$res = $GLOBALS['db']->query("
			SELECT
				".self::getFields($fields)."
			FROM
				#brends b
			".implode(' ', $joins)."
			$where
			$limit
		", $flag);
		if ($res->num_rows) return $res;
		else return false;
	}
	/**
	 * gets brend_id
	 * @param mixed $brendsList  array or mysqli_result
	 * @return int brend_id
	 */
	public static function getBrendIdFromList($brendsList){
		$brendFirst = array();
		foreach($brendsList as $brend){
			if (empty($brendFirst)) $brendFirst = $brend;
			if ($brend['provider_id']) return $brend['id'];
		}
		foreach($brendsList as $brend){
			if ($brend['parent_id']) return $brend['parent_id'];
		}
		return $brendFirst['id'];
	}
	/**
	 * gets string of fields for additing to condition
	 * @param  array  $fields array of fields
	 * @return string output string
	 */
	private static function getFields(array $fields){
		$output = '';
		foreach($fields as $field){
			switch($field){
				case 'provider_id': $output .= "pb.provider_id,"; break;
				case 'title':
					if (in_array('provider_id', self::$additionalFields)) $output .= "IF(pb.provider_id IS NOT NULL, pb.title, b.title) AS title,";
					else $output .= "b.title,";
					break;
				default:
					$output .= "b.{$field},";
			};
		}
		return substr($output, 0, -1);
	}
}
