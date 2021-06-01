<?
require_once('core/DataBase.php');
require_once('core/functions.php');
require_once('vendor/autoload.php');

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
if ($sha1 != $_POST['sha1_hash']) exit();
if ($_POST['notification_type'] == 'p2p-incoming') $comment = 'Пополнение с Яндекс.Деньги';
else $comment = 'Пополнение банковской картой';
$bill = $db->getFieldOnID('users', $_POST['label'], 'bill') + $_POST['withdraw_amount'];
$db->insert(
	'funds',
	[
		'type_operation' => 1,
		'sum' => $_POST['withdraw_amount'],
		'remainder' => $bill,
		'user_id' => $_POST['label'],
		'comment' => $comment
	]
);
$db->update('users', array('bill' => $bill), '`id`='.$_POST['label']);
core\User::checkOverdue($_POST['label'], $_POST['withdraw_amount']);
?>
