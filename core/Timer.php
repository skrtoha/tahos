<?php
namespace core;
class Timer{
	private static $start;
	public static function start(){
		self::$start = microtime(true);
	}
	public static function end(){
		return round(microtime(true) - self::$start, 2);
	}
}
