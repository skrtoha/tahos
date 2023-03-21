<?

use core\Fund;
use core\OrderValue;
use core\User;

require_once('core/DataBase.php');
require_once('core/functions.php');
require_once('vendor/autoload.php');

file_put_contents('json/'.(new DateTime())->format('Y_m_d_H_i_s').'.json', json_encode($_POST));

$db = new core\Database();

$secret_key = 'RMoTDr8+TgrVFTyKv2AK/AC4';
$sha1 = sha1(
    $_POST['notification_type'].'&'.
    $_POST['operation_id'].'&'.
    $_POST['amount'].'&'.
    $_POST['currency'].'&'.
    $_POST['datetime'].'&'.
    $_POST['sender'].'&'.
    $_POST['codepro'].'&'.
    $secret_key.'&'.
    $_POST['label']
);


$label = explode(':', $_POST['label']);

if ($sha1 != $_POST['sha1_hash']) exit();

switch($label[0]){
    case 'account':
        $user_id = $label[1];
        $bill = $db->getFieldOnID('users', $user_id, 'bill_cash') + $_POST['withdraw_amount'];
        if ($_POST['notification_type'] == 'p2p-incoming') $comment = 'Пополнение с Yoomoney';
        else $comment = 'Пополнение банковской картой';
        User::checkOverdue($user_id, $_POST['withdraw_amount']);
        break;
    case 'order':
        $comment = "Онлайн оплата заказа №{$label[1]}";
        $orderInfo = OrderValue::getOrderInfo($label[1]);
        $user_id = $orderInfo['user_id'];
        $bill = $db->getFieldOnID('users', $user_id, 'bill_cash') + $_POST['withdraw_amount'];
        $db->update('orders', ['is_payed' => 1], "`id` = {$label[1]}");
        break;
}

User::checkOverdue($user_id, $_POST['withdraw_amount']);
$db->update('users', array('bill_cash' => $bill), '`id`='.$user_id);
Fund::insert(1, [
    'sum' => $_POST['withdraw_amount'],
    'remainder' => $bill,
    'user_id' => $user_id,
    'comment' => $comment,
    'bill_type' => User::BILL_CASH
]);
User::checkDebt($user_id, $_POST['withdraw_amount'], User::BILL_CASH, Fund::$last_id);
die();
?>
