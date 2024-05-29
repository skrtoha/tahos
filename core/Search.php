<?php

namespace core;

use core\Provider\Abcp;
use core\Provider\Absel;
use core\Provider\Armtek;
use core\Provider\Autokontinent;
use core\Provider\Autopiter;
use core\Provider\Berg;
use core\Provider\FavoriteParts;
use core\Provider\ForumAuto;
use core\Provider\Mikado;
use core\Provider\Rossko;
use core\Provider\Tahos;

class Search{
    const TYPE_SEARCH_ARTICLE = 'article';
    const TYPE_SEARCH_BARCODE = 'barcode';
    const TYPE_SEARCH_VIN = 'vin';

    const USER_COUNT_SEARCH = 100;

    private static array $coincidences = [];

    /**
     * @return Database
     */
    public static function getInstanceDataBase(): Database
    {
        return $GLOBALS['db'];
    }

    /**
     * Производит поиск номенклатуры по поставщикам
     * @param string $search строка поиска
     * @return array
     */
    public static function searchItemProviders(string $search): array
    {
        if (!Config::$isUseApiProviders){
            echo json_encode([]);
            exit();
        }

        $mikado = new Mikado();
        self::setCoincidences($mikado->getCoincidences($search));

        $armtek = new Armtek();
        self::setCoincidences($armtek->getSearch($search));

        self::setCoincidences(FavoriteParts::getSearch($search));

        $rossko = new Rossko();
        self::setCoincidences($rossko->getSearch($search));

        //из-за того, что изменилось АПИ поставщика, теперь для поска товара
        //стало обязательно передавать бренд
        //setCoincidences(core\Provider\Autoeuro::getSearch($_GET['search']));

        $abcp = new Abcp();
        self::setCoincidences($abcp->getSearch($search));

        self::setCoincidences(Autokontinent::getCoincidences($search));

        self::setCoincidences(ForumAuto::getCoincidences($search));

        self::setCoincidences(Autopiter::getCoincidences($search));

        self::setCoincidences(Berg::getCoincidences($search));

        self::setCoincidences(Absel::getSearch($search));

        return self::$coincidences;
    }

    private static function setCoincidences($c): void
    {
        if (empty($c)) {
            return;
        }
        foreach($c as $key => $value){
            if (!$key || !$value) continue;
            self::$coincidences[$key] = $value;
        }
    }

