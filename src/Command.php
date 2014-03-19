<?php
/**
 * File Command.php
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

namespace sweelix\yii2\nosql;

use yii\base\Component;
use sweelix\yii2\nosql\DataReader;
use Basho\Riak\Exception;
use sweelix\yii2\nosql\riak\IndexType;
use sweelix\curl\Response;

/**
 * Class Command
 *
 * Represents a SQL statement to be executed against a database.
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql
 * @since     XXX
 */
abstract class Command extends Component {
	public static $allowedMode = array(
			'select',
			'selectWithMapReduce',
			'selectWithLink',
			'selectWithIndex',
			'insert',
			'update',
			'delete',
			'counters',
			'props',
	);

	/**
	 * @var string noSqlDb
	 */
	public $noSqlDb;

	/**
	 * @var array $commandData The settings array
	 * <code>
	 * array(
	 *   'mode'        => 'select' | 'insert' | 'update' | 'delete',
	 *   'bucket'      => bucket Name,
	 *   'key'         => The object key
	 *   'headers'     => array(
	 *      	'headerKey1' => 'headerValue1',
	 *      	'headerKey2' => 'headerValue2',
	 *      	//etc..
	 *   ),
	 *   'queryParams' => array(
	 *   		'queryParamsKey1' => 'queryParamsValue1',
	 *   		'queryParamsKey2' => 'queryParamsValue2',
	 *      	//etc..
	 *   ),
	 *   'data' => data to send (The body request)
	 * )
	 * </code>
	 */
	public $commandData = array();
	
	/**
	 * The commandData setter.
	 * 
	 * @param array $commandData The settings array.
	 * 
	 * @return Command the command object itself.
	 * @since  XXX
	 */
	public function setCommandData(array $commandData) {
		if ($this->commandData !== $commandData) {
			$this->commandData = $commandData;
		}
		return $this;
	}
	
	/**
	 * The commandData getter.
	 * 
	 * @return array the commandData.
	 * 
	 * @since  XXX
	 */
	public function getCommandData() {
		return $this->commandData;
	}
	
	private $_query;
	
	/**
	 * set query to build headers, links, indexed ,metadata, bucket, body(data)
	 *
	 * @param Query $query is the array of headers, links, indexed ,metadata, bucket, body(data)
	 * Ex: array('headers'=>array(...), 'links'=>array(...), ....)
	 *
	 * @return Command the command object itself
	 * @since XXX
	 */
	public function setQuery(Query $query) {
		$this->commandData = $this->noSqlDb->getQueryBuilder()->build($query);
		$this->query = $query;
		return $this;
	}
	
	/**
	 * get query to build headers, links, indexed ,metadata, bucket, body(data)
	 *
	 * @return Query the query object
	 * @since XXX
	 */
	public function getQuery() {
		return $this->query;
	}
	
	/**
	 * Set query
	 *
	 * @param Query $query the query
	 *
	 * @return void
	 * @since  XXX
	 */
//	abstract public function setQuery($query);

	/**
	 * Creates an INSERT command.
	 * For example:
	 * ~~~
	 * $connection->createCommand()->insert()->execute();
	 * ~~~
	 * The method will properly $data to be inserted.
	 * Note that the created command is not executed until [[execute()]] is called.
	 *
	 * @param string $bucket The bucket of the object wanted to insert.
	 * @param string $key    The object key wanted to insert.
	 * @param array  $object The object data.
	 *
	 * @return Command the command object itself
	 * @since XXX
	 */
	public function insert($bucket, $key, array $object) {
		$commandData = $this->noSqlDb->getQueryBuilder()->insert($bucket, $key, $object);
		return $this->setCommandData($commandData);
	}

	/**
	 * Creates an UPDATE command.
	 * For example:
	 * ~~~
	 * $connection->createCommand()->update($bucket, $objectKey)->execute();
	 * ~~~
	 * The method will properly $data to be updated with $objectKey.
	 * Note that the created command is not executed until [[execute()]] is called.
	 *
	 * @param string     $bucket    The bucket of object to update. 
	 * @param string|int $objectKey The key of object to update.
	 * @param array      $data      The object data.
	 * 
	 * @return Command The command object itself.
	 * @since XXX
	 */
	public function update($bucket, $objectKey, $data = array()) {
		$commandData = $this->noSqlDb->getQueryBuilder()->update($bucket, $objectKey, $data);
		return $this->setCommandData($commandData);
	}

