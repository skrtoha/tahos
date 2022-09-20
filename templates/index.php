<?
use core\Category;

$title="Торговая площадка Тахос";
$res_vehicles = $db->query("
	SELECT
		v.id,
		v.title,
		v.href
	FROM
		#vehicles v
	ORDER BY v.title
");
$categories = Category::getAll('c.isShowOnMainPage = 1 AND sc.isShowOnMainPage = 1');
?>
<div id="selection">
	<div class="selection">
		<h2>Подбор запчастей</h2>
		<p>Для подбора запчастей по каталогам, выберите нужный Вам тип транспорта, далее его марку, модель и год если требуется. </p>
		<form action="#" method="get">
			<div class="select active">
				<select class="vehicle_select" data-placeholder="Тип">
					<option label="Вид транспорта" value=""></option>
					<?while($row = $res_vehicles->fetch_assoc()){?>
						<option value="<?=$row['id']?>"><?=$row['title']?></option>
					<?}?>
				</select>
			</div>
			<div class="select">
				<select class="brend_select" disabled data-placeholder="Марка">
					<option label="Выбор бренда" value=""></option>
				</select>
			</div>
			<div class="select">
				<select class="year_select" disabled data-placeholder="Год выпуска">
					<option label="Выбор года" value=""></option>
				</select>
			</div>
			<div class="select">
				<select disabled class="model_select" data-placeholder="Модель" data-search="true">
					<option label="Выбор модели" value=""></option>
				</select>
			</div>
		</form>
	</div>
	<div class="selection">
		<div class="categories">
			<?foreach($categories as $category_title => $value){?>
				<div class="category">
					<h3 class="title"><a href="/category/<?=$value['href']?>"><?=$category_title?></a></h3>
					<ul class="left">
						<?foreach($value['subcategories'] as $sc){?>
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

	