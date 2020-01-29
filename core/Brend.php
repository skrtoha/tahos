<?php
namespace core;
class Brend{
	private static $defaultFields = ['id', 'title', 'parent_id'];
	public static function get($conditions = array(), $additionalFields = array()){
		$fields = array_merge(self::$defaultFields, $additionalFields);
		$where = '';
		$joins = array();
		if (!empty($conditions)){
			foreach ($conditions as $field => $value){
				switch($field){
					case 'id': $where .= "b.id = {$value} AND "; break;
					case 'title': 
						if (isset($conditions['provider_id'])) $where .= "(b.title LIKE '%$value%' OR (pb.title LIKE '%$value%' AND pb.provider_id = {$conditions['provider_id']})) AND ";
						else $where .= "b.title LIKE '%$value%' AND ";
						break;
					case 'provider_id':
						$joins[] = "LEFT JOIN #provider_brends pb ON pb.brend_id = b.id";
						break;
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
		", 'result');
	}
	private static function getFields($fields){
		$output = '';
		foreach($fields as $field){
			switch($field){
				case 'provider_id': $output .= "pb.provider_id,"; break;
				default:
					$output .= "b.{$field},";
			};
		}
		return substr($output, 0, -1);
	}
}
