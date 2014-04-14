<?php

/**
 * File DataReader.php
 *
 * PHP version 5.3+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql
 */
namespace sweelix\yii2\nosql\riak;

use sweelix\curl\Response;
use yii\db\Exception;

/**
 * Class DataReader
 *
 * @author Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license http://www.sweelix.net/license license
 * @version XXX
 * @link http://www.sweelix.net
 * @category nosql
 * @package sweelix.nosql
 * @since XXX
 */
class DataReader implements \Countable, \ArrayAccess, \Iterator
{

    const DATA_KEY = 'data';

    const SIBLINGS_KEY = 'siblings';

    const RESPONSESTATUS_KEY = '.status';

    const HEADERS_KEY = '.headers';

    const ETAG_KEY = '.etag';

    const VCLOCK_KEY = '.vclock';

    const LINK_KEY = '.link';

    const INDEX_KEY = '.index';

    const META_KEY = '.meta';

    const OBJECT_KEY = '.key';

    /**
     * Current index.
     *
     * @var integer
     */
    private $index = 0;

    /**
     * The array which contains objects.
     *
     * @var array
     */
    private $objects = array();

    /**
     * Constructor
     *
     * @param Response $response
     *            The command generating the query result.
     *
     * @return void
     * @since XXX
     */
    public function __construct(Response $response = null)
    {
        \Yii::trace('Begin build with resposne : ' . var_export($response, true) . "\n", __CLASS__);
        if ($response !== null) {
            if ($response->getIsMultipart() === true) {
                $test = array();
                foreach ($response->extractMultipartDataAsResponse() as $currentResponse) {
                    $this->objects[] = $this->buildObject($currentResponse);
                }
            } else {
                $this->objects[] = $this->buildObject($response);
            }
        }
        $this->index = 0;
        \Yii::trace('Begin end : ' . var_export($this, true) . "\n", __CLASS__);
    }

    /**
     * Build an object of the given response.
     * <code>
     * array(
     * 'data' => 'object data', or null if no data.
     * 'siblings' => 'object siblings',
     * '.status' => 'The resposne status',
     * '.headers' => 'The heades keys => values',
     * '.etag' => 'The etag of object',
     * '.vclock' => 'The vclock',
     * '.link' => 'Object's link',
     * '.index' => 'Object's indexes',
     * '.meta' => 'Object's metadata',
     * )
     * </code>
     *
     * @param Response $response
     *            build the object from response.
     * @param mixed $objectKey
     *            The key of object to build.
     *
     * @return array
     * @since XXX
     */
    private function buildObject(Response $response, $objectKey = null)
    {
        $ret = array();
        if ($response->getIsMultiPart() === true) {
            foreach ($response->extractMultipartDataAsResponse() as $response) {
                $ret[] = $this->buildObject($response);
            }
            if (empty($ret)) {
                $ret[] = array();
            }
        } else {
            // INIT OBJECT
            $ret = $this->createObject();
            $data = $response->getData();

            if ($objectKey !== null) {
                $ret[self::OBJECT_KEY] = $objectKey;
            }

            $ret[self::DATA_KEY] = array();
            $ret[self::SIBLINGS_KEY] = array();
            // ASSIGN DATA OR SIBLINGS
            if (is_array($data) === true) {
                foreach ($data as $key => $value) {
                    $ret[self::DATA_KEY][$key] = $value;
                }
            } else {
                if (is_string($data) === true && substr($data, 0, 9) === 'Siblings:') {
                    $siblings = str_replace("Siblings:\n", '', $data);

                    $ret[self::SIBLINGS_KEY] = preg_split('/\s+/', $siblings, - 1, PREG_SPLIT_NO_EMPTY);
                } else {
                    $ret[self::DATA_KEY] = $data;
                }
            }

            // ASSIGN RESPONSESTATUS AND HEADERS
            $ret[self::RESPONSESTATUS_KEY] = $response->getStatus();
            $ret[self::HEADERS_KEY] = $response->getHeaders();

            // ASSIGN LINK, META AND INDEXES
            foreach ($ret[self::HEADERS_KEY] as $key => $value) {
                $matches = array();
                if (preg_match('/X-Riak-([^-]+)-?(.*)/i', $key, $matches) > 0) {
                    $type = $matches[1];
                    $data = $matches[2];
                    if ($type == 'Index') {
                        $data = explode('_', $data);
                        $ret[self::INDEX_KEY][lcfirst($data[0])] = array(
                            $value,
                            $data[1]
                        );
                    } else {
                        if ($type == 'Vclock') {
                            $ret[self::VCLOCK_KEY] = $value;
                        } else {
                            $ret['.' . strtolower($type)][lcfirst($data)] = $value;
                        }
                    }
                }
            }

            // ASSIGN ETAG
            if (isset($ret[self::HEADERS_KEY]['Etag']) === true) {
                $ret[self::ETAG_KEY] = $ret[self::HEADERS_KEY]['Etag'];
            }
            // ASSIGN LINK
            if (isset($ret[self::HEADERS_KEY]['Link']) === true) {
                $ret[self::LINK_KEY] = explode(', ', $ret[self::HEADERS_KEY]['Link']);
            }
        }
        return $ret;
    }

