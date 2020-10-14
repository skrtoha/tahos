<?php
use core\Managers;
switch($_GET['act']){
	case 'storeForSubscribe':
		$res_mainStores = $db->query("
			SELECT
				ps.id,
				ps.cipher
				p.title
			FROM
				#provider_stores ps 
			LEFT JOIN
				#providers p ON p.id = ps.provider_id
			WHERE
				ps.is_main = 1
		");
		storeForSubscribe();
		break;
}
function storeForSubscribe(){

}
