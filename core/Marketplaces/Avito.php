<?php

namespace core\Marketplaces;

class Avito extends Marketplaces{
    public static function getCommonList($params = []): array
    {
        $query = self::getQueryCommonList();
        return self::getDBInstance()->query($query)->fetch_all(MYSQLI_ASSOC);
    }

    private static function getQueryCommonList(): string
    {
    $query = "
            SELECT SQL_CALC_FOUND_ROWS
                si.item_id,
                IF (
                    pb.title IS NOT NULL,
                    pb.title,
                    b.title
                ) AS brend,
                i.article,
                i.title_full,
                ad.description,
                si.in_stock,
                MIN(CEIL(si.price * c.rate + si.price * c.rate * ps.percent / 100)) as price,
                si.packaging,
                cat.parent_id,
                cat.title as category,
                cat.id as category_id,
                cat_parent.title as category_parent
            FROM
                #store_items si
                    LEFT JOIN
                #categories_items ci on ci.item_id = si.item_id
                    LEFT JOIN
                #item_marketplace_description ad ON ad.item_id = si.item_id
                    LEFT JOIN
                #categories cat on cat.id = ci.category_id
                    LEFT JOIN
                #categories cat_parent on cat_parent.id = cat.parent_id
                    LEFT JOIN
                #items i ON i.id = si.item_id
                    LEFT JOIN
                #brends b ON b.id = i.brend_id
                    LEFT JOIN
                #provider_brends pb ON pb.brend_id = i.brend_id AND pb.provider_id = 36
                    LEFT JOIN
                #provider_stores ps ON ps.id = si.store_id
                    LEFT JOIN
                #currencies c ON c.id=ps.currency_id
            WHERE
                    si.store_id IN (23) AND
                    si.price > 0 AND
                    ad.description is not null AND
                    cat.parent_id in (200, 197, 132, 136, 138)
            GROUP BY
                si.item_id
            ORDER BY b.title
        ";
    return $query;
}
}