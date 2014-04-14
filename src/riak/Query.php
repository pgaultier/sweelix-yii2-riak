<?php

/**
 * File Query.php
 *
 * PHP version 5.3+
 *
 * @author    Dzung Nguyen <dungnh@ilucians.com>
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql
 */
namespace sweelix\yii2\nosql\riak;

use sweelix\yii2\nosql\riak\IndexType;
use sweelix\yii2\nosql\riak\MapReduce;
use sweelix\yii2\nosql\riak\phase\Link;
use sweelix\yii2\nosql\riak\phase\Map;
use sweelix\yii2\nosql\riak\phase\Reduce;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use Exception;

/**
 * Class Query
 *
 * Query reprensents a Fetch Nosql request.
 *
 * It provides a set of methods to facilitate the preparation of Fetch request. Those methods
 * can be chained together.
 *
 * <code>
 * $query = new Query();
 * $query->select()->withStorage('bucketTest')->withKey('objectTest')->one();
 * $query->select()->withStorage('bucketTest')->withLink('')
 * </code>
 *
 * @author Dzung Nguyen <dungnh@ilucians.com>
 * @author Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license http://www.sweelix.net/license license
 * @version XXX
 * @link http://www.sweelix.net
 * @category nosql
 * @package sweelix.nosql
 * @since XXX
 */
class Query extends Component
{

    /**
     *
     * @var Connection connection to nosql db
     */
    public $noSqlDb;

    protected $mode;

    /**
     *
     * @var String bucket bucket name
     */
    protected $bucket = null;

    /**
     *
     * @var String Int of bucket
     */
    protected $key = null;

    /**
     *
     * @var Array indexes of object
     */
    protected $index = array();

    /**
     *
     * @var Array links of related objects
     */
    protected $links = array();

    /**
     *
     * @var integer Number of replicas need to agree when retriving object.
     */
    protected $r;

    /**
     *
     * @var integer Number of replicas need to be online when doing read.
     */
    protected $pr;

    /**
     *
     * @var integer The basic_quorum
     */
    protected $basicQuorum;

    /**
     *
     * @var string The notfound_ok query parameters
     */
    protected $notFounfOk;

    /**
     *
     * @var boolean The queryParameters 'chunked'
     * @see http://docs.basho.com/riak/latest/dev/references/http/mapreduce/
     */
    protected $chunked;

    /**
     *
     * @var string An additionnal request parameters to get a sibling.
     * @see http://docs.basho.com/riak/latest/dev/references/http/fetch-object/#Siblings-examples
     */
    protected $vtag;

    protected $limit;

    protected $returnTerms;

    protected $streaming;

    protected $continuation;

    /**
     *
     * @var string The header field 'If-None-Match'.
     */
    protected $etag;

    protected $accept;

    /**
     *
     * @var string The header field 'If-Modified-Since'.
     */
    protected $lastModified;

    /**
     *
     * @var array map/reduce/link of parameters's request
     */
    protected $mapReduce;

    /**
     * Generates a string select query of DB.
     *
     * @return Query (set select mode of Query)
     * @since XXX
     */
    public function select()
    {
        // $this->_mode = 'select';
        return $this;
    }

    /**
     * Create a Command instance
     *
     * @param Connection $noSqlDb
     *            the database connection used to generate the query statement.
     *            If this parameter is not given, the `db` application component will be used.
     *
     * @return Command instance
     * @since XXX
     */
    public function createCommand($noSqlDb = null)
    {
        if ($noSqlDb === null) {
            $noSqlDb = \Yii::$app->nosql;
        }
        $this->noSqlDb = $noSqlDb;
        $commandData = $noSqlDb->getQueryBuilder()->build($this);
        return $noSqlDb->createCommand($commandData);
    }

    /**
     * Executes the query and returns all results as an array.
     *
     * @param Connection $db
     *            the database connection used to generate the query statement.
     *            If this parameter is not given, the `db` application component will be used.
     *
     * @return array the query results. If the query results in nothing, an empty array will be returned.
     * @since XXX
     */
    public function all($db = null)
    {
        $command = $this->createCommand($db);
        return $command->queryAll();
    }

    /**
     * Executes the query and returns a single row of result.
     *
     * @param Connection $db
     *            the database connection used to generate the query statement.
     *            If this parameter is not given, the `db` application component will be used.
     *
     * @return array boolean first row (in terms of an array) of the query result. False is returned if the query
     *         results in nothing.
     * @since XXX
     */
    public function one($db = null)
    {
        $command = $this->createCommand($db);
        return $command->queryOne();
    }

    /**
     * Generates a name of bucket to select
     *
     * @param mixed $bucketName
     *            is name of bucket or array of inputs for map/reduce/link query
     *
     * @return Query (set bucket name for select statement)
     * @since XXX
     */
    public function fromBucket($bucketName)
    {
        $this->bucket = $bucketName;
        return $this;
    }

