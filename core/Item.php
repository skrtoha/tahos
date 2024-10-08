<?php
namespace core;
class Item{
    private static $fieldsAdditionalOptions = [];

	public static $lastInsertedItemID = false;

	public static function articleClear($article){
		return preg_replace('/[^\w_а-яА-Я]+/u', '', $article);
	}

	/**
	 * updates item
	 * @param  array $fields field => value
	 * @param  array $where  id or article and brend_id is required
	 * @return boolean true if updated sucessfully
	 */
	public static function update($fields, $where){
        self::$fieldsAdditionalOptions = [];
		if (!$where) return false;
		if (strpos($_SERVER['REQUEST_URI'], '/admin/?view=items') === false){
			$where['is_blocked'] = 0;
		}
		return self::processUpdate($fields, $where);
	}
	public static function insert($fields){
        self::$fieldsAdditionalOptions = [];
		$db = self::getInstanceDataBase();
		$barcode = $fields['barcode'] ?: false;
		unset($fields['barcode']);
        self::setAdditionalOptions($fields);
		$resItems = $db->insert('items', $fields);
		if ($resItems !== true) return $resItems;
		$last_id = $db->last_id();
        self::setAdditionalOptions($fields, $last_id);
		$db->insert('item_articles', ['item_id' => $last_id, 'item_diff' => $last_id]);
		if ($barcode){
			$resBarcode = $db->insert('item_barcodes', [
				'item_id' => $last_id,
				'barcode' => $barcode
			]);
			if ($resBarcode !== true){
				$db->delete('items', "`id` = $last_id");
				return $resBarcode;
			}
		}
		self::$lastInsertedItemID = $last_id;
		return true;
	}

    private static function getColumnItemOptions(){
        static $output;
        if ($output) return $output;
        $result = self::getInstanceDataBase()->query("SHOW COLUMNS FROM #item_options");
        $output = $result;
        return $output;
    }

    public static function setAdditionalOptions(& $fields, $item_id = null){
        if (empty(self::$fieldsAdditionalOptions)){
            $res_columns = self::getColumnItemOptions();
            foreach($res_columns as $column){
                if (!array_key_exists($column['Field'], $fields)) continue;
                self::$fieldsAdditionalOptions[$column['Field']] = $fields[$column['Field']];
                unset($fields[$column['Field']]);
            }
        }

        if (empty(self::$fieldsAdditionalOptions) || is_null($item_id)) return;
        self::getInstanceDataBase()->insert(
            'item_options',
            array_merge(['item_id' => $item_id], self::$fieldsAdditionalOptions),
            ['duplicate' => self::$fieldsAdditionalOptions]
        );
    }
	public static function getByBrendIDAndArticle($brend_id, $article){
		$article = self::articleClear($article);
		$query = self::getQueryItemInfo();
		$query .= "
			WHERE
				`brend_id` = $brend_id AND `article` = '$article'
		";
		$res_items = $GLOBALS['db']->query($query);
		if (!$res_items->num_rows) return false;
		return $res_items->fetch_assoc();
	}

    /**
     * @return Database
     */
    public static function getInstanceDataBase(): Database
    {
		return $GLOBALS['db'];
	}

