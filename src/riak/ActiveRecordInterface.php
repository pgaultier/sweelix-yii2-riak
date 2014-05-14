<?php
/**
 * File ActiveRecordInterface.php
 *
 * PHP version 5.4+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2014 Ibitux
 * @license   http://www.ibitux.com/license license
 * @version   XXX
 * @link      http://www.ibitux.com
 * @category  nosql
 * @package   sweelix.nosql.riak
 */
namespace sweelix\yii2\nosql\riak;

use yii\db\ActiveRecordInterface as BaseActiveRecordInterface;

/**
 * Class ActiveRecordInterface
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2014 Ibitux
 * @license   http://www.ibitux.com/license license
 * @version   XXX
 * @link      http://www.ibitux.com
 * @category  nosql
 * @package   sweelix.nosql.riak
 * @since     XXX
 */
interface ActiveRecordInterface extends BaseActiveRecordInterface
{
    /**
     * Returns the bucketName of the activeRecord
     *
     * @return string
     * @since  XXX
     */
    public function getBucketName();

    public function metadata();

    public function hasMetadatum($name);

    public function getMetadatum($name);

    public function getMetadata();

    public function setMetadata($name, $value);

    public function indexes();

    public function hasIndex($name);

    public function getIndex($name);

    public function getIndexes();

    public function setIndex($name, $value);

    /**
     * Returns whether key is mandatory.
     *
     * @return boolean
     * @since  XXX
     */
    public static function isKeyMandatory();

    /**
     * Define the attributes of the active record
     *
     * @return array
     * @since  XXX
     */
    public static function attributeNames();

    /**
     * Define the indexes of the active record
     *
     * @return array
     * @since  XXX
     */
    public static function indexNames();

    /**
     * Define the metadata of the active record
     *
     * @return array
     * @since  XXX
     */
    public static function metadataNames();

    /**
     * Return wheteher the ActiveRecord bucketName is dynamic or not
     */
    public static function isDynamicRecord();
}
