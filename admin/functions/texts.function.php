<?class Texts{
	private $error;
	public function __construct($db){
		$this->db = $db;
	}
	function theme(){
		if (!empty($_POST)) $this->change_theme();
		if ($_GET['id']){
			$res_help_texts = $this->db->query("
				SELECT
					ht.*,
					GROUP_CONCAT(ttr.rubric_id) AS rubrics
				FROM
					#help_texts ht
				LEFT JOIN
					#texts_to_rubrics ttr ON ttr.text_id=ht.id
				WHERE
					ht.id={$_GET['id']}
				GROUP BY
					ht.id
			", '');
			$array = $res_help_texts->fetch_assoc();
			if ($array['rubrics']) $rubrics = explode(',', $array['rubrics']);
			else $rubrics = array();
		}
		else{
			$array = $_POST;
			$rubrics = array();
		}
		$res_help_rubrics = $this->db->query("
			SELECT
				hr.id,
				hr.title
			FROM
				#help_rubrics hr
			ORDER BY
				title
		", '');
		if ($_GET['id']){?>
			<div class="actions">
				<a class="item_remove" href="?view=help&act=theme_delete&id=<?=$_GET['id']?>">Удалить</a>
			</div>
		<?}?>
		<div class="t_form">
			<div class="bg">
				<form action="" method="post" enctype="multipart/form-data">
					<div class="field">
						<div class="title">Название</div>
						<div class="value"><input type=text name="title" value="<?=$array['title']?>"></div>
					</div>
					<div class="field">
						<div class="title">Текст</div>
						<div class="value">
							<textarea name="text" class="need"><?=$array['text']?></textarea>
						</div>
					</div>
					<?if ($res_help_rubrics->num_rows){?>
						<div class="field">
							<div class="title">Отображать в рубриках</div>
							<div class="value">
								<?while($row = $res_help_rubrics->fetch_assoc()){?>
									<label>
										<input <?=in_array($row['id'], $rubrics) ? 'checked' : ''?> type="checkbox" name="rubric_id[]" value="<?=$row['id']?>">
										<?=$row['title']?>
									</label><br>
								<?}
							}
							else{?>
								<p>Рубрик не задано</p>
							<?}?>
							</div>
						</div>
					<div class="field">
						<div class="title"></div>
						<div class="value"><input type="submit" class="button" value="Сохранить"></div>
					</div>
				</form>
			</div>
		</div>
	<?}
	private function change_theme(){
		// debug($_POST); exit();
		if (!$this->theme_validation()){
			message($this->error, false);
			return false;
		}
		$this->db->delete("texts_to_rubrics", "`text_id`={$_GET['id']}");
		$array = [
			'title' => $_POST['title'],
			'text' => $_POST['text'],
			'href' => translite($_POST['title'])
		];
		if (!$_GET['id']){
			$res = $this->db->insert('help_texts', $array);
			$text_id = $this->db->last_id();
		}
		else{
			$res = $this->db->update('help_texts', $array, "`id`={$_GET['id']}");
			$text_id = $_GET['id'];
		}
		if (!empty($_POST['rubric_id'])){
			foreach ($_POST['rubric_id'] as $value){
				$this->db->insert('texts_to_rubrics', ['text_id' => $text_id, 'rubric_id' => $value]);
			}
		}
		if ($res === true){
			message('Успешно сохранено!');
			header("Location: ?view=texts&tab=help#tabs|texts:help");
		} 
		else message($res, false);
	}
	private function change_rubric(){
		// debug($_POST);
		if (!$this->rubric_validation()){
			message($this->error, false);
			return false;
		}
		$array = [
			'title' => $_POST['title'],
			'href' => translite($_POST['title'])
		];
		if (!$_GET['id']){
			$res = $this->db->insert('help_rubrics', $array);
		}
		else{
			$res = $this->db->update('help_rubrics', $array, "`id`={$_GET['id']}");
		}
		if ($res === true){
			message('Успешно сохранено!');
			header("Location: ?view=texts&tab=help&act=rubrics#tabs|texts:help");
		} 
		else message($res, false);
	}
	private function theme_validation(){
		if(!$_POST['title']){
			$this->error = 'Название не должно быть пустым!';
			return false;
		}
		if (!$_POST['text']){
			$this->error = 'Текст не должен быть пустым!';
			return false;
		}
		return true;
	}
	private function rubric_validation(){
		if(!$_POST['title']){
			$this->error = 'Название не должно быть пустым!';
			return false;
		}
		return true;
	}
	function themes(){
		$res_help_texts = $this->db->query("
			SELECT
				ht.id,
				ht.title,
				GROUP_CONCAT(hr.title SEPARATOR ', ') AS rubric_title
			FROM
				#help_texts ht
			LEFT JOIN
				#texts_to_rubrics ttr ON ttr.text_id=ht.id
			LEFT JOIN
				#help_rubrics hr ON hr.id=ttr.rubric_id
			GROUP BY ttr.text_id
			ORDER BY ht.title
		", '');?>
		<div id="total" style="margin-top: 10px;">Всего: <?=$res_help_texts->num_rows?></div>
		<div class="actions">
			<a href="?view=texts&tab=help&act=theme_add#tabs|texts:help">Добавить</a>
			<a href="?view=texts&tab=help&act=rubrics#tabs|texts:help">Список рубрик</a>
			<a href="?view=texts&tab=help&act=help_main#tabs|texts:help">Текст по умолчанию</a>
		</div>
		<table class="t_table" cellspacing="1">
			<tr class="head">
				<td>Название</td>
				<td>Рубрики</td>
			</tr>
			<?if ($res_help_texts->num_rows){
				while($row = $res_help_texts->fetch_assoc()){?>
					<tr text_id="<?=$row['id']?>">
						<td><?=$row['title']?></td>
						<td><?=$row['rubric_title']?></td>
					</tr>
				<?}
			}
			else{?>
				<tr class="removable">
					<td colspan="3">Тем не найдено</td>
				</tr>
			<?}?>
		</table>
	<?}
	function rubrics(){
		$res_rubrics = $this->db->query("
			SELECT	
				hr.*
			FROM
				#help_rubrics hr
			ORDER BY
				hr.title
		", '');?>
		<div id="total" style="margin-top: 10px;">Всего: <?=$res_rubrics->num_rows?></div>
		<div class="actions">
			<a href="?view=texts&tab=help&act=rubric_add#tabs|texts:help">Добавить</a>
			<a href="?view=texts&tab=help#tabs|texts:help">Список тем</a>
		</div>
		<table class="t_table" cellspacing="1">
			<tr class="head">
				<td>Название</td>
			</tr>
			<?if ($res_rubrics->num_rows){
				while($row = $res_rubrics->fetch_assoc()){?>
					<tr rubric_id="<?=$row['id']?>">
						<td><?=$row['title']?></td>
					</tr>
				<?}
			}
			else{?>
				<tr class="removable">
					<td colspan="3">Рубрик не найдено</td>
				</tr>
			<?}?>
		</table>
	<?}
	function rubric(){
		if (!empty($_POST)) $this->change_rubric();
		$this->status = '<a href="/admin">Главная</a> > <a href="?view=help">Помощь</a> > ';
		if ($_GET['id']){
			$res_help_rubrics = $this->db->query("
				SELECT
					hr.id,
					hr.title
				FROM
					#help_rubrics hr
				WHERE
					hr.id={$_GET['id']}
				ORDER BY
					title
			", '');
			$array = $res_help_rubrics->fetch_assoc();
		}
		else{
			$array = $_POST;
		}
		if ($_GET['id']){?>
			<div class="actions">
				<a class="item_remove" href="?view=help&act=rubric_delete&id=<?=$_GET['id']?>">Удалить</a>
			</div>
		<?}?>
		<div class="t_form">
			<div class="bg">
				<form action="" method="post" enctype="multipart/form-data">
					<div class="field">
						<div class="title">Название</div>
						<div class="value"><input type="text" name="title" value="<?=$array['title']?>"></div>
					</div>
					<div class="field">
						<div class="title"></div>
						<div class="value"><input type="submit" class="button" value="Сохранить"></div>
					</div>
				</form>
			</div>
		</div>
	<?}
	function rubric_delete(){
		$res = $this->db->delete('help_rubrics', "`id`={$_GET['id']}");
		if ($res === true){
			message('Успешно удалено!');
			header("Location: ?view=help&act=rubrics");
		}
		else message($res, false);
	}
	function theme_delete(){
		$res = $this->db->delete('help_texts', "`id`={$_GET['id']}");
		if ($res === true){
			message('Успешно удалено!');
			header("Location: ?view=help");
		}
		else message($res, false);
	}
	function settings($field, $title = ''){
		if (!empty($_POST) && $_GET['tab'] == $field){
			$this->db->update('settings', [$field => $_POST['text']], "`id`=1");
			message('Успешно сохранено');
		}?>
		<?if ($title){?>
			<h2><?=$title?></h2>
		<?}?>
		<div class="t_form">
			<div class="bg">
				<form action="" method="post" enctype="multipart/form-data">
					<div class="field">
						<div class="value">
							<textarea name="text" class="need"><?=$this->db->getField('settings', $field, 'id', 1)?></textarea>
						</div>
					</div>
					<div class="field">
						<div class="value"><input type="submit" class="button" value="Сохранить"></div>
					</div>
				</form>
			</div>
		</div>
	<?}
}?>