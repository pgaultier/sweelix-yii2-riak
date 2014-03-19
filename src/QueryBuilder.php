<?php

/**
 * File QueryBuilder.php
 *
 * PHP version 5.3+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql
 */

namespace sweelix\yii2\nosql;

use yii\base\Object;
use sweelix\yii2\nosql\Query;
use sweelix\yii2\nosql\riak\IndexType;

/**
 * Class QueryBuilder
 *
 * This class handle all the queries generation
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql
 * @since     XXX
 */

abstract class QueryBuilder extends Object {
	/**
	 * @var Connection connection to nosql db
	 */
	public $noSqlDb;

	/**
	 * @var String|Int key of bucket
	 */
	public $key;

	/**
	 * @var array data of bucket
	 */
	public $data;

	/**
	 * Constructor.
	 *
	 * @param Connection $connection the nosql database connection.
	 * @param array      $config     name-value pairs that will be used to initialize the object properties
	 *
	 * @return QueryBuilder
	 * @since  XXX
	 */
	public function __construct($connection, $config = array()) {
		$this->noSqlDb = $connection;
		parent::__construct($config);
	}

	/**
	 * Creates a DELETE statement.
	 * For example,
	 *
	 * ~~~
	 * $sql = $queryBuilder->delete('bucket_name', 'object_key');
	 * ~~~
	 *
	 * The method will properly escape the bucket objects.
	 *
	 * @param string $bucket    The bucketname to work with.
	 * @param mixed  $objectKey The key of object to delete (array|string).
	 *
	 * @return array The settings array to execute Command
	 * @since  XXX
	 */
	public function delete($bucket, $objectKey) {
		$commandData = array(
			'mode' => 'delete',
			'bucket' => $bucket,
			'key' => $objectKey,
		);
		return $commandData;
	}

	/**
	 * Creates an INSERT statement.
	 * For example,
	 *
	 * ~~~
	 * $commandData = $queryBuilder->insert('bucket_name', 'object_key', array(
	 * 	 'name' => 'Sam',
	 * 	 'age' => 30,
	 * ));
	 * ~~~
	 *
	 * @param string $bucket    The bucketname to work with.
	 * @param string $objectKey The key of object name that new rows will be inserted into.
	 * @param array  $data      The content to create object.
	 *
	 * @return array The settings array to execute Command
	 * @since  XXX
	 */
	public function insert($bucket, $objectKey, $data) {
		$commandData = array(
			'mode' => 'insert',
			'bucket' => $bucket,
			'key' => $objectKey,
			'data' => $data,
		);
		return $commandData;
	}

	/**
	 * Fectch data of a record and creates an Update statement.
	 * For example
	 *
	 * ~~~
	 * $commandData = $queryBuilder->update('bucket_name', 'object_key', array(
	 * 	 'name' => 'Sam',
	 * 	 'age' => 30,
	 * ));
	 * ~~~
	 *
	 * @param string $bucket    The bucketName to work with.
	 * @param string $objectKey The keys that will be put to get object update.
	 * @param array  $data      The data (name => value) to be updated.
	 *
	 * @return array The settings array to execute Command
	 * @since  XXX
	 */
	public function update($bucket, $objectKey, $data = array()) {
		return array(
			'mode' => 'update',
			'bucket' => $bucket,
			'key' => $objectKey,
			'data' => $data,
		);
	}

	/**
	 * Returns the configurated setting array to execute a Command to increment the counter ($counterKey)
	 * by the given value ($incrementalValue) in the bucket ($bucketName).
	 * 
	 * @param unknown $bucketName       The bucket of the counter to update.
	 * @param unknown $counterKey       The key of the counter to update.
	 * @param unknown $incrementalValue The incrementalValue to add to the counter.
	 * 
	 * @return array The configurated setting array to execute the updateCounter command.
	 * @since  XXX
	 */
	public function updateCounter($bucketName, $counterKey, $incrementalValue) {
		return array(
			'mode' => 'counters',
			'bucket' => $bucketName,
			'key' => $counterKey,
			'data' => $incrementalValue,
		);
	}
	
	/**
	 * Returns the configurated setting array to execute a Command to get the counter value 
	 * of the counter ($counterKey) in the bucket ($bucketName).
	 * 
	 * @param unknown $bucketName The bucket name of the counter to get.
	 * @param unknown $counterKey The key of the counter to get.
	 * 
	 * @return array The configurated setting array to execute the getCounter command.
	 * @since  XXX
	 */
	public function getCounter($bucketName, $counterKey) {
		return array(
			'mode' => 'counters',
			'bucket' => $bucketName,
			'key' => $counterKey,
		);
	}
	
	/**
	 * Returns the configurated setting array to execute a Command to update bucket properties. 
	 * 
	 * @param string $bucketName The bucket name which wanted to update.
	 * @param array  $props      The properties array for the bucket.
	 * 
	 * @return array 
	 * @since  XXX
	 */
	public function alterBucket($bucketName, $props) {
		return array(
			'mode' => 'props',
			'bucket' => $bucketName,
			'data' => array('props' => $props),
		);
	}
	
