<?class Texts{
	private $error;
	public function __construct(\core\Database $db){
		$this->db = $db;
	}
    
    public function getArticleRubricList($id){
        $output = [];
        $result = $this->db->query("
            SELECT
                a.id,
                atr.rubric_id,
                a.title,
                a.text
            FROM #text_article_to_rubric atr
            LEFT JOIN #text_articles a ON a.id = atr.article_id
            WHERE atr.rubric_id = $id
        ");
        while($row = $result->fetch_assoc()) $output[] = $row;
        return $output;
    }
    public function getArticles($column){
        return $this->db->select('text_articles', '*', "`column` = $column");
    }
    public function showHtmlArticleList($array, $column, $act, $title = '', $backUrl = '', $addUrl = '', $deleteUrl = ''){?>
        <div id="total" style="margin-top: 10px;">Всего: <?=count($array)?></div>
        <?if ($title){?>
            <p class="title"><?=$title?></p>
        <?}?>
        <?if ($addUrl || $addUrl){?>
            <div class="actions">
                <?if ($addUrl){?>
                    <a href="<?=$addUrl?>">Добавить</a>
                <?}?>
                <?if ($deleteUrl){?>
                    <a class="delete" href="<?=$deleteUrl?>">Удалить</a>
                <?}?>
            </div>
        <?}?>
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
    public function getRubrics($params = []){
        $where = '';
        if (!empty($params)){
            foreach($params as $key => $value){
                switch ($key){
                    default:
                        $where .= "`$key` = $value AND ";
                }
            }
        }
        if (strlen($where)) $where = substr($where, 0, -5);
        return $this->db->select('text_rubrics', '*', $where);
    }
	public function showHtmlArticle($id, $tab, $title = ''){
		if (!empty($_POST)){
            $post = $_POST;
            $post['column'] = $_GET['tab'];
            if (!$post['href']) $post['href'] = translite($post['title']);
            if ($id){
                $this->db->update('text_articles', $post, "`id` = {$id}");
            }
            else{
                if (isset($post['parent_id'])){
                    $parent_id = $post['parent_id'];
                    unset($post['parent_id']);
                }
                $this->db->insert('text_articles', $post);
                $article_id = $this->db->last_id();
                if(isset($parent_id)){
                    $this->db->insert('text_article_to_rubric', [
                        'article_id' => $article_id,
                        'rubric_id' => $parent_id
                    ]);
                    $url = "/admin/?view=texts&tab=$tab&act=rubric&id=$parent_id#tabs|texts:$tab";
                }
                else $url = "/admin/?view=texts&tab=$tab&act=article&id=".$article_id;
                header("Location: $url");
                die();
            }
            $textInfo = $post;
		}
        else $textInfo = $this->db->select_one('text_articles', '*', "`id` = $id");
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
                    <?if(isset($_GET['parent_id']) && $_GET['parent_id']){?>
                        <input type="hidden" name="parent_id" value="<?=$_GET['parent_id']?>">
                    <?}?>
					<div class="field">
						<div class="value"><input type="submit" class="button" value="Сохранить"></div>
					</div>
				</form>
			</div>
		</div>
        <a class="bottom" href="/admin/?view=texts&tab=<?=$tab?>#tabs|texts:<?=$tab?>">Назад</a>
        <? if ($id){?>
            <a class="bottom delete" href="/admin/?view=texts&tab=<?=$tab?>&act=article_delete&id=<?=$id?>#tabs|texts:<?=$tab?>">Удалить</a>
        <?}?>
	<?}
    public function showHtmlTextRubricForm($rubric_id, $tab, array $params = []){
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
        <? if ($params['delete_url']){?>
            <a class="bottom delete" href="/admin/?view=texts&tab=<?=$tab?>&act=rubric_delete&id=<?=$rubric_id?>">Удалить</a>
        <?}?>
    <?}
}?>