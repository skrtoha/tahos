<?
require_once ($_SERVER['DOCUMENT_ROOT'].'/core/Database.php');
http_response_code(404);
use core\Database;

$title = '404';
$view = '404';
$device = '';
$db = new Database();
ob_start();
?>
<?if (isset($message) && strlen($message) > 0){?>
    <h2><?=$message?></h2>
<?}?>
<div id="not_found">
    <div class="left">
        <h2>404</h2>
        <p>Такой страницы у нас нет, зато есть<br> большой выбор запчастей. Найти их<br>можно на <a href="/">главной странице</a></p>
    </div>
    <div class="right">
        <img src="/img/404.png" alt="">
    </div>
</div>
<?
$content = ob_get_clean();
require_once ($_SERVER['DOCUMENT_ROOT'].'/templates/main.php');