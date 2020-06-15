<?php
namespace core\Exceptions\Autokontinent;
class ErrorBrendID extends \Exception{
	public function process($brend_id){
		$GLOBALS['db']->insert('log_diff', [
			'type' => 'brends',
			'from' => 'Проценка Автоконтинент',
			'text' => $this->getMessage(),
			'param1' => $brend,
			'param2' => $brend
		]);
	}
}
