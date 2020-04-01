<?php
class SettingTest extends \PHPUnit\Framework\TestCase{
	public function setUp(): void
	{
		$this->db = new core\DataBase();
	}
	/**
	 * @dataProvider getSettings
	 */
    public function testSetting($settings){
        $this->assertNotEmpty($settings);
        foreach($settings as $key => $value) return $value;
    }
    /**
     * @depends testSetting
     */
    public function testIdSetting($id){
    	$this->assertNotEquals(1, $id);
    }
    public function getSettings(){
    	$db = new core\DataBase();
    	$settings = $db->select('settings', '*', "`id` = 1");
    	$output = [];
    	foreach($settings as $key => $value) $output[] = [$key => $value];
    	return $output;
    }
}
