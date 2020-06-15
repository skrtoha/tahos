<?php
namespace core\Exceptions\Autokontinent;

use core\Log;
class ErrorPartID extends \Exception{
	public function process($params){
		Log::insert([
  			'text' => 'Ошибка получения part_id',
			'additional' => "osi: {$params['order_id']}-{$params['store_id']}-{$params['item_id']}"
		]);
	}
}
