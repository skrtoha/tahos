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
		// debug($_SERVER); exit();
		// var_dump($this->isDeniedIP($remoteAddr));
		// var_dump($this->isDeniedPage($_SERVER['REQUEST_URI']));
		if ($_SESSION['user']) $this->add([ 
			'ip' => $remoteAddr,
			'url' => $_SERVER['REQUEST_URI'],
			'user_id' => $_SESSION['user'],
			'comment' => $_SERVER['HTTP_USER_AGENT']
		]);
		elseif (
			$this->isDeniedIP($remoteAddr) 
			&& $this->isDeniedPage($_SERVER['REQUEST_URI'])
		){
			$this->add([
				'ip' => $remoteAddr, 
				'url' => $_SERVER['REQUEST_URI'],
				'isDeniedAccess' => 1,
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
		$res_denied_addresses = $this->db->query("
			SELECT
				*
			FROM
				#forbidden_pages
		", '');
		if (!$res_denied_addresses->num_rows) return false;
		foreach($res_denied_addresses as $value){
			if (preg_match("/{$value['page']}/", $page)) return true;
		}
		return false;
	}
	private function isDeniedIP($ip){
		$res_denied_addresses = $this->db->query("
			SELECT
				*
			FROM
				#denied_addresses
			WHERE
				`ip` = '$ip'
		", '');
		if (!$res_denied_addresses->num_rows) return false;
		return true;
	}
	private function add($params){
		$res = $this->db->insert(
			'connections',
			[
				'ip' => $params['ip'],
				'url' => $params['url'],
				'user_id' => isset($params['user_id']) ? $params['user_id'] : null,
				'isDeniedAccess' => isset($params['isDeniedAccess']) ? $params['isDeniedAccess'] : null,
				'comment' => isset($params['comment']) ? $params['comment'] : null
			],
			['print_query' => false]
		);
		$this->connection_id = $this->db->last_id();
	}
	public static function getCommonList($pageSize = null, $pageNumber = null, $params = []){
		$where = '';
		$having = '';
		if (!empty($params)){
			foreach($params as $key => $value){
				switch($key){
					case 'ip':
					case 'url':
					case 'comment':
						$where .= "`$key` LIKE '%$value%' AND ";
						break;
					case 'isDeniedAccess':
						$where .= "c.isDeniedAccess = $value AND ";
						break;
					case 'name':
						$having .= "`$key` LIKE '%$value%' AND ";
						break;
					case 'isHiddenAdminPages':
						$where .= "`url` NOT LIKE '/admin%' AND ";
						break;
				}
			}
		}
		if ($where){
			$where = substr($where, 0, -5);
			$where = "WHERE $where";
		} 
		if ($having){
			$having = substr($having, 0, -5);
			$having = "HAVING $having";
		} 
		$query = "
			SELECT
				c.id,
				c.ip,
				c.url,
				c.user_id,
				IF(c.isDeniedAccess = '1', 'Да', '') AS isDeniedAccess,
				IF(
					u.organization_name <> '',
					CONCAT_WS (' ', u.organization_name, ot.title),
					CONCAT_WS (' ', u.name_1, u.name_2, u.name_3)
				) AS name,
				c.comment,
				DATE_FORMAT(c.created, '%d.%m.%Y %H:%i:%s') AS created
			FROM
				#connections c
			LEFT JOIN	
				#users u ON u.id = c.user_id
			LEFT JOIN 
				#organizations_types ot ON ot.id=u.organization_type
			$where
			$having
			ORDER BY c.created DESC

		";
		if (isset($params['getCount']) && $params['getCount']){
			$query = str_replace('SELECT', 'SELECT SQL_CALC_FOUND_ROWS', $query);
			$query = str_replace('ORDER BY c.created DESC', '', $query);
			$GLOBALS['db']->query($query, '');
			return $GLOBALS['db']->found_rows();
		}
		if ($pageSize && $pageNumber){
			$start = ($params['pageNumber'] - 1) * $params['pageSize'];
			$query .= " LIMIT $start, $pageSize";
		} 
		return $GLOBALS['db']->query($query, '');
	}
	public static function getStatistics($dateFrom, $dateTo, $params = []){
		$from = self::getTimestamp($dateFrom);
		$to = self::getTimestamp($dateTo);
		$query = "
			SELECT
				c.ip,
				COUNT(c.ip) as cnt,
				c.comment
			FROM
				#connections c
			WHERE
				c.created >= '$from' AND c.created <= '$to' AND isDeniedAccess = 0
			GROUP BY
				c.ip
			ORDER BY cnt DESC
		";
		if (isset($params['getCount']) && $params['getCount']){
			$query = str_replace('SELECT', 'SELECT SQL_CALC_FOUND_ROWS', $query);
			$query = str_replace('ORDER BY cnt DESC', '', $query);
			$GLOBALS['db']->query($query, '');
			return $GLOBALS['db']->found_rows();
		}
		if ($params['pageSize'] && $params['pageNumber']){
			$start = ($params['pageNumber'] - 1) * $params['pageSize'];
			$query .= " LIMIT $start, {$params['pageSize']}";
		} 
		$res = $GLOBALS['db']->query($query, '');
		if (!$res->num_rows) return false;
		$statistics = [];
		foreach($res as $value) $statistics[] = $value;
		return $statistics;
	}
	public static function getTimestamp($date){
		$date = \DateTime::createFromFormat('d.m.Y H:i', $date);
		return $date->format('Y-m-d H:i');
	}
}