	/**
	 * This function generates a new query parameter and return it.
	 * ex :
	 * <code>
	 * 		$queryBuilder->addQueryParam('returnbody', 'true'); //will return :
	 * 		array(
	 * 			'returnbody' => 'true'
	 * 		);
	 * </code>
	 * 
	 * @param string $key   The key of new query params (ex : 'returnbody')
	 * @param string $value The value for the given key (ex : 'true')
	 *
	 * @return array  The formatted array to add in queryParams field of commandData 
	 * @since  XXX
	 */
	public function addQueryParam($key, $value) {
		return array(
			$key => $value				
		);
	}
	
	/**
	 * This function generates a new index header field and return it.
	 * Ex :
	 * <code>
	 * 		$queryBuilder->addIndex('indexKey', 'indexValue', TYPE::BIN);//will produce :
	 * 		array(
	 * 			'X-Riak-Index-indexKey_bin' => 'indexValue'
	 * 		);
	 * 
	 * 		$queryBuilder->addIndex('test', 'test', TYPE::INT);//will produce :
	 * 		array(
	 * 			'X-Riak-Index-test_int' => 'test'
	 * 		);
	 * </code>
	 * 
	 * @param string     $indexName The index name to create.
	 * @param string|int $value     The index value
	 * @param string     $type      The type of the index value (TYPE_BIN by default. Other possibility : TYPE_INT)
	 * 
	 * @return array The commandData with new index.
	 * @since  XXX
	 */
	public function addIndex($indexName, $value, $type = IndexType::TYPE_BIN) {
		return array(
			'X-Riak-Index-'.$indexName.$type => $value
		);
	}
	
	/**
	 * This function generates a new header field value for 'Link' field header.
	 * The pair $bucket $key represent an object of the riak database 
	 * Ex : 
	 * <code>
	 * 		$queryBuilder->addIndex('userBucket', 'userKey', 'friendTag');// will produce :
	 * 		'<riak/userBucket/userKey>; riaktag=friendTag';
	 * </code>
	 *  
	 * @param string $bucket The bucket name.
	 * @param string $key    The key of linked object.
	 * @param string $tag    The tag wanted to associate with object.
	 * 
	 * @return string  
	 */
	public function addLink($bucket, $key, $tag) {
		return '</buckets/'.$bucket.'/keys/'.$key.'>; riaktag="'.$tag.'"';
	}

	/**
	 * This function generates a new metadata header field and return it.
	 * <code>
	 * 		$queryBuilder->addMetaData('metaKey', 'metaValue'); //will produce :
	 * 		array(
	 * 			'X-Riak-Meta-metaKey' => 'metaValue'
	 * 		);
	 * </code>
	 *
	 * @param string $key   The metadata key.
	 * @param string $value The metadata value.
	 * 
	 * @return array
	 * @since  XXX
	 */
	public function addMetaData($key, $value) {
		return array(
			'X-Riak-Meta-'.$key => $value
		);
	}
		
	
	/*	abstract public function build($query); Move to BaseQueryBuilder (may be temporarly)*/
	/**
	 * Abstract calss to build current query
	 *
	 * @param Query $query current query
	 *
	 * @return array The settings array to execute Command
	 * @since  XXX
	 */
	public function build(Query $query) {
		$commandData = array(
			'mode' => $query->mode,
			'bucket' => $query->bucket,
			'key' => $query->key,
			'data' => $this->buildBody($query),
			'headers' => $this->buildHeaders($query),
			'queryParams' => $this->buildQueryParams($query),
		);
		return $commandData;
	}

	/**
	 * This function the body of the query
	 * 
	 * @param Query $query The query to build
	 * 
	 * @return string|null
	 * @since  XXX
	 */
	protected function buildBody(Query $query) {
		if ($query->mode === 'selectWithMapReduce') {
			return $query->mapReduce->build(true);
		} else {
			return null;
		}
	}
	
	/**
	 * Build the correct GET parameters
	 * 
	 * @param Query $query The query to build
	 * 
	 * @return array The array of GET params
	 */
	protected function buildQueryParams($query) {
		return array(
			'r' => $query->r,
			'pr' => $query->pr,
			'basic_quorum' => $query->basicQuorum,
			'nofound_ok' => $query->notFoundOk,
			'chunked' => $query->chunked,
			'vtag' => $query->vtag,
			'return_terms' => $query->returnTerms,
			'max_results' => $query->limit,
			'continuation' => $query->continuation,
			'streaming' => $query->streaming,
		);
	}

	/**
	 * Build the correct headers from query
	 * 
	 * @param Query $query The query to build
	 * 
	 * @return array of headers.
	 */
	protected function buildHeaders(Query $query) {
		return array(
			'Accept' => $query->accept,
			'If-None-Match' => $query->etag,
		);
	}
}