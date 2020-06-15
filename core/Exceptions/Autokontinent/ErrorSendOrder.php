<?php
namespace core\Exceptions\Autokontinent;
use core\Log;

class ErrorSendOrder extends \Exception{
	public function process($basket){
		foreach($basket as $b){
			Log::insert([
	  			'text' => $this->getMessage(),
				'additional' => "osi: {$b->comment}"
			]);
		}
	}
}
