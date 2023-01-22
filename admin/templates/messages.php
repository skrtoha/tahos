<?
/** @global \core\Database $db */

switch ($_GET['act']){
    case 'spare_parts_request':
        if (isset($_GET['id'])) spare_parts_request();
        else spare_parts_request_list();
        break;
    default:
        messages();
}
function messages(){
    global $db;
    if (!$_GET['archive']) $where = "c.is_archive IS NULL OR c.is_archive=0";
    else $where = "c.is_archive>0";
    if (!$_GET['archive']){
        $page_title = "Сообщения";
        $status = "<a href='/admin'>Главная</a> > $page_title";
    }
    else{
        $page_title = 'Архив сообщений';
        $status = "
            <a href='/admin'>Главная</a> > 
            <a href='/admin/?view=messages'>Сообщения</a> > 
            $page_title
        ";
    }
    $res_corresponds = $db->query(" 
            SELECT
                c.id,
                c.user_id,
                c.theme_id,
                IF (mth.title IS NOT NULL, mth.title, 'Переписка в товаре заказа') AS theme,
                c.order_id,
                c.store_id,
                c.item_id,
                CONCAT_WS(' ', u.name_1, u.name_2, u.name_3) AS fio,
                IF (m.is_read=0 AND m.sender=1, 0, 1) AS is_read,
                DATE_FORMAT(m.created, '%d.%m.%Y %H:%i') as created,
                m.sender,
                (
                    SELECT
                        COUNT(*) 
                    FROM #messages
                    WHERE correspond_id=c.id
                ) as count
            FROM
                #corresponds c
            LEFT JOIN #messages m ON m.id=c.last_id
            LEFT JOIN #messages_themes mth ON mth.id=c.theme_id
            LEFT JOIN #users u ON u.id=c.user_id
            WHERE
                $where
            ORDER BY is_read, m.created DESC
        ", '');
    if (!$_GET['archive']){?>
        <a href="?view=messages&archive=1">Архив сообщений</a>
    <?}
    else{?>
        <a href="?view=messages">Сообщения</a>
    <?}?>
    <a href="?view=news">Новости</a>
    <?$count = $db->getCount('spare_parts_request', "`is_new` = 1");?>
    <a href="?view=messages&act=spare_parts_request">Запросы на поиск запчастей</a>
    <?if ($count){?>
        <span class="count_spare_parts_request">(<?=$count?>)</span>
    <?}?>
    <div id="total">Всего: <?=$res_corresponds->num_rows?></div>
    <table class="t_table" cellspacing="1">
        <tr class="head">
            <td>Тема</td>
            <td>Тип</td>
            <td>ФИО</td>
            <td>Всего сообщений</td>
            <td>Последнее</td>
        </tr>
        <?if (!$res_corresponds->num_rows){?>
            <td colspan="6">Сообщений не найдено</td>
        <?}
        else{
            while($value = $res_corresponds->fetch_assoc()){?>
                <tr class="messages_box" correspond_id="<?=$value['id']?>" style="<?=!$value['is_read'] ? 'font-weight: 700; color: #a24646' : ''?>"">
                    <td><?=$value['theme']?></td>
                    <?$type = $value['order_id'] ? "Заказ" : "Личная переписка"?>
                    <td><?=$type?></td>
                    <td><?=$value['fio']?></td>
                    <td><?=$value['count']?></td>
                    <td><?=$value['created']?></td>
                </tr>
            <?}
        }?>
    </table>
<?}
function spare_parts_request_list(){
    global $status, $db, $page_title;
    require_once('templates/pagination.php');
    $page_title = 'Запросы на подбор запчастей';
    $all = $db->getCount('spare_parts_request');
    $perPage = 30;
    $linkLimit = 10;
    $page = $_GET['page'] ?: 1;
    $chank = getChank($all, $perPage, $linkLimit, $page);
    $start = $chank[$page] ?: 0;
    $requestList = $db->query("
        SELECT 
            *
        FROM
            tahos_spare_parts_request
        ORDER BY 
            created DESC
        LIMIT
			$start,$perPage
    ");
    $status = "
            <a href='/admin'>Главная</a> > 
            <a href='/admin/?view=messages'>Сообщения</a> > 
            $page_title
        ";

    ?>
    <table class="t_table spare_parts_request" cellspacing="1">
        <tr class="head">
            <td>vin</td>
            <td>Имя</td>
            <td>Телефон</td>
        </tr>
        <?if (!$requestList->num_rows){?>
            <tr class="head">
                <td colspan="3">Сообщений не найдено</td>
            </tr>
        <?}
        else{
            while($row = $requestList->fetch_assoc()){?>
                <tr data-id="<?=$row['id']?>" class="<?=$row['is_new'] ? 'is_new' : ''?>">
                    <td label="vin"><?=$row['vin']?></td>
                    <td label="Телефон"><?=$row['phone']?></td>
                    <td label="Имя"><?=$row['name']?></td>
                </tr>
            <?}
            pagination($chank, $page, ceil($all / $perPage), "?view=messages&act=spare_parts_request&page=");
        }?>
    </table>

<?}
function spare_parts_request(){
    global $db, $page_title, $status;
    $requestInfo = $db->select_one('spare_parts_request', "*", "`id` = {$_GET['id']}");
    $db->update('spare_parts_request', ['is_new' => 0], "`id` = {$_GET['id']}");
    $page_title = "Запрос от {$requestInfo['name']}";
    $status = "
            <a href='/admin'>Главная</a> > 
            <a href='/admin/?view=messages'>Сообщения</a> > 
            <a href='/admin/?view=messages&act=spare_parts_request'>Запросы на поиск запчастей</a> >
            $page_title
        ";
    ?>
    <div class="t_form" id="spare_parts_request">
		<div class="bg">
            <div class="field">
                <div class="title">Имя</div>
                <div class="value"><?=$requestInfo['name']?></div>
            </div>
            <div class="field">
                <div class="title">VIN</div>
                <div class="value"><?=$requestInfo['vin']?></div>
            </div>
            <div class="field">
                <div class="title">Автомобиль</div>
                <div class="value"><?=$requestInfo['car']?></div>
            </div>
            <div class="field">
                <div class="title">Год выпуска</div>
                <div class="value"><?=$requestInfo['issue_year']?></div>
            </div>
            <div class="field">
                <div class="title">Описание</div>
                <div class="value"><?=$requestInfo['description']?></div>
            </div>
            <div class="field">
                <div class="title">Телефон</div>
                <div class="value"><?=$requestInfo['phone']?></div>
            </div>
            <div class="field">
                <div class="title">Дата</div>
                <div class="value">
                    <?=DateTime::createFromFormat('Y-m-d H:i:s', $requestInfo['created'])->format('d.m.Y H:i:s')?>
                </div>
            </div>
		</div>
        <a href="<?=$_SERVER['HTTP_REFERER']?>">Назад</a>
	</div>
<?}