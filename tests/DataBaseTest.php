<?php
class TestUser extends \PHPUnit\Framework\TestCase{
    public function testDatabaseIsWorking(){
        $db = new core\DataBase();
        $this->assertObjectHasAttribute('mysqli', $db);
    }
}
