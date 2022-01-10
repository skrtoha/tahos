<?
$title = 'Помощь';
$rubrics = [];
$res_rubrics = $db->query("
    SELECT
        atr.rubric_id,
        tr.title AS rubric_title,
        atr.article_id,
        ta.title AS article_title,
        ta.href AS article_href
    FROM
		#text_article_to_rubric atr
    LEFT JOIN
        #text_articles ta ON ta.id = atr.article_id
    left JOIN
        #text_rubrics tr ON tr.id = atr.rubric_id
	ORDER BY
		tr.title
", '');
if ($res_rubrics->num_rows){
	while ($row = $res_rubrics->fetch_assoc()){
		$r = & $rubrics[$row['rubric_id']];
		$r['title'] = $row['rubric_title'];
		$r['articles'][$row['article_id']] = $row['article_title'];
	}
}
?>
<div class="help-page">
	<h1>Помощь</h1>
	<div class="questions-block">
		<?if (!empty($rubrics)){
			foreach($rubrics as $rubric_id => $value){
				if (empty($value['articles'])) continue;
                $active = $rubric_id == $_GET['rubric_href'] ? 'active' : ''; ?>
				<button class="accordion <?=$active?>"><?=$value['title']?></button>
				<div class="panel <?=$active ? 'show' : ''?>">
					<ul>
						<?foreach($value['articles'] as $article_id => $title){?>
							<li>
                                <a data-article-id="<?=$article_id?>" href="#"><?=$title?></a>
                            </li>
						<?}?>
					</ul>
				</div>
			<?}
		}
		else{?>
			<p>Рубрик не найдено</p>
		<?}?>
	</div>
	<div class="answer-block">
		<?=file_get_contents('vendor/help_main.txt')?>
	</div>
	<div class="clearfix"></div>
</div>