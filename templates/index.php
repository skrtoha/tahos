<?$title="Торговая площадка Тахос";
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
		<h2>Подбор запчастей</h2>
		<p>Для подбора запчастей по каталогам, выберите нужный Вам тип транспорта, далее его марку, модель и год если требуется. </p>
		<form action="#" method="get">
			<div class="select active">
				<select class="vehicle_select" data-placeholder="Тип">
					<option value=""></option>
					<?while($row = $res_vehicles->fetch_assoc()){?>
						<option href="<?=$row['href']?>" value="<?=$row['id']?>"><?=$row['title']?></option>
					<?}?>
				</select>
			</div>
			<div class="select">
				<select class="brend_select" disabled data-placeholder="Марка">
					<option value=""></option>
				</select>
			</div>
			<div class="select">
				<select class="year_select" disabled data-placeholder="Год выпуска">
					<option value=""></option>
				</select>
			</div>
			<div class="select">
				<select disabled class="model_select" data-placeholder="Модель" data-search="true">
					<option value=""></option>
				</select>
			</div>
		</form>
		</div>
	</div>

	