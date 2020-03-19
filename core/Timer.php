<?php
namespace core;
class Timer{
	private static $start;
	public static function start(){
		self::$start = time();
	}
	public static function end(){
		return time() - self::$start;
	}
}
