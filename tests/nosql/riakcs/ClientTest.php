<?php
/**
 * ClientTest.php
 *
 * PHP version 5.3+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://code.ibitux.net/redlix/
 * @category  tests
 * @package   application.test.unit.nosql.riak
 */

namespace sweelix\yii2\nosql\tests\riakcs;


use sweelix\yii2\nosql\tests\TestCase;

/**
 * Class ClientTest tests the riak-cs client.
 *
 * PHP version 5.3+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://code.ibitux.net/redlix/
 * @category  tests
 * @package   application.test.unit.nosql.riak
 * @since     XXX
 */
class ClientTest extends TestCase {
	
	private $bucketName = 'bucketunittesting';
	private $bucketError = 'bucketError';
	private $objectName = 'objectTest';
	private $objectData = array(
		"key1" => "test1",
		"key2" => "toto",
		"key3" => "titi",
	);
	
	private $objectKeyFilename = 'file.php';
	private $objectKeyMusicname = 'music.mp3';
	
	protected function setUp() {
		parent::setUp();
		$this->mockApplication(require(__DIR__.'/../../data/web.php'));
	}
	
	protected function tearDown() {
		parent::tearDown();
	}

	/**
	 * Create buckets
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function testCreateBucket() {
		/* CREATE BUCKET */
		$response = \Yii::$app->riakcs->client->putBucket($this->bucketName);
		$this->assertTrue($response, 'CreateBucket '.$this->bucketName.' FAILED');
		
