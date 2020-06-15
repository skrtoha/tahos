<?php
namespace core\Exceptions\ForumAuto;
use core\Log;

class ErrorSendingOrder extends \Exception{
	public function process($error, $ov){
		Log::insert([
			'text' => $this->getMessage().': ' . $error->FaultString,
			'additional' => "osi: {$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}"
		]);
	}
}
