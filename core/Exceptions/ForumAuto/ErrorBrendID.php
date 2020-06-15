<?php
namespace core\Exceptions\ForumAuto;
class ErrorBrendID extends \Exception{
	public function process($brend){
		$GLOBALS['db']->insert('log_diff', [
			'type' => 'brends',
			'from' => 'Проценка Автоконтинент',
			'text' => $this->getMessage(),
			'param1' => $brend,
			'param2' => $brend
		]);
	}
}
