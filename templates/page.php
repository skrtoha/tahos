<?
$articleInfo = $db->select_one('text_articles', '*', "`href` = '{$_GET['type']}'");
$title = $articleInfo['title'];
?>
<h2><?=$articleInfo['title']?></h2>
<?=$articleInfo['text']?>