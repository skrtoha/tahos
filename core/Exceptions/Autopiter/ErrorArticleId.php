<?php
namespace core\Exceptions\Autopiter;
class ErrorArticleId extends \Exception{
	public function process(){
		\core\Log::insert([
			'text' => 'Ошибка получение ArticleId',
			'query' => $this->getMessage()
		]);
	}
}