	/**
	 * Creates an DELETE command.
	 * For example:
	 * ~~~
	 * $connection->createCommand()->delete($objectKey)->setQueryData($data)->execute();
	 * ~~~
	 * The method will properly $data to be updated with $objectKey.
	 * Note that the created command is not executed until [[execute()]] is called.
	 *
	 * @param strin        $bucket    The bucket of object to delete.
	 * @param string|array $objectKey The key of object.
	 * 
	 * @return Command the command object itself
	 * @since XXX
	 */
	public function delete($bucket, $objectKey) {
		$commandData = $this->noSqlDb->getQueryBuilder()->delete($bucket, $objectKey);
		return $this->setCommandData($commandData);
	}

	/**
	 * Creates a Command whose will increment the counter key ($counterKey) by the $incrementalValue in the bucket ($bucketName).
	 * <code>
	 * 		$connection->createCommand()->updateCounters('bucketTest', 'counterKey', 1)->execute();
	 * </code>
	 * Note that the created command is not executed until [[execute()]] is called.
	 * 
	 * @param string  $bucketName       The bucket of object to update.
	 * @param string  $counterKey       The key of object to update.
	 * @param integer $incrementalValue The incremental value to add to the object counter.
	 * 
	 * @return Command The command object itself
	 * @since  XXX
	 */
	public function updateCounter($bucketName, $counterKey, $incrementalValue = 1) {
		$commandData = $this->noSqlDb->getQueryBuilder()->updateCounter($bucketName, $counterKey, $incrementalValue);
		return $this->setCommandData($commandData);
	}
	
	/**
	 * Creates a Command whose will update the bucket properties.
	 * <code>
	 * 		$connection->createCommand()->updateBucketProperties('bucketTest', array('allow_mult', true));
	 * </code>
	 * 
	 * @param string $bucketName The bucket whick wanted to update.
	 * @param array  $props      The settings array of the bucket
	 * 
	 * List of allowed keys => values for $props
	 * ~~~
	 * 'n_val' => integer > 0
	 * 'allow_mult' => true or false
	 * 'last_write_wins' => true or false
	 * 'r' || 'w' || 'dw' || 'rw' => 'all' || 'quorum' || 'one' || an integer < n_val
	 * ~~~
	 * See : http://docs.basho.com/riak/latest/dev/references/http/set-bucket-props/ 
	 * 
	 * @return Command The command object itself
	 * @since  XXX
	 */
	public function alterBucket($bucketName, $props) {
		$commandData = $this->noSqlDb->getQueryBuilder()->alterBucket($bucketName, $props);
		return $this->setCommandData($commandData);
	}
	
	/**
	 * This function add GET parameters to the current request.
	 * 
	 * @param string $key   The queryParameter name.
	 * @param string $value The queryParameter value.
	 * 
	 * @return Command the command object itself.
	 * @since  XXX
	 */
	public function addQueryParameter($key, $value) {
		$param = $this->noSqlDb->getQueryBuilder()->addQueryParam($key, $value);
		if (isset($this->commandData['queryParams']) === true) {
			$this->commandData['queryParams'] = array_merge($this->commandData['queryParams'], $param);
		} else {
			$this->commandData['queryParams'] = $param;
		}
		return $this;
	}
	
	/**
	 * This function attach an index to the current request.
	 * 
	 * @param unknown $indexName The index name.
	 * @param unknown $value     The index value.
	 * @param unknown $type      The index type (IndexType::TYPE_BIN || IndexType::TYPE_INT).
	 * 
	 * @return Command the command object itself.
	 * @since  XXX
	 */
	public function addIndex($indexName, $value, $type = IndexType::TYPE_BIN) {
		$index = $this->noSqlDb->getQueryBuilder()->addIndex($indexName, $value, $type);
		return $this->addHeaderField($this->key($index), $this->value($index));
	}
	
	/**
	 * This function attach a link to the current request.
	 * The pair $bucket $key should represent an existing object.
	 * 
	 * @param string $bucket The bucket of linked object.
	 * @param string $key    The key of linked object.
	 * @param string $tag    The tag name of the link.
	 * 
	 * @return Command the command object itself.
	 * @since  XXX
	 */
	public function addLink($bucket, $key, $tag) {
		$link = $this->noSqlDb->getQueryBuilder()->addLink($bucket, $key, $tag);
		
		if (isset($this->commandData['headers']) && isset($this->commandData['headers']['Link']) === true) {
			$this->addHeaderField('Link', $this->commandData['headers']['Link'].', '.$link); 
		} else {
			$this->addHeaderField('Link', $link);				
		}
		return $this;
	}
	
