<?php
namespace core;
class OrderValue{
	private $db;
	public function __construct($db){
		$this->db = $db;
	}

	/**
	 * changes status
	 * @param  [integer] $status_id status_id
	 * @param  [array] $params 
	 *         order_id, store_id, item_id - required for all statuses
	 *         status = 10|11  - price, quan, user_id
	 *         status = 2 - 
	 * @return [boolean] true if changed successfully
	 */
	public function changeStatus($status_id, $params){
		// print_r($params);
		$values = ['status_id' => $status_id];
		switch ($status_id){
			//возврат
			case 2:
				$values['returned'] = "`returned` + $params['quan']";
				$this->updateOrderValue($values, $params);
				$this->reduceStoreItem($params['quan'], $params, '+');
				**********************888888
				$this->insertFunds(1, $params);
			//отменен
			case 10:
				$this->updateOrderValue($values, $params);
				$this->userUpdateReservedFunds($params['user_id'], $params['quan'] * $params['price'], '-');
				$this->reduceStoreItem($params['quan'], $params, '+');
			//заказано
			case 11:
				$values['ordered'] = "`ordered` + {$params['quan']}";
				$this->updateOrderValue($values, $params);
				$this->userUpdateReservedFunds($params['user_id'], $params['quan'] * $params['price']);
				$this->reduceStoreItem($params['quan'], $params);
				break;
			default:
				$this->updateOrderValue($values, $params);
		}
	}

	private function insertFunds($type_operation, $params){
		$this->db->insert(
			'funds',
			[
				'type_operation' => $type_operation,
				'sum' => $params['price'] * $params['new_returned'],
				'remainder' => $_POST['bill'] + $_POST['price'] * $_POST['new_returned'],
				'user_id' => $_POST['user_id'],
				'comment' => addslashes('Возврат средств за "'.$title.'"')
			],
			['print_query' => false]
		);
	}

	/**
	 * updates reserved funds for user
	 * @param  [type] $user_id user_id
	 * @param  [type] $price   value
	 * @return none
	 */
	private function userUpdateReservedFunds($user_id, $price, $act = '+'){
		$this->db->update(
			'users',
			['reserved_funds' => "reserved_funds $act {$price}"],
			"`id`=$user_id"
		);
	}

	/**
	 * updates order values
	 * @param  [array] $values for update
	 * @param  [type] $params for condition (order_id, store_id, item_id)
	 * @return none
	 */
	private function updateOrderValue($values, $params){
		$this->db->update('orders_values', $values, Armtek::getWhere([
			'order_id' => $params['order_id'],
			'store_id' => $params['store_id'],
			'item_id' => $params['item_id']
		]));
	}

	/**
	 * declines store_items
	 * @param  [integer] $quan value for decline
	 * @param  [array] $condition (store_id, item_id)
	 * @param  string $act plus|minus (+|-)
	 * @return none
	 */
	private function reduceStoreItem($quan, $condition, $act = '-'){
		$this->db->update(
			'store_items',
			['in_stock' => "`in_stock` $act $quan"],
			"`store_id`= {$condition['store_id']} AND `item_id` = {$condition['item_id']}"
		);
	}

}
