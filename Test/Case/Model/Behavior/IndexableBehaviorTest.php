<?php
/**
 * Indexable Behavior Test
 */

App::uses('ModelBehavior', 'Model');
App::uses('Model', 'Model');
App::uses('IndexableBehavior', 'Bounce.Model/Behavior');
App::uses('HttpSocket', 'Network/Http');

include __DIR__ . '/../../../bootstrap.php';

class IndexableBehaviorTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();

		$this->HttpSocket = new HttpSocket();

		$this->Model = new TestModel();

		$this->Index = $this->getMock(
			'IndexableBehavior',
			array('_addIndex', '_updateIndex', '_deleteIndex', 'filterData', 'beforeIndex'),
			array(),
			'',
			false
		);

		$this->settings = array('index' => 'main', 'type' => 'type');
	}

	public function tearDown() {
		$model = new ModelClass();
		$model->query('DELETE FROM model_classes');

		$this->HttpSocket->request(array(
			'method' => 'DELETE',
			'uri' => array(
				'host' => ELASTIC_HOST,
				'port' => ELASTIC_PORT,
				'path' => 'test_song'
			)
		));
	}

	/*
		============================================================================================
				UNIT TEST
		============================================================================================
	 */

	public function testAfterSaveCreateIndex() {
		$this->Index->expects($this->once())->method('_addIndex');
		$this->Index->expects($this->once())->method('filterData')->will($this->returnValue(array(1)));
		$this->Index->expects($this->once())->method('beforeIndex')->will($this->returnValue(true));

		$this->Index->setUp($this->Model, $this->settings);
		$this->Index->afterSave($this->Model, true);
	}

	public function testAfterSaveUpdateIndex() {
		$this->Index->expects($this->once())->method('_updateIndex');
		$this->Index->expects($this->once())->method('filterData')->will($this->returnValue(array(1)));
		$this->Index->expects($this->once())->method('beforeIndex')->will($this->returnValue(true));

		$this->Index->setUp($this->Model, $this->settings);
		$this->Index->afterSave($this->Model, false);
	}

	public function testAfterDeleteDeleteIndex() {
		$this->Index->expects($this->once())->method('_deleteIndex');
		$this->Index->expects($this->once())->method('beforeIndex')->will($this->returnValue(true));

		$this->Model->id = 1;
		$this->Index->setUp($this->Model, $this->settings);
		$this->Index->afterDelete($this->Model);
	}

	public function testAfterDeleteDeleteIndexOnNonExistentId() {
		$this->Index->expects($this->never())->method('_deleteIndex');
		$this->Index->expects($this->once())->method('beforeIndex')->will($this->returnValue(true));

		$this->Model->id = null;
		$this->Index->setUp($this->Model, $this->settings);
		$this->Index->afterDelete($this->Model);
	}

	public function testAfterSaveCreateIndexWhenBeforeIndexConditionNotMet() {
		$this->Index->expects($this->never())->method('_addIndex');
		$this->Index->expects($this->never())->method('filterData');
		$this->Index->expects($this->once())->method('beforeIndex')->will($this->returnValue(false));

		$this->Index->setUp($this->Model, $this->settings);
		$this->Index->afterSave($this->Model, true);
	}

	public function testAfterSaveUpdateIndexWhenBeforeIndexConditionNotMet() {
		$this->Index->expects($this->never())->method('_updateIndex');
		$this->Index->expects($this->never())->method('filterData');
		$this->Index->expects($this->once())->method('beforeIndex')->will($this->returnValue(false));

		$this->Index->setUp($this->Model, $this->settings);
		$this->Index->afterSave($this->Model, false);
	}

	public function testAfterSaveSkipIndexingWhenThereIsNoData() {
		$this->Index->expects($this->never())->method('_updateIndex');
		$this->Index->expects($this->never())->method('filterData');
		$this->Index->expects($this->once())->method('beforeIndex')->will($this->returnValue(true));

		$this->Model->data = array();

		$this->Index->setUp($this->Model, $this->settings);
		$res = $this->Index->afterSave($this->Model, false);
		$this->assertTrue($res);
	}

	public function testAfterSaveSkipIndexingWhenThereIsNoWhiteListedData() {
		$this->Index->expects($this->never())->method('_updateIndex');
		$this->Index->expects($this->once())->method('filterData')->will($this->returnValue(array()));
		$this->Index->expects($this->once())->method('beforeIndex')->will($this->returnValue(true));

		$this->Model->data = array(1);

		$this->Index->setUp($this->Model, $this->settings);
		$res = $this->Index->afterSave($this->Model, false);
		$this->assertTrue($res);
	}

	public function testAfterDeleteDeleteIndexWhenBeforeIndexConditionNotMet() {
		$this->Index->expects($this->never())->method('_deleteIndex');
		$this->Index->expects($this->once())->method('beforeIndex')->will($this->returnValue(false));

		$this->Index->setUp($this->Model, $this->settings);
		$this->Index->afterDelete($this->Model);
	}

	public function testSendRequest() {
		$this->Index->HttpSocket = $this->getMock('HttpSocket', array('request'));

		$method = new ReflectionMethod('IndexableBehavior', '_sendRequest');
		$method->setAccessible(true);

		$args = array('XMETHOD', array('uri' => array('path' => 'main/type/')), 'path/x/y?q=abc', array("a" => 100));

		$finalRequest = array(
			'uri' => array(
				'path' => 'main/type/' . $args[2]
			),
			'method' => $args[0],
			'body' => json_encode($args[3])

		);

		$this->Index->HttpSocket->expects($this->once())->method('request')->with($finalRequest);

		$method->invoke($this->Index, $args[0], $args[1], $args[2], $args[3]);
	}

	public function testSetupWithNoType() {
		$s = $this->Model->Behaviors->Indexable->settings;

		$request = array(
			'uri' => array(
				'host' => Configure::read('Bounce.host'),
				'port' => Configure::read('Bounce.port'),
				'path' => 'test_main/test_model/'
			)
		);

		$this->assertEquals($request, $s[$this->Model->alias]['request']);
	}

	public function testSetupWithCustomType() {
		$this->Model = new TestModel2();
		$s = $this->Model->Behaviors->Indexable->settings;

		$request = array(
			'uri' => array(
				'host' => Configure::read('Bounce.host'),
				'port' => Configure::read('Bounce.port'),
				'path' => 'index_/c_name/'
			)
		);

		$this->assertEquals($request, $s[$this->Model->alias]['request']);
	}

