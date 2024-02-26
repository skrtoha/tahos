<?php
require_once ("{$_SERVER['DOCUMENT_ROOT']}/core/Database.php");
\core\Messengers\Telegram::writeLogFile($_REQUEST);
