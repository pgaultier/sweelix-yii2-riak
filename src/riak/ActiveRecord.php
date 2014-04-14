<?php
/**
 * File ActiveRecord.php
 *
 * PHP version 5.3+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak
 */
namespace sweelix\yii2\nosql\riak;

use yii\base\InvalidCallException;
use Yii;
use yii\base\NotSupportedException;

/**
 * Class ActiveRecord
 *
 * This class handle all the records and mimic classic
 * sql ActiveRecord management
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak
 * @since     XXX
 */
abstract class ActiveRecord extends ActiveRecordDynamic implements ActiveRecordInterface
{

    /**
     * @inheritdoc
     */
    public static function find()
    {
        $args = func_get_args();

        if (empty($args)) {
            $ret = parent::find(static::bucketName());
        } else {
            $ret = parent::find(static::bucketName(), $args[0]);
        }
        return $ret;
    }

    /**
     * @inheritdoc
     */
    public static function findOneByIndex($indexName, $indexValue, $bucketName = null)
    {
        return parent::findOneByIndex($indexName, $indexValue, static::bucketName());
    }

    /**
     * @inheritdoc
     */
    public static function findAllByIndex($indexName, $indexValue, $indexEndValue, $bucketName = null)
    {
        return parent::findAllByIndex($indexName, $indexValue, $indexEndValue, static::bucketName());
    }

    /**
     * @inheritdoc
     */
    public function setBucket($bucketName)
    {
        throw new InvalidCallException('Bucket name should be static (Declared in bucketName())');
    }

    /**
     * @inheritdoc
     */
    public function getBucket()
    {
        return static::bucketName();
    }

    /*public function hasMany($arClass, $riakTag)
    {
        return parent::hasManyInBucket($arClass, $riakTag, static::bucketName());
    }

    public function hasOne($arClass, $riakTag)
    {
        return parent::hasManyInBucket($arClass, $riakTag, static::bucketName());
    }*/
}
