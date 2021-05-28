<?php
class SettingTest extends \PHPUnit\Framework\TestCase{
	public function setUp(): void
	{
		$this->db = new core\Database();
	}
	/**
	 * @dataProvider getSettings
	 */
    public function testSetting($settings){
        $this->assertNotEmpty($settings);
        $db = $this->createMock(core\Database::class);
        $db->method('select')->willReturn(true);
        // $settings = $db->select('settings', '*', "`id` = 1");
        // print_r($settings);
        // foreach($settings as $key => $value) return $value;
    }
    /**
     * @depends testSetting
     */
    public function testIdSetting($id){
    	$this->assertNotEquals(1, $id);
    }
    public function getSettings(){

    	$db = new core\Database();
    	$settings = $db->select('settings', '*', "`id` = 1");
    	$output = [];
    	foreach($settings as $key => $value) $output[] = [$key => $value];
    	return $output;
    }
}