		/* CREATE BUCKET WITH FORBIDDEN NAME */
		$response = \Yii::$app->riakcs->client->putBucket($this->bucketError);
		$this->assertFalse($response, 'CreateBucket '.$this->bucketError.' SUCCED (should failed)');
	}

	/**
	 * Get content of empty buckets.
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function testGetEmptyBucket() {
		/* GET BUCKET */
		$response = \Yii::$app->riakcs->client->getBucket($this->bucketName);
		$this->assertEmpty($response, "GetBucket response should be empty. Current value : ".var_export($response, true));
		
		/* GET BUCKET INEXISTANT */
		$response = \Yii::$app->riakcs->client->getBucket($this->bucketError);
		$this->assertFalse($response, "getBucket response should be false. Current value : ".var_export($response, true));
		
	}
		
	/**
	 * Create objects in buckets.
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function testPutObject() {		
		/* Insert Object with json data */
		$response = \Yii::$app->riakcs->client->putObject($this->bucketName, $this->objectName, $this->objectData);
		$this->assertTrue($response, "PutObject ".$this->objectName.", response should be true. Current value : ".var_export($response, true));
		
		/* Insert Object with xml data */
		$xmlData =  $this->encodeObj($this->objectData);
		$xmlObjectName = $this->objectName.'Xml';
		$response = \Yii::$app->riakcs->client->putObject($this->bucketName, $xmlObjectName, $xmlData, 'application/xml');
		$this->assertTrue($response, "PutObject ".$xmlObjectName." in ".$this->bucketName." failed. Current value : ".var_export($response, true));
		
		/* Insert object in inexistant bucket */
		$response = \Yii::$app->riakcs->client->putObject($this->bucketError, $this->objectName, $this->objectData);
		$this->assertFalse($response, "PutObject ".$this->objectName." in bucket named ".$this->bucketError." should return false. Current value .".var_export($response, true));
	}

	/**
	 * Get object
	 * 
	 * @return void
	 * @since  XXX 
	 */
	public function testGetObject() {
		$objectKeys = array('headers', 'data');
		$headerDefaultKeys = array(
			'Server',
			'Last-Modified',
			'Etag',
			'Date',
			'Content-Type',
			'Content-Length'
		);

		/* OBJECT FOUND (json) */
		$object = \Yii::$app->riakcs->client->getObject($this->bucketName, $this->objectName);
		$this->assertNotEmpty($object);
		$this->checkArrayHasKeys($objectKeys, $object);
		$this->checkArrayHasKeys($headerDefaultKeys, $object['headers']);
		$this->assertEquals('application/json', $object['headers']['Content-Type']);
		
		/* OBJECT FOUND (xml) */
		$xmlObjectName = $this->objectName.'Xml';
		$object = \Yii::$app->riakcs->client->getObject($this->bucketName, $xmlObjectName);
		$this->assertNotEmpty($object);
		$this->checkArrayHasKeys($objectKeys, $object);
		$this->checkArrayHasKeys($headerDefaultKeys, $object['headers']);
		$this->assertEquals('application/xml', $object['headers']['Content-Type']);
		
		/* NO OBJECT FOUND */
		$objectNotFound = \Yii::$app->riakcs->client->getObject($this->bucketError, $this->objectName);
		$this->assertFalse($objectNotFound, "GetObject ".$this->objectName." in bucket ".$this->bucketError." should not be found. Current value : ".var_export($objectNotFound, true));
	}
	
	/**
	 * Put file
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function testPutFile() {
		$littleFilePath = \Yii::getAlias('@yiiunit').DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'file.php'; //74 KO
		$bigFilePath = \Yii::getAlias('@yiiunit').DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'music.mp3';   //15,7 MB
		
		/* Put little file */
		$response = \Yii::$app->riakcs->client->putFile($this->bucketName, $this->objectKeyFilename, $littleFilePath);
		$this->assertTrue($response);
		
		/* Put big file (if file > 5MB, it will upload with a multiupload) */
		$response = \Yii::$app->riakcs->client->putFile($this->bucketName, $this->objectKeyMusicname, $bigFilePath);
		$this->assertTrue($response);
	}

	/**
	 * Delete files (same than deleteObject)
	 * 
	 * @return void
	 * @since  XXX 
	 */
	public function testDeleteFile() {
		$response = \Yii::$app->riakcs->client->deleteObject($this->bucketName, $this->objectKeyFilename);
		$this->assertTrue($response);
		
		$response = \Yii::$app->riakcs->client->deleteObject($this->bucketName, $this->objectKeyMusicname);
		$this->assertTrue($response);
	}
	
	/**
	 * Get content of filled buckets
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function testGetFilledBucket() {
		$mandotoryKeys = array("key", "Last-Modified", "etag", "size", "storage", "ownerId", "ownerName");
		$objects = \Yii::$app->riakcs->client->getBucket($this->bucketName);
		$this->assertNotEmpty($objects, "GetFilledBucket ".$this->bucketName." should return an not empty array. Current value : ".var_export($objects, true));
		$this->assertEquals(2, count($objects));
		foreach ($objects as $object) {
			$this->checkArrayHasKeys($mandotoryKeys, $object);
		}
	}	
		
	/**
	 * Delete an object from bucket
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function testDeleteObject() {
		/* DELETE OBJECT FROM BUCKET */
		$response = \Yii::$app->riakcs->client->deleteObject($this->bucketName, $this->objectName);
		$this->assertTrue($response, "DeleteObject ".$this->objectName." in bucket ".$this->bucketName.", response should be true. Current value :".var_export($response, true));
		
		/* DELETE OBJECT FROM BUCKET */
		$xmlObjectName = $this->objectName."Xml";
		$response = \Yii::$app->riakcs->client->deleteObject($this->bucketName, $xmlObjectName);
		$this->assertTrue($response, "DeleteObject ".$xmlObjectName." in bucket ".$this->bucketName.", response should be true. Current value :".var_export($response, true));
		
		/* DELETE OBJECT FROM INEXISTANT BUCKET */
		$response = \Yii::$app->riakcs->client->deleteObject($this->bucketError, $this->objectName);
		$this->assertFalse($response);
	}
	
	/**
	 * Delete a bucket
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function testDeleteBucket() {
		$response = \Yii::$app->riakcs->client->deleteBucket($this->bucketName);
		$this->assertTrue($response, 'deleteBucket : '.$this->bucketName.' FAILED');
		
		$response = \Yii::$app->riakcs->client->deleteBucket($this->bucketError);
		$this->assertFalse($response, 'deleteBucket : '.$this->bucketError.' SUCCEED (should failed)');
	}
	
	/**
	 * Check if array has keys.
	 * 
	 * @param array $keys  Keys which should be present in the array
	 * @param array $array The array to check
	 * 
	 * @return void
	 * @since  XXX
	 */
	private function checkArrayHasKeys($keys, $array) {
		foreach ($keys as $key) {
			$this->assertArrayHasKey($key, $array, "Missing key : ".$key." in array :".var_export($array, true));
		}
	}
	
	/**
	 * Encode an object as XML string
	 *
	 * @param Object $obj
	 * @param string $root_node
	 * 
	 * @return string $xml
	 * @since  XXX
	 */
	private function encodeObj($obj, $root_node = 'response') {
		$xml = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;
		$xml .= $this->encode($obj, $root_node, $depth = 0);
		return $xml;
	}
	
	
	/**
	 * Encode an object as XML string
	 *
	 * @param Object|array $data
	 * @param string       $root_node
	 * @param int          $depth Used for indentation
	 * 
	 * @return string $xml
	 * @since  XXX
	 */
	private function encode($data, $node, $depth) {
		$xml = str_repeat("\t", $depth);
		$xml .= "<{$node}>" . PHP_EOL;
		foreach($data as $key => $val) {
			if(is_array($val) || is_object($val)) {
				$xml .= self::encode($val, $key, ($depth + 1));
			} else {
				$xml .= str_repeat("\t", ($depth + 1));
				$xml .= "<{$key}>" . htmlspecialchars($val) . "</{$key}>" . PHP_EOL;
			}
		}
		$xml .= str_repeat("\t", $depth);
		$xml .= "</{$node}>" . PHP_EOL;
		return $xml;
	}
	
}