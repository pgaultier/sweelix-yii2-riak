<?php
/**
 * BasicCommand.php
 *
 * PHP version 5.3+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://code.ibitux.net/redlix/
 * @category  controllers
 * @package   application.redlix.controllers
 */

namespace redlix\tests\unit\nosql\riak;

use Yii;
use sweelix\yii2\nosql\riak\Command;
use sweelix\yii2\nosql\riak\IndexType;
use sweelix\yii2\nosql\DataReader;
use sweelix\yii2\nosql\tests\TestCase;

/**
 * BasicCommand.php
 *
 * PHP version 5.3+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://code.ibitux.net/redlix/
 * @category  controllers
 * @package   application.redlix.controllers
 */
class CommandTest extends TestCase { 
	private $command;
	
	private $bucketName = 'riakBucketTest';
	private $objectName = 'riakObjectTest';
	private $objectData = array(
		'objectDataKey' => 'objectDataValue',
	);
	
	protected function setUp() {
		parent::setUp();
		$this->mockApplication(require(__DIR__.'/../../data/web.php'));
		$this->command = \Yii::$app->riak->createCommand();
	} 

	
	public function testInit() {
		$this->assertInstanceOf('sweelix\yii2\nosql\Command', $this->command);
		$this->assertInstanceOf('sweelix\yii2\nosql\riak\Command', $this->command);
		
		$this->assertInstanceOf('sweelix\yii2\nosql\Connection', $this->command->noSqlDb);
		$this->assertInstanceOf('sweelix\yii2\nosql\riak\Connection', $this->command->noSqlDb);
		
		$this->assertEmpty($this->command->getCommandData());	
	}
	
	public function testInsert() {
		$response = $this->command
		->insert($this->bucketName, $this->objectName, $this->objectData)
		->execute();
		$this->checkResponseIntegrity($response);


		$this->resetCommand();
		$this->command->setMode('insert');
		$this->command->setBucket($this->bucketName);
		$this->command->setKey($this->objectName.'WithSetters');
		$this->command->setData($this->objectData);
		$response = $this->command->execute();
		$this->checkResponseIntegrity($response);
		
		$this->resetCommand();
		
		$commandData = array(
			'mode' => 'insert',
			'bucket' => $this->bucketName,
			'key' => $this->objectName.'WithCommandData',
			'data' => $this->objectData
		);
		$response = $this->command->setCommandData($commandData)->execute();
		
		$this->checkResponseIntegrity($response);
		
		$this->resetCommand();
		
	}
	
	public function testInsertWithMeta() {
		$response = $this->command
		->insert($this->bucketName, $this->objectName.'WithMeta', $this->objectData)
		->addMetaData('metaTestKey', 'metaTestValue')
		->execute();
		$this->checkResponseIntegrity($response);
		
		$this->resetCommand();
		
		$this->command->setMode('insert');
		$this->command->setBucket($this->bucketName);
		$this->command->setKey($this->objectName.'WithMetaSetters');
		$this->command->setData($this->objectData);
		$this->command->setHeaders(array(
			'X-Riak-metaTestKeySetter' => 'metaTestValue'
		));
		$response = $this->command->execute();
		$this->checkResponseIntegrity($response);
	}
	
	public function testInsertWithIndexes() {
		$response = $this->command->insert($this->bucketName, $this->objectName.'WithIndexBin', $this->objectData)
		->addIndex('indexTestKeyBin', 'indexTestValueBin')
		->execute();
		
		$this->checkResponseIntegrity($response);
		
		$response = $this->command->insert($this->bucketName, $this->objectName.'WithIndexInt', $this->objectData)
		->addIndex('indexTestKeyInt', 123, IndexType::TYPE_INTEGER)
		->execute();
		$this->checkResponseIntegrity($response);
	}
	
