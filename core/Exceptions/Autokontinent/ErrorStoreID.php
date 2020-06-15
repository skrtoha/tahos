<?php
namespace core\Exceptions\Autokontinent;
class ErrorStoreID extends \Exception{
	public function process($part, $query){
		debug($part, $query);
	}
}
