<?php
namespace sweelix\yii2\nosql\tests\data;

use sweelix\yii2\nosql\riak\ActiveRecord;
use sweelix\yii2\nosql\riak\IndexType;
use Yii;

/**
 *
 * @author clatour
 *
 * @property string $userLogin
 * @property string $userFirstname
 * @property string $userLastname
 * @property string $userAge
 * @property string $userDateCreate
 * @property User   $mom
 * @property array  $friends
 */
class User extends ActiveRecord
{

    public static function isKeyMandatory()
    {
        return true;
    }

    public static function bucketName()
    {
        return 'unitTestUser';
    }

    public static function attributeNames()
    {
        return [
            'userLogin' => ['autoIndex' => IndexType::TYPE_BIN],
            'userFirstname',
            'userLastname'
        ];
    }

    public static function resolverClassName()
    {
        return null;
    }

    public static function getDb()
    {
        return Yii::$app->riak;
    }

    public static function indexNames()
    {
        return [
            'userAge' => IndexType::TYPE_INTEGER,
            'userTest' //NOT USED IN TEST
        ];
    }

    public static function metadataNames()
    {
        return [
            'userDateCreate'
        ];
    }

    public function rules()
    {
        // TODO: you should only define rules for those attributes that will receive user inputs.
        return [
            [['userLogin', 'userFirstname', 'userLastname', 'userAge', 'userDateCreate'], 'required'],
        ];
    }

    public function scenarios()
    {
        return [
            'default' => ['userLogin', 'userFirstname', 'userLastname', 'userAge', 'userDateCreate'],
        ];
    }

    public function getMom()
    {
        return $this->hasOne(User::className(), 'mom');
    }

    public function getFriends()
    {
        return $this->hasMany(User::className(), 'friend');
    }

    public function getSuperFriends()
    {
        return 'toto';
    }
}