	public function testInsertWithLinks() {
		$response = $this->command->insert($this->bucketName, $this->objectName.'WithLink', $this->objectData)
		->addLink($this->bucketName, $this->objectName, 'link')
		->addLink($this->bucketName, $this->objectName.'WithIndexBin', 'link')
		->addLink($this->bucketName, $this->objectName.'WithIndexInt', 'anotherLink')
		->execute();
		$this->checkResponseIntegrity($response);
	}
	
	public function testWithQueryParams() {
		//INSERT WITH HELPERS
		$response = $this->command->insert($this->bucketName, $this->objectName.'WithQueryParams', $this->objectData)
		->addQueryParameter('return_body', true)
		->execute();
		
		$this->checkResponseIntegrity($response);
		$this->checkObjectIntegrity($response->current());
		
		$this->resetCommand();
		
		$queryParams = array(
			'return_body' => true,
		);
		
		//INSERT WITH SETTER
		$this->command->setMode('insert');
		$this->command->setBucket($this->bucketName);
		$this->command->setKey($this->objectName.'QueryParamsSetter');
		$this->command->setData($this->objectData);
		$this->command->setQueryParams($queryParams);
		$this->command->execute();
		$this->checkResponseIntegrity($response);
//		$this->checkObjectIntegrity($response->current());
		
		$this->resetCommand();
		
		//INSERT WITH COMMAND DATA
		$commandData = array(
			'mode' => 'insert',
			'bucket' => $this->bucketName,
			'key' => $this->objectName.'WithQueryParamsCommandData',
			'queryParams' => $queryParams
		);
		$response = $this->command->setCommandData($commandData)->execute();
		$this->checkResponseIntegrity($response);
		var_dump($response);
//		$this->checkObjectIntegrity($response->current());
	}
	
	public function testFailInsert() {
		try {
			$this->command->setMode('Inexistant mode');
			$this->assertFalse(false);
		} catch (\Exception $e) {
			$this->assertTrue(true);
		}
		
		
	}

	public function alterBucket() {
		
	}
	
	public function testSelect() {
		$response = $this->command->setCommandData(array(
			'mode' => 'select',
			'bucket' => $this->bucketName,
			'key' => $this->objectName,
		))->queryOne();
		
		$this->checkResponseIntegrity($response);
		$this->checkObjectIntegrity($response->current());
	}
	
	
	public function testUpdate() {
		
	}
	
	public function testDelete() {
		$suffixes = array(
			'',
			'WithSetters',
			'WithCommandData',
			'WithIndexBin',
			'WithIndexInt',
			'WithMeta',
			'WithMetaSetters',
			'WithLink',
			'WithQueryCommandData',
			'WithQueryParams',
			'WithQueryParamsCommandData',
			'WithQueryParamsSetter'
		);
		
		foreach ($suffixes as $suffix) {
			$response = $this->command->delete($this->bucketName, $this->objectName.$suffix)->execute();
			$this->checkResponseIntegrity($response);
		}
	} 

	private function checkObjectIntegrity($object, $exceptedStatusCode = 200) {
		$this->assertArrayHasKey(DataReader::RESPONSESTATUS_KEY, $object);
		$this->assertArrayHasKey(DataReader::HEADERS_KEY, $object);
		$this->assertArrayHasKey(DataReader::DATA_KEY, $object);
		$this->assertArrayHasKey(DataReader::SIBLINGS_KEY, $object);
		$this->assertArrayHasKey(DataReader::ETAG_KEY, $object);
		$this->assertArrayHasKey(DataReader::VCLOCK_KEY, $object);
		$this->assertArrayHasKey(DataReader::META_KEY, $object);
		$this->assertArrayHasKey(DataReader::LINK_KEY, $object);
		$this->assertArrayHasKey(DataReader::INDEX_KEY, $object);
		
		$this->assertEquals($exceptedStatusCode, $object[DataReader::RESPONSESTATUS_KEY]);
	}
	
	private function checkResponseIntegrity($response, $exceptedStatus = 200) {
		$this->assertInstanceOf('sweelix\yii2\nosql\DataReader', $response);
	}
	private function resetCommand() {
		$this->command = \Yii::$app->riak->createCommand();
	}
}