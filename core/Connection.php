<?
namespace core;

class Connection{
	private $deny = [
		'46.229.168',
		'137.0.0.1'
	];
	private $deniedPages = [
		'article',
		'search',
		'original-catalogs'
	];
	private $remoteAddr;
	private $url;
	public $denyAccess = false;
	public $connection_id;
	public function __construct($db){
		$remoteAddr = $_SERVER['REMOTE_ADDR'];
		$this->db = $db;
		if ($_SESSION['user']) $this->add([
			'ip' => $remoteAddr,
			'url' => $_SERVER['REQUEST_URI'],
			'user_id' => $_SESSION['user'],
			'comment' => $_SERVER['HTTP_USER_AGENT']
		]);
		elseif ($this->isDeniedIp($remoteAddr) && $this->isDeniedPage($_SERVER['REQUEST_URI'])){
			$this->add([
				'ip' => $remoteAddr, 
				'url' => $_SERVER['REQUEST_URI'],
				'isDenyAccess' => 1,
				'comment' => $_SERVER['HTTP_USER_AGENT']
			]);
			$this->denyAccess = true;
		}
		else $this->add([
			'ip' => $remoteAddr,
			'url' => $_SERVER['REQUEST_URI'],
			'comment' => $_SERVER['HTTP_USER_AGENT']
		]);
	}
	private function isDeniedPage($page){
		foreach ($this->deniedPages as $value){
			if (preg_match("/^\/$value/", $page)) return true;
		}
		return false;
	}
	private function isDeniedIp($str){
		foreach ($this->deny as $value){
			if (preg_match("/^$value/", $str)){
				return true;
			} 
		}
		return false;
	}
	private function add($params){
		$res = $this->db->insert(
			'connections',
			[
				'ip' => $params['ip'],
				'url' => $params['url'],
				'user_id' => isset($params['user_id']) ? $params['user_id'] : null,
				'isDenyAccess' => isset($params['isDenyAccess']) ? $params['isDenyAccess'] : null,
				'comment' => isset($params['comment']) ? $params['comment'] : null
			],
			['print_query' => false]
		);
		$this->connection_id = $this->db->last_id();
	}
}
