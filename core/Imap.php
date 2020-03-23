<?php
namespace core;
class Imap{
	private $login = 'price@tahos.ru';
	private $password = 'Anton12345';
	public $error;

	public function __construct($imap){
		try{
			$this->connection = imap_open($imap, $this->login, $this->password);
			if (!$this->connection) throw new \Exception('Не удалось подключиться к почте по протоколу Imap');
		} catch(\Exception $e){
			$this->error = $e->getMessage();
			Log::insertThroughException($e);
		}
	}
	public function getLastMailFrom($params){
		$data = array();
		if ($this->error) return false;
		$num = imap_num_msg($this->connection);
		for($i = $num; $i > $num - 20; $i--){
			$header = imap_header($this->connection, $i);
			$from = $header->from[0]->mailbox.'@'.$header->from[0]->host;
			if ($from != $params['from']) continue;
			$d = & $data[$i];
			$body = "";
			$d['time'] = time($header->MailDate);
			$d['date'] = $header->MailDate;
			$d['title'] = $this->get_imap_title($header->subject);
			$d['from'] = $from;

			$msg_structure = imap_fetchstructure($this->connection, $i);
			$msg_body = imap_fetchbody($this->connection, $i, 1);

			// debug($msg_structure);
			$recursive_data = $this->recursive_search($msg_structure);
			if($recursive_data["encoding"] == 0 || $recursive_data["encoding"] == 1){
				$body = $msg_body;
			}
			if($recursive_data["encoding"] == 4){
				$body = $this->structure_encoding($recursive_data["encoding"], $msg_body);
			}
			if($recursive_data["encoding"] == 3){
				$body = $this->structure_encoding($recursive_data["encoding"], $msg_body);
			}
			if($recursive_data["encoding"] == 2){
				$body = $this->structure_encoding($recursive_data["encoding"], $msg_body);
			}
			if(!$this->check_utf8($recursive_data["charset"])){
				$body = $this->convert_to_utf8($recursive_data["charset"], $msg_body);
			}
			$d['body'] = base64_encode($body);
			// debug($msg_structure->parts); exit();
			if(isset($msg_structure->parts)){
				for($j = 1, $f = 2; $j < count($msg_structure->parts); $j++, $f++){
					$type = $msg_structure->parts[$j]->subtype;
					$d["attachs"][$j]["type"] = $type;
					$d["attachs"][$j]["size"] = $msg_structure->parts[$j]->bytes;
					$d["attachs"][$j]["name"] = $this->getNameFromParameters($msg_structure->parts[$j]->parameters);
					if (!preg_match('/'.$params['name'].'/u', $d["attachs"][$j]["name"])) continue;
					$d["attachs"][$j]["file"] = $this->structure_encoding(
						$msg_structure->parts[$j]->encoding,
						imap_fetchbody($this->connection, $i, $f)
					);
					file_put_contents("{$_SERVER['DOCUMENT_ROOT']}/tmp/{$d["attachs"][$j]["name"]}", $d["attachs"][$j]["file"]);
					return $_SERVER['DOCUMENT_ROOT']."/tmp/{$d["attachs"][$j]["name"]}";
					// debug($d); exit();
				}
			}
		}
	}
	private function getNameFromParameters($parameters){
		foreach ($parameters as $value){
			if ($value->attribute == 'name') return $value->value;
		}
	}
	private function get_imap_title($str){
		$mime = imap_mime_header_decode($str);
		$title = "";
		foreach($mime as $key => $m){
			if(!$this->check_utf8($m->charset)) $title .= $this->convert_to_utf8($m->charset, $m->text);
			else $title .= $m->text;
		}
		return $title;
	}
	private function check_utf8($charset){
		if(strtolower($charset) != "utf-8")return false;
		return true;
	}
	public static function convert_to_utf8($in_charset, $str){
		return iconv(strtolower($in_charset), "utf-8", $str);
	}
	private function recursive_search($structure){
		$encoding = "";
		if($structure->subtype == "HTML" ||
		   $structure->type == 0){
			if($structure->parameters[0]->attribute == "charset"){
				$charset = $structure->parameters[0]->value;
			}
			return array(
				"encoding" => $structure->encoding,
				"charset"  => strtolower($charset),
				"subtype"  => $structure->subtype
			);
		}else{
			if(isset($structure->parts[0])){
				return $this->recursive_search($structure->parts[0]);
			}else{
				if($structure->parameters[0]->attribute == "charset"){
					$charset = $structure->parameters[0]->value;
				}
				return array(
					"encoding" => $structure->encoding,
					"charset"  => strtolower($charset),
					"subtype"  => $structure->subtype
				);
			}
		}
	}
	private function structure_encoding($encoding, $msg_body){
		switch((int) $encoding){
			case 4:
				$body = imap_qprint($msg_body);
				break;
			case 3:
				$body = imap_base64($msg_body);
				break;
			case 2:
				$body = imap_binary($msg_body);
				break;
			case 1:
				$body = imap_8bit($msg_body);
				break;
			case 0:
				$body = $msg_body;
				break;
			default:
				$body = "";
				break;
		}
		return $body;
	}
}
