<?
/** @var string $title */
/** @var \core\Database $db */
/** @var array $categories */

use core\User;

$title="Торговая площадка Тахос";

if (!empty($_POST)){
    $currentDateTime = new DateTime();
    $previousDateTime = $currentDateTime->sub(DateInterval::createFromDateString('1 hour'));
    $result = $db->getCount(
        'spare_parts_request',
        "`ip` = '{$_SERVER['REMOTE_ADDR']}' AND `created` >= '".$previousDateTime->format('Y-m-d H:i:s')."'"
    );
    if (!empty($result)){
        message('Запрос недавно уже был отправлен', false);
        header("Location: {$_SERVER['HTTP_REFERER']}");
        die();
    }
    $result = User::setSparePartsRequest($_POST);
    if ($result === true){
        message('Запрос успешно отправлен!');
        header("Location: {$_SERVER['HTTP_REFERER']}");
        die();
    }
    else message($result, false);
}

$res_vehicles = $db->query("
	SELECT
		v.id,
		v.title,
		v.href
	FROM
		#vehicles v
	ORDER BY v.title
");
?>
<div id="selection">
	<div class="selection">
		<h2>Подбор запчастей экспертом</h2>
		<p></p>
		<form id="spare_parts_request" action="#" method="post">
            <div class="flex">
                <div class="selection">
                    <label>
                        <span>VIN-номер или номер кузова</span>
                        <input required placeholder="XW8ZZZ7pzfg001290" type="text" name="vin">
                    </label>

                </div>
                <div class="selection">
                    <label>
                        <span>автомобиль</span>
                        <input placeholder="Renault Sandero II" type="text" name="car">
                    </label>
                    <label>
                        <span>год выпуска</span>
                        <input placeholder="2003" type="text" name="issue_year">
                    </label>
                </div>
                <div class="selection">
                    <label>
                        <span>требуемые запчасти</span>
                        <textarea required name="description" cols="30" rows="10"></textarea>
                    </label>
                </div>
                <div class="selection">
                    <label>
                        <span>телефон</span>
                        <?$value = isset($user['phone']) && $user['phone'] ? $user['phone'] : '';?>
                        <input required type="text" name="phone" value="<?=$value?>">
                    </label>
                    <label>
                        <span>имя</span>
                        <?$value = isset($user['full_name']) && $user['full_name'] ? $user['full_name'] : '';?>
                        <input required type="text" name="name"  value="<?=$value?>">
                    </label>
                </div>
            </div>
            <p class="description">VIN-номер и номер кузова (для японских автомобилей) имеют решающее значение в подборе запчасти. Без него нет гарантии точного определения применимости запчасти.</p>
            <input type="submit" value="Отправить">
		</form>
	</div>
	<div class="selection">
		<div class="categories">
			<?foreach($categories as $category_title => $value){
                if (!$value['isShowOnMainPage']) continue;?>
				<div class="category">
					<h3 class="title"><a href="/category/<?=$value['href']?>"><?=$category_title?></a></h3>
					<ul class="left">
						<?foreach($value['subcategories'] as $sc){
                            if (!$sc['isShowOnMainPage']) continue;?>
							<li>
								<a href="/category/<?=$value['href']?>/<?=$sc['href']?>"><?=$sc['title']?></a>
							</li>
						<?}?>
					</ul>
					<?if (file_exists(core\Config::$imgPath . '/' . "categories/{$value['id']}.jpg")){?>
						<div class="right">
							<a href="/category/<?=$value['href']?>">
								<img alt="<?=$value['href']?>" src="<?=core\Config::$imgUrl?>/categories/<?=$value['id']?>.jpg">
							</a>
						</div>
					<?}?>
				</div>
			<?}?>
		</div>
	</div>
</div>

	