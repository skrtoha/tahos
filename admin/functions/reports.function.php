<?class Reports{
	private $tab;
	public function __construct($tab, \core\Database $db){
		$this->db = $db;
		$this->tab = $tab;
	}
	private function getLogQuery(){
		return "
			SELECT
				ld.param1 AS item_id,
				i1.brend_id AS brend1_id,
				i1.article AS article1,
				i1.title_full AS title_full1,
				b1.title AS brend1,
				ld.param2 AS item_diff,
				i2.brend_id AS brend2_id,
				i2.article AS article2,
				i2.title_full AS title_full2,
				b2.title AS brend2,
				ld.text,
				ld.type,
				ld.from,
				ld.is_processed
			FROM
				#log_diff ld
			LEFT JOIN
				#items i1 ON i1.id=ld.param1
			LEFT JOIN
				#items i2 ON i2.id=ld.param2
			LEFT JOIN
				#brends b1 ON b1.id=i1.brend_id
			LEFT JOIN
				#brends b2 ON b2.id=i2.brend_id
		";
	}
	public function getNomenclature(){
		$query = $this->getLogQuery();
		$query .= " WHERE ld.type IN ('substitutes', 'analogies')";
		return $this->db->query($query, '');
	}
	public function nomenclatureHide(){
		if (empty($_POST['values'])) return false;
		foreach($_POST['values'] as $value){
			$a = explode('-', $value['value']);
			$this->db->update(
				$value['name'],
				['status' => 2],
				"(`item_id`= {$a[0]} AND `item_diff`= {$a[1]}) OR 
					(`item_id`= {$a[1]} AND `item_diff`= {$a[0]})"
			);
			$this->db->update(
				'log_diff',
				['is_processed' => 1],
				"`param1`= {$a[0]} AND `param2` = $a[1] AND `type`='{$value['name']}'"
			);
		}
	}
	public function nomenclatureClear(){
		// debug($_GET);
		$this->db->delete('log_diff', "`type` IN ('substitutes', 'analogies')");
	}
	public function getBrends(){
		$query = $this->getLogQuery();
		$query .= " WHERE ld.type IN ('brends')";
		return $this->db->query($query, '');
	}
	public function brendsClear(){
		$this->db->delete('log_diff', "`type` IN ('brends')");
	}
	public function getWrongAnalogies(){
		return $this->db->select('log_diff', '*', "`type`='wrongAnalogy'");
	}
	public function clearWrongAnalogies(){
		return $this->db->delete('log_diff', "`type`='wrongAnalogy'");
	}
	public function hideWrongAnalogy(){
		$this->db->delete('log_diff', "`type`='wrongAnalogy' AND `param1`={$_POST['item_id']} AND `param2`={$_POST['item_diff']}");
		$this->db->update(
            'analogies',
            ['status' => 2],
            "`item_id`={$_POST['item_id']} AND `item_diff`={$_POST['item_diff']}"
        );
        $this->db->update(
            'analogies',
            ['status' => 2],
            "`item_id`={$_POST['item_diff']} AND `item_diff`={$_POST['item_id']}"
        );
	}
}?>