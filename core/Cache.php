<?php
namespace core;

class Cache{
    /** @var \Memcached|\Memcache */
    private $classCache;

    /**
     * @throws \Exception
     */
    private function __construct(){
        if (!class_exists(Config::$cacheClass)) {
            throw new \Exception('Класс '.Config::$cacheClass.' не найден');
        }
        $this->classCache = new Config::$cacheClass;
        $this->classCache->addServer('localhost', 11211);
    }

    private static function getInstance()
    {
        static $self;
        if ($self) return $self;

        $self = new static();
        return $self;
    }

    public static function set($key, $value, $expiration = 0): bool
    {
        try {
            $self = self::getInstance();
        }
        catch (\Exception $e) {
            return false;
        }

        if (Config::$cacheClass == 'Memcached') {
            return $self->classCache->set($key, $value, $expiration);
        }
        if (Config::$cacheClass == 'Memcache') {
            return $self->classCache->set($key, $value, 0, $expiration);
        }
        return false;
    }

    public static function get($key){
        try {
            $self = self::getInstance();
        }
        catch (\Exception $e) {
            return false;
        }

        return $self->classCache->get($key);
    }

    public static function flush(): void
    {
        try {
            $self = self::getInstance();
        }
        catch (\Exception $e) {
            return;
        }

        $self->classCache->flush();
    }

    public static function delete($key): bool
    {
        $self = self::getInstance();
        return $self->classCache->delete($key);
    }

    public static function getResult() {
        $self = self::getInstance();
        return $self->classCache->getResultCode();
    }

    private function getSettings() {
        static $output;
        if ($output) {
            return $output;
        };
        $settings = Setting::get('site_settings', null, 'all');
        $output = $settings;
        return $settings;
    }

    public static function useCache() {
        $settings = self::getInstance()->getSettings();
        return $settings['cache_use'];
    }

    public static function getDuration() {
        $settings = self::getInstance()->getSettings();
        return $settings['cache_duration'] * 3600;
    }

}