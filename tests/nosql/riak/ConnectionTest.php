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
 * @package   application.test.unit.nosql.riak
 */
namespace redlix\tests\unit\nosql\riak;

use Exception;
use sweelix\yii2\nosql\tests\TestCase;

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
class ConnectionTest extends TestCase
{

    private $exceptedQueryBuilderClass = 'sweelix\yii2\nosql\riak\QueryBuilder';

    private $exceptedCommandClass = 'sweelix\yii2\nosql\riak\Command';

    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication(require (__DIR__ . '/../../data/web.php'));
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testOpenShouldSucceed()
    {
        $this->assertInstanceOf('sweelix\yii2\nosql\riak\Connection', \Yii::$app->riak);
        try {
            \Yii::$app->riak->open();
            $this->assertTrue(\Yii::$app->riak->isActive);

        } catch (\Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    public function testOpenShouldFailed()
    {
        $this->setExpectedException('Exception');
        \Yii::$app->riakError->open();

        \Yii::$app->riakError->dsn = '';
        $this->assertEmpty(\Yii::$app->riakError->dsn);
        $this->setExpectedException('Exception');
        \Yii::$app->riakError->open();
    }

    public function testGetQueryBuilder()
    {
        $qb = \Yii::$app->riak->getQueryBuilder();
        $this->assertInstanceOf('sweelix\yii2\nosql\riak\QueryBuilder', $qb);
    }

    public function testCreateCommand()
    {
        $command = \Yii::$app->riak->createCommand();
        $this->assertInstanceOf('sweelix\yii2\nosql\riak\Command', $command);
        $this->assertEmpty($command->commandData);
    }

    public function testClose()
    {
        if (! \Yii::$app->riak->isActive) {
            \Yii::$app->riak->open();
            $this->assertTrue(\Yii::$app->riak->isActive);
        }
        \Yii::$app->riak->close();
        $this->assertFalse(\Yii::$app->riak->isActive);

    }
}
