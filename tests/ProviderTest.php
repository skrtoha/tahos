<?php
use core\FavoriteParts;
use core\Impex;
use core\Rossko;
use core\DataBase;
use core\Abcp;
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
}
