<?php
namespace sweelix\yii2\nosql\tests\data;

use sweelix\yii2\nosql\riak\ActiveRecord;
use sweelix\yii2\nosql\riak\IndexType;
use Yii;

/**
 * @author clatour
 *
 * @property string $companyName
 * @property string $companySiret
 * @property string $companyCity
 * @property int    $companyNbEmployees
 * @property string $companyDateCreate
 */
class Company extends ActiveRecord
{

    public static function isKeyMandatory()
    {
        return false;
    }

    public static function bucketName()
    {
        return 'unitTestCompany';
    }

    public static function attributeNames()
    {
        return [
            'companyName' => ['autoIndex' => IndexType::TYPE_BIN],
            'companySiret',
            'companyCity'
        ];
    }

    public static function getDb()
    {
        return Yii::$app->riak;
    }

    public static function indexNames()
    {
        return [
            'companyNbEmployees' => IndexType::TYPE_INTEGER
        ];
    }

    public static function metadataNames()
    {
        return [
            'companyDateCreate',
        ];
    }

    /**
     * Validation rules for model attributes
     *
     * @return array validation rules for model attributes.
     * @since XXX
     */
    public function rules()
    {
        // TODO: you should only define rules for those attributes that will receive user inputs.
        return [
            [['companyName', 'companySiret', 'companyCity', 'companyNbEmployees', 'companyDateCreate'], 'required'],
        ];
    }

    public function scenarios()
    {
        return [
            'default' => ['companyName', 'companySiret', 'companyCity', 'companyNbEmployees', 'companyDateCreate'],
        ];
    }

    public function getEmployees()
    {
        return $this->hasMany(User::className(), 'employees');
    }

    public function getBoss()
    {
        return $this->hasOne(User::className(), 'boss');
    }
}
