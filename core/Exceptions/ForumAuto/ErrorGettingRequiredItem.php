<?php
namespace core\Exceptions\ForumAuto;
use core\Log;

class ErrorGettingRequiredItem extends \Exception{
	public function process($ov){
		Log::insert([
			'text' => $this->getMessage(),
			'additional' => "osi: {$ov['order_id']}-{$ov['store_id']}-{$ov['item_id']}"
		]);
	}
}