	/**
	 * This function add attach metadata to the current request.
	 * 
	 * @param string $key   The metadata key
	 * @param string $value The metadata value
	 * 
	 * @return Command the command object itself.
	 * @since  XXX
	 */
	public function addMetaData($key, $value) {
		$metaData = $this->noSqlDb->getQueryBuilder()->addMetaData($key, $value);
		return $this->addHeaderField($this->key($metaData), $this->value($metaData));
	}
	
	/**
	 * This function add an headerField.
	 * 
	 * @param unknown $key   The headerField key
	 * @param unknown $value The headerField value
	 * 
	 * @return Command the command object itself.
	 * @since  XXX
	 */
	public function addHeaderField($key, $value) {
		$headerField = array($key => $value);
		if (isset($this->commandData['headers']) === true) {
			$this->commandData['headers'] = array_merge($this->commandData['headers'], $headerField);
		} else {
			$this->commandData['headers'] = $headerField;
		}
		return $this;
	}
	

	
	/**
	 * Execute the query statement: select | insert | update | delete.
	 *
	 * @param Boolean $raw return with raw data or not
	 *
	 * @return Response object or array data of query
	 * @throws Exception execution failed
	 * @since XXX
	 */
//MOVED TEMPORARLY TO BASE COMMAND. (we'll se if other nosql execute function will differ).
//	abstract public function execute($raw = false);
	
	/**
	 * Execute the query statement: select | insert | update | delete.
	 *
	 * @param Boolean $raw return with raw data or not
	 *
	 * @return Response object or array data of query
	 * @throws Exception execution failed
	 * @since XXX
	 */
	public function execute($raw = false) {
		$this->noSqlDb->open();
		$mode = $this->mode;
		$result = null;
		$response = null;
		switch ($mode) {
			case 'select':
				$response = $this->noSqlDb->client->getObject($this->bucket, $this->key, $this->queryParams, $this->headers);
				break;
			case 'selectWithMapReduce' :
				$response = $this->noSqlDb->client->queryMapReduce($this->data);
				break;
			case 'selectWithIndex' :
				$response = $this->noSqlDb->client->queryIndexes(
								$this->bucket, 
								$this->key,
								$this->query->index['value'],
								$this->query->index['endValue'],
								$this->queryParams);
				break;
			case 'selectWithLink' :
				$response = $this->noSqlDb->client->queryLinks($this->bucket, $this->key, $this->query->links);
				break;
			case 'insert':
				$queryParams = $this->queryParams;
				if (isset($queryParams['returnbody']) === false) {
					$queryParams['returnbody'] = 'true';
				}
				$response = $this->noSqlDb->client->storeObject($this->bucket, $this->key, $this->data, $queryParams, $this->headers);
				break;
			case 'update':
				if (isset($this->headers['X-Riak-Vclock']) === false) {
					throw new Exception('VClock is needed to update an object.');
				} else {
					$queryParams = $this->queryParams;
					if (isset($queryParams['returnbody']) === false) {
						$queryParams['returnbody'] = 'true';
					}
					$response = $this->noSqlDb->client->storeObject($this->bucket, $this->key, $this->data, $queryParams, $this->headers);
				}
				break;
			case 'delete':
				$response = $this->noSqlDb->client->deleteObject($this->bucket, $this->key, $this->queryParams, $this->headers);
				break;
			case 'counters' :
				$response = $this->noSqlDb->client->updateCounters($this->bucket, $this->key, $this->data, $this->queryParams, $this->headers);
				break;
			case 'props' :
				$response = $this->noSqlDb->client->alterBucket($this->bucket, $this->data);
				break;
		}
		if ($raw === false && $response instanceof Response) {
			$result = new DataReader($response);
		} else if ($raw === true && $response instanceof Response) {
			$result = $response;
		}
		return $result;
	}
	
	
	
	/**
	 * Executes the query statement and returns query result.
	 *
	 * @return DataReader the reader object for fetching the query result
	 * @throws Exception execution failed
	 * @since XXX
	 */
	abstract public function query();

