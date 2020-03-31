<?php  
namespace core;
class Config {
	var $db_prefix = "tahos_";
	var $host = "127.0.0.1";
	var $db = "tahos";
	var $user = "Anton";
	var $password = "Anton10317";

	public static $imgPath = '';
	public static $imgUrl = '/images';
	public static function getImgPath(){
		if (self::$imgPath) return self::$imgPath;
		return $_SERVER['DOCUMENT_ROOT'].'/images';
	}
}
?>