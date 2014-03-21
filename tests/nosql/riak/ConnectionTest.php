<?php
/**
 * ConnectionTest.php
 *
 * PHP version 5.3+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://code.ibitux.net/redlix/
 * @category  tests
 * @package   sweelix.yii2.nosql.tests
 */

namespace redlix\tests\unit\nosql\riak;

use sweelix\yii2\nosql\tests\TestCase;
use yii\base\InvalidConfigException;
use sweelix\yii2\nosql\riak\QueryBuilder;

/**
 * Class Connection tests the riak connection.
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
class ConnectionTest extends TestCase {

	private $exceptedQueryBuilderClass = 'sweelix\yii2\nosql\riak\QueryBuilder';
	private $exceptedCommandClass = 'sweelix\yii2\nosql\riak\Command';
	
	private $exceptedInitialValue = array(
		'getR' => 2,
		'getW' => 2,
		'getDw' => 2
	);
	
	private $setters = array(
		'setR' => 3,
		'setW' => 3,
		'setDw' => 3	
	);
	
	
	protected function setUp() {
		parent::setUp();
		$this->mockApplication(require(__DIR__.'/../../data/web.php'));
	}
	
	protected function tearDown() {
		parent::tearDown();
	}
	
	public function testOpenShouldSucceed() {
		$this->assertInstanceOf('sweelix\yii2\nosql\riak\Connection', \Yii::$app->riak);
		try {
			\Yii::$app->riak->open();
			$this->assertTrue(\Yii::$app->riak->isActive);
			
			//TEST INITIAL VALUE
			foreach ($this->exceptedInitialValue as $getter => $exceptedValue) {
				$this->assertEquals($exceptedValue, \Yii::$app->riak->$getter());
			}

			//TEST SETTER
			foreach ($this->setters as $setter => $value) {
				\Yii::$app->riak->$setter($value);
				$getter = str_replace('set', 'get', $setter);
				$this->assertEquals($value, \Yii::$app->riak->$getter());
			}
		} catch (\Exception $e) {
			$this->assertTrue(false, $e->getMessage());
		}
	}
	
	public function testOpenShouldFailed() {
		try {
			\Yii::$app->riakError->open();
			$this->assertTrue(false);
		} catch (\Exception $e) {
			$this->assertFalse(\Yii::$app->riakError->isActive);
		}
		
		try {
			\Yii::$app->riakError->dsn = '';
			$this->assertEmpty(\Yii::$app->riakError->dsn);
			\Yii::$app->riakError->open();
			$this->assertTrue(false);
		} catch (\Exception $e) {
			$this->assertFalse(\Yii::$app->riakError->isActive);
			$this->assertInstanceOf('yii\base\InvalidConfigException', $e);
			$this->assertEquals('Connection::dsn cannot be empty.', $e->getMessage());
		}
		
		try {
			\Yii::$app->nosqlError->open();
			$this->assertTrue(false);
		} catch (\Exception $e) {
			$this->assertFalse(\Yii::$app->nosqlError->isActive);
		}
	}
	
	public function testGetQueryBuilder() {
		$qb = \Yii::$app->riak->getQueryBuilder();
		$this->assertInstanceOf('sweelix\yii2\nosql\riak\QueryBuilder', $qb);
	}
	
	public function testCreateCommand() {
		$command = \Yii::$app->riak->createCommand();
		$this->assertInstanceOf('sweelix\yii2\nosql\riak\Command', $command);
		$this->assertEmpty($command->commandData);
	}
	
	public function testClose() {
		if (!\Yii::$app->riak->isActive) {
			\Yii::$app->riak->open();
			$this->assertTrue(\Yii::$app->riak->isActive);
		}
		\Yii::$app->riak->close();
		$this->assertFalse(\Yii::$app->riak->isActive);
		
		$this->assertFalse(\Yii::$app->riakError->isActive);
	}
}