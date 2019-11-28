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
			<div class="clear"></div>
		</form>
		<p>Для подбора запчастей по каталогам, выберите нужный Вам тип транспорта, далее его марку, модель и год если требуется. </p>
			<div class="clear"></div>
		</div>
		<div id="actions">

			<h2>Акции</h2>

			<div id="actions_slider">

				<div class="actions_slider">
					<div class="item"><img src="img/actions__action1.jpg" alt="Actions"></div>
					<div class="item"><img src="img/actions__action1.jpg" alt="Actions"></div>
					<div class="item"><img src="img/actions__action1.jpg" alt="Actions"></div>
					<div class="item"><img src="img/actions__action1.jpg" alt="Actions"></div>
				</div>

				<div class="prev">
					<div class="prev_btn"></div>
				</div>

				<div class="next">
					<div class="next_btn"></div>
				</div>

			</div>

		</div>

		<!-- /actions -->

	</div>

	<!-- news -->

	<div id="news">

		<h3>Новости</h3>

		<div class="news">

			<a href="#">
				<img src="img/news/news__news1.jpg" alt="News">
				<span class="news_date">16.04.2017</span>
				<span class="news_title one_row">Добавлены новые каталогии kia</span>
			</a>

			<a href="#">
				<img src="img/news/news__news2.jpg" alt="News">
				<span class="news_date">12.04.2017</span>
				<span class="news_title">Стартует новая акции на любые масла купленные до июня 2017</span>
			</a>

			<a href="#">
				<img src="img/news/news__news3.jpg" alt="News">
				<span class="news_date">09.04.2017</span>
				<span class="news_title">Горячая растпродажа зимних шин Nokian Купи 4 шины и получишь...</span>
			</a>

		</div>

	</div>

	<!-- /news -->

<div class="clear"></div>

	