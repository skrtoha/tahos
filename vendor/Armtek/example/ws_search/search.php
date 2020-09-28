<?php
// error_reporting(-1);
ini_set('display_errors', 1);

require_once 'vendor/Armtek/example/config.php';
require_once 'vendor/Armtek/autoloader.php';

use ArmtekRestClient\Http\Exception\ArmtekException as ArmtekException; 
use ArmtekRestClient\Http\Config\Config as ArmtekRestClientConfig;
use ArmtekRestClient\Http\ArmtekRestClient as ArmtekRestClient; 

try {
	// init configuration 
	$armtek_client_config = new ArmtekRestClientConfig($user_settings);  
	// init client
	$armtek_client = new ArmtekRestClient($armtek_client_config);
	$params = [
		'VKORG'         => '4000'       
		,'KUNNR_RG'     => '43179679'
		,'PIN'          => core\Item::articleClear($_GET['search'])
		,'BRAND'        => ''
		,'QUERY_TYPE'   => 1
		,'KUNNR_ZA'     => ''
		,'INCOTERMS'    => ''
		,'VBELN'        => ''
	];
	// requeest params for send
	$request_params = [

		'url' => 'search/search',
		'params' => [
			'VKORG'         => !empty($params['VKORG']) ? $params['VKORG'] : (isset($ws_default_settings['VKORG'])?$ws_default_settings['VKORG']:'')       
			,'KUNNR_RG'     => isset($params['KUNNR_RG'])?$params['KUNNR_RG']:(isset($ws_default_settings['KUNNR_RG'])?$ws_default_settings['KUNNR_RG']:'')
			,'PIN'          => isset($params['PIN'])?$params['PIN']:''
			,'BRAND'        => isset($params['BRAND'])?$params['BRAND']:''
			,'QUERY_TYPE'   => isset($params['QUERY_TYPE'])?$params['QUERY_TYPE']:''
			,'KUNNR_ZA'     => isset($params['KUNNR_ZA'])?$params['KUNNR_ZA']:(isset($ws_default_settings['KUNNR_ZA'])?$ws_default_settings['KUNNR_ZA']:'')
			,'INCOTERMS'    => isset($params['INCOTERMS'])?$params['INCOTERMS']:(isset($ws_default_settings['INCOTERMS'])?$ws_default_settings['INCOTERMS']:'')
			,'VBELN'        => isset($params['VBELN'])?$params['VBELN']:(isset($ws_default_settings['VBELN'])?$ws_default_settings['VBELN']:'')
			,'format'       => 'json'
		]
	];
	// send data
	$response = $armtek_client->post($request_params);
	// in case of json
	$json_responce_data = $response->json();
} catch (ArmtekException $e) {
	$json_responce_data = $e->getMessage(); 
}
// debug($json_responce_data);
$arr_keyzak = ['MOV0000027', 'MOV0000019', 'MOV0003863'];
// $json_responce_data = (array)$json_responce_data;
if (!$json_responce_data->RESP->MSG){
	foreach($json_responce_data->RESP as $key => $value){
		if (!in_array($value->KEYZAK, $arr_keyzak)) continue;
		$keyzak[$value->KEYZAK][] = [
			'PIN' => $value->PIN,
			'BRAND' => $value->BRAND,
			'NAME' => $value->NAME,
			'ANALOG' => $value->ANALOG
		];
	}
}
	
debug($keyzak);
// 
// echo "<h1>Пример вызова поиска</h1>";
// echo "<h2>Входные параметры</h2>";
// echo "<pre>"; print_r( $request_params ); echo "</pre>"; 
// echo "<h2>Ответ</h2>";
// echo "<pre>"; print_r( $json_responce_data ); echo "</pre>";
?>