/**
 * Test that beforeIndex callback always return true by default
 *
 * @covers IndexableBehavior::beforeIndex
 */
	public function testBeforeIndex() {
		$index = new IndexableBehavior();
		$this->assertTrue($index->beforeIndex($this->Model));
	}

/**
 * Test that beforeIndex redefined by Model is called
 *
 * @covers IndexableBehavior::beforeIndex
 */
	public function testBeforeIndexCalledFromModel() {
		$this->Model = new TestModel2();
		$this->assertEquals('value', $this->Model->beforeIndex());
	}

/**
 * @covers IndexableBehavior::_addIndex
 */
	public function testaddIndex() {
		$method = new ReflectionMethod('IndexableBehavior', '_addIndex');
		$method->setAccessible(true);

		$this->Index = $this->getMock(
			'IndexableBehavior',
			array('_addIndex', '_updateIndex', '_deleteIndex', 'filterData', 'beforeIndex', '_sendRequest'),
			array(),
			'',
			false
		);

		$this->Index->settings['TestModel'] = array('request' => array(
			'uri' => array(
				'path' => 'x'
			)
		));

		$this->Index->expects($this->once())->method('_sendRequest')->with('PUT', $this->Index->settings['TestModel']['request'], 'id', array());

		$method->invoke($this->Index, $this->Model, 'id', array());
	}

/**
 * @covers IndexableBehavior::_updateIndex
 */
	public function testUpdateIndex() {
		$method = new ReflectionMethod('IndexableBehavior', '_updateIndex');
		$method->setAccessible(true);

		$this->Index = $this->getMock(
			'IndexableBehavior',
			array('_addIndex', '_updateIndex', '_deleteIndex', 'filterData', 'beforeIndex', '_sendRequest'),
			array(),
			'',
			false
		);

		$this->Index->settings['TestModel'] = array('request' => array(
			'uri' => array(
				'path' => 'x'
			)
		));

		$this->Index->expects($this->once())->method('_sendRequest')->with('POST', $this->Index->settings['TestModel']['request'], 'id/_update', array());

		$method->invoke($this->Index, $this->Model, 'id', array());
	}

/**
 * @covers IndexableBehavior::_deleteIndex
 */
	public function testDeleteIndex() {
		$method = new ReflectionMethod('IndexableBehavior', '_deleteIndex');
		$method->setAccessible(true);

		$this->Index = $this->getMock(
			'IndexableBehavior',
			array('_addIndex', '_updateIndex', '_deleteIndex', 'filterData', 'beforeIndex', '_sendRequest'),
			array(),
			'',
			false
		);

		$this->Index->settings['TestModel'] = array('request' => array(
			'uri' => array(
				'path' => 'x'
			)
		));

		$this->Index->expects($this->once())->method('_sendRequest')->with('DELETE', $this->Index->settings['TestModel']['request'], 'id');

		$method->invoke($this->Index, $this->Model, 'id', array());
	}

