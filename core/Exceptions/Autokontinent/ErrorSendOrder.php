<?php
namespace core\Exceptions\Autokontinent;
use core\Log;

class ErrorSendOrder extends \Exception{
	public function process($basket){
		debug($basket);
		/*Log::insert([
  			'text' => $this->getMessage(),
			'additional' => "osi: {$params['order_id']}-{$params['store_id']}-{$params['item_id']}"
		]);*/
	}
}
