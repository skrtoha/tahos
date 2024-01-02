<?php

namespace core;

use core\Messengers\Telegram;

class Cache{
    /** @var \Memcached|\Memcache */
    private $classCache;

    private function __construct($ip = null){
        $this->classCache = new Config::$cacheClass;
        $this->classCache->addServer('localhost', 11211);
    }

    private static function getInstance(): Cache
    {
        static $self;
        if ($self) return $self;

        $self = new static();
        return $self;
    }

    public static function set($key, $value): bool
    {
        $self = self::getInstance();
        return $self->classCache->set($key, $value);
    }

    public static function get($key){
        $self = self::getInstance();
        return $self->classCache->get($key);
    }

    public static function delete($key): bool
    {
        $self = self::getInstance();
        return $self->classCache->delete($key);
    }

}