/**
 * With false mapping (default), all datas are indexed
 *
 * @covers IndexableBehavior::filterData
 */
	public function testFilterDataWithFalseMapping() {
		$indexable = new IndexableBehavior();
		$indexable->setup($this->Model, array(
			'index' => 'main',
			'type' => 'song'
		));

		$datas = array(
			'id' => 1,
			'title' => 'Viva la vida',
			'author' => array(
				'id' => 125,
				'name' => 'Coldplay'
			),
			'album' => array(
				'id' => 56,
				'title' => 'Viva la vida',
				'release_date' => array(
					'usa' => '2010-01-26T06:00:00'
				)
			),
			'tracks' => 3,
			'length' => 563
		);

		$res = $indexable->filterData($this->Model, array('TestModel' => $datas));
		$this->assertEquals($datas, $res);
	}

/**
 * With empty witelist, there is not fields to index
 *
 * @covers IndexableBehavior::filterData
 */
	public function testFilterDataWithEmptyWhitelist() {
		$indexable = new IndexableBehavior();
		$indexable->setup($this->Model, array(
			'index' => 'main',
			'type' => 'song',
			'mapping' => array()
		));

		$datas = array(
			'id' => 1,
			'title' => 'Viva la vida',
			'author' => array(
				'id' => 125,
				'name' => 'Coldplay'
			),
			'album' => array(
				'id' => 56,
				'title' => 'Viva la vida',
				'release_date' => array(
					'usa' => '2010-01-26T06:00:00'
				)
			),
			'tracks' => 3,
			'length' => 563
		);

		$res = $indexable->filterData($this->Model, array('TestModel' => $datas));
		$this->assertEmpty($res);
	}

/**
 * With a valid mapping, filterData filter out unwanted datas
 *
 * @covers IndexableBehavior::filterData
 */
	public function testFilterData() {
		$indexable = new IndexableBehavior();
		$indexable->setup($this->Model, array(
			'index' => 'main',
			'type' => 'song',
			'mapping' => array(
				'id' => 'integer',
				'title' => 'string',
				'author.id' => 'integer',
				'album.release_date.usa' => 'date'
			)
		));

		$datas = array(
			'id' => 1,
			'title' => 'Viva la vida',
			'author' => array(
				'id' => 125,
				'name' => 'Coldplay'
			),
			'album' => array(
				'id' => 56,
				'title' => 'Viva la vida',
				'release_date' => array(
					'usa' => '2010-01-26T06:00:00'
				)
			),
			'tracks' => 3,
			'length' => 563
		);

		$res = $indexable->filterData($this->Model, array('TestModel' => $datas));

		$finalDatas = $datas;
		unset($finalDatas['author']['name']);
		unset($finalDatas['tracks'], $finalDatas['length'], $finalDatas['album']['id'], $finalDatas['album']['title']);

		$this->assertCount(4, $res);
		$this->assertEquals($finalDatas, $res);
	}

	/*
		============================================================================================
				INTEGRATION TEST
		============================================================================================
	 */

/**
 * Test that index is created afer saving a model
 * @return [type] [description]
 */
	public function testCreateIndexAfterSave() {
		$ModelClass = new ModelClass();
		$ModelClass->Behaviors->Indexable->settings['ModelClass']['request']['uri']['host'] = ELASTIC_HOST;
		$ModelClass->Behaviors->Indexable->settings['ModelClass']['request']['uri']['port'] = ELASTIC_PORT;

		$datas = array(
			'id' => 1,
			'title' => 'Viva la vida',
			'length' => 120,
			'track' => 1
		);

		$ModelClass->save($datas);

		$response = $this->HttpSocket->request(array(
			'method' => 'GET',
			'uri' => array(
					'host' => ELASTIC_HOST,
					'port' => ELASTIC_PORT,
					'path' => 'test_song/model_class/1'
				)
			)
		);

		$response = json_decode($response, true);
		$source = $response['_source'];

		$this->assertArrayHasKey('_index', $response);
		$this->assertArrayHasKey('_type', $response);
		$this->assertArrayHasKey('_id', $response);
		$this->assertArrayHasKey('_source', $response);
		$this->assertTrue($response['exists']);
		$this->assertEquals('test_song', $response['_index']);
		$this->assertEquals('model_class', $response['_type']);
		$this->assertEquals('1', $response['_id']);
		$this->assertEquals( array(
			'id' => 1,
			'title' => 'Viva la vida'),
			$source
		);
	}

