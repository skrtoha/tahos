<?php
/** @global $db \core\Database */

namespace core;

class Category{
    public static function getAll($where = ''){
        global $db;
        if ($where) $where = "WHERE $where";
        $res_categories = $db->query("
            SELECT 
                c.id AS category_id,
                c.title AS category,
                c.pos AS category_pos,
                sc.pos AS subcategory_pos,
                sc.title AS subcategory,
                sc.id AS subcategory_id,
                c.href AS category_href,
                sc.href AS subcategory_href
            FROM #categories c
            LEFT JOIN #categories sc ON c.id = sc.parent_id
            WHERE
                c.isShowOnMainPage = 1 AND sc.isShowOnMainPage = 1
            ORDER BY c.pos, sc.pos
        ", '');
        $categories = [];
        foreach($res_categories as $row){
            $c = & $categories[$row['category']];
            $c['id'] = $row['category_id'];
            $c['href'] = $row['category_href'];
            $c['subcategories'][] = [
                'title' => $row['subcategory'],
                'href' => $row['subcategory_href']
            ];
        }
        return $categories;
    }
}