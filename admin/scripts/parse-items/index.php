<?

use core\Item;
use core\Provider\Armtek;

/** @var int $category_id */
/** @var array $filterValues */

set_time_limit(0);
require_once($_SERVER['DOCUMENT_ROOT'].'/core/DataBase.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

$db = new core\Database();
$errors = [];

require_once ('../parse-items/json/accum/filterValues.php');

for($i = 1; $i <= 16; $i++){
    $result = json_decode(file_get_contents("json/accum/$i.json"), true);
    foreach($result['data']['entities'] as $entity){
        $brend_id = Armtek::getBrendId($entity['fields']['brand']);
        if (!$brend_id){
            $errors[] = "Бренд {$entity['brand']} не найден";
            continue;
        }
        $article = Item::articleClear($entity['fields']['part_number']);

        if (!$article) {
            continue;
        }

        $resItemInsert = Item::insert([
            'brend_id' => $brend_id,
            'article' => $article,
            'article_cat' => $entity['fields']['part_number'],
            'title_full' => $entity['title'],
            'title' => $entity['title']
        ]);

        if ($resItemInsert !== true){
            $res = $db->select_one('items', 'id', "`article` = '{$article}' and brend_id = $brend_id");
            $item_id = $res['id'];
        }
        else $item_id = Item::$lastInsertedItemID;

        if (!$item_id){
            $errors[] = "Ошибка получения item_id {$entity['brand']}[$brend_id] - {$entity['article']}";
            continue;
        }

        $res1 = $db->insert('categories_items', [
            'item_id' => $item_id,
            'category_id' => $category_id
        ]);

        /*if ($res1 !== true) {
            continue;
        }*/

        foreach($entity['fields'] as $title => $value){
            if (!$value || !key_exists($title, $filterValues)) {
                continue;
            }
            if (is_numeric($value)){
                if((string) $value == (string) (int) $value){
                    $value = (int) $value;
                }
            }

            $filter_value_id = false;
            switch($title){
                default:
                    if (is_array($filterValues[$title])) {
                        $filter_value_id = $filterValues[$title][$value];
                    }
                    else {
                        $result = $db->select_one(
                            'filters_values',
                            'id',
                            "`filter_id` = {$filterValues[$title]} and `title` = '{$value}'"
                        );
                        if ($result){
                            $filter_value_id = $result['id'];
                        }
                        else {
                            $db->insert('filters_values', [
                                'filter_id' => $filterValues[$title],
                                'title' => $value
                            ]);
                            $filter_value_id = $db->last_id();
                        }
                    }
            }
            if (!$filter_value_id){
                continue;
            }

            $resItemValuesInsert = $db->insert('items_values', [
                'item_id' => $item_id,
                'value_id' => $filter_value_id
            ]);
        }
    }
}