	/**
	 * Executes the query statement and returns ALL rows at once.
	 *
	 * @param Query $query The query to execute.
	 *
	 * @return array all rows of the query result. Each array element is an array representing a row of data.
	 * An empty array is returned if the query results in nothing.
	 * @since XXX
	 */
	abstract public function queryAll($query);

	/**
	 * Executes the query statement and returns the first row of the result.
	 * This method is best used when only the first row of result is needed for a query.
	 *
	 * @param Query $query The query to exec.
	 *
	 * @return array|boolean the first row (in terms of an array) of the query result. False is returned if the query
	 * results in nothing.
	 * @since XXX
	 */
	abstract public function queryOne($query);

	/**
	 * Set the bucket to use.
	 * 
	 * @param string $bucket The bucket name.
	 *
	 * @return void
	 * @since XXX
	 */
	public function setBucket($bucket) {
		$this->commandData['bucket'] = $bucket;
	}

	/**
	 * The bucket name getter
	 * 
	 * @return string
	 * @since  XXX
	 */
	public function getBucket() {
		return isset($this->commandData['bucket']) === true ? $this->commandData['bucket'] : null;
	}
	
	/**
	 * Set the key of object to GET/PUT/POST/DELETE
	 * 
	 * @param string $key The object key.
	 * 
	 * @return void
	 * @since XXX
	 */
	public function setKey($key) {
		$this->commandData['key'] = $key;
	}
	
	/**
	 * The key getter
	 * 
	 * @return string
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function getKey() {
		return isset($this->commandData['key']) === true ? $this->commandData['key'] : null;
	}
	
	/**
	 * Set the mode.
	 * See self::allowedMode to see allowed values for mode.
	 * 
	 * @param string $mode The mode to set.
	 * 
	 * @return void
	 * @since XXX
	 */
	public function setMode($mode) {
		$mode = strtolower($mode);
		if (in_array($mode, $this->allowedMode)) {
			$this->commandData['mode'] = $mode;
		} else {
			throw new Exception('Found unknown mode for command: ' . $mode);
		}
	}
	
	/**
	 * The mode getter
	 *  
	 * @return string
	 * @since  XXX
	 */
	public function getMode() {
		return isset($this->commandData['mode']) === true ? $this->commandData['mode'] : null;
	}	
		
	
	/**
	 * Set some additionnal headers.
	 * 
	 * @param array $headers Headers to set.
	 * <code>
	 * 		array(
	 *    		'headerKey' => 'headerValue',
	 * 		) 
	 * </code>
	 *
	 * @return void
	 * @since XXX
	 */
	public function setHeaders(array $headers) {
		$this->commandData['headers'] = $headers;
	}
	
	/**
	 * The request headers getter
	 * 
	 * @return array
	 * @since  XXX
	 */
	public function getHeaders() {
		return isset($this->commandData['headers']) === true ? $this->commandData['headers'] : array();
	}
	
	/**
	 * Set additionnal get params.
	 * 
	 * @param array $queryParams
	 * array(
	 *    'queryParamKey' => 'queryParamValue',
	 * );
	 * 
	 * @return void
	 * @since XXX
	 */
	public function setQueryParams(array $queryParams) {
		$this->commandData['queryParams'] = $queryParams;
	}
	
	/**
	 * The queryParams getter
	 * 
	 * @return array
	 * @since  XXX
	 */
	public function getQueryParams() {
		return isset($this->commandData['queryParams']) === true ? $this->commandData['queryParams'] : array();
	}
	
	/**
	 * Body request setter
	 * 
	 * @param array $data The body request data.
	 * 
	 * @return void
	 * @since XXX
	 */
	public function setData(array $data) {
		$this->commandData['data'] = $data;
	}
	
	/**
	 * Body request getter
	 * 
	 * @return array
	 * @since  XXX
	 */
	public function getData() {
		return isset($this->commandData['data']) === true ? $this->commandData['data'] : null;
	}

	/**
	 * Returns the first key of an array.
	 * 
	 * @param array $array The array
	 * 
	 * @return mixed The first key of the given array.
	 * @since  XXX
	 */
	private function key(array $array) {
		$keys = array_keys($array);
		return array_shift($keys);
	}
	
	/**
	 * Returns the first value of an array
	 * 
	 * @param unknown $array The array
	 * 
	 * @return mixed
	 * @since  XXX
	 */
	private function value($array) {
		$values = array_values($array);
		return array_shift($values);
	}
}
