<?php
if (isset($_GET['fail']) && $_GET['fail'] == 'true'){
    require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/functions.php");
    message('Оплата не прошла', false);
    header("Location: /account");
    die();
}
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/Database.php");
\core\Messengers\Telegram::writeLogFile($_REQUEST);
\core\Payment\Paykeeper::setPayment($_REQUEST);
