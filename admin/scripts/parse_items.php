<?
set_time_limit(0);
require_once($_SERVER['DOCUMENT_ROOT'].'/core/DataBase.php');
require_once ($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');

$db = new core\Database();
$errors = [];

$filterValues = [
    'shinyDiametrDyuym' => 274,
    'shinyIndeksNagruzki' => [
        '84 (500 кг)' => 1459,
        '98 (750 кг)' => 1473,
        '99 (775 кг)' => 1474
    ],
    'shinyIndeksSkorosti' => 277,
    'shinySezonnost' => [
        'зима' => 2103,
        'лето' => 1447,
        'всесез' => 1585
    ],
    'shinyShirinaProfilyaMm' => 272,
    'shinyTekhnologiyaRunflat' => [
        'true' => 1393
    ],
    'shinyTipTransportnogoSredstva' => [
        'легковой' => 2107
    ],
    'shinyVysotaProfilya' => 273,
    'shinyShipy'
];

function get_filter_value_id($filter_id, $value){
    global $db, $errors;
    $category_id = 100;
    static $filterValues;
    $fv = & $filterValues[$filter_id][$value];
    if (isset($fv)) return $fv;
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
              tfv.title = '$value' and 
              tfv.filter_id = $filter_id
    ";
    $result = $db->query($query);
    if (!$result->num_rows){
        $errors[] = "Не найдено $filter_id $value";
        $fv = false;
        return false;
    }
    $result = $result->fetch_assoc();
    $fv = $result['filter_value_id'];
    return $fv;
}

for($i = 0; $i < 236; $i++){
//    $result = json_decode(getResultQuery($i), true);
    $result = json_decode(file_get_contents('test.json'), true);
    foreach($result['entities'] as $entity){
        $brend_id = \core\Provider\Armtek::getBrendId($entity['brand']);
        if (!$brend_id){
            $errors[] = "Бренд {$entity['brand']} не найден";
            continue;
        }
        $resItemInsert = \core\Item::insert([
            'brend_id' => $brend_id,
            'article' => $entity['article'],
            'article_cat' => $entity['article'],
            'title_full' => $entity['title']
        ]);

        if ($resItemInsert !== true){
            $errors[] = $resItemInsert;
            continue;
        }
        $item_id = \core\Item::$lastInsertedItemID;

        foreach($entity['fields'] as $title => $value){
            switch($title){
                case 'shinyDiametrDyuym':
                case 'shinyShirinaProfilyaMm':
                case 'shinyVysotaProfilya':
                    $filter_value_id = $filterValues[$title];
                    break;
                case 'shinyIndeksSkorosti':
                    $value = preg_replace('/ \(.*\)/i', '', $value);
                    $filter_value_id = get_filter_value_id($filterValues[$title], $value);
                    break;
            }
            $db->insert('items_values', [
                'item_id' => $item_id,
                'value_id' => $filter_value_id
            ]);

        }
    }
}



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