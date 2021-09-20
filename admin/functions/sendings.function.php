<?
class Sendings{
	private 
		$where = '',
		$having = '',
		$limit = '',
		$user_id,
		$start;
	public 
		$status, 
		$pageTitle,
		$searchText,
		$searchStatus;
	function __construct($user_id = null, $db){
		$this->db = $db;
		$this->searchText = isset($_GET['search']) ? $_GET['search'] : null;
		$this->searchStatus = isset($_GET['status']) ? $_GET['status'] : null; 
		if ($this->searchText || $this->searchStatus){
			$this->start = ($_GET['pageNumber'] - 1) * $_GET['pageSize'];
			$this->limit = " LIMIT $this->start,{$_GET['pageSize']}";
			$this->pageTitle = 'Поиск по доставкам';
			$this->status = "<a href='/admin'>Главная</a> > <a href='?view=sendings'>Доставки</a> > $this->pageTitle";
			if ($this->searchText){
				if (is_numeric($this->searchText)) $this->where .= " WHERE s.id=$this->searchText";
				else $this->having .= "fio LIKE '%$this->searchText%' AND ";
			}
			if ($this->searchStatus) $this->having .= "status = '$this->searchStatus' AND ";
			if ($this->having) $this->having = " HAVING ".substr($this->having, 0, -5);
		}
		elseif ($_GET['id']){
			$this->where = " WHERE s.id={$_GET['id']}";
			$this->pageTitle = "Доставка №{$_GET['id']}";
			$this->status = "<a href='/admin'>Главная</a> > <a href='?view=sendings'>Доставки</a> > $this->pageTitle";
		} 
		else{
			$this->start = ($_GET['pageNumber'] - 1) * $_GET['pageSize'];
			$this->limit = " LIMIT $this->start,{$_GET['pageSize']}";
			$this->pageTitle = "Доставки";
			$this->status = "<a href='/admin'>Главная</a> > $this->pageTitle";
		}
		if ($user_id){
			$this->user_id = $user_id;
			$this->where = " WHERE s.user_id=$this->user_id";
			$this->limit = '';
		} 
	}
	function getTotalNumber(){
		if (!$this->searchText && !$this->searchStatus) return $this->db->getCount('sendings');
		$query = "
			SELECT SQL_CALC_FOUND_ROWS
				COUNT(s.id),
				CONCAT_WS(' ', u.name_1, u.name_2, u.name_3) AS fio,
				IF (s.is_sent, 'Отправлено', 'Ожидает') AS status
			FROM
				#sendings s
			LEFT JOIN #users u ON s.user_id=u.id
			GROUP BY s.id
			$this->where
			$this->having
		";
		$this->db->query($query, '');
		return $this->db->found_rows();
	}
	function getAjax(){
		switch($_GET['act']){
			case 'common_list': 
				$sendings = $this->getSendings();
				$this->db->update('sendings', ['is_new' => 0], "`is_new`=1");
				echo json_encode($sendings);
				break;
		}
		exit();
	}
	function getSendings(){
		$query = "
			SELECT
				IF(
					s.entity IS NOT NULL,
					s.entity,
					CONCAT_WS(' ', s.name_1, s.name_2, s.name_3)
				) AS receiver,
				s.*,
				DATE_FORMAT(s.created, '%d.%m.%Y %H:%i') AS date,
				u.id AS user_id,
				CONCAT_WS(' ', u.name_1, u.name_2, u.name_3) AS fio,
				SUM(ov.price * oiv.issued) AS sum,
				IF (s.is_new, 'is_new', '') AS is_new,
				d.title AS sub_delivery,
				IF (s.is_sent, 'Отправлено', 'Ожидает') AS status,
			    ua.json,
                s.address_id
			FROM
				#sendings s
			LEFT JOIN #order_issue_values oiv ON oiv.issue_id=s.issue_id
			LEFT JOIN #orders_values ov ON ov.order_id=oiv.order_id AND ov.item_id=oiv.item_id
			LEFT JOIN #users u ON s.user_id=u.id
			LEFT JOIN #deliveries d ON d.id=s.sub_delivery
			LEFT JOIN #user_addresses ua ON ua.id = s.address_id
			$this->where
			GROUP BY oiv.issue_id
			$this->having
			ORDER BY s.is_new DESC, s.is_sent ASC, s.created DESC
			$this->limit
		";
		return $this->db->select_unique($query, 'result');
	}
	function getSendingValues($issue_id){
		$query = "
			SELECT
				ps.cipher AS store,
				oiv.order_id,
				oiv.item_id,
				b.title AS brend,
				i.article,
				i.title_full,
				ov.price,
				(ov.price * oiv.issued) AS sum,
				ov.comment,
				oiv.issued
			FROM
				#order_issue_values oiv
			LEFT JOIN
				#items i ON i.id=oiv.item_id
			LEFT JOIN
				#brends b ON b.id=i.brend_id
			LEFT JOIN
				#orders_values ov ON ov.order_id=oiv.order_id AND ov.item_id=oiv.item_id
			LEFT JOIN
				#provider_stores ps ON ps.id=ov.store_id
			WHERE
				oiv.issue_id=$issue_id
		";
		return $this->db->query($query, 'query');
	}
	function setSent($id){
		$this->db->update('sendings', ['is_sent' => 1], "`id`=$id");
	}
}
?>