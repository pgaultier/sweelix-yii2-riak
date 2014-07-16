<?php
/**
 * File ActiveRecordInterface.php
 *
 * PHP version 5.4+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @author    Philippe Gaultier <pgaultier@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak
 */

namespace sweelix\yii2\nosql\riak;

use yii\db\ActiveRecordInterface as BaseActiveRecordInterface;

/**
 * Class ActiveRecordInterface
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @author    Philippe Gaultier <pgaultier@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak
 * @since     XXX
 */
interface ActiveRecordInterface extends BaseActiveRecordInterface
{
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
    public function hasMetadata($name);

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
     * If $value is null, Sets the metadata values in a massive way.
     *
     * @param string $name  The metadata name to set
     * @param mixed  $value The value to set
     *
     * @return void
     * @since  XXX
     */
    public function setMetadata($name, $value = null);

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
    public function hasIndex(&$name);

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
     * Sets the indexes values in a massive way.
     *
     * @param array $values Values as $name => $value to set.
     *
     * @return void
     * @since  XXX
     */
    public function setIndexes($values);

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
     * Returns the bucketName of the ActiveRecord.
     *
     * @return string
     * @since  XXX
     */
    public static function bucketName();

    /**
     * Define the metadata of the active record
     *
     * @return array
     * @since  XXX
     */
    public static function metadataNames();

    /**
     * Returns the resolver class name
     * If returns null, no resolve would be done while the saving process.
     *
     * @return string The resolver class name
     * @since  XXX
     */
    public static function resolverClassName();
}
