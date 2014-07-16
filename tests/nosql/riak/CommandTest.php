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
use sweelix\yii2\nosql\riak\IndexType;
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
 * @category  tests
 * @package   application.test.unit.nosql.riak
 */
class CommandTest extends TestCase
{

    private $command;

    private $bucketName = 'riakBucketTest';

    private $objectName = 'riakObjectTest';

    private $objectData = array(
        'objectDataKey' => 'objectDataValue'
    );

    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication(require (__DIR__ . '/../../data/web.php'));
        $this->command = \Yii::$app->riak->createCommand();
    }

    public function testInit()
    {
        $this->assertInstanceOf('sweelix\yii2\nosql\riak\Command', $this->command);
        $this->assertInstanceOf('sweelix\yii2\nosql\riak\Connection', $this->command->noSqlDb);
        $this->assertEmpty($this->command->getCommandData());
    }

    /**
     * Basic
     * insert
     */
    public function testInsert()
    {
        $this->command->setMode('insert');
        $this->command->setBucket($this->bucketName);
        $this->command->setKey($this->objectName);
        $this->command->setData($this->objectData);//->execute();
        //OR
        $response = $this->command->insert($this->bucketName, $this->objectName, $this->objectData)->execute();
        $this->resetCommand();


        $this->checkResponseIntegrity($response);
        $this->assertEquals($this->bucketName, $response['bucket']);
        $this->assertEquals($this->objectName, $response['key']);
        $this->assertNotEmpty($response['values']);
        $object = $response['values'][0];
        $this->checkObjectIntegrity($object);
    }

    public function testInsertWithoutKey()
    {
        $response = $this->command->insert($this->bucketName, null, $this->objectData)->execute();
        $this->checkResponseIntegrity($response);
        $object = $response['values'][0];
        $this->checkObjectIntegrity($object);

        $response = $this->command->delete($response['bucket'], $response['key'])->execute();
        $this->assertTrue($response);
    }

    public function testInsertWithMetaAndLinksAndIndexes()
    {
        $response = $this->command->insert($this->bucketName, $this->objectName.'WithMeta', $this->objectData)
        ->addMetadata('metaName', 'metaValue')
        ->addLink($this->bucketName, $this->objectName, 'parent')
        ->addIndex('indexNameBin', 'indexValue', IndexType::TYPE_BIN)
        ->addIndex('indexNameInt', 20, IndexType::TYPE_INTEGER)
        ->execute();


        $this->checkResponseIntegrity($response);
        $object = $response['values'][0];


        $this->checkObjectIntegrity(
            $object,
            true,
            ['Metaname' => 'metaValue'],
            [
                [$this->bucketName, $this->objectName, 'parent']
            ],
            [
                'indexnameint_int' => 20,
                'indexnamebin_bin' => 'indexValue'
            ]
        );


        $metadata = $object['metadata'];

        //CHECK COUNT INDEX, LINKS AND META
        $this->assertCount(2, $metadata['index']);
        $this->assertCount(1, $metadata['Links']);
        $this->assertCount(1, $metadata['X-Riak-Meta']);

        //CHECK THEIR VALUES
        $this->assertArrayHasKey('indexnamebin_bin', $metadata['index']);
        $this->assertEquals('indexValue', $metadata['index']['indexnamebin_bin']);
        $this->assertArrayHasKey('indexnameint_int', $metadata['index']);
        $this->assertEquals(20, $metadata['index']['indexnameint_int']);

        $this->assertEquals($this->bucketName, $metadata['Links'][0][0]);
        $this->assertEquals($this->objectName, $metadata['Links'][0][1]);
        $this->assertEquals('parent', $metadata['Links'][0][2]);

        $this->assertArrayHasKey('X-Riak-Meta-Metaname', $metadata['X-Riak-Meta']);
        $this->assertEquals('metaValue', $metadata['X-Riak-Meta']['X-Riak-Meta-Metaname']);

        $this->assertEquals('application/json', $metadata['content-type']);


        //FAILURE
        $this->resetCommand();
        $this->setExpectedException('\sweelix\yii2\nosql\riak\RiakException');
        $response = $this->command->insert($this->bucketName, $this->objectName.'Failure', $this->objectData)
        ->addIndex('indexFailure', 'failure', IndexType::TYPE_INTEGER)
        ->execute();
        //FAILURE CAUSE TRYING TO ADD INDEX OF TYPE BIN WHEN EXPECTED INT
    }

    /**
     * BASIC Select/Update
     */
    public function testSelectAndUpdate()
    {
        //SELECT
        $this->command->setMode('select');
        $this->command->setBucket($this->bucketName);
        $this->command->setKey($this->objectName);
        //OR
        $this->command->setCommandData(array(
            'mode' => 'select',
            'bucket' => $this->bucketName,
            'key' => $this->objectName
        ));

        $response = $this->command->execute();

        $this->checkResponseIntegrity($response);
        $object = $response['values'][0];
        $this->checkObjectIntegrity($object);



        $vclock = $response['vclock'];

        $commandData = [
            'mode' => 'update',
            'bucket' => $this->bucketName,
            'key' => $this->objectName,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Riak-Vclock' => $vclock
            ],
            'queryParams' => [
                'returnbody' => 'true',
                'w' => 1
            ],
            'data' => ['objectUpdated' => true]
        ];

        //UPDATE
        $this->command->update($this->bucketName, $this->objectName, array('objectUpdated' => true));
        $this->command->setHeaders([
            'X-Riak-Vclock' => $vclock,
            'Content-Type' => 'application/json'
        ]);
        $this->command->setQueryParams([
            'returnbody' => 'true',
            'w' => 1
        ]);
        $this->assertEquals($commandData, $this->command->commandData);

        //OR
        $this->resetCommand();
        $this->command->update($this->bucketName, $this->objectName, array('objectUpdated' => true))
        ->addQueryParameter('returnbody', 'true')
        ->addQueryParameter('w', 1)
        ->addHeaderField('X-Riak-Vclock', $vclock) //THOSE 2 LINES
        ->vclock($vclock)                          //DO THE SAME THING
        ->addHeaderField('Content-Type', 'application/json');
        $this->assertEquals($commandData, $this->command->commandData);

        //OR
        $this->command->setCommandData(array(
            'mode' => 'update',
            'bucket' => $this->bucketName,
            'key' => $this->objectName,
            'headers' => [
                'X-Riak-Meta-metaName' => 'metaValue',
                'X-Riak-Vclock' => $vclock
            ],
            'queryParams' => [
                'returnbody' => 'true'
            ]
        ));
        $response = $this->command->execute();

        $this->checkResponseIntegrity($response);
        $object = $response['values'][0];
        $this->checkObjectIntegrity($object);


        //TRYING TO UPDATE OBJECT WHITOUT HIS VCLOCK WILL RAISE AN EXCEPTION
        $this->setExpectedException('\sweelix\yii2\nosql\riak\RiakException');
        $this->command->update($this->bucketName, $this->objectName, array('objectUpdated' => 'willFail'))
        ->execute();
    }

    /**
     * SELECT/UPDATE COUNTER
     */
    public function testUpdateCounter()
    {
        $response = $this->command->updateCounter($this->bucketName, 'counterTest', 10)->execute();
        $this->assertTrue($response);

        $response = $this->command->setCommandData([
            'mode' => 'selectCounter',
            'bucket' => $this->bucketName,
            'key' => 'counterTest'
        ])->execute();
        $this->assertEquals(10, $response);

        $response = $this->command->updateCounter($this->bucketName, 'counterTest', -10)->execute();
        $this->assertTrue($response);

        $response = $this->command->setCommandData([
            'mode' => 'selectCounter',
            'bucket' => $this->bucketName,
            'key' => 'counterTest'
        ])->execute();
        $this->assertEquals(0, $response);
    }

    /**
     * SELECT/MODIFY BUCKET PROPERTIES
     */
    public function testBucketProps()
    {
        $response = $this->command->alterBucket($this->bucketName, [
            'allow_mult' => false
        ])->execute();
        $this->assertTrue($response);

        $response = $this->command->setCommandData([
            'mode' => 'selectBucketProps',
            'bucket' => $this->bucketName
        ])->execute();


        $this->assertArrayHasKey('allow_mult', $response);
        $this->assertArrayHasKey('basic_quorum', $response);
        $this->assertArrayHasKey('big_vclock', $response);
        $this->assertArrayHasKey('chash_keyfun', $response);
        $this->assertArrayHasKey('dw', $response);
        $this->assertArrayHasKey('last_write_wins', $response);
        $this->assertArrayHasKey('linkfun', $response);
        $this->assertArrayHasKey('n_val', $response);
        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('notfound_ok', $response);
        $this->assertArrayHasKey('old_vclock', $response);
        $this->assertArrayHasKey('postcommit', $response);
        $this->assertArrayHasKey('pr', $response);
        $this->assertArrayHasKey('precommit', $response);
        $this->assertArrayHasKey('pw', $response);
        $this->assertArrayHasKey('r', $response);
        $this->assertArrayHasKey('rw', $response);
        $this->assertArrayHasKey('small_vclock', $response);
        $this->assertArrayHasKey('w', $response);
        $this->assertArrayHasKey('young_vclock', $response);

        $this->assertFalse($response['allow_mult']);

        $response = $this->command->alterBucket($this->bucketName, [
            'allow_mult' => true
        ])->execute();
        $this->assertTrue($response);

        $response = $this->command->setCommandData([
            'mode' => 'selectBucketProps',
            'bucket' => $this->bucketName
        ])->execute();
        $this->assertTrue($response['allow_mult']);

    }

    /**
     * TEST SOME FAILS
     */
    public function testFail()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->command->setMode('UnknowMode');
    }

    public function testFail1()
    {
        $this->setExpectedException('\sweelix\yii2\nosql\riak\RiakException');
        $this->command->setCommandData(['mode' => 'UnknowMode'])->execute();
    }


    /**
     * DELETE
     */
    public function testDelete()
    {
        $response = $this->command->delete($this->bucketName, $this->objectName)->execute();
        $this->assertTrue($response);
        $response = $this->command->delete($this->bucketName, $this->objectName.'WithMeta')->execute();
        $this->assertTrue($response);
        //AVOID FAILED WHEN RELAUNCH SCRIPT
        sleep(3);
    }

    private function checkObjectIntegrity(
        $object,
        $checkData = false,
        $metaToCheck = array(),
        $linkToCheck = array(),
        $indexToCheck = array()
    ) {
        $this->assertArrayHasKey('metadata', $object);
        $this->assertArrayHasKey('data', $object);

        $metadata = $object['metadata'];

        $this->assertArrayHasKey('Links', $metadata);
        $this->assertArrayHasKey('X-Riak-Vtag', $metadata);
        $this->assertArrayHasKey('content-type', $metadata);
        $this->assertArrayHasKey('index', $metadata);
        $this->assertArrayHasKey('X-Riak-Last-Modified', $metadata);
        $this->assertArrayHasKey('X-Riak-Meta', $metadata);

        if ($checkData) {
            $this->assertCount(count($metaToCheck), $metadata['X-Riak-Meta']);
            $this->assertCount(count($indexToCheck), $metadata['index']);
            $this->assertCount(count($linkToCheck), $metadata['Links']);

            foreach ($metaToCheck as $name => $value) {
                $this->assertArrayHasKey('X-Riak-Meta-'.$name, $metadata['X-Riak-Meta']);
                $this->assertEquals($value, $metadata['X-Riak-Meta']['X-Riak-Meta-'.$name]);
            }

            $this->assertEquals($linkToCheck, $metadata['Links']);

            foreach ($indexToCheck as $name => $value) {
                $this->assertArrayHasKey($name, $metadata['index']);
                $this->assertEquals($value, $metadata['index'][$name]);
            }
        }

    }

    private function checkResponseIntegrity($response, $objectListCount = 1)
    {
        $this->assertArrayHasKey('bucket', $response);
        $this->assertNotEmpty($response['bucket']);
        $this->assertArrayHasKey('key', $response);
        $this->assertNotEmpty($response['key']);
        $this->assertArrayHasKey('vclock', $response);
        $this->assertNotEmpty($response['vclock']);
        $this->assertArrayHasKey('values', $response);
        $this->assertCount($objectListCount, $response['values']);
    }


    private function resetCommand()
    {
        $this->command = \Yii::$app->riak->createCommand();
    }
}
