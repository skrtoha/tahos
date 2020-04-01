<?php
use core\Provider\FavoriteParts;
use core\Provider\Impex;
use core\Provider\Rossko;
use core\DataBase;
use core\Provider\Abcp;
use core\Provider;

class ProviderTest extends \PHPUnit\Framework\TestCase{
    public function testRossko(){
        $rossko = new Rossko(new DataBase());
        $this->assertTrue(Provider::getIsEnabledApiSearch($rossko->provider_id), 'api поиска отключено');
        $this->assertTrue(Provider::getIsEnabledApiSearch($rossko->provider_id), 'api заказов отключено');
        $result = $rossko->getSearch('VKMV 7PK1749');
        $this->assertNotEmpty($result, 'Не срабатывает API');
    }
    public function testImpex(){
        $data = Impex::getData([
            'article' => '90919-01122',
            'brend' => 'Toyota / Lexus'
        ]);
        $this->assertTrue(Provider::getIsEnabledApiSearch(Impex::$provider_id), 'api поиска отключено');
        $this->assertTrue(Provider::getIsEnabledApiSearch(Impex::$provider_id), 'api заказов отключено');
        $this->assertEmpty($data['error'], "Ошибка:", $data['error']);
    }
    public function testFavoriteParts(){
        $this->assertTrue(Provider::getIsEnabledApiSearch(FavoriteParts::$provider_id), 'api поиска отключено');
        $this->assertTrue(Provider::getIsEnabledApiSearch(FavoriteParts::$provider_id), 'api заказов отключено');
        $response = Abcp::getUrlData(
            'http://api.favorit-parts.ru/hs/hsprice/?key='.FavoriteParts::$key.'&number=53610-SNR-A01'
        );
        $this->assertNotNull($response, 'Ошибка api, возможено отключен vpn');
        $item = FavoriteParts::getItem('Honda', '53610SNRA01');
    }
    // public function testArmtekOrder(){
    //     $res_items = core\Armtek::getItems('armtek');
    //     if (!$res_items->num_rows){
            
    //     }
    // }
    // public function testRosskoOrder(){
    //     $db = new DataBase();
    //     $db->update('other_orders', ['response' => NULL], "`order_id`= 630 AND item_id = 8780334 AND store_id = 24");
    //     $rossko = new Rossko($db);
    //     $rossko->sendOrder(24);

    //     // $db->update('other_orders', ['response' => 'OK', 'updated' => '2019-10-15 18:21:56'], "`order_id`= 630 AND item_id = 8780334 AND store_id = 24");
    // }
}