    public static function searchItemDatabase($search, $type, $flag = ''){
        $for_search = Item::articleClear($search);

        switch ($type){
            case self::TYPE_SEARCH_ARTICLE:
                $where = "i.`article`='$for_search'";
                $where .= " OR (i.title_full LIKE '{$search}%' AND si.price IS NOT NULL)";
                break;
            case self::TYPE_SEARCH_BARCODE:
                $where =  "ib.`barcode`='$for_search'";
                break;
        }
        $res_items = self::getInstanceDataBase()->query("
            SELECT
                i.id,
                b.title as brend,
                i.brend_id AS brend_id,
                IF (
                    i.article !='',
                    i.article,
                    ib.barcode
                )
                AS article,
                IF (i.title_full!='', i.title_full, i.title) AS title_full,
                FLOOR(si.price * c.rate + si.price * c.rate * (ps.percent/100)) AS price,
                si.store_id,
                IF (si.in_stock, ps.delivery, ps.under_order) AS delivery,
                ps.cipher AS cipher,
                i.is_blocked,
                IF (
                    i.applicability !='' || i.characteristics !=''  || i.full_desc !='' || i.photo != '',
                    1,
                    0
                ) as is_desc
            FROM #items i
            LEFT JOIN #brends b ON b.id=i.brend_id
            LEFT JOIN #item_barcodes ib ON ib.item_id = i.id
            LEFT JOIN #store_items si ON si.item_id=i.id
            LEFT JOIN #provider_stores ps ON ps.id=si.store_id
            LEFT JOIN #currencies c ON ps.currency_id=c.id
            WHERE $where
            LIMIT 0, ".self::USER_COUNT_SEARCH."
	    ", $flag);

        if (!$res_items->num_rows) return false;

        $items = [];
        while($item = $res_items->fetch_assoc()){
            $i = & $items[$item['id']];
            $i['brend'] = $item['brend'];
            $i['brend_id'] = $item['brend_id'];
            $i['article'] = $item['article'];
            $i['title_full'] = $item['title_full'];
            $i['is_blocked'] = $item['is_blocked'];

            //for displaying coincided items
            if (in_array($item['provider_id'], [11, 12, 18, 20, 21])) $i['is_armtek'] = 1;

            if (isset($i['price'])){
                if ($item['price'] < $i['price']) $i['price'] = $item['price'];
            }
            else $i['price'] = $item['price'];
            if (isset($i['delivery'])){
                if ($item['delivery'] < $i['delivery']) $i['delivery'] = $item['delivery'];
            }
            else $i['delivery'] = $item['delivery'];
        }
        return $items;
    }

    public static function articleStoreItems($item_id, $user_id = null, $filters = [], $search_type = 'articles'): array
    {
        $user = [];
        if ($user_id){
            $result = User::get(['user_id' => $user_id]);
            foreach ($result as $value) $user = $value;
        }

        $q_item = self::getQueryArticleStoreItems($item_id, $user, $search_type, $filters);
        $res_item = self::getInstanceDataBase()->query($q_item, '');
        if (!$res_item->num_rows){
            $q_item = "
                SELECT 
                    IF (i.title_full<>'', i.title_full, i.title) as title_full,
                    IF (
                            i.article_cat != '', 
                            i.article_cat, 
                            IF (
                                i.article !='',
                                i.article,
                                ib.barcode
                            )
                    ) as article,
                    b.title as brend,
                    b.id as brend_id,
                    IF (
                        i.applicability !='' || i.characteristics !=''  || i.full_desc !='',
                        1,
                        0
                    ) as is_desc,
                    i.photo,
                    i.id as item_id
                FROM #item_$search_type diff
                LEFT JOIN #items i ON i.id=diff.item_diff
                LEFT JOIN #brends b ON b.id=i.brend_id
                LEFT JOIN #item_barcodes ib ON ib.item_id = i.id
                WHERE
                    diff.item_id=$item_id
		    ";
            $items = self::getInstanceDataBase()->select_unique($q_item, '');
            foreach($items as $item){
                $array['store_items'][$item['item_id']] = $item;
                $array['store_items'][$item['item_id']]['list'] = array();
            }
            $array['prices'] = array();
            $array['deliveries'] = array();
            $array['query'] = self::getInstanceDataBase()->query($q_item, 'query');
            return $array;
        }

        $items = Tahos::parseResItem($res_item);

        foreach ($items as $v){
            $p = & $store_items[$v['item_id']];
            $p['title_full'] = $v['title_full'];
            $p['article'] = $v['article'];
            $p['brend'] = $v['brend'];
            $p['brend_id'] = $v['brend_id'];
            $p['is_desc'] = $v['is_desc'];
            $p['photo'] = $v['photo'];
            $p['item_id'] = $v['item_id'];
            $p['status'] = $v['status'];
            $list['delivery_date'] = Provider::getDiliveryDate(
                json_decode($v['workSchedule'], true),
                json_decode($v['calendar'], true),
                $v['delivery']
            );

            if (Provider::$todayIssue) $v['prevail'] = 1;
            else $v['prevail'] = 0;

            $list['store_id'] = $v['store_id'];
            $list['checked'] = $v['checked'];
            $list['in_stock'] = (int) $v['in_stock'] ? $v['in_stock'] : 'Под заказ';
            $list['cipher'] = $v['cipher'];
            $list['provider'] = $v['provider'];
            $list['packaging'] = $v['packaging'];
            $list['packaging_text'] = $v['packaging_text'];

            if (!(int)$v['in_stock'] && $v['provider_id'] != 1) continue;

            $list['delivery'] = $v['delivery'];
            $list['price'] = $v['price'];
            $list['in_basket'] = $v['in_basket'];
            $list['noReturn'] = $v['noReturn'] ? "class='noReturn' title='Возврат поставщику невозможен!'" : '';
            if ($v['prevail']){
                $p['prevails'][$v['store_id']] = $list;
                $prices[] = $v['price'];
                $deliveries[] = $v['delivery'];
                continue;
            }
            else{
                if (isset($filters['in_stock']) && $filters['in_stock']) continue;
                $p['list'][$v['store_id']] = $list;
            }
            $p['deliveries'][] = $v['delivery'];
            $prices[] = $v['price'];
            $deliveries[] = $v['delivery'];
        }

        foreach($store_items as $key => $value){
            $p = & $store_items[$key];
            if (!empty($p['list'])) $p['list'] = array_merge($p['list']);
            if (!empty($p['prevails'])){
                usort($p['prevails'], function($a, $b){
                    if ($a['delivery'] <  $b['delivery']) return false;
                    return true;
                });
            }
            if (!empty($p['list'])) $p['min_price'] = $p['list'][0];
            if (isset($p['deliveries']) && count($p['deliveries']) == 2) $p['min_delivery'] = $p['list'][1];
            else{
                if (isset($p['deliveries']) && count($p['deliveries']) > 2){
                    $min_delivery = min($p['deliveries']);
                    unset($p['deliveries']);
                    foreach ($p['list'] as $k => $v){
                        if ($v['delivery'] == $min_delivery) $p['min_delivery'] = $v;
                    }
                }
            }
        }

        return [
            'store_items' => $store_items,
            'prices' => $prices ?: array(),
            'deliveries' => $deliveries ?: array(),
            'hide_analogies' => '',
            'user' => $user
        ];
    }

    private static function getQueryArticleStoreItems($item_id, $user, $search_type, $filters = []){
        if ($user){
            $join_basket = "
                LEFT JOIN 
                        #basket ba 
                ON 
                    si.store_id=ba.store_id AND 
                    si.item_id=ba.item_id AND 
                    ba.user_id={$user['id']}
		    ";
            $ba_quan = " ba.quan as in_basket, ";
            $userDiscount = "@price * {$user['discount']} / 100";
        }
        else $userDiscount = 0;
        if (!$user['show_all_analogies'] && $search_type == 'analogies') $hide_analogies = true;
        else $hide_analogies = false;

        if ($search_type == 'analogies'){
            $selectAnalogies = 'diff.status, ';
            $whereAnalogies = 'AND diff.status IN (0, 1)';
        }

        $q_item = "
		SELECT
			diff.item_diff as item_id,
			$selectAnalogies
			si.in_stock,
			IF(
				si.packaging != 1,
				CONCAT(
					'&nbsp;(<span>уп.&nbsp;',
					si.packaging,
					'&nbsp;шт.</span>)'
				),
				''
			) as packaging_text,
			si.packaging,
			b.title as brend,
			i.brend_id as brend_id,
			i.photo,
			ps.cipher,
			ps.provider_id,
			p.title AS provider,
			ps.id as store_id,
			ps.checked,
			IF (ps.workSchedule IS NOT NULL, ps.workSchedule, p.workSchedule) AS workSchedule,
			IF (
				i.article_cat != '', 
				i.article_cat, 
				IF (
					i.article !='',
					i.article,
					ib.barcode
				)
			) as article,
			IF (i.title_full!='', i.title_full, i.title) as title_full,
			@delivery := CASE
				WHEN aok.order_term IS NOT NULL THEN aok.order_term
				ELSE
					IF (si.in_stock = 0, ps.under_order, ps.delivery) 
			END AS delivery,
			IF(ps.calendar IS NOT NULL, ps.calendar, p.calendar) AS  calendar,
			IF(ps.workSchedule IS NOT NULL, ps.workSchedule, p.workSchedule) AS  workSchedule,
			ps.noReturn,
			ps.percent,
			@price := si.price * c.rate + si.price * c.rate * ps.percent / 100,
			CEIL(@price - $userDiscount) AS price,
			$ba_quan
			IF (
				i.applicability !='' || i.characteristics !=''  || i.full_desc !='' || i.photo != '',
				1,
				0
			) as is_desc
		FROM #item_$search_type diff
		RIGHT JOIN #store_items si ON si.item_id=diff.item_diff
		LEFT JOIN #provider_stores ps ON ps.id=si.store_id AND ps.block = 0
		LEFT JOIN #providers p ON p.id = ps.provider_id
		LEFT JOIN #currencies c ON c.id=ps.currency_id
		LEFT JOIN #items i ON diff.item_diff=i.id
		LEFT JOIN #brends b ON b.id=i.brend_id
		LEFT JOIN #item_barcodes ib ON ib.item_id = i.id
		LEFT JOIN #autoeuro_order_keys aok ON aok.item_id = si.item_id AND aok.store_id = si.store_id
		$join_basket
		WHERE diff.item_id=$item_id $whereAnalogies
	";
        if ($hide_analogies) $q_item .= ' AND si.item_id IS NOT NULL';
        if (!empty($filters)){
            if ($filters['in_stock']) $q_item .= ' AND si.in_stock>0';
            if (isset($filters['is_main'])) $q_item .= " AND ps.is_main = {$filters['is_main']}";
            if (isset($filters['price_from']) && isset($filters['time_from'])){
                $q_item .= "
				HAVING
					price BETWEEN {$filters['price_from']} AND {$filters['price_to']} AND
					delivery BETWEEN {$filters['time_from']} AND {$filters['time_to']} 
			";

            }
        }
        else $q_item .= " HAVING price>0";

        //строка закоментирована, т.к. затягивался поиск
        /*if (!$hide_analogies){
            if (empty($filters)) $q_item .= " OR price IS NULL";
            else $q_item .= " OR si.price IS NULL";
        } */

        $q_item .= ' ORDER BY b.title, price, delivery';
        return $q_item;
    }
}