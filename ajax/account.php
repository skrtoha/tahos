<?
session_start();
require_once('../core/DataBase.php');
require_once('../core/functions.php');
require_once('../admin/functions/order_issues.function.php');

$db = new core\Database();
$connection = new core\Connection($db);
$db->connection_id = $connection->connection_id;
$db->setProfiling();

switch($_POST['act']){
    case 'get_bill':
        $res_user = \core\User::get(['user_id' => $_SESSION['user']]);
        foreach ($res_user as $value) $user = $value;

        $operations_types = array(
            1 => 'Пополнение счета',
            2 => 'Списание средств',
            3 => 'Зачисление бонусов',
            4 => 'Списание бонусов'
        );
        $where = "`type_operation` IN (1,2) AND `user_id` = {$_SESSION['user']} AND ";
        switch ($_POST['bill_type']){
            case 'cash':
                $where .= "`bill_type` = ".\core\User::BILL_CASH." AND ";
                break;
            case 'cashless':
                $where .= "`bill_type` = ".\core\User::BILL_CASHLESS." AND ";
                break;
            }
            if ($_POST['period'] == 'selected'){
                $begin = DateTime::createFromFormat('d.m.Y H:i:s', "{$_POST['begin']} 00:00:00")
                    ->format('Y-m-d H:i:s');
                $end = DateTime::createFromFormat('d.m.Y H:i:s', "{$_POST['end']} 23:59:59")
                    ->format('Y-m-d H:i:s');
                $where .= "`created` BETWEEN '$begin' AND '$end' AND ";
            }
        $where = substr($where, 0, -5);
        $funds = $db->select('funds', '*', $where , 'created', false);?>
        <table>
            <tr>
                <th>Вид операции</th>
                <th>Комментарий</th>
                <th>Дата</th>
                <th>Сумма</th>
                <th>Остаток</th>
            </tr>
            <?if (isset($funds) && count($funds)){
                foreach ($funds as $fund){
                    if ($fund['issue_id']){?>
                        <tr data-issue-id="<?=$fund['issue_id']?>">
                    <?}
                    else{?>
                        <tr>
                    <?}?>
                        <td><?=$operations_types[$fund['type_operation']]?></td>
                        <td class="name-col">
                            <?=stripslashes($fund['comment'])?>
                            <?if ($fund['issue_id']){?>
                                №<?=$fund['issue_id']?>
                            <?}?>
                        </td>
                        <td><?=date('d.m.Y H:i', strtotime($fund['created']))?></td>
                        <td>
                            <?$color = $fund['type_operation'] == 1 ? 'positive-color' : 'negative-color';
                            $minus_plus = $fund['type_operation'] == 1 ? '+' : '-';
                            if ($fund['issue_id']){
                                $color = 'gray-colour';
                                $minus_plus = '';
                            }
                            ?>
                            <span class="<?=$color?>">
                                <?=$minus_plus?>
                                <span class="price_format"><?=$fund['sum']?></span><i class="fa fa-rub" aria-hidden="true"></i>
                            </span>
                            <?if ($fund['issue_id']){?>
                                <span class="status">
                                    <?if ($fund['paid'] < $fund['sum']){?>
                                        <span class="negative-color">не оплачено</span>
                                        <?
                                        $created = DateTime::createFromFormat('Y-m-d H:i:s', $fund['created']);
                                        $difference = time() - $created->getTimestamp();
                                        $difference = floor($difference / 24 / 60 / 60);

                                        if ($difference >= $user['defermentOfPayment']){?>
                                            <span class="delay negative-color">просрок <?=$difference - $user['defermentOfPayment']?> д.</span>
                                        <?}
                                        else{
                                            $headline = $created->add(new DateInterval("P{$user['defermentOfPayment']}D"));
                                            $difference = $headline->getTimestamp() - time();
                                            $difference = floor($difference / 24 / 60 / 60);
                                            ?>
                                            <span class="delay account-total">осталось <?=$difference?> д.</span>
                                        <?}?>
                                    <?}?>
                                </span>
                            <?}?>

                        </td>
                        <td>
                            <?=$fund['remainder']?><i class="fa fa-rub" aria-hidden="true"></i>
                            <?if ($fund['issue_id']){?>
                                <span class="icon-enlarge2"></span>
                            <?}?>
                        </td>
                    </tr>
                <?}
            }
            else{?>
                <tr><td colspan="4">Операций со счетом не найдено</td></tr>
            <?}?>
        </table>
        <table class="small-view">
            <tr>
                <th>Операция</th>
                <th>Дата</th>
                <th>Сумма</th>
            </tr>
            <?if (isset($funds) && count($funds)){
                foreach ($funds as $fund){
                    if (in_array($fund['type_operation'], [3,4])) continue;
                    if ($fund['issue_id']){?>
                        <tr data-issue-id="<?=$fund['issue_id']?>">
                    <?}
                    else{?>
                        <tr>
                    <?}?>
                        <td class="name-col">
                            <?=stripslashes($fund['comment'])?>
                            <?if ($fund['issue_id']){?>
                                №<?=$fund['issue_id']?>
                            <?}?>
                        </td>
                        <td><?=date('d.m.Y H:i', strtotime($fund['created']))?></td>
                        <td>
                            <?$color = $fund['type_operation'] == 1 ? 'positive-color' : 'negative-color';
                            if ($fund['issue_id']) $color = 'gray-colour';
                            $minus_plus = $fund['type_operation'] == 1 ? '+' : '-';?>
                            <span class="<?=$color?>">
                                <?=$minus_plus?>
                                <span class="price_format"><?=$fund['sum']?></span><i class="fa fa-rub" aria-hidden="true"></i>
                            </span>
                            <?if ($fund['issue_id']){?>
                                <span class="status">
                                    <?if ($fund['paid'] < $fund['sum']){?>
                                        <span class="negative-color">не оплачено</span>
                                        <?
                                        $created = DateTime::createFromFormat('Y-m-d H:i:s', $fund['created']);
                                        $difference = time() - $created->getTimestamp();
                                        $difference = floor($difference / 24 / 60 / 60);

                                        if ($difference >= $user['defermentOfPayment']){?>
                                            <span class="delay negative-color">просрок <?=$difference - $user['defermentOfPayment']?> д.</span>
                                        <?}
                                        else{
                                            $headline = $created->add(new DateInterval("P{$user['defermentOfPayment']}D"));
                                            $difference = $headline->getTimestamp() - time();
                                            $difference = floor($difference / 24 / 60 / 60);
                                            ?>
                                            <span class="delay account-total">осталось <?=$difference?> д.</span>
                                        <?}?>
                                    <?}?>
                                </span>
                            <?}?>
                        </td>
                    </tr>
            <?}
            }?>
        </table>

        <?break;
    case 'getOrderIssueInfo':
        $issuesClass = new Issues(null, $db);
        $res = json_encode($issuesClass->getIssueWithUser($_POST['issue_id']));
        break;

}
echo $res;
?>
