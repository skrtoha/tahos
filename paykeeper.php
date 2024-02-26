<?php
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/Database.php");
\core\Payment\Paykeeper::setPayment($_REQUEST);
