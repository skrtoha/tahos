<?
$dbtest = new Database([
	'host' => 'localhost',
	'db' => 'tahostest',
	'user' => 'tahos',
	'password' => '',
	'db_prefix' => 'tahostest_'
]);
class Abcp{
	private $userlogin = 'info@tahos.ru';
	private $userpsw = 'vk640431';
	private $baseUrl = 'http://voshod-avto.ru.public.api.abcp.ru/search';
	private $brands;
	private $db;
	public $number;
	public function __construct($number, $db = null){
		$this->number = preg_replace('/\W+/', '', $number);
		$this->userpsw = md5($this->userpsw);
		$this->db = $db;
		$this->brands = $this->db->select_unique("
			SELECT
				b.title AS brand
			FROM
				#items i
			LEFT JOIN
				#brends b ON b.id=i.brend_id
			WHERE
				i.article='$this->number'
		", '');
	}
	private function getQueryString($type, $params = []){
		$str = $this->baseUrl."/$type/?userlogin=$this->userlogin&userpsw=$this->userpsw";
		if (empty($params)) return $str; 
		foreach($params as $key => $value) $str .= "&$key=$value";
		return $str;
	}
	public function getItems(){
		foreach($this->brands as $key => $value){
			$url = $this->getQueryString('articles', ['number' => $this->number, 'brand' => $value['brand']]);
			$response = file_get_contents($url);
			$array = json_decode($response, true);
			// foreach($array as $v) $items[$v['distributorId']][] = $v;
			$items = $array;
		}
		debug($items);
		exit();
		$url = $this->getQueryString('batch');
		$string = static::getPostData('http://voshod-avto.ru.public.api.abcp.ru/search/batch', $data);
		$array = json_decode($string, true);
		debug($array);
	}
	private function getBrands(){
		$queryString = $this->getQueryString('brands', ['number' => $this->number]);
		$response = file_get_contents($queryString);
		$response = json_decode($response, true);
		// debug($response); exit();
		foreach($response as $value) $brands[] = [
			'number' => $value['number'],
			'brand' => $value['brand']
		];
		return $brands;
	}
	public static function getPostData($url, $data){
		$context = stream_context_create([
			'http' => [
				'method' => 'POST',
				'content' => http_build_query($data)
			]
		]);
		return file_get_contents($url, null, $context);
	}
}
$abcp = new Abcp($_GET['search'], $db, $dbtest);
// debug($abcp);
$items = $abcp->getItems();
// echo $abcp->getBrands();
exit();


$query = "http://voshod-avto.ru.public.api.abcp.ru/search/batch/?userlogin=info@tahos.ru&userpsw=132ac40e1f227fe2b22ad6182d296ab8&search[0][number]=01089&search[0][brand]=Febi";
$response = file_get_contents($query);
// echo "$response";

debug($response);
?>