/**
 * Test that index is updated afer saving a model
 * @return [type] [description]
 */
	public function testUpdateIndexAfterSave() {
		$ModelClass = new ModelClass();
		$ModelClass->Behaviors->Indexable->settings['ModelClass']['request']['uri']['host'] = ELASTIC_HOST;
		$ModelClass->Behaviors->Indexable->settings['ModelClass']['request']['uri']['port'] = ELASTIC_PORT;

		$id = rand(0, 1000);

		$datas = array(
			'id' => $id,
			'title' => 'Viva la vida',
			'length' => 120,
			'track' => 1
		);

		$this->HttpSocket->request(array(
			'method' => 'PUT',
			'uri' => array(
				'host' => ELASTIC_HOST,
				'port' => ELASTIC_PORT,
				'path' => 'test_song/model_class/' . $id
			),
			'body' => json_encode($datas)
		));

		$ModelClass->query("INSERT INTO model_classes (id, title) VALUES(" . $id . ", 'test')");

		$datas['track'] = 2;
		$datas['title'] = 'Vive la vie';
		$ModelClass->save(array('ModelClass' => $datas));

		$response = $this->HttpSocket->request(array(
			'method' => 'GET',
			'uri' => array(
					'host' => ELASTIC_HOST,
					'port' => ELASTIC_PORT,
					'path' => 'test_song/model_class/' . $id
				)
			)
		);

		$response = json_decode($response, true);
		$source = $response['_source'];
		// Because we can't test the date value
		unset($source['created'], $source['modified']);

		$this->assertArrayHasKey('_source', $response);
		$this->assertTrue($response['exists']);
		$this->assertEquals($datas['id'], $source['id'], 'ID remain unchanged');
		$this->assertEquals($datas['title'], $source['title'], 'Title was updated');
		$this->assertEquals($datas['length'], $source['length'], 'Length remain unchanged because not in whitelist');
		$this->assertEquals(1, $source['track'], 'Track remain unchanged because not in whitelist');
	}

/**
 * Test that index is deleted afer deleting a model
 * @return [type] [description]
 */
	public function testDeleteIndexAfterDelete() {
		$ModelClass = new ModelClass();
		$ModelClass->Behaviors->Indexable->settings['ModelClass']['request']['uri']['host'] = ELASTIC_HOST;
		$ModelClass->Behaviors->Indexable->settings['ModelClass']['request']['uri']['port'] = ELASTIC_PORT;

		$id = rand(0, 1000);

		$ModelClass->query("INSERT INTO model_classes (id, title) VALUES(" . $id . ", 'test')");

		$this->HttpSocket->request(array(
			'method' => 'PUT',
			'uri' => array(
				'host' => ELASTIC_HOST,
				'port' => ELASTIC_PORT,
				'path' => 'test_song/model_class/' . $id
			),
			'body' => json_encode(array('id' => $id))
		));

		$this->HttpSocket->request(array(
			'method' => 'PUT',
			'uri' => array(
				'host' => ELASTIC_HOST,
				'port' => ELASTIC_PORT,
				'path' => 'test_song/model_class/25'
			),
			'body' => json_encode(array('id' => 25))
		));

		$response = $this->HttpSocket->request(array(
			'method' => 'GET',
			'uri' => array(
					'host' => ELASTIC_HOST,
					'port' => ELASTIC_PORT,
					'path' => 'test_song/model_class/' . $id
				)
			)
		);

		$response = json_decode($response, true);
		$this->assertTrue($response['exists']);
		$this->assertEquals(array('id' => $id), $response['_source']);


		$ModelClass->id = $id;
		$ModelClass->delete($id);

		$response = $this->HttpSocket->request(array(
			'method' => 'GET',
			'uri' => array(
					'host' => ELASTIC_HOST,
					'port' => ELASTIC_PORT,
					'path' => 'test_song/model_class/' . $id
				)
			)
		);

		$response = json_decode($response, true);

		// Deleting the index
		$this->assertFalse($response['exists']);

		// Don't delete other index
		$response = $this->HttpSocket->request(array(
			'method' => 'GET',
			'uri' => array(
					'host' => ELASTIC_HOST,
					'port' => ELASTIC_PORT,
					'path' => 'test_song/model_class/25'
				)
			)
		);

		$response = json_decode($response, true);
		$this->assertTrue($response['exists']);
	}

}

/*
	============================================================================================
			DUMMY CLASS
	============================================================================================
 */

class TestModel extends Model {

	public $actsAs = array('Bounce.Indexable' => array(
			'index' => 'test_main'
		)
	);

	public $data = array('TestModel' => array());
}

class TestModel2 extends Model {

	public $actsAs = array('Bounce.Indexable' => array(
			'index' => 'index_',
			'type' => 'c_name'
		)
	);

	public $data = array('TestModel2' => array());

	public function beforeIndex() {
		return 'value';
	}

}

class ModelClass extends Model {

	public $useDbConfig = 'test';

	public $actsAs = array('Bounce.Indexable' => array(
			'index' => 'test_song',
			'mapping' => array(
				'id' => 'integer',
				'title' => 'string',
				'author.id' => 'integer',
				'album.release_date.usa' => 'date'
			)
		)
	);
}