    /**
     * Generates a key of bucket to select.
     *
     * @param mixed $objectKey
     *            is the key of object (string | int)
     *
     * @return Query (set the key of object)
     * @since XXX
     */
    public function withKey($objectKey)
    {
        if (isset($this->mode) === true) {
            throw new Exception('Chained mulitiple with is forbidden');
        }
        $this->mode = 'select';
        $this->key = $objectKey;
        return $this;
    }

    /**
     * Set the map reduce of the current query.
     * If no value is entered, this function will instantiate a mapReduce.
     * Then, you will be able to add phase (with link(), map() and reduce() functions).
     *
     * @param mixed $inputs
     *            string or array.
     *            - string : a bucket name
     *            - array : array(
     *            array(
     *            bucketName,
     *            objectKey,
     *            keydata
     *            ),
     *            //ETC...
     *            )
     * @return Query The query object itself
     * @since XXX
     */
    public function withMapReduce($inputs = null)
    {
        if (isset($this->mode) === true) {
            throw new Exception('Chained mulitiple with is forbidden');
        }
        $this->mode = 'selectWithMapReduce';
        $this->mapReduce = new MapReduce();
        if (is_string($inputs)) {
            $this->mapReduce->addInput($inputs);
        } elseif (is_array($inputs)) {
            foreach ($inputs as $input) {
                if (count($input) === 1) {
                    $this->mapReduce->addInput($input[0]);
                } elseif (count($input) === 2) {
                    $this->mapReduce->addInput($input[0], $input[1]);
                } else {
                    $this->mapReduce->addInput($input[0], $input[1], $input[2]);
                }
            }
        } elseif ($inputs === null) {
            if ($this->bucket) {
                $this->mapReduce->addInput($this->bucket);
            } else {
                throw new InvalidConfigException('No bucket setted');
            }
        } else {
            throw new InvalidParamException(
                'Inputs should be a string reprensenting a bucket or an
                array of bucket, key, keydata. (array($bucket, $key, $keydata))'
            );
        }
        return $this;
    }

    /**
     * Add a map phase to the current query mapReduce.
     *
     * @param Map $map
     *            The map phase to add to the mapReduce
     *
     * @return Query The query object itself
     * @since XXX
     */
    public function map(Map $map)
    {
        $this->mapReduce->addPhase($map);
        return $this;
    }

    /**
     * Add a reduce phase to the current query mapReduce.
     *
     * @param Reduce $reduce
     *            The reduce phase to add to the mapReduce.
     *
     * @return Query The query object itself
     * @since XXX.
     */
    public function reduce(Reduce $reduce)
    {
        $this->mapReduce->addPhase($reduce);
        return $this;
    }

    /**
     * Add a link phase to the current query mapReduce.
     *
     * @param Link $link
     *            The link phase to add to the mapReduce.
     *
     * @return Query The query object itself.
     * @since XXX
     */
    public function link(Link $link)
    {
        $this->mapReduce->addPhase($link);
        return $this;
    }

    /**
     * Search from index
     *
     * @param string $indexName
     *            The name of searched index
     * @param string $value
     *            The value of the searched index (Or the startValue if $endValue is setted)
     * @param string $endValue
     *            The end value of the searched index.
     * @param string $type
     *            The type of the value.
     *
     * @return Query The query object itself
     * @since XXX
     */
    public function withIndex($indexName, $value, $endValue = null, $type = IndexType::TYPE_BIN)
    {
        if (isset($this->mode) === true) {
            throw new Exception('Chained mulitiple with is forbidden');
        }
        $this->mode = 'selectWithIndex';
        $this->index = array(
            'indexName' => $indexName . $type,
            'value' => $value,
            'endValue' => $endValue
        );
        return $this;
    }

    /**
     * Search link.
     *
     * @param string $bucketName
     *            the bucketname (set '_' to search in any bucket)
     * @param string $riakTag
     *            The searched tag (set '_' to match any tag)
     * @param string $keep
     *            The keep argument ('0' or '1' or '_')
     *
     *            <code>
     *            $query->select()->fromBucket('user')->withKey('userTest')->linked('user', 'friend', '1')->all();
     *            //search all user who are friend with userTest in the bucket 'user'
     *            //will call the url : /buckets/user/keys/userTest/user,friend,1
     *            </code>
     *
     * @return Query The query object itself.
     * @since XXX
     */
    public function linked($bucketName = '_', $riakTag = '_', $keep = 1)
    {
        $this->mode = 'selectWithLink';
        $this->links = array();
        return $this->addLinked($bucketName, $riakTag, $keep);
    }

