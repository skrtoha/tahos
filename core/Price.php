<?php
namespace core;
require_once ($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');
require_once ('templates/functions.php');
class Price{
	public $log;
	public $brends = array();
	public $insertedBrends = 0;
	public $insertedItems = 0;
	public $insertedStoreItems = 0;
	public $nameFileLog;
	
	public $isLogging;
	
	/**
	 * [$isInsertBrend insert brend if not found]
	 * @var boolean
	 */
	public $isInsertBrend = false;
	/**
	 * [$isInsertItem insert item]
	 * @var boolean
	 */
	public $isInsertItem = false;
	public $brendErrors = [];
	
	public function __construct($db, $emailPrice){
		$this->db = $db;
		$this->isInsertBrend = $emailPrice['isAddBrend'];
		$this->isInsertItem = $emailPrice['isAddItem'];
		$this->type = $emailPrice['title'];
		$this->nameFileLog = $this->type.'_'.date('d.m.Y_H-i-s').'.txt';
		$this->isLogging = isset($emailPrice['isLogging']) ? $emailPrice['isLogging'] : false;
		
		if ($this->isLogging){
            $this->log = new \Katzgrau\KLogger\Logger('logs', \Psr\Log\LogLevel::WARNING, array(
                'filename' => $this->nameFileLog,
                'dateFormat' => 'G:i:s'
            ));
        }
	}
	private function setLog($type, $message){
	    if (!$this->isLogging) return;
	    $this->log->$type($message);
    }
	public function getBrendId($brendTitle, $row = null){
		if (!$this->brends[$brendTitle]) {
			$brend = $this->db->select_one('brends', ['id', 'title', 'parent_id'], "`title`='{$brendTitle}'");
			$brend_id = $brend['parent_id'] ? $brend['parent_id'] : $brend['id'];
			$this->brends[$brendTitle] = $brend_id;
		}
		else $brend_id = $this->brends[$brendTitle];
		if (!$brend_id && !$this->isInsertBrend){
			if (!in_array($brendTitle, $this->brendErrors)){
			    $this->setLog('error',"Бренд $brendTitle в $row не найден");
				$this->brendErrors[] = $brendTitle;
			}
			return false;
		} 
		if (!$brend_id){
			$res = $this->db->insert(
				'brends', 
				[
					'title' => $brendTitle,
					'href' => translite($brendTitle),
					'parent_id' => 0
				]
			);
			if ($res === true){
				$brend_id = $this->db->last_id();
				$this->db->insert(
					'log_diff',
					[
						'type' => 'brends',
						'from' => $this->type,
						'text' => "Вставлен новый бренд <a href='/admin/?view=brends&id=$brend_id&act=change' target='_blank'>{$brendTitle}</a>",
						'param1' => $brendTitle,
						'param2' => $brendTitle
					]
				);
				$this->brends[$brendTitle] = $brend_id;
				$this->insertedBrends++;
				return $brend_id;
			} 
			else{
				$this->setLog('error',"В строке $i: {$this->db->last_query} | $res");
				return false;
			}
		}
		return $brend_id;
	}
	public static function articleClear($article){
		return preg_replace('/[^\wа-яА-Я]+/u', '', $article);
	}
	/**
	 * [getItemId description]
	 * @param  [type] $array [description]
	 * @return [type]        [description]
	 */
	public function getItemId($array, $isAddItem = true){
		$article = self::articleClear($array['article']);
		$item = $this->db->select_one('items', 'id', "`brend_id`={$array['brend_id']} AND `article`='$article'");
		if (empty($item) && !$this->isInsertItem){
			$this->setLog('error', "В строке {$array['row']} не найдено {$array['brend']} - {$array['article']}");
			return false;
		} 
		if (empty($item)){
			$res = Item::insert([
				'brend_id' => $array['brend_id'],
				'article' => $article,
				'article_cat' => $array['article'],
				'title' => $array['title'] ? $array['title'] : 'Деталь',
				'title_full' => $array['title'] ? $array['title'] : 'Деталь',
				'source' => $this->type
			]);
			if ($res === true){
                $this->insertedItems++;
				return Item::$lastInsertedItemID;
			} 
			else{
				if (isset($array['row'])){
					$this->setLog('error', "В строке {$array['row']}: {$this->db->last_query} | $res");
				}
				return false;
			}
		}
		else return $item['id'];
	}
	public function insertStoreItem($array){
		$res = $this->db->insert(
			'store_items',
			[
				'store_id' => $array['store_id'],
				'item_id' => $array['item_id'],
				'price' => str_replace([' ', ','], ['', '.'], $array['price']),
				'in_stock' => preg_replace('/\D+/', '', $array['in_stock']),
				'packaging' => $array['packaging'] ? $array['packaging'] : 1
			]
		);
		if ($res === true) $this->insertedStoreItems++;
		else $this->setLog('error',"Строка {$array['row']}: {$this->db->last_query} | $res");
	}
}
