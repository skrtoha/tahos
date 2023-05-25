<?php

namespace core\Marketplaces;

use core\Database;

class Marketplaces{
    protected static function getDBInstance(): Database
    {
        if ($GLOBALS['db']) return $GLOBALS['db'];
        return new Database();
    }

    public static function setItemDescription($item_id, $description){
        $description = trim($description);
        self::getDBInstance()->insert(
            'item_marketplace_description',
            [
                'item_id' => $item_id,
                'description' => $description
            ],
            ['duplicate' => [
                'description' => $description
            ]]
        );
    }
}