<?
namespace core\OriginalCatalog;

use core\OriginalCatalog\OriginalCatalog;
use core\OriginalCatalog\PartsCatalogs;
use core\Setting;

class OriginalCatalog{
	public static function getCommonListModels($vehicle, $brend){
		$commonListOfModels = [];
		$res_models = $GLOBALS['db']->query("
			SELECT
				m.id,
				m.title,
				m.href
			FROM
				#models AS m
			LEFT JOIN #vehicles v ON m.vehicle_id=v.id
			LEFT JOIN #brends b ON m.brend_id=b.id
			WHERE
				b.href='$vehicle' AND v.href='$brend'
			ORDER BY m.title
		", '');
		if ($res_models->num_rows){
			while($row = $res_models->fetch_assoc()){
				$commonListOfModels[$row['id']]['title'] = $row['title'];
				$commonListOfModels[$row['id']]['href'] = $row['href'];			
			}
		}

		$modelsPartsCatalogs = PartsCatalogs::getModels($_GET['brend']);
		$i = 0;
		foreach($modelsPartsCatalogs as $obj){
			$commonListOfModels['pc_' . $i]['title'] = $obj->name;
			$commonListOfModels['pc_' . $i]['href'] = $obj->id;
			$i++;
		}

		$models = [];
		foreach($commonListOfModels as $id => $row){
			$letter = mb_strtoupper(mb_substr($row['title'], 0 , 1, 'UTF-8'), 'UTF-8');
			$models[$letter][$id] = [
				'title' => $row['title'],
				'href' => $row['href']
			];
		}
		return $models;
	}
}