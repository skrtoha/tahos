<?php
namespace core\Provider;
use core\Database;
use core\Provider;
use core\Config;

class Tahos extends Provider{
	public static $store_id = Config::MAIN_STORE_ID;
	public static $provider_id = 14;
	public static $isDoNotShowStoresCheeperTahos = true;

	public static function parseResItem(\mysqli_result $res_items): array
	{
		$items = [];
		$tahosPrices = [];
		foreach($res_items as $v){
			$items[] = $v;
			if ($v['store_id'] == self::$store_id && $v['in_stock'] > 0){
                $tahosPrices[$v['item_id']] = $v['price'];
            }
		}
		$output = [];
		foreach($items as $v){
			if (
				isset($tahosPrices[$v['item_id']]) &&
                $v['price'] < $tahosPrices[$v['item_id']]
			) continue;
			$output[] = $v;
		}
		return $output;
	}
	public static function getPrice($params){
		return [
			'price' => $params['price'],
			'available' => $params['in_stock']
		];
	}
	public static function getItemsToOrder($provider_id): array {
		return [];
	}

    private static function getDbInstance(): Database
    {
        return $GLOBALS['db'];
    }
	public static function isInBasket($params){}
	public static function removeFromBasket($ov){}
	public static function addToBasket($params){}
	/**
	 * [processExcelFileForSubscribePrices description]
	 * @param  \mysqli_result $res_store_items [description]
	 * @param  integer        $discount        [description]
	 * @return string file path
	 */
	public static function processExcelFileForSubscribePrices(\mysqli_result $res_store_items, $fileName, $discount = 0): string
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php');
		
		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$row = 1;

		$sheet->setCellValueByColumnAndRow(1, $row, 'Бренд');
		$sheet->setCellValueByColumnAndRow(2, $row, 'Артикул');
		$sheet->setCellValueByColumnAndRow(3, $row, 'Название');
		$sheet->setCellValueByColumnAndRow(4, $row, 'Наличие');
		$sheet->setCellValueByColumnAndRow(5, $row, 'Цена');
		$sheet->setCellValueByColumnAndRow(6, $row, 'Кратность');

		foreach($res_store_items as $si){
			$row++;
			$si['price'] = ceil($si['price'] - $si['price'] * $discount / 100);
			$sheet->setCellValueByColumnAndRow(1, $row, $si['brend']);
			$sheet->setCellValueByColumnAndRow(2, $row, $si['article']);
			$sheet->setCellValueByColumnAndRow(3, $row, $si['title_full']);
			$sheet->setCellValueByColumnAndRow(4, $row, $si['in_stock']);
			$sheet->setCellValueByColumnAndRow(5, $row, $si['price']);
			$sheet->setCellValueByColumnAndRow(6, $row, $si['packaging']);
		}

		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
		$file = Config::$tmpFolderPath . "/$fileName.xlsx";
		$writer->save($file);
		return $file;
	}
    public static function isSelfStore($store_id){
        $result = self::getDbInstance()->select_one('provider_stores', ['provider_id'], "`id` = {$store_id}");
        if ($result['provider_id'] == self::$provider_id) return true;
        return false;
    }
    public static function getSelfStores(){
        return self::getDbInstance()->select('provider_stores', ['id', 'title'], "`self` = 1");
    }
}
