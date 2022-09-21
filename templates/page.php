<?
use core\Exceptions\NotFoundException;

$articleInfo = $db->select_one('text_articles', '*', "`href` = '{$_GET['type']}'");

if (!$articleInfo) throw new NotFoundException('Страница не найдена');

$title = $articleInfo['title'];
?>
<h2><?=$articleInfo['title']?></h2>
<?=$articleInfo['text']?>