<?class Texts{
	private $error;
	public function __construct(\core\Database $db){
		$this->db = $db;
	}
    public function getTextRubricList($id){
        $output = [];
        $result = $this->db->query("
            SELECT
                t.id,
                ttr.rubric_id,
                t.title,
                t.text
            FROM #texts_to_rubrics ttr
            LEFT JOIN #texts t ON t.id = ttr.text_id
            WHERE ttr.rubric_id = $id
        ");
        while($row = $result->fetch_assoc()) $output[] = $row;
        return $output;
    }
    public function getTexts($column){
        $rubrics = $this->db->select('texts', '*', "`column` = $column");
        return $rubrics;
    }
    public function showHtmlTextList($array, $column, $act, $title = '', $backUrl = ''){?>
        <div id="total" style="margin-top: 10px;">Всего: <?=count($array)?></div>
        <?if ($title){?>
            <p class="title"><?=$title?></p>
        <?}?>
        <div class="actions">
            <a href="?view=texts&tab=<?=$column?>&act=<?=$act?>_add#tabs|texts:<?=$column?>">Добавить</a>
        </div>
        <table class="t_table" cellspacing="1">
            <thead>
                <tr class="head">
                    <td>Название</td>
                </tr>
            </thead>
            <tbody>
                <?foreach($array as $value){?>
                    <tr data-id="<?=$value['id']?>" data-href="/admin/?view=texts&tab=<?=$column?>&act=<?=$act?>&id=<?=$value['id']?>#tabs|texts:<?=$column?>">
                        <td><?=$value['title']?></td>
                    </tr>
                <?}?>
            </tbody>
        </table>
        <?if ($backUrl){?>
            <a href="<?=$backUrl?>">Назад</a>
        <?}?>
    <?}
    public function getRubrics(){
        return $this->db->select('text_rubrics', '*');
    }
	public function showHtmlText($text_id, $tab, $title = ''){
		if (!empty($_POST)){
            $post = $_POST;
            if (!$post['href']) $post['href'] = translite($post['title']);
            if ($text_id){
                $this->db->update('texts', $post, "`id` = {$text_id}");
            }
            else{
                $this->db->insert('texts', $post);
                header("Location: /admin/?view=texts&tab=$tab&act=rubric&id=".$this->db->last_id());
                die();
            }
            $textInfo = $post;
		}
        else $textInfo = $this->db->select_one('texts', '*', "`id` = $text_id");
        ?>
		<?if ($title){?>
			<h2><?=$title?></h2>
		<?}?>
		<div class="t_form">
			<div class="bg">
				<form action="" method="post" enctype="multipart/form-data">
                    <div class="field">
                        <div class="value">
                            <input required type="text" name="title" value="<?=$textInfo['title']?>">
                        </div>
                    </div>
					<div class="field">
						<div class="value">
							<textarea name="text" class="need"><?=$textInfo['text']?></textarea>
						</div>
					</div>
                    <div class="field">
                        <div class="value">
                            <input type="text" name="href" value="<?=$textInfo['href']?>">
                        </div>
                    </div>
					<div class="field">
						<div class="value"><input type="submit" class="button" value="Сохранить"></div>
					</div>
				</form>
			</div>
		</div>
        <a class="bottom" href="/admin/?view=texts&tab=<?=$tab?>">Назад</a>
        <? if ($text_id){?>
            <a class="bottom delete" href="/admin/?view=texts&tab=<?=$tab?>&act=rubric_delete&id=<?=$text_id?>">Удалить</a>
        <?}?>
	<?}
    public function showHtmlTextRubricForm($rubric_id, $tab){
        if ($rubric_id){
            $rubricInfo = $this->db->select_one('text_rubrics', '*', "`id` = $rubric_id");
        }
        if (!empty($_POST)){
            $post = $_POST;
            if ($rubric_id){
                $this->db->update('text_rubrics', $post, "`id` = {$rubric_id}");
                $rubricInfo['title'] = $post['title'];
            }
            else{
                $this->db->insert('text_rubrics', $post);
                header("Location: /admin/?view=texts&tab=$tab&act=rubric&id=".$this->db->last_id());
                die();
            }
        }?>
        <h3>Редактирование рубрики</h3>
        <div class="t_form">
            <div class="bg">
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="field">
                        <div class="value">
                            <input required type="text" name="title" value="<?=$rubricInfo['title']?>">
                        </div>
                    </div>
                    <div class="field">
                        <div class="value"><input type="submit" class="button" value="Сохранить"></div>
                    </div>
                </form>
            </div>
        </div>
        <a class="bottom" href="/admin/?view=texts&tab=<?=$tab?>#tabs|texts:<?=$tab?>">Назад</a>
        <? if ($rubric_id){?>
            <a class="bottom delete" href="/admin/?view=texts&tab=<?=$tab?>&act=rubric_delete&id=<?=$rubric_id?>">Удалить</a>
        <?}?>
    <?}
}?>