    /**
     * This function append a new link for the query
     *
     * @param string $bucketName
     *            The bucketName (set '_' to search in any bucket)
     * @param string $riakTag
     *            The searched tag (set '_' to match any tag)
     * @param string $keep
     *            The keep argument ('0' or '1' or '_')
     *
     *            WARNING : Use linked() before addlinked().
     *            <code>
     *            $query
     *            ->select()
     *            ->fromBucket('user')
     *            ->withKey('userTest')
     *            ->linked('user', 'friend', '0')
     *            ->addLinked('user', 'friend', '1');
     *            //search all users who are friend with userTest's friends. (Recherche les amis des amis de userTest).
     *            //Will produce the url : /buckets/user/keys/userTest/user,friend,0/user,friend,1
     *            </code>
     *            You can chained as much as you want. (->linked(...)->addLinked(...)->addLinked(...)->etc....)
     *
     * @return Query The query object itself
     * @since XXX
     */
    public function addLinked($bucketName = '_', $riakTag = '_', $keep = '_')
    {
        $this->links[] = array(
            implode(',', array(
                $bucketName,
                $riakTag,
                $keep
            ))
        );
        return $this;
    }

    /**
     * Set the Accept header
     *
     * @param string $accept
     *            The Accept header
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html to see allowed values.
     *
     * @return Query The query object itself
     * @since XXX
     */
    public function accept($accept = 'application/json')
    {
        $this->accept = $accept;
        return $this;
    }

    /**
     * Set the number of objects wanted.
     * (only for search withIndex)
     *
     * @param integer $limit
     *            The maximum number of wanted object.
     *
     * @return Query The query object itself
     * @since XXX
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the return_terms ('true' or 'false').
     * (only for search withIndex)
     *
     * @param string $returnTerms
     *            The return_terms.
     *
     * @return Query The query object itself.
     */
    public function returnTerms($returnTerms = 'true')
    {
        $this->returnTerms = $returnTerms;
        return $this;
    }

    /**
     * Set the r value.
     *
     * @param int $value
     *            How many replicas need to agree when retrieving the object
     *            (if not specified, the bucket r value will be used).
     *
     * @return Query The query object itself.
     * @since XXX
     */
    public function r($value)
    {
        $this->r = $value;
        return $this;
    }

    /**
     * Set the pr value
     *
     * @param integer $value
     *            How many primary replicas need to be online when doing the read
     *            (if not specified, the bucket r value will be used).
     *
     * @return Query The query object itself
     * @since XXX
     */
    public function pr($value)
    {
        $this->pr = $value;
        return $this;
    }

    /**
     * Set the notFoundOk
     *
     * @param string $value
     *            Whether to treat notfounds as successful reads for the purposes of R
     *            (if not specified, the bucket notfound_ok value will be used)
     *
     * @return Query The query object itself
     * @since XXX
     */
    public function notFoundOk($value)
    {
        $this->notFounfOk = $value;
        return $this;
    }

    /**
     * Set the vtag.
     *
     * @param string $value
     *            When accessing an object with siblings, which sibling to retrieve.
     *
     * @return \sweelix\yii2\nosql\Query The query object itself
     * @since XXX
     */
    public function vtag($value)
    {
        $this->vtag = $value;
        return $this;
    }

    /**
     * mode getter
     *
     * @return string
     * @since XXX
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * bucket getter
     *
     * @return string
     * @since XXX
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * key getter
     *
     * @return Ambigous <string, number>
     * @since XXX
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * The map reduce getter.
     *
     * @return MapReduce The current mapReduce of the query.
     */
    public function getMapReduce()
    {
        return $this->mapReduce;
    }

    /**
     * index search getter
     *
     * @return array
     * @since XXX
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Links getter.
     *
     * @return array
     * @since XXX
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * The r getter
     *
     * @return number
     * @since XXX
     */
    public function getR()
    {
        return $this->r;
    }

    /**
     * The pr getter
     *
     * @return number
     * @since XXX
     */
    public function getPr()
    {
        return $this->pr;
    }

    /**
     * NotFoundOK getter
     *
     * @return string
     * @since XXX
     */
    public function getNotFoundOk()
    {
        return $this->notFounfOk;
    }

    /**
     * The basic quorum getter.
     *
     * @return number
     * @since XXX
     */
    public function getBasicQuorum()
    {
        return $this->basicQuorum;
    }

    /**
     * Chuncked getter
     *
     * @return boolean
     * @since XXX
     */
    public function getChunked()
    {
        return $this->chunked;
    }

    /**
     * vtag getter
     *
     * @return string
     * @since XXX
     */
    public function getVtag()
    {
        return $this->vtag;
    }

    /**
     * Return terms getter
     *
     * @return string
     * @since XXX
     */
    public function getReturnTerms()
    {
        return $this->returnTerms;
    }

    /**
     * The limit getter.
     *
     * @return number
     * @since XXX
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * The continuation getter
     *
     * @return string
     * @since XXX
     */
    public function getContinuation()
    {
        return $this->continuation;
    }

    /**
     * The streaming getter
     *
     * @return string
     */
    public function getStreaming()
    {
        return $this->streaming;
    }

    /**
     * The etage getter
     *
     * @return string
     * @since XXX
     */
    public function getEtag()
    {
        return $this->etag;
    }

    /**
     * The accept getter
     *
     * @return string
     * @since XXX
     */
    public function getAccept()
    {
        return $this->accept;
    }
}
