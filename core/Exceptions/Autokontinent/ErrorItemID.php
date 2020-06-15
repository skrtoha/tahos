<?php
namespace core\Exceptions\Autokontinent;
class ErrorItemID extends \Exception{
	public function process($brend_id, $part){
		debug($part, $this->getMessage());
	}
}
