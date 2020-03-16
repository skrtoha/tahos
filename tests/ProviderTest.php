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
        $this->assertFalse(Provider::getIsDisabledApiSearch($rossko->provider_id), 'api поиска отключено');
        $this->assertFalse(Provider::getIsDisabledApiOrder($rossko->provider_id), 'api заказов отключено');
        $result = $rossko->getSearch('VKMV 7PK1749');
        $this->assertNotEmpty($result, 'Не срабатывает API');
    }
    public function testImpex(){
        $data = Impex::getData([
            'article' => '90919-01122',
            'brend' => 'Toyota / Lexus'
        ]);
        $this->assertFalse(Provider::getIsDisabledApiSearch(Impex::$provider_id), 'api поиска отключено');
        $this->assertFalse(Provider::getIsDisabledApiOrder(Impex::$provider_id), 'api заказов отключено');
        $this->assertEmpty($data['error'], "Ошибка:", $data['error']);
    }
    public function testFavoriteParts(){
        $this->assertFalse(Provider::getIsDisabledApiSearch(FavoriteParts::$provider_id), 'api поиска отключено');
        $this->assertFalse(Provider::getIsDisabledApiOrder(FavoriteParts::$provider_id), 'api заказов отключено');
        $response = Abcp::getUrlData(
            'http://api.favorit-parts.ru/hs/hsprice/?key='.FavoriteParts::$key.'&number=53610-SNR-A01'
        );
        $this->assertNotEmpty($response, 'не работает api');
        $item = FavoriteParts::getItem('Honda', '53610SNRA01');
    }
}
