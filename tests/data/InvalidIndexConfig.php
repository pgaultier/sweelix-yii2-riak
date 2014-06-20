<?php
namespace sweelix\yii2\nosql\tests\data;

use sweelix\yii2\nosql\riak\ActiveRecord;
use Yii;

/**
 *
 * @author clatour
 */
class InvalidIndexConfig extends ActiveRecord
{

    public static function isKeyMandatory()
    {
        return true;
    }

    public static function bucketName()
    {
        return 'noneed';
    }

    public static function attributeNames()
    {
        return [
        ];
    }

    public static function getDb()
    {
        return Yii::$app->riak;
    }

    public static function indexNames()
    {
        return [
            'indexName' => 'indexTypeInvalid'
        ];
    }

    public static function metadataNames()
    {
        return [
        ];
    }

    public function rules()
    {
        // TODO: you should only define rules for those attributes that will receive user inputs.
        return [
        ];
    }

    public function scenarios()
    {
        return [
        ];
    }
}
