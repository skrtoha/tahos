<?php  
namespace core;
use core\Exceptions\DataBaseNoConnectException;
class Database {
	public $config;
	public $mysqli;
	public $last_query;
    public $indexBy = '';
	public $isProfiling = false;
	private $profiling;
	public $connection_id;
	public $found_rows;
	private $last_id;
	public function __construct(){
		$this->config = new Config();
		$this->db_prefix = $this->config->db_prefix;
		$this->mysqli = new \mysqli(
			$this->config->host,
			$this->config->user,
			$this->config->password,
			$this->config->db
		);
		if ($this->mysqli->connect_error) throw new DataBaseNoConnectException($this->mysqli->connect_error);
		$this->mysqli->query("SET NAMES utf8");
	}
	public function setConnectionID($connection_id){
		if ($connection_id){
			$this->connection_id = $connection_id;
			return true;
		}
		return false;
	}
	public function setProfiling($connection_id = null){
		if (!$this->isProfiling) return false;
		$connection_id = $connection_id ? $connection_id : $this->connection_id;
		if ($this->setConnectionID($connection_id)){
			$this->profiling = new Profiling($this->config, $this->connection_id);
			return true;
		}
		return false;
	}
	private function getDurationOfLastQuery(){
		if ($this->mysqli->error) return 'NULL';
		$res = $this->mysqli->query("SELECT SUM(duration) FROM information_schema.profiling GROUP BY query_id LIMIT 1;");
		foreach($res as $value) return ($value['SUM(duration)']);
	}
	function query($query, $show_query = '', $replaceSymbols = true){
		if ($replaceSymbols) $query = str_replace('#', $this->db_prefix, $query);
        $this->last_query = $query;
		if ($show_query == 'get') return $query;
		if ($show_query == 'print'){
			echo "<pre>$query</pre>";
			return false;
		} 
		if ($this->isProfiling) $this->mysqli->query("SET profiling=1");
		$res = $this->mysqli->query($query);
		if (preg_match('/SQL_CALC_FOUND_ROWS/', $query)) {
			$res_found_ros = $this->query("SELECT FOUND_ROWS()", '');
			$array = $res_found_ros->fetch_assoc();
			$this->found_rows = $array['FOUND_ROWS()'];
		}
		if (preg_match('/^INSERT INTO/', $query) && $res == true) $this->last_id = $this->mysqli->insert_id;
		if ($this->isProfiling && isset($this->profiling)) $this->profiling->add([
			'query' => $query,
			'affected_rows' => $this->mysqli->affected_rows,
			'error' => $this->mysqli->error,
			'insert_id' => $this->mysqli->insert_id,
			'duration' => $this->getDurationOfLastQuery(),
		]);
		if ($show_query == 'mysqli'){
			echo "$query<br>";
			debug($this->get_mysqli());
			exit();
		}
		if ($show_query == 'debug' || $show_query == 'result'){
			echo "<b>Запрос:</b><br><pre>$query</pre>";
			echo "<br>";
			$error = $this->error();
			if ($error){
				echo "<b>Ошибка: </b><br>".$this->error();
				echo "<br>";
			}
			else echo "<b>Количество строк:</b> {$res->num_rows}<br>";
			if ($show_query == 'result'){
				$array = $res->fetch_assoc();?>
				<table style="border-spacing: 0">
					<tr>
						<?foreach($array as $key => $value){?>
							<th style="border: 1px solid black"><?=$key?></th>
						<?}?>
					</tr>
					<tr>
						<?foreach($array as $key => $value){?>
							<td style="border: 1px solid black"><?=$value?></td>
						<?}?>
					</tr>
					<?while($row = $res->fetch_assoc()){?>
						<tr>
							<?foreach($row as $key => $value){?>
								<td style="border: 1px solid black"><?=$value?></td>
							<?}?>
						</tr>
					<?}?>
				</table>
			<?}
			exit();
		};
		return $res;
	}
	//объект mysqli
	function get_mysqli(){
		return $this->mysqli;
	}
	function error(){
		return $this->mysqli->error;
	}
	//получает количество затронутых строк после запроса
	function rows_affected(){
		return $this->mysqli->affected_rows;
	}
	//detected how many last select-query contents found rows without limit
	//for usage this function in select query is needed to add after select SQL_CALC_FOUND_ROWS
	function found_rows(){
		return $this->found_rows;
	}
	function last_id(){
		return $this->last_id;
	}
	function rows_matches(){
		$row = explode('  ', $this->mysqli->info);
		$r = explode(': ', $row[0]);
		return $r['1'];
	}
	function select_unique($sql, $show_query = false){
		$query = str_replace('#', $this->db_prefix, $sql);
		$this->last_query = $query;
		switch($show_query){
			case 'print':
				echo "<pre>$query</pre>";
				return false;
				break;
			case 'get':
				return $query;
				break;
			case 'debug':
				$res = $this->query($query);
				echo "<b>Запрос:</b><br><pre>$query</pre>";
				echo "<br>";
				$error = $this->error();
				if ($error){
					echo "<b>Ошибка: </b><br>".$error;
					echo "<br>";
				}
				else echo "<b>Количество строк:</b> {$res->num_rows}";
				exit();
				break;
			default:
				$res = $this->query($query);
				if ($res->num_rows == 0) return false;
				while ($row = $res->fetch_assoc()){
					$data[] = $row;
				} 
				return $data;
		}
	}
	function count_unique($sql, $show_sql = false){
		$sql = str_replace('#', $this->db_prefix, $sql);
		$this->last_query = $sql;
		$result_set = $this->query($sql);
		if ($show_sql){
			if ($show_sql == 'query') return $sql;
			echo "<b>Запрос:</b><br><pre>$sql</pre>";
			echo "<br>";
			$error = $this->error();
			if ($error){
				echo "<b>Ошибка: </b><br>".$error;
				echo "<br>";
			}
			else echo "<b>Количество строк:</b> {$result_set->num_rows}";
			exit();
		}
		else{
			if (!$result_set) return false;
			$i = 0;
			while ($row = $result_set->fetch_assoc()) {
				$data[$i] = $row;
				$i++;
			}
			$result_set->close();
			return $data[0]['count'];
		}
	}

