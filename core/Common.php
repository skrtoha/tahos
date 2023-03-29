<?php
namespace core;

use Memcache;

class Common{
    private static $memcache;

    /**
     * @return Memcache
     */
    public static function getMemcache(): Memcache
    {
        if (self::$memcache) return self::$memcache;
        self::$memcache = new Memcache();
        self::$memcache->connect('localhost', 11211);
        return self::$memcache;
    }
}