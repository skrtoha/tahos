<?
namespace core\Provider;
use core\Provider;
class Tahos extends Provider{
	public static $store_id = 23;
	public static $provider_id = 14;
	public static $isDoNotShowStoresCheeperTahos = true;

	public static function parseResItem(\mysqli_result $res_items): array
	{
		$priceTahos = NULL;	
		$items = [];
		foreach($res_items as $v){
			$items[] = $v;
			if ($v['store_id'] == self::$store_id) $priceTahos = $v['price'];
		}
		if (!self::$isDoNotShowStoresCheeperTahos || !$priceTahos) return $items;
		$output = [];
		foreach($items as $v){
			if ($v['price'] < $priceTahos && $v['store_id'] != self::$store_id) continue;
			$output[] = $v;
		}
		return $output;
	}
	public static function getStoreItems(): \mysqli_result
	{
		return parent::getInstanceDataBase()->query("
			SELECT
				b.title AS brend,
				i.article,
				i.title_full,
				si.in_stock,
				CEIL(si.price * c.rate + si.price * c.rate * ps.percent / 100) as price,
				si.packaging
			FROM
				#store_items si
			LEFT JOIN
				#items i ON i.id = si.item_id
			LEFT JOIN
				#brends b ON b.id = i.brend_id
			LEFT JOIN
				#provider_stores ps ON ps.id = si.store_id
			LEFT JOIN 
				#currencies c ON c.id=ps.currency_id
			WHERE
				si.store_id = " . self::$store_id . "
		", '');
	}
	public static function getPrice($params){}
	public static function getItemsToOrder($provider_id): array {}
	public static function isInBasket($params){}
	public static function removeFromBasket($ov){}
	public static function addToBasket($params){}
	/**
	 * [processExcelFileForSubscribePrices description]
	 * @param  \mysqli_result $res_store_items [description]
	 * @param  integer        $discount        [description]
	 * @return string file path
	 */
	public static function processExcelFileForSubscribePrices(\mysqli_result $res_store_items, $discount = 0): string
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
		$file = $_SERVER['DOCUMENT_ROOT'] . '/tmp/price.xlsx';
		$writer->save($file);
		return $file;
	}
}
