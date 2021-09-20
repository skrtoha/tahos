<?
if (!$_GET['id']){
	$title = 'Формирование отправки';
	// debug($_GET);
	$user_id = $_SESSION['user'];
	$count_templates = 3;
	if (!$user_id) header('Location: /');
	if (!empty($_POST)){
		$not = ['save_template', 'items'];
		foreach ($_POST as $key => $value){
			if (!in_array($key, $not)) $array[$key] = trim($value);
		} 
		$array['user_id'] = $user_id;
		// debug($_POST, 'post');
		// debug($array); exit();
		if (isset($_POST['save_template'])){
			$templates = $db->select('templates', 'id', "`user_id`=$user_id", 'created', true);
			foreach($array as $key => $value){
				if ($key == 'issue_id') continue;
				$templates_insert[$key] = $value;
			}
			if (count($templates) < $count_templates) $db->insert('templates', $templates_insert);
			else $db->update('templates', $templates_insert, "`id`={$templates[0]['id']}");
			// exit();
		}
		$res = $db->insert('sendings', $array);
		if ($res !== true) die("$res | $db->last_query");
		message('Доставка успешно сформирована!');
		header('Location: /orders#tabs|orders:sendings');
	}
	$items = $db->query("
		SELECT
			ov.order_id,
			ov.store_id,
			ov.item_id,
			i.title_full AS title,
			IF(i.article_cat != '', i.article_cat, i.article) AS article,
			@quan := (ov.arrived - ov.issued) AS quan,
			(i.weight * @quan) AS weight,
			b.title AS brend,
			(ov.price * @quan) AS price,
			ov.comment,
			DATE_FORMAT(ov.updated, '%d.%m.%Y %H:%i') AS date
		FROM
			#orders_values ov
		LEFT JOIN #items i ON i.id=ov.item_id
		LEFT JOIN #brends b ON b.id=i.brend_id
		LEFT JOIN tahos_orders o ON o.id = ov.order_id
		WHERE
			ov.user_id={$_SESSION['user']} AND
			ov.status_id=3 AND o.delivery = 'Доставка' AND
            (
                (isCompletelyArrived(ov.order_id) = 1 AND o.entire_order = 1) OR
                o.entire_order = 0
            )
	", '');
	$weight = 0;
	$sum = 0;
	$quan = 0;
	?>
	<div class="sending-page">
		<h1>Формирование отправки</h1>
		<?if (!$items->num_rows){?>
			<h4>Нет товаров для отправки</h4>
		<?}
		else{?>
			<h4>Доступные товары</h4>
			<?if ($device == 'desktop' || $device == 'tablet'){?>
				<table>
					<tr>
						<th>Наименование</th>
						<th>Поступление на склад</th>
						<th>Кол-во</th>
						<th>Вес</th>
						<th>Сумма</th>
						<th>Комментарий</th>
						<th></th>
					</tr>
					<?foreach($items as $item){
						$weight += $item['weight'];
						$quan += $item['quan'];
						$price += $item['price'];?>
						<tr>
							<td class="name-col">
								<b style="font-weight: 700"><?=$item['brend']?></b> 
								<a href="<?=core\Item::getHrefArticle($item['article'])?>" class="articul"><?=$item['article']?></a> 
								<?=$item['title']?>
							</td>
							<td><?=$item['date']?></td>
							<td><span class="quan"><?=$item['quan']?></span></td>
							<td><span class="weight"><?=$item['weight']?></span>гр.</td>
							<td><span class="price"><?=$item['price']?></span> <i class="fa fa-rub" aria-hidden="true"></i></td>
							<td><?=$item['comment']?></td>
							<td>
								<input
									type="checkbox"
									checked
									name="<?=$item['order_id']?>:<?=$item['item_id']?>:<?=$item['store_id']?>"
									value="<?=$item['quan']?>"
									class="item"
								>
							</td>
						</tr>
					<?}?>
				</table>
			<?}?>
			<?if ($device == 'mobile'){?>
				<table class="small-view">
					<tr>
						<th>Товар</th>
						<th></th>
					</tr>
					<?foreach ($items as $item){
						$weight += $item['weight'];
						$quan += $item['quan'];
						$price += $item['price'];
						?>
						<tr>
							<td class="name-col">
								<b style="font-weight: 700"><?=$item['brend']?></b>  <br>
								<a href="<?=core\Item::getHrefArticle($item['article'])?>" class="articul"><?=$item['article']?></a>  <br>
								<?=$item['title']?> <br> <br>
								<p>Поступление на склад: <?=$item['date']?></p>
								<p>Количество: <span class="quan"><?=$item['quan']?></span></p>
								<p>Вес: <span class="weight"><?=$item['weight']?></span> гр.</p>
								<p>Цена: <span class="price"><?=$item['price']?></span> <i class="fa fa-rub" aria-hidden="true"></i></p>
								<textarea readonly><?=$item['comment']?></textarea>
							</td>
							<td>
								<input 
									type="checkbox"
									checked 
									name="<?=$item['order_id']?>:<?=$item['item_id']?>:<?=$item['store_id']?>"
									value="<?=$item['quan']?>"
									class="item" 
								>
							</td>
						</tr>
					<?}?>
				</table>
			<?}?>
			<div class="summary">
				<p>Всего товаров: <span class="goods-count"><?=$quan?></span></p>
				<p>Общий вес: <span class="weight-total"><span><?=$weight?></span> гр.</span></p>
				<p>Общая сумма: <span class="amount-total"><span><?=$price?></span> <i class="fa fa-rub" aria-hidden="true"></i></span></p>
			</div>
			<form id="delivery-form" method="post">
				<input type="hidden" name="issue_id" value="">
				<h4>Способ доставки</h4>
				<div id="main_deliveries" class="select-wrap">
					<?$deliveries = $db->select('deliveries', '*', "`parent_id`=0", '', '', '', true);?>
					<select name="delivery_way">
						<option value="" selected=""></option>
						<?foreach ($deliveries as $key => $value){?>
							<option value="<?=$key?>"><?=$value['title']?></option>
						<?}?>
					</select>
				</div><!-- delivery-way -->
				<div id="sub_delivery" class="select-wrap hidden"></div><!-- sub_delivery -->
				<div class="input-wrap">
					<label for="surname">Организация: </label>
					<input type="text" id="surname" name="entity">
				</div><!-- Организация -->
				<div class="input-wrap">
					<label for="surname">Фамилия: </label>
					<input type="text" id="surname" name="name_1">
				</div><!-- Фамилия -->
				<div class="input-wrap">
					<label for="name">Имя: </label>
					<input type="text" id="name" name="name_2">
				</div><!-- Имя -->
				<div class="input-wrap">
					<label for="patrinymic">Отчество: </label>
					<input type="text" id="patrinymic" name="name_3" >
				</div><!-- Отчество -->
                <?$addresses = $db->select('user_addresses', '*', "`user_id` = {$_SESSION['user']}");
                if (!empty($addresses)){
                    $disabled = $user['delivery_type'] == 'Самовывоз' ? 'disabled' : ''; ?>
                    <div class="input-wrap">
                        <label for="patrinymic">Адрес: </label>
                        <select name="address_id" <?=$disabled?>>
                            <?$counter = 0;
                            foreach($addresses as $row){
                                $counter++;
                                $selected = $counter == 1 && $user['delivery_type'] == 'Доставка' ? 'checked' : ''?>
                                <option value="<?=$row['id']?>" <?=$row['is_default'] == 1 ? 'selected' : ''?>>
                                    <?=\core\UserAddress::getString($row['id'], json_decode($row['json'], true))?>
                                </option>
                            <?}?>
                        </select>
                    </div>
                <?}?>
				<div class="input-wrap">
					<label for="phone">Телефон: </label>
					<input type="tel" id="phone" name="telefon" placeholder="+7 (___) ___-__-__">
				</div><!-- Телефон -->
				<div class="input-wrap for-tk">
					<label for="pasport">Серия и номер паспорта: </label>
					<input name="pasport" type="text" id="pasport" placeholder="____ №______">
				</div>
				<div class="checkbx-wrap">
					<label for="insure">Страховать груз</label>
					<input type="checkbox" id="insure" value="1" name="insure">
				</div><!-- Страховать груз -->
				<div class="checkbx-wrap">
					<label for="save-template">Сохранить шаблон доставки</label>
					<input type="checkbox" id="save-template" name="save_template">
				</div><!-- Сохранить шаблон доставки -->
				<button>Сформировать отправку</button>
			</form>
			<?$templatesResult = $db->query("
                SELECT
                    t.*,
                    ua.json
                FROM
                    #templates t
                LEFT JOIN
                    #user_addresses ua ON ua.id = t.address_id
                WHERE
                    t.user_id = $user_id
            ", '');
			$deliveries = $db->select('deliveries', '*', '', '', '', '', true);
			if ($templatesResult->num_rows){?>
				<div class="templates-block">
					<input type="hidden" id="js_deliveries" value="<?=str_replace('"', '#', json_encode($deliveries))?>">
					<h4>Сохраненные шаблоны</h4>
					<ol>
						<?foreach($templatesResult as $template){?>
							<li>
								<?=getStrTemplate($template)?>
								<i class="fa fa-times delete_template" aria-hidden="true" data-id="<?=$template['id']?>"></i> 
							</li>
						<?}?>
					</ol>
				</div>
			<?}
		}?>	
	</div>
<?}
else{
	$title = "Отправка №{$_GET['id']}";
	require_once "admin/functions/sendings.function.php";
	$sendings = new Sendings($_SESSION['user'], $db);
	$sending = $sendings->getSendings();
	$sending = $sending[0];
	$sending_values = $sendings->getSendingValues($sending['issue_id']);
	?>
	<div class="sending-page">
		<h1>Отправка №<?=$_GET['id']?></h1>
		<h3>Общие данные</h3>
		<?if ($device == 'desktop' || $device == 'tablet'){?>
			<table>
				<tr>
					<th>Номер</th>
					<th>Дата</th>
					<th>Статус</th>
					<th>Сумма</th>
				</tr>
				<tr>
					<td><?=$sending['id']?></td>
					<td><?=$sending['date']?></td>
					<td><?=$sending['status']?></td>
					<td>
						<?=$sending['sum']?>
							<i class="fa fa-rub" aria-hidden="true"></i>
					</td>
				</tr>
			</table>
		<?}
		else{?>
			<div>
				<p><strong>Номер:</strong> <span><?=$sending['id']?></span></p>
				<p><strong>Дата:</strong> <span><?=$sending['date']?></span></p>
				<p><strong>Статус:</strong> <span><?=$sending['status']?></span></p>
				<p><strong>Сумма:</strong> <span><?=$sending['sum']?><i class="fa fa-rub" aria-hidden="true"></i></span></p>
			</div>
		<?}?>
		<h3>Данные о доставке</h3>
		<?if ($device == 'desktop' || $device == 'tablet'){?>
			<table>
				<tr>
					<th>Получатель</th>
					<th>Способ доставки</th>
					<th>Адрес</th>
					<th>Телефон</th>
					<th>Паспорт</th>
					<th>Страхование</th>
				</tr>
				<tr>
				<td><?=$sending['receiver'];?></td>
				<td><?=$sending['sub_delivery']?></td>
				<td>
                    <?=\core\UserAddress::getString(
                        $sending['address_id'],
                        json_decode($sending['json'], true)
                    )?>
                </td>
				<td><?=$sending['telefon']?></td>
				<td><?=$sending['pasport']?></td>
				<td><?=$sending['insure'] ? 'Да' : 'Нет'?></td>
			</tr>
			</table>
		<?}
		else{?>
			<div>
					<p><strong>Получатель:</strong> <span><?=$sending['receiver'];?></span></p>
					<p><strong>Способ доставки:</strong> <span><?=$sending['sub_delivery']?></span></p>
					<p><strong>Индекс:</strong> <span><?=$sending['index']?></span></p>
					<p><strong>Город:</strong> <span><?=$sending['city']?></span></p>
					<p><strong>Улица:</strong> <span><?=$sending['street']?></span></p>
					<p><strong>Дом:</strong> <span><?=$sending['house']?></span></p>
					<p><strong>Квартира:</strong> <span><?=$sending['flat']?></span></p>
					<p><strong>Телефон:</strong> <span><?=$sending['telefon']?></span></p>
					<p><strong>Паспорт:</strong> <span><?=$sending['pasport']?></span></p>
					<p><strong>Страхование:</strong> <span><?=$sending['insure'] ? 'Да' : 'Нет'?></span></p>
			</div>
		<?}?>
		<h3>Товары в доставке</h3>
		<?if ($device == 'desktop' || $device == 'tablet'){?>
			<table>
				<tr>
					<th>Поставщик</th>
					<th>Бренд</th>
					<th>Артикул</th>
					<th>Наименование</th>
					<th>Цена</th>
					<th>Кол-во</th>
					<th>Сумма</th>
					<th>Комментарий</th>
				</tr>
				<?foreach($sending_values as $sv){?>
					<tr>
						<td><?=$sv['store']?></td>
						<td><?=$sv['brend']?></td>
						<td><?=$sv['article']?></td>
						<td><?=$sv['title_full']?></td>
						<td><?=$sv['price']?></td>
						<td><?=$sv['issued']?></td>
						<td>
								<?=$sv['sum']?>
								<i class="fa fa-rub" aria-hidden="true"></i>
						</td>
						<td><?=$sv['comment']?></td>
					</tr>
				<?}?>
			</table>
		<?}
		else{
			foreach($sending_values as $sv){?>	
				<div style="border-bottom: 1px dashed">
					<p><strong>Поставщик</strong> <span><?=$sv['store']?></span></p>
					<p><strong>Бренд</strong> <span><?=$sv['brend']?></span></p>
					<p><strong>Артикул</strong> <span><?=$sv['article']?></span></p>
					<p><strong>Наименование</strong> <span><?=$sv['title_full']?></span></p>
					<p><strong>Цена</strong> <span><?=$sv['price']?></span></p>
					<p><strong>Кол-во</strong> <span><?=$sv['issued']?></span></p>
					<p><strong>Сумма</strong> <span><?=$sv['sum']?><i class="fa fa-rub" aria-hidden="true"></i></span></p>
					<p><strong>Комментарий</strong> <span><?=$sv['comment']?></span></p>
				</div>
			<?}
		}
}?>