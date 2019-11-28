<?
$title = 'Помощь';
// debug($_GET); 
$res_rubrics = $db->query("
	SELECT
		hr.id AS rubric_id,
		hr.href AS href,
		hr.title AS rubric_title,
		ht.id AS text_id,
		ht.title AS text_title
	FROM
		#texts_to_rubrics ttr
	LEFT JOIN
		#help_rubrics hr ON hr.id=ttr.rubric_id
	LEFT JOIN
		#help_texts ht ON ht.id=ttr.text_id
	ORDER BY
		hr.title
", '');
if ($res_rubrics->num_rows){
	while ($row = $res_rubrics->fetch_assoc()){
		$r = & $rubrics[$row['rubric_id']];
		$r['title'] = $row['rubric_title'];
		$r['href'] = $row['href'];
		$r['text'][$row['text_id']] = $row['text_title'];
	}
}
// debug($rubrics);
?>
<div class="help-page">
	<h1>Помощь</h1>
	<div class="questions-block">
		<?if (!empty($rubrics)){
			foreach($rubrics as $key => $value){
				if (empty($value['text'])) continue;?>
				<button class="accordion <?=$value['href'] == $_GET['rubric_href'] ? 'active' : ''?>"><?=$value['title']?></button>
				<div class="panel <?=$value['href'] == $_GET['rubric_href'] ? 'show' : ''?>">
					<ul>
						<?foreach($value['text'] as $k => $v){?>
							<li><a text_id="<?=$k?>" href="#"><?=$v?></a></li>
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
		<?=file_get_contents('admin/vendor/help_main.txt')?>
	</div>
	<div class="clearfix"></div>
</div>