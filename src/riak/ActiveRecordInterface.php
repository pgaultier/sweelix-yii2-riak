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

    /**
     * Returns the list of all metadata names of the record.
     *
     * ~~
     * [
     *  'metadataName1',
     *  'metadataName2'
     * ]
     * ~~
     *
     * @return array
     * @since  XXX
     */
    public function metadata();

    /**
     * Returns if the current activeRecord has a metadata named as $name
     *
     * @param string $name The metadata name to check
     *
     * @return boolean whether metadata exist
     * @since  XXX
     */
    public function hasMetadatum($name);

    /**
     * Returns the value of the current activeRecord metadata name ($name)
     *
     * @param string $name The metadata name
     *
     * @return mixed The value of the metadata named $name
     * @since  XXX
     */
    public function getMetadata($name);

    /**
     * Set the activeRecord's metadatum named $name with the value $value
     *
     * @param string $name  The metadata name to set
     * @param mixed  $value The value to set
     *
     * @return void
     * @since  XXX
     */
    public function setMetadata($name, $value);

    /**
     * Return the avtiveRecord's indexes as an array
     *
     * ~~
     * [
     *  'indexName1' => 'indexValue1',
     *  'indexName2' => 'indexValue2',
     * ]
     * ~~
     *
     * @return array
     * @since  XXX
     */
    public function indexes();

    /**
     * Returns if the current activeRecord has an index named as $name
     *
     * @param string $name The index name to check
     *
     * @return boolean
     * @since  XXX
     */
    public function hasIndex($name);

    /**
     * Returns the value of the current activeRecord index name ($name)
     *
     * @param string $name
     *
     * @return mixed The index value of index named $name
     * @since  XXX
     */
    public function getIndex($name);

    /**
     * Sets the named index value.
     *
     * @param string $name  The index name
     * @param mixed  $value The index value
     *
     * @return void
     * @since  XXX
     */
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
}
