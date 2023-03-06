<?php
namespace core;

class Synchronization{
	public static function getNoneSynchronizedOrders(){
		return self::getOrders(['synchronized' => 0], '');
	}
	public static function getOrders($params, $flag){
		$output = [];
		$res_order_values = OrderValue::get($params, $flag);
		foreach($res_order_values as $ov){
            if (!$ov['bill_type']) continue;

			$o = & $output[$ov['order_id']];
			$o['user_id'] = $ov['user_id'];
			$o['userName'] = mb_strtoupper($ov['userName']);
			$o['created'] = $ov['created'];
            $o['billType'] = $ov['bill_type'];
            $o['typeOrganization'] = $ov['typeOrganization'];
            $o['userCreated'] = $ov['userCreated'];
            $o['arrangement_uid'] = $ov['arrangement_uid'];
			$output[$ov['order_id']]['values'][] = [
				'status_id' => $ov['status_id'],
				'provider_id' => $ov['provider_id'],
				'provider' => mb_strtoupper($ov['provider']),
				'cipher' => $ov['cipher'],
				'order_id' => $ov['order_id'],
				'user_id' => $ov['user_id'],
				'store_id' => $ov['store_id'],
				'providerStore' => $ov['providerStore'],
                'typeOrganization' => $ov['typeOrganization'],
				'brend' => $ov['brend'],
				'brend_id' => $ov['brend_id'],
				'item_id' => $ov['item_id'],
				'article' => $ov['article'],
				'article_cat' => $ov['article_cat'] ?: $ov['article'],
				'title_full' => $ov['title_full'],
				'packaging' => $ov['packaging'],
				'price' => $ov['price'],
				'quan' => $ov['quan'],
				'ordered' => $ov['ordered'],
				'arrived' => $ov['arrived'],
				'issued' => $ov['issued'],
                'issue_id' => $ov['issue_id'],
                'issued_date' => $ov['issued_date'],
				'returned' => $ov['returned'],
				'updated' => $ov['updated'] ?: $ov['created'],
				'withoutMarkup' => $ov['withoutMarkup'],
                'return_price' => $ov['return_price'],
                'return_data' => $ov['return_data']
			];
		}
		return $output;
	}
	public static function setOrdersSynchronized($osi){
        foreach($osi as $row){
            $array = explode('-', $row);
            $GLOBALS['db']->query("
                UPDATE 
                    #orders_values 
                SET 
                    synchronized = 1 
                WHERE 
                    (`order_id` = {$array[0]} AND `store_id` = {$array[1]} AND `item_id` = {$array[2]}) AND 
                    synchronized = 0
	    	");
        }
	}
	public static function getArrayOSIFromString($osi){
		$array = explode('-', $osi);
		return [
			'order_id' => $array[0],
			'store_id' => $array[1],
			'item_id' => $array[2]
		]; 
	}
    public static function createItem($data){
        /** @var Database $db */
        $db = $GLOBALS['db'];

        $output = [
            'error' => '',
            'result' => []
        ];

        if ($data['brend']['id'] == 0){
            $result = $db->select_one(
                'brends',
                'id',
                "`title` = '{$data['brend']['title']}' AND `parent_id` = 0"
            );
            if (!empty($result)){
                $data['brend_id'] = $result['id'];
                $output['result']['created_brend_id'] = $result['id'];
            }
            else{
                $result = $db->insert('brends', [
                    'title' => $data['brend']['title'],
                    'href' => translite($data['brend']['title']),
                    'parent_id' => 0
                ]);
                if ($result !== true){
                    $output['error'] = $result;
                    return $output;
                }
                $data['brend_id'] = $db->last_id();
                $output['result']['created_brend_id'] = $data['brend_id'];
            }
        }
        else $data['brend_id'] = $data['brend']['id'];

        unset($data['brend']);

        if ($data['id']) $result = Item::update($data, ['id' => $data['id']]);
        else{
            $query = Item::getQueryItemInfo();
            $query .= " WHERE i.article = '{$data['article']}' AND i.brend_id = {$data['brend_id']}";

            $result = $db->query($query);
            if ($result->num_rows){
                $row = $result->fetch_assoc();
                $output['result']['created_item_id'] = $row['id'];
                return $output;
            }

            $result = Item::insert($data);
            if ($result === true) $output['result']['created_item_id'] = Item::$lastInsertedItemID;
        }

        if ($result !== true){
            $output['error'] = $result;
            return $output;
        }

        return $output;
    }
}
