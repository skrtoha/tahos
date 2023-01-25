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
    <div class="selection spare_parts_request">
        <div class="left">
            <h2>Найдите модель по vin-номеру</h2>
            <p class="description">
                VIN автомобиля является самым надежным идентификатором. <br>
                Если ищете японский автомобиль, то введите FRAME
            </p>
            <form action="" class="spare_parts_request">
                <label>
                    <input type="text" name="q" placeholder="VIN или FRAME" required>
                </label>
                <button class="icon-search"></button>
                <p>Например: <span class="example">SJNFCAP10U0339326</span>, или FRAME <span class="example">KZN185-9023353</span></p>
            </form>
        </div>
        <div class="right">
            <h2>Подбор запчастей экспертом</h2>
            <p></p>
            <form id="spare_parts_request" action="#" method="post">
                <div class="flex">
                    <div class="selection">
                        <img src="/img/icons/spare_parts_icon.svg" alt="">
                    </div>
                    <div class="selection">
                        <label class="vin">
                            <span>VIN-номер или номер кузова</span>
                            <input required placeholder="XW8ZZZ7pzfg001290" type="text" name="vin">
                        </label>

                    </div>
                    <div class="selection car_issue_year">
                        <label class="car">
                            <span>автомобиль</span>
                            <input placeholder="Renault Sandero II" type="text" name="car">
                        </label>
                        <label class="issue_year">
                            <span>год</span>
                            <input placeholder="2003" type="text" name="issue_year">
                        </label>
                    </div>
                    <div class="selection">
                        <label class="phone">
                            <span>телефон</span>
                            <?$value = isset($user['phone']) && $user['phone'] ? $user['phone'] : '';?>
                            <input required type="text" name="phone" value="<?=$value?>">
                        </label>

                    </div>
                    <div class="selection">
                        <label class="name">
                            <span>имя</span>
                            <?$value = isset($user['name']) && $user['name'] ? $user['name'] : '';?>
                            <input required type="text" name="name" value="<?=$value?>">
                        </label>
                    </div>
                    <div class="selection">
                        <label class="description">
                            <span>требуемые запчасти</span>
                            <textarea required name="description"></textarea>
                        </label>
                    </div>
                    <input type="submit" value="Отправить">
                </div>
            </form>
        </div>
    </div>
</div>

	