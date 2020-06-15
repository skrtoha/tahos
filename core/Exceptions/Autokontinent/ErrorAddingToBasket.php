<?php
namespace core\Exceptions\Autokontinent;

use core\Log;
class ErrorAddingToBasket extends \Exception{
	public function process($params){
		Log::insert([
  			'text' => $this->getMessage(),
			'additional' => "osi: {$params['order_id']}-{$params['store_id']}-{$params['item_id']}"
		]);
	}
}