    /**
     * This function create an empty DataReader object.
     *
     * @return array object initializing
     * @since XXX
     */
    private function createObject()
    {
        $ret = array();
        $ret[self::RESPONSESTATUS_KEY] = null;
        $ret[self::HEADERS_KEY] = array();
        $ret[self::DATA_KEY] = array();
        $ret[self::SIBLINGS_KEY] = array();
        $ret[self::ETAG_KEY] = null;
        $ret[self::VCLOCK_KEY] = null;
        $ret[self::META_KEY] = array();
        $ret[self::LINK_KEY] = array();
        $ret[self::INDEX_KEY] = array();
        return $ret;
    }

    /**
     * (non-PHPdoc)
     *
     * @see Iterator::current()
     *
     * @return array Representing an object.
     * @since XXX
     */
    public function current()
    {
        if (isset($this->objects[$this->index]) === true) {
            return $this->objects[$this->index];
        } else {
            return null;
        }
    }

    /**
     * Increment the index.
     *
     * @see Iterator::next()
     *
     * @return void
     * @since XXX
     */
    public function next()
    {
        ++ $this->index;
    }

    /**
     * (non-PHPdoc)
     *
     * @see Iterator::key()
     *
     * @return void
     * @since XXX
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * Wether the current index is valid.
     *
     * @see Iterator::valid()
     *
     * @return bool
     * @since XXX
     */
    public function valid()
    {
        return isset($this->objects[$this->index]);
    }

    /**
     * Repositionning the index to the begining.
     *
     * @see Iterator::rewind()
     *
     * @return void
     * @since XXX
     */
    public function rewind()
    {
        $this->index = 0;
    }

    /**
     * Returns the number of object.
     *
     * @see Countable::count()
     *
     * @return integer
     * @since XXX
     */
    public function count()
    {
        // TODO Auto-generated method stub
        return count($this->objects);
    }

    /**
     * Wether the offset exists.
     *
     * @param integer $offset
     *            The offset to test.
     *
     * @see ArrayAccess::offsetExists()
     *
     * @return bool
     * @since XXX
     */
    public function offsetExists($offset)
    {
        // TODO Auto-generated method stub
        return isset($this->objects[$offset]);
    }

    /**
     * Returns the object at $offset
     *
     * @param integer $offset
     *            The offset to get.
     *
     * @see ArrayAccess::offsetGet()
     *
     * @return array Return the object at $offset or null if not exist.
     * @since XXX
     *
     */
    public function offsetGet($offset)
    {
        return (isset($this->objects[$offset]) === true ? $this->objects[$offset] : null);
    }

    /**
     * Set the object at offset $offset
     *
     * @param integer $offset
     *            The offset to set.
     * @param mixed $value
     *            The value to set.
     *
     * @see ArrayAccess::offsetSet()
     *
     * @return void
     * @since XXX
     */
    public function offsetSet($offset, $value)
    {
        throw new Exception(501);
    }

    /**
     * This function unset the object at $offset.
     *
     * @param integer $offset
     *            The offset to unset.
     *
     * @see ArrayAccess::offsetUnset()
     *
     * @return void
     * @since XXX
     */
    public function offsetUnset($offset)
    {
        unset($this->objects[$offset]);
    }

    /**
     * Add object from a given $response
     *
     * @param Response $response
     *            The response to build.
     * @param string $objectKey
     *            The object key of the object to add.
     *
     * @return void
     * @since XXX
     */
    public function addObject(Response $response, $objectKey = null)
    {
        $this->objects[] = $this->buildObject($response, $objectKey);
    }

    /**
     * Add an object.
     *
     * @param array $object
     *
     * @return void
     * @since XXX
     */
    public function addRawObject($object)
    {
        $this->objects[] = $object;
    }
}
