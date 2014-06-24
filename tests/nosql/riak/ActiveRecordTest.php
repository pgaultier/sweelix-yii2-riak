<?php
/**
 * SiteController.php
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

use sweelix\yii2\nosql\tests\TestCase;
use sweelix\yii2\nosql\tests\data\User;
use sweelix\yii2\nosql\riak\ActiveRecord;
use sweelix\yii2\nosql\tests\data\Company;
use sweelix\yii2\nosql\riak\KeyFilter;
use sweelix\yii2\nosql\tests\data\InvalidIndexConfig;

/**
 * SiteController is the base controller of the applicaiton
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
class ActiveRecordTest extends TestCase
{

    const BUCKET_NAME = 'user';

    const OBJECT_KEY = 'user';

    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication(require (__DIR__ . '/../../data/web.php'));
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testSetterGetter()
    {
        $user = new User();

        $date = date('c');

        $user->setAttributes([
//            'userLogin' => 'clatour@ibitux.com', SETTED BY INDEX CAUSE AUTOINDEXED
            'userFirstname' => 'Christophe',
            'userLastname' => 'Latour',
        ]);

        $indexes = ['userAge' => 23, 'userLogin' => 'clatour@ibitux.com'];

        $user->setIndexes($indexes);


        $meta = ['userDateCreate' => $date];

        $user->setMetadata($meta);

        $this->assertEquals('clatour@ibitux.com', $user->userLogin);
        $this->assertEquals('Christophe', $user->userFirstname);
        $this->assertEquals('Latour', $user->userLastname);
        $this->assertEquals(23, $user->userAge);
        $this->assertEquals(23, $user->indexes['userAge']);
        $this->assertEquals(23, $user->getIndex('userAge'));
        $this->assertEquals($date, $user->getMetadata('userDateCreate'));
        $this->assertEquals($date, $user->userDateCreate);

        $this->assertEquals($indexes, $user->indexes);
        $this->assertEquals($meta, $user->metadata);


        //DO THE SAME WITH OTHER SETTER
        $user = new User();

        $user->userLogin = 'clatour@ibitux.com';
        $user->userLastname = 'Latour';
        $user->userFirstname = 'Christophe';
        $user->userAge = 23;
        $user->userDateCreate = $date;

        $this->assertEquals('clatour@ibitux.com', $user->userLogin);
        $this->assertEquals('Christophe', $user->userFirstname);
        $this->assertEquals('Latour', $user->userLastname);
        $this->assertEquals(23, $user->userAge);
        $this->assertEquals($date, $user->userDateCreate);

        //DO THE SAME WITH OTHER SETTER
        $user = new User();

        $user->setAttribute('userLogin', 'clatour@ibitux.com');
        $user->setAttribute('userLastname', 'Latour');
        $user->setAttribute('userFirstname', 'Christophe');
        $user->setIndex('userAge', 23);
        $user->setMetadata('userDateCreate', $date);

        $this->assertEquals('clatour@ibitux.com', $user->userLogin);
        $this->assertEquals('Christophe', $user->userFirstname);
        $this->assertEquals('Latour', $user->userLastname);
        $this->assertEquals(23, $user->userAge);
        $this->assertEquals($date, $user->userDateCreate);
    }

    public function testInsertGetWithoutKey()
    {
        $recordWithRiakKey = new Company();

        $recordWithRiakKey->companyName = 'Ibitux';
        $recordWithRiakKey->companySiret = '52381032300023';
        $recordWithRiakKey->companyCity = 'Paris';
        $recordWithRiakKey->companyNbEmployees = 7;
        $recordWithRiakKey->companyDateCreate = date('c');

        $this->assertTrue($recordWithRiakKey->isNewRecord);
        $this->assertTrue($recordWithRiakKey->validate());
        $this->assertEquals(1, $recordWithRiakKey->save());


        $this->assertNotEmpty($recordWithRiakKey->key);
        $this->assertFalse($recordWithRiakKey->isNewRecord);

        $this->assertTrue($recordWithRiakKey->delete());
    }

    public function testInsert()
    {
        $christophe = new User();

        $christophe->key = 'clatour@ibitux.com|23|20130202';
        $christophe->userLogin = 'clatour@ibitux.com';
        $christophe->userFirstname = 'Christophe';
        $christophe->userLastname = 'Latour';
        $christophe->userAge = 23;
        $christophe->userDateCreate = date('c');

        if ($christophe->validate()) {
            $christophe->save();
        }

        $damien = new User();

        $damien->key = 'ddesplats@ibitux.com|27|20131224';
        $damien->userLogin = 'ddesplats@ibitux.com';
        $damien->userFirstname = 'Damien';
        $damien->userLastname = 'Desplats';
        $damien->userAge = 27;
        $damien->userDateCreate = date('c');

        if ($damien->validate()) {
            $damien->save();
        }

        $cyril = new User();

        $cyril->key = 'cmarois@ibitux.com|26|20140101';
        $cyril->userLogin = 'cmarois@ibitux.com';
        $cyril->userFirstname = 'Cyril';
        $cyril->userLastname = 'Marois';
        $cyril->userAge = 26;
        $cyril->userDateCreate = date('c');

        if ($cyril->validate()) {
            $cyril->save();
        }

    }

    public function testFindByKey()
    {
        $christophe = User::findOne('clatour@ibitux.com|23|20130202');
        $this->assertInstanceOf('sweelix\yii2\nosql\riak\ActiveRecord', $christophe);
        $christophe instanceof ActiveRecord;
        $this->assertEquals('clatour@ibitux.com|23|20130202', $christophe->key);
        $this->assertFalse($christophe->isNewRecord);
        $exceptedAttr = [
            'userLogin' => 'clatour@ibitux.com',
            'userFirstname' => 'Christophe',
            'userLastname' => 'Latour'
        ];
        $this->assertEquals($exceptedAttr, $christophe->attributes);
        $exceptedIndexes = [
            'userLogin' => 'clatour@ibitux.com',
            'userAge' => 23
        ];
        $this->assertEquals($exceptedIndexes, $christophe->indexes);


        //NOT FOUND
        $userNotFound = User::findOne('userNotFound');
        $this->assertNull($userNotFound);
    }


    public function testUpdate()
    {
        $christophe = User::findOne('clatour@ibitux.com|23|20130202');

        $this->assertInstanceOf('sweelix\yii2\nosql\riak\ActiveRecord', $christophe);
        $christophe instanceof User;
        $this->assertEquals('clatour@ibitux.com', $christophe->userLogin);
        $this->assertEquals(23, $christophe->userAge);

        $christophe->userLogin = 'clat@ibitux.com';
        $christophe->userAge = 24;

        $this->assertTrue($christophe->validate());
        $this->assertEquals(1, $christophe->save());

        $this->assertEquals('clat@ibitux.com', $christophe->userLogin);
        $this->assertEquals(24, $christophe->userAge);
    }


    public function testFindByKeyFilter()
    {
        $keyFilter = new KeyFilter();
        $keyFilter->tokenize('|', 1)->startsWith('c')->and()->endsWith('m')->and()->startsWith('clatour');
        $users = User::findByKeyFilter($keyFilter);

        $this->assertCount(1, $users);
        $this->assertEquals('clatour@ibitux.com|23|20130202', $users[0]->key);

        $keyFilter->reset();
        $keyFilter->tokenize('|', 1)->startsWith('c');
        $users = User::findByKeyFilter($keyFilter);

        $this->assertCount(2, $users);


        $keyFilter->reset();

        $keyFilter->tokenize('|', 2)->between("23", "27");
        $users = User::findByKeyFilter($keyFilter);

        $this->assertCount(1, $users);

        $keyFilter->reset();
        $keyFilter->tokenize('|', 2)->between("23", "27", true);
        $users = User::findByKeyFilter($keyFilter);

        $this->assertCount(3, $users);


        $keyFilter->reset();
        $keyFilter->tokenize('|', 1)->setMember(['clatour@ibitux.com', 'cmarois@ibitux.com']);
        $users = User::findByKeyFilter($keyFilter);

        $this->assertCount(2, $users);
    }

    public function testFindByIndex()
    {
        $users = User::findByIndex('userAge', 24, 37);

        $this->assertCount(3, $users);
        foreach ($users as $user) {
            $this->assertInstanceOf('sweelix\yii2\nosql\riak\ActiveRecord', $user);
        }

    }

    public function testLink()
    {
        $christophe = User::findOne('clatour@ibitux.com|23|20130202');
        $damien = User::findOne('ddesplats@ibitux.com|27|20131224');
        $cyril = User::findOne('cmarois@ibitux.com|26|20140101');

        $christophe->link('friends', $damien);
        $christophe->link('friends', $cyril);


        $this->assertEquals(1, $christophe->save());

        $christophe = null;
        $christophe = User::findOne('clatour@ibitux.com|23|20130202');
        $this->assertCount(2, $christophe->friends);

        //TRY TO RELINK EXISTING RELATION
        $christophe->link('friends', $cyril);
        $this->assertEquals(1, $christophe->save());
        $this->assertCount(2, $christophe->friends);

        foreach ($christophe->friends as $friend) {
            $this->assertInstanceOf('sweelix\yii2\nosql\riak\ActiveRecord', $friend);
            $this->assertEmpty($friend->friends);
        }


        $mom = new User();

        $mom->key = 'mom';
        $mom->userLogin = 'mom@ibitux.com';
        $mom->userFirstname = 'mom';
        $mom->userLastname = 'mom';
        $mom->userAge = 53;
        $mom->userDateCreate = date('c');

        $this->assertTrue($mom->validate());
        $this->assertEquals(1, $mom->save());
        //LINK ONE

        $christophe->link('mom', $mom);
        $this->assertEquals(1, $christophe->save());

        $momTmp = $christophe->mom;
        $this->assertTrue($mom->equals($momTmp));
    }

    public function testUnlink()
    {
        $christophe = User::findOne('clatour@ibitux.com|23|20130202');
        $damien = User::findOne('ddesplats@ibitux.com|27|20131224');
        $cyril = User::findOne('cmarois@ibitux.com|26|20140101');


        $christophe->unlink('friends', $cyril);

        $this->assertCount(2, $christophe->friends);
        $this->assertEquals(1, $christophe->save());
        $friends = $christophe->friends;
        $this->assertCount(1, $friends);

        $this->assertTrue($damien->equals($friends[0]));

        $christophe->unlink('friends', $damien);
        $this->assertEquals(1, $christophe->save());
        $this->assertEmpty($christophe->friends);

        $mom = User::findOne('mom');
        $this->assertTrue($mom->equals($christophe->mom));
        $christophe->unlink('mom', $mom);
        $this->assertTrue($mom->equals($christophe->mom));
        $this->assertEquals(1, $christophe->save());
        $this->assertNull($christophe->mom);
    }

    public function testEquals()
    {
        $user = User::findOne('mom');
        $user2 = User::findOne('clatour@ibitux.com|23|20130202');
        $user3 = User::findOne('clatour@ibitux.com|23|20130202');

        $this->assertTrue($user2->equals($user3));
        $this->assertFalse($user->equals($user3));

        $new = new User();
        $new2 = new User();

        $new->userFirstname = 'toto';
        $new2->userFirstname = 'toto';
        $this->assertFalse($new->equals($new2));
    }


    public function testFail()
    {

        $user = new User();

        $this->assertNull($user->userAge);
        $this->assertNull($user->userDateCreate);
        $this->assertNull($user->userLogin);


        $user->userAge = 12;
        $user->userLogin = 'test';
        $user->userFirstname = 'test';
        $user->userLastname = 'test';
        $user->userDateCreate = date('c');
        $this->setExpectedException('Exception'); //CAUSE NO KEY WAS SETTED
        $user->save();
    }

    public function testFail1()
    {
        $this->setExpectedException('yii\base\NotSupportedException');
        User::findAll('test');
    }

    public function testFail2()
    {
        $this->setExpectedException('yii\base\NotSupportedException');
        User::primaryKey();
    }

    public function testFail3()
    {
        $this->setExpectedException('yii\base\InvalidCallException');
        $user = new User();
        $user->delete();
    }

    public function testFail4()
    {
        $this->setExpectedException('InvalidArgumentException');
        User::findByIndex('indexNotValid', 'test');
    }

    public function testFail5()
    {
        $user = new User();
        $user2 = new User();

        $this->setExpectedException('yii\base\InvalidCallException');
        //CAN'T LINK 2 NEW RECORD
        $user->link('friends', $user2);
    }

    public function testFail6()
    {
        $user = User::findOne('mom');
        $user2 = new User();

        $this->setExpectedException('yii\base\InvalidCallException');
        //CAN'T LINK ONE FETCHED RECORD WITH ANOTHER NEW RECORD (JUST SAVE IT BEFORE)
        $user->link('friends', $user2);

        //SHOULD DO $user2->save() then $user->link('friends', $user2);
    }

    public function testFail7()
    {
        $user = new User();

        $this->setExpectedException('yii\base\InvalidParamException');
        $user->setIndex('toto', 'titi');
    }

    public function testFail8()
    {
        $user = new User();

        $this->setExpectedException('yii\base\InvalidParamException');
        $user->setMetadata('toto', 'titi');
    }

    public function testFail9()
    {
        $invalidRecord = new InvalidIndexConfig();

        $this->setExpectedException('yii\base\InvalidConfigException');
        $invalidRecord->indexes;
    }

    public function testFail10()
    {
        $meta = ['userDateCreate' => null];
        $user = new User();

        $user->setMetadata($meta);
        $this->assertNull($user->userDateCreate);

        $meta = ['metaNotFound' => null];
        $this->setExpectedException('yii\base\InvalidParamException');
        $user->setMetadata($meta);
    }

    public function testFail11()
    {
        $user = User::findOne('mom');
        $user2 = User::findOne('clatour@ibitux.com|23|20130202');

        $this->setExpectedException('\yii\base\InvalidParamException');
        $user->link('inexistantRelation', $user2);
    }

    public function testFail12()
    {
        $user = User::findOne('mom');
        $user2 = User::findOne('clatour@ibitux.com|23|20130202');

        $this->setExpectedException('yii\base\InvalidParamException');
        $user->link('superFriends', $user2);
    }

    public function testFail13()
    {
        $keyFilter = new KeyFilter();

        $this->setExpectedException('yii\base\InvalidCallException');
        $keyFilter->equals('test')->and()->or()->between('28', '27', true);
    }

    public function testFail14()
    {
        $keyFilter = new KeyFilter();

        $this->setExpectedException('yii\base\InvalidCallException');
        $keyFilter->invalidCall();

    }

    public function testFail15()
    {
        $keyFilter = new KeyFilter();

        $keyFilter->equals('clatour@ibitux.com');
        $this->setExpectedException('yii\base\InvalidConfigException');
        $keyFilter->build();
    }

    public function testDelete()
    {
        $response = User::findOne('clatour@ibitux.com|23|20130202')->delete();
        $this->assertTrue($response);
        $response = User::findOne('cmarois@ibitux.com|26|20140101')->delete();
        $this->assertTrue($response);
        $response = User::findOne('ddesplats@ibitux.com|27|20131224')->delete();
        $this->assertTrue($response);
        $this->assertTrue(User::findOne('mom')->delete());
    }
}
