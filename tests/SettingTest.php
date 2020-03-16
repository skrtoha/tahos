<?php
class SettingTest extends \PHPUnit\Framework\TestCase{
    public function testSetting(){
        $db = new core\DataBase();
        $row = $db->select_one('settings', '*', "`id`=1");
        $this->assertNotEmpty($row, 'ошибка получения settings');
    }
}
