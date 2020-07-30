<?php
namespace core\Exceptions\Autopiter;
class ErrorGetModel extends \Exception{
	public function process(){
		debug($this->getMessage());
	}
}
