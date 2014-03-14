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
	const BUCKET_NAME = 'user';
	const OBJECT_KEY = 'user';
	
	protected function setUp() {
		parent::setUp();
		$this->mockApplication(require(__DIR__.'/../../data/web.php'));

	} 
	
	/**
	 * This function test the insert of objects.
	 */
	public function testInsert() {
		echo PHP_EOL.'TEST INSERT BEGIN'.PHP_EOL;
		$command = Yii::$app->nosql->createCommand();
		
		$this->assertInstanceOf('sweelix\yii2\nosql\Command', $command);
		$this->assertInstanceOf('sweelix\yii2\nosql\riak\Command', $command);
		
		$command instanceof Command;
		
		//CREATING USER 0 (Basic insert)
		$response = $command->insert(self::BUCKET_NAME, self::OBJECT_KEY.'0', array(
				'firstname' => 'Christophe',
				'lastname' => 'Latour',
				'email' => 'clatour@ibitux.com',
		))->execute();
		
		$this->assertInstanceOf('sweelix\yii2\nosql\DataReader', $response);
		
		$object = $response->current();
		
		$this->checkDataReaderObject($object);
		
		$this->assertEquals(200, $object['.status']);
		$this->assertEquals('Christophe', $object['data']['firstname']);
		$this->assertEquals('Latour', $object['data']['lastname']);
		$this->assertEquals('clatour@ibitux.com', $object['data']['email']);		
		echo 'insert '.self::OBJECT_KEY.'0 : OK'.PHP_EOL;
	
		
		//Creating user (With index)
		for ($i = 1 ; $i < 10 ; $i++) {
			$key = self::OBJECT_KEY.$i;
			$response = $command->insert(self::BUCKET_NAME, $key, array(
					'firstname' => $key,
					'lastname' => 'noneed',
					'email' => 'userTest'.$i.'@ibitux.com',
			))->addIndex('age', $i + 20, IndexType::TYPE_INTEGER)->execute();
			
			//TEST CLASS
			$this->assertInstanceOf('sweelix\yii2\nosql\DataReader', $response);
			$object = $response->current();
				
			//TEST ALL KEYS ARE THERE
			$this->checkDataReaderObject($object);

			$this->assertArrayHasKey('age', $object[DataReader::INDEX_KEY]);
			
			$this->assertCount(2, $object[DataReader::INDEX_KEY]['age']);
			$this->assertEquals($i + 20, $object[DataReader::INDEX_KEY]['age'][0]);
			
			echo 'insert '.$key.' with indexes : OK'.PHP_EOL;
		}

		//Creating user With Link and Index
		for ($i = 10 ; $i < 20 ; $i++) {
			$key = self::OBJECT_KEY.$i;
			$response = $command->insert(self::BUCKET_NAME, $key, array(
				'firstname' => $key,
				'lastname' => 'noneed',
				'email' => 'userTest'.$i.'@ibitux.com',
			))->addIndex('ville', 'Paris'.$i)
			->addLink('user', 'user'.$i - 1, 'prev')
			->addLink('user', 'user'.$i + 1, 'next')
			->execute();

			//TEST CLASS
			$this->assertInstanceOf('sweelix\yii2\nosql\DataReader', $response);
			$object = $response->current();
			
			$this->checkDataReaderObject($object); 
			
			$this->assertArrayHasKey('ville', $object[DataReader::INDEX_KEY]);
			$this->assertCount(2, $object[DataReader::INDEX_KEY]['ville']);
			$this->assertEquals('Paris'.$i, $object[DataReader::INDEX_KEY]['ville'][0]);
			$this->assertCount(3, $object[DataReader::LINK_KEY]);
			echo 'insert '.$key.' with links : OK'.PHP_EOL;
		}

		//Creating with Metadata
		for ($i = 20 ; $i < 30 ; $i++) {
			$key = self::OBJECT_KEY.$i;
			$response = $command->insert(self::BUCKET_NAME, $key, array(
				'firstname' => $key,
				'lastname' => 'noneed',
				'email' => 'userTest'.$i.'@ibitux.com',	
			))->addMetaData('metatest', 'meta')
			->execute();
			
			$this->assertInstanceOf('sweelix\yii2\nosql\DataReader', $response);
			$object = $response->current();
			
			$this->checkDataReaderObject($object);
			
			$this->assertEquals(200, $object['.status']);
			$this->assertEquals($key, $object['data']['firstname']);
			$this->assertEquals('noneed', $object['data']['lastname']);
			$this->assertEquals('userTest'.$i.'@ibitux.com', $object['data']['email']);
			$this->assertArrayHasKey('metatest', $object[DataReader::META_KEY]);
			$this->assertEquals('meta', $object[DataReader::META_KEY]['metatest']);

			echo 'insert '.$key.' with metadata : OK'.PHP_EOL;
		}
		echo PHP_EOL.'TEST INSERT END'.PHP_EOL;
	}
	

	public function testDelete() {
		echo PHP_EOL.'TEST DELETE BEGIN'.PHP_EOL;
		$command = Yii::$app->nosql->createCommand();

		$this->assertInstanceOf('sweelix\yii2\nosql\riak\Command', $command, 'Type error');

		$command instanceof Command;


		for ($i = 0 ; $i < 30; $i++) {
			echo 'Delete user '.$i.'...';
			$response = $command->delete('user', 'user'.$i)->execute();

			$this->assertInstanceOf('sweelix\yii2\nosql\DataReader', $response);
			$object = $response->current();
			
			$this->checkDataReaderObject($object);
			
			$this->assertEquals(204, $object[DataReader::RESPONSESTATUS_KEY]);
			
			
			echo ' Done.'.PHP_EOL;
		}
		
		$response = $command->delete('user', 'user0')->execute();
		
		$this->assertInstanceOf('sweelix\yii2\nosql\DataReader', $response);
		$object = $response->current();
		
		$this->checkDataReaderObject($object);
		$this->assertEquals(404, $object[DataReader::RESPONSESTATUS_KEY]);

		echo PHP_EOL.'TEST DELETE END'.PHP_EOL;
		
	}
	
	private function checkDataReaderObject($object) {
		$this->assertArrayHasKey(DataReader::RESPONSESTATUS_KEY, $object);
		$this->assertArrayHasKey(DataReader::HEADERS_KEY, $object);
		$this->assertArrayHasKey(DataReader::DATA_KEY, $object);
		$this->assertArrayHasKey(DataReader::SIBLINGS_KEY, $object);
		$this->assertArrayHasKey(DataReader::ETAG_KEY, $object);
		$this->assertArrayHasKey(DataReader::VCLOCK_KEY, $object);
		$this->assertArrayHasKey(DataReader::META_KEY, $object);
		$this->assertArrayHasKey(DataReader::LINK_KEY, $object);
		$this->assertArrayHasKey(DataReader::INDEX_KEY, $object);
	}
}