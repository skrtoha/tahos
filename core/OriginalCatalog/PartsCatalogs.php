<?
namespace core\OriginalCatalog;

use core\OriginalCatalog\OriginalCatalog;
use core\Setting;
use core\Provider;
use core\Brend; 

class PartsCatalogs extends OriginalCatalog{
	public static function getParams(){
		static $params;
		$params = new \stdClass();
		$params->ApiKey = 'OEM-API-C5AA077A-DC97-45B8-AE6A-6E5BB12E706E';
		$params->url = 'https://api.parts-catalogs.com/v1/';
		return $params;
	}

	public static function getUserIP(){
		$json = Provider::getCurlUrlData(self::getParams()->url . 'ip/', [], [
			'Authorization: ' . self::getParams()->ApiKey,
			'accept: application/json'
		]);
		$result = json_decode($json);
		return $result['ip'];
	}

	private static function getBrendInfo($brand_name, $provider_id = false){
		$res_brend = Brend::get(
			[
				'title' => $brand_name, 
				'provider_id' => $provider_id,
			], 
			['href']/*, 'result'*/);
		if (!$res_brend->num_rows){
			$brends[$brand_name] = NULL;
			return false;
		} 
		$brend = $res_brend->fetch_assoc();
		if ($brend['parent_id']) $brend_id = $brend['parent_id'];
		else $brend_id = $brend['id'];
		return [
			'id' => $brend_id,
			'href' => $brend['href']
		];
	}

	/**
	 * возвращает список допустимых брендов
	 * @return object объект с брендами вида 
	 *                       brend_id(наш id бренда по базе) => [
	 *                       	'href' - href бренда по нашей базе,
	 *                       	'name' - наименование PartsCatalogs,
	 *                        	'id' - id PartsCatalogs
	 *                       ]
	 */
	public static function getAvailableBrends(){
		static $brends;
		if (!$brends){
			$brends = new \stdClass();
			$json = Provider::getCurlUrlData(self::getParams()->url . 'catalogs/', [], [
				'Authorization: ' . self::getParams()->ApiKey,
				'accept: application/json'
			]);
			$result = json_decode($json);
			foreach($result as $obj){
				$name = $obj->name;
				$brendInfo = self::getBrendInfo($name, \core\Provider\Tahos::$provider_id);
				$brend_id = $brendInfo['id'];
				$brends->$brend_id->href = $brendInfo['href'];
				$brends->$brend_id->name = $name;
				$brends->$brend_id->id = $obj->id;
			} 
		}
		return $brends;
	}

	/**
	 * возращает id бренда PartsCatalogs по нашему href
	 * @param  string $brendHref наш href
	 * @return string 
	 */
	private static function getIdOfBrend($brendHref){
		$brends = self::getAvailableBrends();
		foreach($brends as $brend_id => $obj){
			if ($obj->href == $brendHref) return $obj->href;
		}
		return false;
	}

	public static function getModels($brendHref){
		$idOfBrend = self::getIdOfBrend($brendHref);
		if (!$idOfBrend) return false;

		$json = Provider::getCurlUrlData(self::getParams()->url . 'catalogs/' . $idOfBrend . '/models/', [], [
			'Authorization: ' . self::getParams()->ApiKey,
			'accept: application/json'
		]);
		$result = json_decode($json);
		if (isset($result->code)) return [];
		return $result;
	}

	public static function getCarsByModelIdWithFilters($brendHref, $modelId){
		$idOfBrend = self::getIdOfBrend($brendHref);
		if (!$idOfBrend) return false;

		$json = Provider::getCurlUrlData(self::getParams()->url . 'catalogs/' . $idOfBrend . '/cars2/?modelId=' . $modelId, [], [
			'Accept-Language: ru',
			'Authorization: ' . self::getParams()->ApiKey,
			'accept: application/json'
		]);

		$result = json_decode($json);

		$filtersCommonList = [];
		$output = [];

		foreach($result as $m){
			$output[$m->id]['title'] = $m->name;
			if (!empty($m->parameters)){
				foreach($m->parameters as $p){
					$filtersCommonList[] = $p->name;
					$output[$m->id]['parameters'][$p->name] = $p->value;
				}
			}
		}

		return [
			'cars' => $output,
			'filtersCommonList' => array_unique($filtersCommonList)
		];
	}

	public static function getGroupInfo($brendHref, $carId, $groupId){
		$idOfBrend = self::getIdOfBrend($brendHref);
		if (!$idOfBrend) return false;

		$json = Provider::getCurlUrlData(self::getParams()->url . 'catalogs/' . $idOfBrend . '/groups2/?carId=' . $carId . '&groupId=' . $groupId, [], [
			'Accept-Language: ru',
			'Authorization: ' . self::getParams()->ApiKey,
			'accept: application/json'
		]);
		return json_decode($json);
	}

	public static function getNodes($brendHref, $carId){
		$idOfBrend = self::getIdOfBrend($brendHref);
		if (!$idOfBrend) return false;

		$nodes = [];

		$json = Provider::getCurlUrlData(self::getParams()->url . 'catalogs/' . $idOfBrend . '/groups2/?carId=' . $carId, [], [
			'Accept-Language: ru',
			'Authorization: ' . self::getParams()->ApiKey,
			'accept: application/json'
		]);

		return json_decode($json);
	}

	public static function getModelInfoByModelID($brendHref, $modelid){
		$models = self::getModels($brendHref);
		foreach($models as $model){
			if ($model->id == $modelid) return $model;
		}
		return false;
	}

	public static function getNode($brendHref, $carId, $groupId){
		$idOfBrend = self::getIdOfBrend($brendHref);
		if (!$idOfBrend) return false;
		$json = Provider::getCurlUrlData(self::getParams()->url . 'catalogs/' . $idOfBrend . '/parts2/' . '?carId=' . $carId . '&groupId=' . $groupId, [], [
			'Authorization: ' . self::getParams()->ApiKey,
			'accept: application/json',
			'Accept-Language: ru'
		]);
		$result = json_decode($json);
		return $result;
	}
}