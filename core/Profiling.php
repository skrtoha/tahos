<?
namespace core;
class Profiling{
	private $sequence = 1;
	private $thread_id = NULL;
	private $db_prefix;
	public function __construct($config, $connection_id){
		$this->mysqli = new \mysqli(
			$config->host,
			$config->user,
			$config->password,
			$config->db
		);
		$this->db_prefix = $config->db_prefix;
		$this->connection_id = $connection_id;
		$this->mysqli->query("SET NAMES utf8");
		$this->thread_id = $this->createThread();
		if (!$this->thread_id) die("Ошибка получения thread_id");
	}
	private function getUserIdForProfiling(){
		if (isset($_SESSION['auth']) && preg_match('/^\/admin/', $_SERVER['REQUEST_URI'])) return 0;
		if (isset($_SESSION['user'])) return $_SESSION['user'];
		return 'NULL';
	}
	private function createThread(){
		$user_id = $this->getUserIdForProfiling();
		$this->mysqli->query("
			INSERT INTO
				{$this->db_prefix}query_threads
				(`ip`, `user_id`, `url`, `connection_id`)
			VALUES (
				'{$_SERVER['REMOTE_ADDR']}',
				$user_id,
				'{$_SERVER['REQUEST_URI']}',
				{$this->connection_id}
			)
		");
		return $this->mysqli->insert_id;
	}
	public function add($data){
		$query = $this->mysqli->real_escape_string($data['query']);
		$error = $this->mysqli->real_escape_string($data['error']);
		$res = $this->mysqli->query("
			INSERT INTO
				{$this->db_prefix}queries
				(`thread_id`, `query`, `affectedRows`, `error`, `duration`, `sequence`) 
			VALUES (
				{$this->thread_id},
				'$query',
				{$data['affected_rows']},
				'$error',
				{$data['duration']},
				{$this->sequence}
			)
		");
		if ($res !== true) die("Ошибка с tahos_queries {$this->mysqli->error} | {$query}");
		$this->sequence++;
	}
}
