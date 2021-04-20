<?php
class DataBaseTest extends \PHPUnit\Framework\TestCase{
	public static $db;
	public static function setUpBeforeClass(): void
	{
		self::$db = new core\DataBase();
	}
   /* public function testDatabaseIsWorking(){
        $this->assertObjectHasAttribute('mysqli', self::$db);
    }
    public function testDatabaseIsWorking2(){
        $this->markTestIncomplete();
    }
     public function testDatabaseIsWorking3(){
        $this->markTestSkipped("error");
    }*/
    public static function tearDownAfterClass(): void
    {
    	self::$db = null;
    }
    public function testSetProfiling(){
        $db = $this->createMock(core\DataBase::class);
        $db->method('setConnectionID')->willReturn(true);
        $db->method('setProfiling')->willReturn(true);
        //тоже самое
        $map = [
            ['h','u','p','d',true],
            ['h','h','h','h',false],
        ];
        $db->method('setProfiling')->will(
            // $this->returnValue(true)
            // $this->returnArgument(0)
            $this->returnValueMap($map)
        );
        $this->assertTrue($db->setProfiling(10));
    }
}
