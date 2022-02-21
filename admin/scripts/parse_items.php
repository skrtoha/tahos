<?

use core\Item;
use core\Provider\Armtek;

set_time_limit(0);
require_once($_SERVER['DOCUMENT_ROOT'].'/core/DataBase.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

$db = new core\Database();
$errors = [];
$category_id = 99;

$filterValues = [
    'akbDlina' => 268,
    'akbKlemmy' => [
        'выносные (Азия)' => 2325,
        'тонкие вынос.(Азия)' => 2331,
        'конусные' => 5779,
        'резьбовые (Америка)' => 5780,
        'универсальные (болт и конус)' => 5781,
        'мото' => 5782,
        'под болт' => 5783,
        'прочее' => 5785,
        'стандартные (Т1)' => 2326,
        'тонкие (Т3)' => 2331,
        'винтовые' => 5786,
        'винтовые + стандартные (T1)' => 5787
    ],
    'akbModelAkkumulyatora' => 612,
    'akbNominalnoyeNapryazheniye' => [
        '12V' =>2322
    ],
    'akbStartstop' => [
        true => 5790,
        false => 5791
    ],
    'akbSukhozaryazhennaya' => [
        true => 5792,
        false => 5793
    ],
    'akbSeriya' => 613,
    'akbPolyarnost' => 265,
    'akbPuskovoytok' => 263,
    'akbShirina' => 269,
    'akbSposobKrepleniya' => [
        'B01 нижнее крепление' => 2399,
        'B13' => 2327,
        'B00' => 2324,
        'B03 нижнее крепление' => 2454,
        'нижнее крепление' => 5774,
        'универсальное крепление' => 5775,
        'верхняя планка' => 5776
    ],
    'akbEtn' => 618,
    'akbTipBatarei' => [
        'AGM' => 2329,
        'Ca/Ca (Кальциевые)' => 2323,
        'EFB' => 5777,
        'Sb/Ca (Гибридные)' => 2453,
        'Sb/Sb (Малосурьмянистые)' => 5778,
    ],
    'akbVysota' => 270,
    'akbYemkost' => 264,
    'akbObsluzhivayemaya' => [
        true => 5788,
        false => 5789
    ]
];

function get_filter_value_id($filter_id, $value, $is_like = false){
    global $db, $errors, $category_id;
    static $filterValues;
    $fv = & $filterValues[$filter_id][$value];
    if (isset($fv)) return $fv;

    if ($is_like) $whereTitle = "tfv.title like '%$value%' and ";
    else $whereTitle = "tfv.title = '$value' and ";

    $query = "
        select
            tf.id,
            tf.title,
            tfv.title as filter_value,
            tfv.id as filter_value_id
        from
            tahos_filters_values tfv
                left join
            tahos_filters tf on tfv.filter_id = tf.id
                left join
            tahos_categories tc on tf.category_id = tc.id
        where 
              tf.category_id = $category_id and 
              $whereTitle
              tfv.filter_id = $filter_id
    ";
    $result = $db->query($query);
    if (!$result->num_rows){
        $errors[] = "Не найдено $filter_id $value";
        return false;
    }
    $result = $result->fetch_assoc();
    $fv = $result['filter_value_id'];
    return $fv;
}

for($i = 1; $i <= 3; $i++){
    $result = json_decode(file_get_contents("json/$i.json"), true);
    foreach($result['entities'] as $entity){
        $brend_id = Armtek::getBrendId($entity['brand']);
        if (!$brend_id){
            $errors[] = "Бренд {$entity['brand']} не найден";
            continue;
        }
        $article = Item::articleClear($entity['article']);
        $resItemInsert = Item::insert([
            'brend_id' => $brend_id,
            'article' => $article,
            'article_cat' => $entity['article'],
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

        $db->insert('categories_items', [
            'item_id' => $item_id,
            'category_id' => $category_id
        ]);

        //todo для грузовых
        $resItemValuesInsert = $db->insert('items_values', [
            'item_id' => $item_id,
            'value_id' => 2458
        ]);

        if ($entity['brand']){
            switch($entity['brand']){
                case 'АКОМ': $filter_value_id = 1751; break;
                default:
                    $filter_value_id = get_filter_value_id(262, $entity['brand'], true);
            }
            if ($filter_value_id !== false){
                $db->insert('items_values', [
                    'item_id' => $item_id,
                    'value_id' => $filter_value_id
                ]);

            }
        }

        foreach($entity['fields'] as $title => $value){
            if (!$value) continue;
            if (is_numeric($value)){
                if((string) $value == (string) (int) $value){
                    $value = (int) $value;
                }
            }
            $isFoundedTitle = false;
            $filter_value_id = false;
            switch($title){
                case 'akbDlina':
                case 'akbModelAkkumulyatora':
                case 'akbSeriya':
                case 'akbPolyarnost':
                case 'akbPuskovoytok':
                case 'akbShirina':
                case 'akbEtn':
                case 'akbVysota':
                case 'akbYemkost':
                    $filter_value_id = get_filter_value_id($filterValues[$title], $value);
                    if (!$filter_value_id){
                        $db->insert('filters_values', [
                            'title' => $value,
                            'filter_id' => $filterValues[$title]
                        ]);
                        $filter_value_id = $db->last_id();
                    }
                    $isFoundedTitle = true;
                    break;
                case 'akbKlemmy':
                case 'akbNominalnoyeNapryazheniye':
                case 'akbStartstop':
                case 'akbSukhozaryazhennaya':
                case 'akbSposobKrepleniya':
                case 'akbTipBatarei':
                case 'akbObsluzhivayemaya':
                    $isFoundedTitle = true;
                    $filter_value_id = $filterValues[$title][$value];
                    break;
            }
            if (!$filter_value_id && $isFoundedTitle && $value){
                $errors[] = "$item_id: ошибка получения value_id для $title $value";
                continue;
            }

            if ($filter_value_id){
                $resItemValuesInsert = $db->insert('items_values', [
                    'item_id' => $item_id,
                    'value_id' => $filter_value_id
                ]);
            }
        }
        echo "Конец fields";
    }
    echo "";
}
echo json_encode($errors);



function getResultQuery($page = 0){
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://www.autorus.ru/api2/catalog/section',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{"code":"shiny-legkovyye","query":{"page": "'.$page.'", "limit": 50}}',
        CURLOPT_HTTPHEADER => array(
            'authorization: Bearer',
            'sec-ch-ua: \\" Not A;Brand\\";v=\\"99\\", \\"Chromium\\";v=\\"98\\", \\"Google Chrome\\";v=\\"98\\"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: \\"Windows\\"',
            'sec-fetch-dest: empty',
            'sec-fetch-mode: cors',
            'sec-fetch-site: same-site',
            'accept-language: ru-UA,ru;q=0.9,en-GB;q=0.8,en;q=0.7,ru-RU;q=0.6,en-US;q=0.5',
            'content-type: application/json',
            'pragma: no-cache',
            'Cookie: qrator_msid=1644854652.588.V4OKCOBQMhbPlwrJ-qji0p9llb68nl580jstdla06ndb3o9p8; sess_arus=s%3AiL2BRmdHn_a0Wpzvwj3SgAFOhfOrE7ZA.BW40A8iHrV8J%2Fd26VmmRS2ZIdHqfNS94D3jViDQkqhw'
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;

}