		/**
			* @param $table_name
			* @param $fields
			* @param string $where
			* @return array|bool|null
			*/
	function select_one($table_name, $fields, $where = ""){
		$table_name = $this->db_prefix.$table_name;
		if (is_array($fields)){
			$str = "";
			foreach ($fields as $field) $str .= "`$field`,";
			$str = substr($str, 0, -1);
			$fields = $str;
		}
		elseif (strpos($fields, ",")){
			$fields = explode(",", $fields);
			$str = "";
			for ($i = 0; $i < count($fields); $i++) $str .= "`".$fields[$i]."`,";
			$str = substr($str, 0, -1);
			$fields = $str;
		}
		elseif ($fields =="*") $fields = "*";
		elseif (preg_match("/MAX/", $fields)) $fields = $fields; 
		else $fields = "`".$fields."`";
		$query = "SELECT $fields FROM $table_name WHERE $where";
		$this->last_query = $query;
		// echo $query;
		$result_set = $this->query($query);
		if ($result_set->num_rows) return $result_set->fetch_assoc();
		else return false;
	}
	function select($table_name, $fields, $where = "", $order = "", $up = true, $limit = "", $for_id = false){
		$table_name = $this->db_prefix.$table_name;
		if (is_array($fields)){
			$str = "";
			foreach ($fields as $field) $str .= "`$field`,";
			$str = substr($str, 0, -1);
			$fields = $str;
		}
		elseif (strpos($fields, ",")){
			$fields = explode(",", $fields);
			$str = "";
			for ($i = 0; $i < count($fields); $i++) $str .= "`".$fields[$i]."`,";
			$str = substr($str, 0, -1);
			$fields = $str;
		}
		elseif ($fields =="*") $fields = "*";
		elseif (preg_match("/MAX/", $fields)) $fields = $fields; 
		else $fields = "`".$fields."`";
		if (!$order) $order = "";
		else {
			if ($order != "RAND()"){
				$order = "ORDER BY $order";
				if (!$up) $order .= " DESC";
			}
			else $order = "ORDER BY $order";
		}
		if ($limit) $limit = "LIMIT $limit";
		if($where) $query = "SELECT $fields FROM $table_name WHERE $where $order $limit";
		else $query = "SELECT $fields FROM $table_name $order $limit";
		// echo $query;
		$this->last_query = $query;
		$result_set = $this->query($query);
		if (!$result_set) return false;
		$i = 0;
		while ($row = $result_set->fetch_assoc()) {
			$data[$i] = $row;
			$i++;
		}
		$result_set->close();
		if ($for_id and count($data)){
			$array = array();
			foreach ($data as $key => $value) {
				$id = array_shift($value);
				$array[$id] = $value;
			}
			return $array;
		}
        if ($this->indexBy){
            $output = [];
            foreach($data as $value){
                $field = $value[$this->indexBy];
                $output[$field] = $value;
            }
            $this->indexBy = '';
            return $output;
        }
		return $data;
	}
	/**
	 * inserts row into database
	 * @param  string $table_name table name
	 * @param  array $new_values insert values
	 * @param  array  $insert_params params
	 		print - print the query;
	 		deincrement_duplicate - reduces the autoincrement into 1 after failed query 
	 		duplicate => [
				'field' => 'value'
			],
			get - gets query
	 * @return mixed true if query is successful, else error
	 */
	function insert($table_name, $new_values, $insert_params = array()){
		$table_name = $this->db_prefix.$table_name;
		$query = "INSERT INTO `$table_name` (";
		foreach ($new_values as $field => $value) {
			$query .="`".$field."`,";
		}
		$query = substr($query, 0, -1);
		$query .= ") VALUES (";
		foreach ($new_values as $field => $value){
			if ($value == '') $query .= 'DEFAULT,';
			elseif (!is_numeric($value)) $query .= "'".$this->mysqli->real_escape_string($value) ."',";
			else $query .= "'".$value."',";
		} 
		$query = substr($query, 0, -1);
		$query .= ")";
		if (isset($insert_params['duplicate'])){
			$query .= " ON DUPLICATE KEY UPDATE ";
            if (is_array($insert_params['duplicate'])){
                foreach($insert_params['duplicate'] as $key => $value){
                    $query .= "`$key` = ";
                    if ($value == '') $query .= 'DEFAULT,';
                    elseif (!is_numeric($value)) $query .= "'".$this->mysqli->real_escape_string($value) ."',";
                    else $query .= "'".$value."',";
                }
                $query = substr($query, 0, -1);
            }
            if (is_string($insert_params['duplicate'])) $query .= $insert_params['duplicate'];

		}
		if (isset($insert_params['print'])){
			echo "<pre>$query</pre>";
			return false;
		}
		$this->last_query = $query;
		if (isset($insert_params['get'])) return $query;
		$res = $this->query($query, '');
		if (isset($insert_params['deincrement_duplicate']) && $res === false){
			$error = $this->error();
			$res_dd = $this->query("
				SELECT auto_increment FROM information_schema.tables WHERE table_name='$table_name';
			");
			$r = $res_dd->fetch_assoc();
			$auto_increment = $r['auto_increment'];
			$auto_increment--;
			$this->query("
				ALTER TABLE $table_name AUTO_INCREMENT = $auto_increment;
			");
			return $error;
		}
		elseif ($res === false) return $this->error();
		else return true;
    }
	function update ($table_name, $upd_fields, $where, $iconv = false){
		$table_name = $this->db_prefix.$table_name;
		$query = "UPDATE `$table_name` SET ";
		if (!$iconv) foreach ($upd_fields as $field => $value){
			if (preg_match("/`$field`/", $value)) $query .= "`$field` = $value,";
			elseif (!$value && $value != '0') $query .= "`$field` = DEFAULT,";
			else $query .= "`$field` = '".$this->mysqli->real_escape_string($value)."',";
		} 
		else foreach ($upd_fields as $field => $value) $query .= "`$field` = '".iconv("UTF-8", "WINDOWS-1251", $value)."',";
		$query = substr($query, 0, -1);
		if ($where){
			$query.=" WHERE $where";
			$this->last_query = $query;
			// echo "$query";
			if ($this->query($query)) return true;
			else return $this->error();
		}
		else return false;
	}
	function delete ($table_name, $where = ""){
		$table_name = $this->db_prefix.$table_name;
		if ($where) {
			$query = "DELETE FROM $table_name WHERE $where";
			$this->last_query = $query;
			// echo $query;
			if ($this->query($query)) return true;
			else return $this->error();
		}
		else return false;
	}
	function deleteOnID($table_name, $id){
		if ($this->delete($table_name, 'id='.$id)) return true;
		else return false;
	}
	/**
	 * [getField description]
	 * @param  [type] $table_name [description]
	 * @param  [type] $field_out  [description]
	 * @param  [type] $field_in   [description]
	 * @param  [type] $value_in   [description]
	 * @return [type]             [description]
	 */
	function getField($table_name, $field_out, $field_in, $value_in){
		$table_name = $this->db_prefix.$table_name;
		$query = "SELECT `$field_out` FROM `$table_name` WHERE $field_in = '$value_in'";
		$this->last_query = $query;
		// echo $query;
		$result_set = $this->query($query);
		// debug($result_set);
		if (!$result_set) return false;
		$i = 0;
		while ($row = $result_set->fetch_assoc()) {
			// debug($row);
			$data[$i] = $row;
			$i++;
		}
		return $data[0][$field_out];
	}	
	function getFieldOnID($table_name, $id, $field){
		return $this->getField($table_name, $field, 'id', $id);
	}
	function getCount($table_name, $where = "", $field = '*'){
		$table_name = $this->db_prefix.$table_name;
		if (!$where) $query = "SELECT COUNT($field) FROM $table_name";
		else $query = "SELECT COUNT($field) FROM $table_name WHERE $where";
		$this->last_query = $query;
		$result_set = $this->query($query);
		if (!$result_set) return false;
		$i = 0;
		while ($row = $result_set->fetch_assoc()) {
			$data[$i] = $row;
			$i++;
		}
		$result_set->close();
		return $data[0]["COUNT($field)"];
	}
	public function getMax($table_name, $field, $where = ''){
		$table_name = $this->db_prefix.$table_name;
		$query = "SELECT MAX($field) FROM $table_name";
		if ($where) $query .= " WHERE $where";
		$this->last_query = $query;
		$result_set = $this->query($query);
		if (!$result_set) return false;
		$i = 0;
		while ($row = $result_set->fetch_assoc()) {
			$data[$i] = $row;
			$i++;
		}
		$result_set->close();
		return $data[0]["MAX($field)"];
	}
}

spl_autoload_register(function($class){
	$class = str_replace('\\', '/', $class);
	$notLoading = [
		'ArmtekRestClientConfig',
		'ArmtekRestClient',
		'ArmtekException',
		'LogLevel'
	];
	foreach($notLoading as $value){
		if (preg_match("/$value/", $class)) return false;
	}
	include $_SERVER['DOCUMENT_ROOT'].'/'.$class.'.php';
});
?>