<?
namespace admin\functions;
class LeftMenu{
	public static $commonPermisions = [
		'Номенклатура' => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Обновление цен',
		'Доставки',
		'Заказы',
		'Возвраты' => [
			'Изменение'
		],
		'Финансовые операции',
		'Категории товаров' => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Подкатегории' => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Бренды товаров' => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Сообщения',
		'Валюта',
		'Прайсы',
		'Поставщики' => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Точки выдачи' => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Соединения',
		'Пользователи' => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Менеджеры' => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Оригинальные каталоги'  => [
			'Добавление',
			'Удаление',
			'Изменение'
		],
		'Выдачи товара',
		'Тексты',
		'Файлы',
		'Отчеты',
		'Тест API поставщиков',
		'Рассылки прайсов'
	];

	public static $pagesViews = [
		'Администрирование' => ['administration'],
		'Номенклатура' => ['items'],
		'Обновление цен' => ['min_prices'],
		'Доставки' => ['sendings'],
		'Заказы' => ['orders'],
		'Возвраты' => ['returns'],
		'Финансовые операции' => ['funds'],
		'Категории товаров' => ['categories'],
		'Подкатегории' => ['category'],
		'Бренды товаров' => ['brends'],
		'Сообщения' => ['messages', 'correspond'],
		'Валюта' => ['currencies', 'get_currencies'],
		'Прайсы' => ['prices'],
		'Поставщики' => ['providers'],
		'Точки выдачи' => ['issues'],
		'Соединения' => ['connections'],
		'Пользователи' => ['users'],
		'Менеджеры' => ['managers'],
		'Оригинальные каталоги' => ['original-catalogs'],
		'Выдачи товара' => ['order_issues'],
		'Тексты' => ['texts'],
		'Файлы' => ['files'],
		'Отчеты' => ['reports'],
		'Тест API поставщиков' => ['test_api_providers'],
		'Рассылки прайсов' => ['subscribePrices']
	];

	public static $defaultPermissions = [
		'authorization',
		'index',
		'cron'
	];

	public static $leftMenu = [
		'Администрирование' => [
			'Обновление цен' => 'min_prices',
			'Валюта' => 'currencies',
			'Точки выдачи' => 'issues',
			'Тексты' => 'texts',
			'Файлы' => 'files',
			'Соединения' => 'connections',
			'Тест API поставщиков' => 'test_api_providers',
			'Рассылки прайсов' => 'subscribePrices'
		],
		'Финансовые операции' => 'funds',
		'Отчеты' => [
			'Номенклатура' => 'reports&tab=nomenclature',
			'Бренды' => 'reports&tab=brends',
			'Неправильный аналог' => 'reports&tab=wrongAnalogy',
			'Удаление товара' => 'reports&tab=request_delete_item',
			'Покупаемость' => 'reports&tab=purchaseability',
			'История поиска' => 'reports&tab=searchHistory',
			'Остатки на основном складе' => 'reports&tab=remainsMainStore'
		],
		'Номенклатура' => 'items',
		'Доставки' => 'sendings',
		'Заказы' => 'orders',
		'Возвраты' => 'returns',
		'Категории товаров' => 'categories',
		'Бренды товаров' => 'brends',
		'Сообщения' => 'messages',
		'Прайсы' => 'prices',
		'Поставщики' => 'providers',
		'Пользователи' => 'users',
		'Оригинальные каталоги' => 'original-catalogs',
		'Выдачи товара' => 'order_issues'
	];

	public static function getCountNew(string $view): int
	{
		switch($view){
			case 'returns':
			case 'orders':
				return $GLOBALS['db']->getCount($view, '`is_new` = 1');
				break;
			case 'messages':
				return $GLOBALS['db']->getCount('messages', '`is_read` = 0 AND sender = 1');
				break;
			default: return 0;
		}
	}
}
