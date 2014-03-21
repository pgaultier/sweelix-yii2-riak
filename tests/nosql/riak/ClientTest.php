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
 * @package   sweelix.yii2.nosql.tests
 */

namespace redlix\tests\unit\nosql\riak;

use sweelix\yii2\nosql\tests\TestCase;

/**
 * Class ClientTest tests the riak client
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

	
	protected function setUp() {
		parent::setUp();
		$this->mockApplication(require(__DIR__.'/../../data/web.php'));
	}
	
	protected function tearDown() {
		parent::tearDown();
	}
	
	public function testTest() {
		
	}
}