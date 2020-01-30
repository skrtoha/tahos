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
						if (isset($conditions['provider_id'])) $where .= "(b.title = '$value' OR (pb.title = '$value' AND pb.provider_id = {$conditions['provider_id']})) AND ";
						else $where .= "b.title = '$value' AND ";
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
		$brendsList = $GLOBALS['db']->query("
			SELECT
				".self::getFields($fields)."
			FROM
				#brends b
			".implode(' ', $joins)."
			$where
		", '');
		if (isset($additionalFields['provider_id'])){
			foreach($brendsList as $value) {
				if ($value['provider_id']) return $value;
			}
		}

	}
	private static function getFields($fields){
		$output = '';
		foreach($fields as $field){
			switch($field){
				case 'provider_id': $output .= "pb.provider_id,"; break;
				case 'title':
					if (in_array('provider_id', $fields)) $output .= "IF(pb.provider_id IS NOT NULL, pb.title, b.title) AS title,";
					else $output .= "b.title,";
					break;
				default:
					$output .= "b.{$field},";
			};
		}
		return substr($output, 0, -1);
	}
}
