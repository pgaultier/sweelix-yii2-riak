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
 * @package   sweelix.yii2.nosql.riak
 */

namespace sweelix\yii2\nosql\riak;

use sweelix\yii2\nosql\Command as BaseCommand;
use sweelix\yii2\nosql\Query;
use sweelix\yii2\nosql\DataReader;

/**
 * Class Command
 *
 * Represents a Riak statement to be executed against database.
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.yii2.nosql.riak
 * @since     XXX
 */
class Command extends BaseCommand {
	
	/**
	 * Executes the query statement and returns ALL rows at once. 
	 * The Query object to checked the indexes, mapReduce, link 
	 * to determine the query will use queryMapReduce or queryIndexes or queryLink
	 *
	 * @param Query $query is query object with prepared param's query
	 * 
	 * @return array all rows of the query result. Each array element is an array representing a row of data.
	 * An empty array is returned if the query results in nothing.
	 * @throws Exception execution failed
	 * @since XXX
	 */
	public function queryAll() {
		$this->noSqlDb->open();
		$dataReturn = array();
		// queryIndexes
		if (!empty($query->index) && $this->mode === 'selectWithIndex') {				
			$response = $this->noSqlDb->client->queryIndexes(
					$this->bucket,
					$query->index['indexName'],
					$query->index['value'],
					$query->index['endValue'],
					$this->queryParams);
			$body = $response->getBody();
			$body = $response->getData();
			$dataReader = new DataReader();
			foreach ($body['keys'] as $key) {
				$response = $this->noSqlDb->client->getObject($this->bucket, $key);
				$dataReader->addObject($response);
			}
			return $dataReader;
				
		}
		if (!empty($query->links) && $this->mode === 'selectWithLink') {
			$response = $this->noSqlDb->client->queryLinks($this->bucket, $this->key, $query->links);
		}
		// queryMapReduce or queryLink
		if (!empty($query->mapReduce) && $this->mode === 'selectWithMapReduce') {
			$response = $this->noSqlDb->client->queryMapReduce($this->data);
			$dataReader = new DataReader();
			$data = $response->getData();
			foreach ($data as $i => $obj) {
				$dataReader->addRawObject($obj);
			}
			return $dataReader;
		}
		return new DataReader($response);
	}

	/**
	 * Executes the query statement and returns the first row of the result.
	 * This method is best used when only the first row of result is needed for a query.
	 * The Query object to checked the indexes, mapReduce, link 
	 * to determine the query will use queryMapReduce or queryIndexes or queryLink
	 *
	 * @param Query $query is query object with prepare param's query
	 * 
	 * @return array|boolean the first row (in terms of an array) of the query result. False is returned if the query
	 * results in nothing.
	 * @throws Exception execution failed
	 * @since XXX
	 */
	public function queryOne() {
		$this->noSqlDb->open();
		$dataReturn = array();
		// queryIndexes
		if (!empty($query->index) && $this->mode === 'selectWithIndex') {
			$response = $this->noSqlDb->client->queryIndexes(
					$query->bucket, //BucketName 
					$query->index['indexName'], //IndexName
					$query->index['value'], //Value
					(isset($query->index['endValue']) === true ? $query->index['endValue'] : null)); //endValue if setted or null
			$dataReader = new DataReader();
			$body = $response->getData();
			foreach ($body['keys'] as $key) {
				$response = $this->noSqlDb->client->getObject($this->bucket, $key);
				$dataReader->addObject($response, $key);
				break;
			}
			return $dataReader;
		} else if (!empty($query->links) && $this->mode == 'selectWithLink') {
			$response = $this->noSqlDb->client->queryLinks($this->bucket, $this->key, $query->links);
		} else {
			$response = $this->noSqlDb->client->getObject($this->bucket, $this->key, $this->queryParams, $this->headers);
		} 
		return new DataReader($response);
	}

	/**
	 * Executes the query statement and returns query result.
	 *
	 * @return DataReader the reader object for fetching the query result
	 * @throws Exception execution failed
	 * @since XXX
	 */
	public function query() {
		
	}

	/**
	 * This function will add the Vclock field header to the request.
	 * 
	 * @param string $vclock The vclock of the object to update (mandatory for update).
	 * 
	 * @return Command The command object itself.
	 */
	public function vclock($vclock) {
		$this->addHeaderField('X-Riak-Vclock', $vclock);
		return $this;
	}
	
}