	public static function getByID($item_id, $params = []){
		$query = self::getQueryItemInfo($params);
		$query .= "
			WHERE
				i.id = $item_id
		";
		$res = $GLOBALS['db']->query($query, '');
		return $res->fetch_assoc();
	}
	public static function getByArticle(string $article, $additionalFields = []): \mysqli_result
	{
		$article = self::articleClear($article);
		return $GLOBALS['db']->query("
			SELECT
				i.id,
				IF(i.article_cat != '', i.article_cat, i.article) AS article,
				i.title_full,
				i.brend_id,
				b.title AS brend
			FROM
				#items i
			LEFT JOIN
				#brends b ON b.id = i.brend_id
			WHERE
				i.article = '$article'
		", '');
	}
	private static function processUpdate($fields, $where){
        self::$fieldsAdditionalOptions = [];
		$conditions = '';
		foreach($where as $key => $value) $conditions .= "`{$key}` = '{$value}' AND ";
		$conditions = substr($conditions, 0, -5);
		try{
			if (isset($where['id']) && isset($fields['barcode'])){
				$GLOBALS['db']->delete('item_barcodes', "`item_id` = {$where['id']}");
				if ($fields['barcode']) $res = $GLOBALS['db']->insert(
					'item_barcodes',
					[
						'item_id' => $where['id'],
						'barcode' => $fields['barcode']
					]
				); 
				else $res = true;
				if ($res !== true) throw new \Exception($res);
				unset($fields['barcode']);
                self::setAdditionalOptions($fields, $where['id']);
			}
			$res = $GLOBALS['db']->update('items', $fields, $conditions);
			if ($res !== true) throw new \Exception($res);
		} catch(\Exception $c){
			Log::insertThroughException($c, [
				'query' => $GLOBALS['db']->last_query,
				'text' => $res
			]);
			return $res;
		}
		return true;
	}
	public static function clearAnalogies($item_id){
		return $GLOBALS['db']->delete('item_analogies', "`item_id` = $item_id OR `item_diff` = $item_id");
	}
	public static function getHrefArticle($article){
		return "/search/article/$article";
	}
	public static function getFiltersByItemID($item_id){
		$res_filters = self::getInstanceDataBase()->query("
			SELECT
				c.title AS category,
				ci.category_id,
				f.id AS filter_id,
				f.title AS filter,
				fv.id AS fv_id,
				fv.title AS fv,
				IF(iv.value_id IS NOT NULL, 'checked', '') AS checked,
				CAST(fv.title AS UNSIGNED) as sort_fv
			FROM
				#categories_items ci
			LEFT JOIN
				#filters f ON f.category_id = ci.category_id
			LEFT JOIN
				#filters_values fv ON fv.filter_id = f.id
			LEFT JOIN
				#categories c ON c.id = ci.category_id
			LEFT JOIN
				#items_values iv ON iv.item_id = {$_GET['id']} AND iv.value_id = fv.id
			WHERE
				ci.item_id = {$item_id}
			ORDER BY
				f.pos, sort_fv, fv.title
		", '');
		if(!$res_filters->num_rows) return false;
		$output = [];
		foreach($res_filters as $v){
			$c = & $output[$v['category_id']];
			$c['id'] = $v['category_id'];
			$c['title'] = $v['category'];
			$f = & $c['filters'][$v['filter_id']];
			$f['id'] = $v['filter_id'];
			$f['title'] = $v['filter'];
			$f['filter_values'][$v['fv_id']]['id'] = $v['fv_id'];
			$f['filter_values'][$v['fv_id']]['title'] = $v['fv'];
			$f['filter_values'][$v['fv_id']]['checked'] = $v['checked'];
		}
		return $output;
	}
	public static function getQueryItemsByCategoryID($category_id, $params){
		$where = '';
		$having = '';
		$limit = '';
		$order = '';
		$cnt = 0;
		if (!empty($params)){
			foreach($params as $key => $value){
				if (empty($value)) continue;
				switch($key){
					case 'search'	:
						$having .= "bat LIKE '%$value%' AND ";
						break;
					case 'fv':
						if ($params['comparing']){
							foreach($value as $filter_id => $fv_ids){
								$where .= '(fv.id IN (' . implode(',', $fv_ids) . ') AND fv.filter_id = ' . $filter_id . ') OR ';
								$cnt ++;
							}
						}
						else{
							$values = [];
							foreach($value as $filter_id => $fv_ids){
								foreach($fv_ids as $fv) $values[] = $fv;
							}
							$where .= 'fv.id IN ('.implode(',', $values).') OR ';
							$cnt = $cnt + count($values);
						}
						break;
					case 'sliders':
						foreach($value as $filter_id => $slider){
							$cnt++;
							$array = explode(';', $slider);
							$where .= "(CAST(fv.title AS UNSIGNED) BETWEEN {$array[0]} AND {$array[1]} AND fv.filter_id = {$filter_id}) OR ";
						}
						break;
					case 'limit': $limit = "LIMIT {$value}"; break;
					case 'sort': 
						switch($value){
							case 'title_full': $order .= "i.title_full {$params['direction']}"; break;
							case 'price': $order .= "p.price {$params['direction']}"; break;
							case 'rating': $order .= "i.rating {$params['direction']}"; break;
						}
						break;
				}
			}
		}
		if ($where){
			$where = substr($where, 0, -4);
			$where = " AND ($where)";
		} 
		if ($cnt) $having .= "cnt >= $cnt AND ";
		if ($having){
			$having = substr($having, 0, -5);
			$having = "HAVING $having";
		} 
		if ($order) $order = "ORDER BY $order";
		$query =  "
			SELECT
				ci.item_id,
				i.brend_id,
				i.article,
				i.title_full,
				b.title AS brend,
				CONCAT_WS(' ', b.title, i.article, i.title_full) AS bat,
				i.photo,
				p.price,
				COUNT(iv.item_id) AS cnt,
				p.delivery
			FROM
				#categories_items ci
			LEFT JOIN
				#items_values iv ON iv.item_id = ci.item_id 
			LEFT JOIN
				#items i ON i.id = ci.item_id
			LEFT JOIN
				#filters_values fv ON fv.id = iv.value_id
			LEFT JOIN
				#brends b ON b.id = i.brend_id
			RIGHT JOIN
				#prices p ON ci.item_id = p.item_id 
			WHERE
				ci.category_id = $category_id
				$where
			GROUP BY
				iv.item_id
			$having
			 $order
			$limit
		";
		return $query;
	}
	public static function getItemsByCategoryID($category_id, $params = []): array
	{
		$query = self::getQueryItemsByCategoryID($category_id, $params);
		$res_items = $GLOBALS['db']->query($query, '');
		if (!$res_items->num_rows) return [];
		foreach($res_items as $value){
			$items[] = $value;
			$items_ids[] = $value['item_id'];
		};
		$item_values = self::getItemValues($params['viewTab'], $items_ids);
		foreach($items as $key => $value){
			if (isset($item_values[$value['item_id']])){
				foreach($item_values[$value['item_id']] as $fv) $items[$key]['filter_values'][] = $fv;
			}
		} 
		return $items;
	}
	public static function getHtmlRating($rating){
		$str = '';
		$div = $rating / 2;
		// echo $rating;
		for ($i = 1; $i <= 5; $i++){
			if ($i < $div) $fa = 'fa-star';
			else{
				if ($div == $i) $fa = 'fa-star';
				elseif ($div + 0.5 == $i) $fa = 'fa-star-half-o';
				else $fa = 'fa-star-o';
			}
			$str .= '<i class="fa '.$fa.'" aria-hidden="true"></i>';
		}
		return $str;
	}
	public static function getItemValues($viewTab, array $ids): array
	{
		if ($viewTab == 'mosaic-view') $where = "f.isShowMosaicView = 1";
		else $where = 'f.isShowListView = 1';
		$query = "
			SELECT
				iv.item_id,
				fv.filter_id,
				f.title AS filter,
				fv.id AS fv_id,
				fv.title AS filter_value
			FROM
				#items_values iv
			LEFT JOIN
				#filters_values fv ON fv.id = iv.value_id
			LEFT JOIN
				#filters f ON f.id = fv.filter_id
			WHERE
				iv.item_id IN (".implode(',', $ids).") AND
				$where
			ORDER BY
				f.pos
		";
		$res = $GLOBALS['db']->query($query, '');
		if (!$res->num_rows) return false;
		$output = [];
		foreach($res as $value){
			$output[$value['item_id']][$value['fv_id']]['filter_id'] = $value['filter_id'];
			$output[$value['item_id']][$value['fv_id']]['filter'] = $value['filter'];
			$output[$value['item_id']][$value['fv_id']]['fv_id'] = $value['fv_id'];
			$output[$value['item_id']][$value['fv_id']]['filter_value'] = $value['filter_value'];
		}
		return $output;
	}
	public static function getCategoriesByItemID($item_id){
		$res_items = $GLOBALS['db']->query("
			SELECT
				c.id AS category_id,
				c.title AS category
			FROM
				#categories_items ci
			LEFT JOIN
				#categories c ON c.id = ci.category_id
			WHERE
				ci.item_id = $item_id
		", '');
		if (!$res_items->num_rows) return false;
		$output = [];
		foreach($res_items as $item) $output[$item['category_id']] = $item['category'];
		return $output;
	}
	public static function getStoreItemsByItemID($item_id){
		$res_items = $GLOBALS['db']->query("
			SELECT
				si.store_id,
				si.price,
				ps.cipher
			FROM
				#store_items si
			LEFT JOIN
				#provider_stores ps ON ps.id = si.store_id
			WHERE
				si.item_id = $item_id
		", '');
		if (!$res_items->num_rows) return false;
		$output = [];
		foreach($res_items as $item){
			$output[$item['cipher']] = [
				'store_id' => $item['store_id'],
				'price' => $item['price']
			];
		} 
		return $output;
	}
	/**
	 * get query of item
	 * @param  array $params 
	 *         withCategories - add info about categories, after it's nessesary add "GROUP BY i.id"
	 * @return [type]         [description]
	 */
	public static function getQueryItemInfo($params = []): string
    {
		$query = "
			SELECT
				i.*,
				ib.barcode,
				b.title AS brend
			";
        if (in_array('additional_options', $params)){
            $query .= ",
                IF(ISNULL(io.depth), 0, io.depth) AS depth,
                IF(ISNULL(io.width), 0, io.width) AS width,
                IF(ISNULL(io.height), 0, io.height) AS height,
                IF(ISNULL(io.weight), 0, io.weight) AS weight,
                IF(ISNULL(io.measure_id), 0, io.measure_id) AS measure_id,
                IF(ISNULL(io.amount_package), 0, io.amount_package) AS amount_package,
                m.title AS measure_title
            ";
        }
        if (in_array('itemVin', $params)){
            $query .= ",
                iv.vin,
                DATE_FORMAT(iv.created, '%d.%m.%Y %H:%i:%s') AS created
            ";
        }
        if (in_array('marketplace_description', $params)){
            $query .= ",ad.description AS marketplace_description";
        }
		if (in_array('withCategories', $params)){
			$query .= ",GROUP_CONCAT(c.title SEPARATOR '; ') AS categories";
		}
        if (in_array('category_tahos', $params)){
            $query .= ",ci.category_id as tahos_category_id";
        }
        if (in_array('ozon_item', $params)){
            $query .= ", IF(ISNULL(oi.markup_marketplace), 25, oi.markup_marketplace) as markup_marketplace";
            $query .= ", IF(ISNULL(oi.item_title), i.title, oi.item_title) as name";
        }
		$query .= "
			FROM
				#items i
			LEFT JOIN
				#brends b ON b.id = i.brend_id
			LEFT JOIN
				#item_barcodes ib ON ib.item_id = i.id
		";
        if (in_array('additional_options', $params)){
            $query .= "
                LEFT JOIN
                    #item_options io ON io.item_id = i.id
                LEFT JOIN
                    #measures m ON m.id = io.measure_id
            ";
        }
        if (in_array('marketplace_description', $params)){
            $query .= "
                LEFT JOIN
                    #item_marketplace_description ad ON ad.item_id = i.id 
            ";
        }
		if (in_array('withCategories', $params)){
			$query .= "
				LEFT JOIN	
					#categories_items ci ON diff.item_diff = ci.item_id
				LEFT JOIN
					#categories c ON c.id = ci.category_id
			";
		}
        if (in_array('itemVin', $params)){
            $query .= "
                 LEFT JOIN
                    #item_vin iv ON iv.item_id = i.id
            ";
        }
        if (in_array('category_tahos', $params)){
            $query .= "
                 LEFT JOIN
                    #categories_items ci ON ci.item_id = i.id
            ";
        }
        if (in_array('ozon_item', $params)){
            $query .= "
                 LEFT JOIN
                    #ozon_item oi ON oi.offer_id = i.id
            ";
        }
		return $query;
	}
	public static function getResItemDiff($type, $item_id, $flag = '')
	{
		$analogiesFields = '';
		if ($type == 'analogies'){
			$analogiesFields = "diff.status,";
		}
		$query = "
			SELECT
			i.id AS item_id,
			b.title AS brend,
			i.article,
            i.article_cat,
			i.title_full,
			ib.barcode,
			$analogiesFields
			GROUP_CONCAT(c.title SEPARATOR '; ') AS categories
		FROM
			#item_$type diff
		LEFT JOIN
			#items i
		ON i.id = diff.item_diff
		LEFT JOIN
			#brends b ON b.id = i.brend_id
		LEFT JOIN
			#item_barcodes ib ON ib.item_id = diff.item_diff
		LEFT JOIN	
			#categories_items ci ON diff.item_diff = ci.item_id
		LEFT JOIN
			#categories c ON c.id = ci.category_id
		WHERE
			diff.item_id = $item_id AND i.id != $item_id
		GROUP BY
			diff.item_diff
		ORDER BY
			b.title
		";
		return $GLOBALS['db']->query($query, $flag);
	}
	public static function deleteMissingPhoto($files, $photos, $type){
		if (empty($files)) return;
		foreach($files as $existingFile){
			$isForDeleting = true;
			$fileName = preg_replace('/.*\//', '', $existingFile);
			if (!empty($photos)){
				foreach($photos as $photo){
					if (strpos($photo[$type], $fileName) !== false){
						$isForDeleting = false;
						break;
					} 
				}
			}
			if ($isForDeleting) unlink($existingFile);
		}
	}
    private static function tplMenu($category,$str, $category_id = null){
        $selected = $category_id && $category['id'] == $category_id ? 'selected' : '';
        if($category['parent_id'] == 0){
            $menu = '<option '.$selected.' value="'.$category['id'].'">'.$category['title'].'</option>';
        }else{
            $menu = '<option '.$selected.' value="'.$category['id'].'">'.$str.' '.$category['title'].'</option>';
        }
        if(isset($category['childs'])){
            $i = 1;
            for($j = 0; $j < $i; $j++) $str .= '→';
            $menu .= self::showCat($category['childs'], $str, $category_id);
        }
        return $menu;
    }
    public static function showCat($data, $str, $category_id = null){
        $string = '';
        foreach($data as $item){
            $string .= self::tplMenu($item, $str, $category_id);
        }
        return $string;
    }
    public static function getCat($where = ''){
        global $db;
        static $output;

        if (!empty($output[$where])) return $output[$where];

        $query = "SELECT * from #categories";
        if ($where) $query .= " WHERE $where";
        $res = $db->query($query);
        $output[$where] = array();
        while($row = $res->fetch_assoc()){
            $output[$where][$row['id']] = $row;
        }
        return $output[$where];
    }
    public static function getTree($dataset) {
        $tree = array();
        foreach ($dataset as $id => &$node) {
            //Если нет вложений
            if (!$node['parent_id']){
                $tree[$id] = &$node;
            }else{
                //Если есть потомки, то перебираем массив
                $dataset[$node['parent_id']]['childs'][$id] = &$node;
            }
        }
        return $tree;
    }
    public static function getTplCategory($category_id = null, $parent_id = null){
        $cat  = self::getCat();
        $tree = self::getTree($cat);

        if (is_null($parent_id)) return self::showCat($tree, '', $category_id);

        foreach($tree as $key => & $value){
            if ($key != $parent_id) continue;
            return self::showCat($value['childs'], '', $category_id);
        }
        return false;
    }
    public static function getMainCategory($selectedCategory = null): string
    {
        $cat  = Item::getCat('`parent_id` = 0');
        $tree = Item::getTree($cat);
        $cat_menu = Item::showCat($tree, '', $selectedCategory);
        return "
            <select class='main_category'>
                <option value=''>выберите...</option>
                $cat_menu
            </select>
        ";
    }
    public static function getSubCategory($parent_id, $selected = null, $multipleCategory = false): string
    {
        $tpl = Item::getTplCategory($selected, $parent_id);
        $name = 'category_id';
        if (!$multipleCategory) $name .= '[]';
        return "
            <select name='$name'>
                $tpl
            </select>
            <span class='icon-cancel-circle1'></span>
        ";
    }

    //todo реализация не оптимальна, нужна доработка
    public static function getParentMainCategory($initital){
        global $db;
        $output = null;
        $value = $initital;
        while(!$output){
            $result = $db->select_one('categories', '*', "`id` = $value");
            if ($result['parent_id'] == 0) return $result['id'];
            else $value = $result['parent_id'];
        }
        return false;
    }


}
