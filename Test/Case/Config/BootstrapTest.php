<?php

class BootstrapTest extends CakeTestCase {

	public function setup() {
		parent::setup();

		include __DIR__ . '/../../../Config/bootstrap.php';
	}

	public function testConfigContainsElasticServerConnectionData() {
		$datas = Configure::read('Bounce');
		$this->assertArrayHasKey('host', $datas);
		$this->assertArrayHasKey('port', $datas);
		$this->assertTrue(is_numeric($datas['port']));
	}

}