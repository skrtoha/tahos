<?php

namespace core;

class Seo{
    const TYPE_CONTENT_HTML = 'html';
    const TYPE_CONTENT_TEXT = 'text';
    public static array $type_tag = [
        'title',
        'description',
        'keywords',
        'common',
    ];

    public static array $type_content = [
        self::TYPE_CONTENT_TEXT,
        self::TYPE_CONTENT_HTML
    ];

    public static function get($url, $type_tag = '') {
        $cacheId = "seo-$url-$type_tag";
        $result = Cache::get($cacheId);
        if (!empty($result)) {
            return $result;
        }

        $output = [];
        $where = "`active` = 1 AND `url` = '{$url}'";
        if (!empty($type_tag)) {
            $where .= " AND `type` = '{$type_tag}'";
        }
        $result = Database::getInstance()->select('seo','*', $where);

        if (empty($result)) {
            return [];
        }

        foreach ($result as $row) {
            $output[$row['type_tag']] = $row['content'];
        }
        unset($result);

        Cache::set($cacheId, $output);
        return $output;
    }
}