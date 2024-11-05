<?php
/* @var $db Database */

use core\Database;
use core\Seo;

$act = $_GET['act'];
switch ($act) {
    case 'edit':
        if (!empty($_POST)) {
            if (!isset($_POST['active'])) {
                $_POST['active'] = 0;
            }
            $_POST['content'] = trim($_POST['content']);

            if (isset($_GET['id'])) {
                $res = Database::getInstance()->update('seo', $_POST, "`id` = {$_GET['id']}");
            }
            else {
                $res = Database::getInstance()->insert('seo', $_POST);
                $last_id = Database::getInstance()->last_id();
                header("Location: /admin/?view=seo&act=edit&id={$last_id}");
                die();
            }
            if ($res !== true) {
                message($res);
            }

        }
        if (isset($_GET['id'])) {
            $seo = Database::getInstance()->select_one('seo', '*', "`id` = {$_GET['id']}");
        }
        else {
            $seo = [];
            $seo['active'] = 1;
        }
        if (!empty($_GET['type_content'])) {
            $seo['type_content'] = $_GET['type_content'];
        }
        show_form($seo);
        break;
	default:
		view();
}
function view(){
	global $status, $page_title;
	require_once('templates/pagination.php');

    if (!empty($_GET['search'])) {
		$where = "`url` LIKE '%{$_GET['search']}%' or `type_tag` LIKE '%{$_GET['search']}%'";
		$page_title = "Поиск настроек SEO";
	}
	else{
		$where = "";
		$page_title = "Настройки SEO";
	}
    $status = "<a href='/admin'>Главная</a> > $page_title";

	$all = Database::getInstance()->getCount('seo', $where);
	if ($where) {
        $where = "WHERE $where";
    }

	$perPage = 30;
	$linkLimit = 10;
	$page = $_GET['page'] ?: 1;
	$chunk = getChank($all, $perPage, $linkLimit, $page);
	$start = $chunk[$page] ?: 0;

	$res = Database::getInstance()->query("
		SELECT
			*
		FROM
			#seo
		$where
		ORDER BY
			created DESC
		LIMIT
			$start,$perPage
	", '');
	?>
	<div id="total">Всего: <?=$all?></div>
	<div class="actions" style="">
        <form>
            <input type="hidden" name="view" value="seo">
            <input type="text" name="search" value="<?=$_GET['search']?>" placeholder="Поиск">
            <input type="submit" value="Искать">
        </form>
        <a href="/admin/?view=seo&act=edit">Добавить</a>
	</div>
	<table class="t_table">
        <thead>
            <tr class="head">
                <td>URL</td>
                <td>Тип</td>
            </tr>
        </thead>

		<?if ($res->num_rows){?>
            <tbody>
                <?while($row = $res->fetch_assoc()){?>
                    <tr data-id="<?=$row['id']?>">
                        <td>
                            <?=str_replace(
                                $_GET['search'],
                                "<b>{$_GET['search']}</b>",
                                $row['url']
                            )?>
                        </td>
                        <td><?=$row['type_tag']?></td>
                    </tr>
                <?}?>
            </tbody>
        <?}?>
	</table>
	<?pagination($chunk, $page, ceil($all / $perPage), "?view=seo&search={$_GET['search']}&page=");?>
<?}

function show_form($seo){
    global $status, $page_title;
    $page_title = 'Редактирование';
    $status = "<a href='/admin'>Главная</a> > <a href='?view=seo'>Настройки SEO</a> > $page_title";
    ?>
    <div class="action">
        <a href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a>
    </div>
    <div class="t_form">
        <div class="bg">
            <form method="post" enctype="multipart/form-data">
                <div class="field">
                    <div class="title">URL</div>
                    <div class="value">
                        <input type=text name="url" value="<?=$seo['url']?>">
                    </div>
                </div>
                <div class="field">
                    <div class="title">Тип</div>
                    <div class="value">
                        <select name="type_tag">
                            <?foreach(Seo::$type_tag as $type){?>
                                <option
                                    <?=$seo['type_tag'] == $type ? 'selected' : ''?>
                                    value="<?=$type?>"
                                >
                                    <?=$type?>
                                </option>
                            <?}?>
                        </select>
                    </div>
                </div>
                <div class="field">
                    <div class="title">Активен</div>
                    <div class="value">
                        <input type="checkbox" name="active" value="1" <?=$seo['active'] ? 'checked' : ''?>>
                    </div>
                </div>
                <div class="field">
                    <div class="title">Контент</div>
                    <div class="value">
                        <select name="type_content">
                            <?foreach(Seo::$type_content as $type){?>
                                <option
                                    <?=$seo['type_content'] == $type ? 'selected' : ''?>
                                    value="<?=$type?>"
                                >
                                    <?=$type?>
                                </option>
                            <?}?>
                        </select>
                        <textarea
                            class="<?=$seo['type_content'] == Seo::TYPE_CONTENT_HTML ? 'need' : ''?>"
                            type="text"
                            name="content"
                        ><?=$seo['content']?></textarea>
                    </div>
                </div>
                <div class="field">
                    <div class="title"></div>
                    <div class="value">
                        <input type="submit" value="Сохранить">
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="action">
        <a href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a>
    </div>
<?}