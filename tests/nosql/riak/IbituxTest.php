<?php
/**
 * IbituxTest.php
 *
 * PHP version 5.4+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://code.ibitux.net/redlix/
 * @category  controllers
 * @package   application.redlix.controllers
 */

namespace redlix\tests\unit\nosql\riak;

use sweelix\yii2\nosql\tests\TestCase;
/**
 * IbituxTest.php
 *
 * PHP version 5.4+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://code.ibitux.net/redlix/
 * @category  controllers
 * @package   application.redlix.controllers
 */
class IbituxTest extends TestCase { 
	
	protected function setUp() {
		parent::setUp();
		$this->mockApplication(require(__DIR__.'/../../data/web.php'));
	}
	
	
	public function testInsertWithCommand() {
		//INSERT WITH RAW COMMAND
		$response = \Yii::$app->riak->createCommand()->setCommandData(array(
			'mode' => 'insert',
			'bucket' => 'user',
			'key' => 'user1',
			'headers' => array(
				'X-Riak-Index-rate_int' => 1,
				'X-Riak-Meta-lastname' => 'Latour',
			),
			'queryParams' => array(
				'returnbody' => true
			)
		))->execute();
	
		var_dump($response->current());
	}
	
	public function testDeleteWithCommand() {
		$response = \Yii::$app->riak->createCommand()->setCommandData(array(
			'mode' => 'delete',
			'bucket' => 'user',
			'key' => 'user1'
		))->execute();
		var_dump($response->current());
	}
}