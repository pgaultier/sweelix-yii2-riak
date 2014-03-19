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
 * @package   sweelix.nosql.riak
 */

namespace sweelix\yii2\nosql\riak;

use sweelix\yii2\nosql\QueryBuilder as BaseQueryBuilder;

/**
 * Class Query
 *
 * This class handle all the queries (findByKey, mapreduce, ...)
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak
 * @since     XXX
 */
class QueryBuilder extends BaseQueryBuilder {

	/**
	 * Build the query parameters statement
	 *
	 * @param Query $query is the query object
	 * 
	 * @return Query object with builded parameters
	 * @since XXX
	 */
/*	public function build($query) {
		parent::build($query);
		// build meta header
		if (count($query->metadata) > 0) {
			$this->buildMetadata($query);
		}
		// build indexes header
		if (count($query->index) > 0) {
			$this->buildIndex($query);
		}
		// build link of object
		if (count($query->links) > 0) {
			$this->buildLink($query);
		}
		// build additional parameters
		$this->buildAdditionalParameters($query);
		return $query;
	}*/

	/**
	 * Build additional meta header of object (set the header of request with Metadata)
	 * 
	 * @param Query $query is the query object
	 * 
	 * @return none
	 * @since  XXX
	 */
	protected function buildMetadata($query) {
		foreach ($query->metadata as $key => $value) {
			$query->headers['X-Riak-Meta-' . $key] = $value;
		}
	}

	/**
	 * Build indexes header of object.
	 *
	 * @param Query $query is the query object
	 * 
	 * @return none
	 * @since  XXX
	 */
	protected function buildIndex($query) {
		foreach ($query->indexes as $key => $value) {
			$query->headers['X-Riak-Index-' . $key] = $value;
		}
	}

	/**
	 * Build link relate of object
	 *
	 * @param Query $query is the query object
	 * 
	 * @return none
	 * @since  XXX
	 */
	protected function buildLink($query) {
		$query->headers['Link'] = implode(',', $query->links);
	}

	/**
	 * Build additional parameters of object
	 *
	 * @param Query $query is the query object
	 * 
	 * @return none
	 * @since  XXX
	 */
	protected function buildAdditionalParameters($query) {
		if ($query->w !== null) {
			$query->additionalParameters['w'] = $query->w;
		}
		if ($query->dw !== null) {
			$query->additionalParameters['dw'] = $query->dw;
		}
		if ($query->w !== null) {
			$query->additionalParameters['pw'] = $query->pw;
		}
	}

	/**
	 * Build functions map parameters's request. 
	 * Analyzer the Query->_mapReduce array to json content to set body for request 
	 *
	 * @param Query $query is the query object
	 * 
	 * @return none
	 * @since  XXX
	 */
	protected function buildMap($query) {
		
	}

	/**
	 * Build functions reduce parameters's request.
	 * Analyzer the Query->_mapReduce array to json content to set body for request 
	 *
	 * @param Query $query is the query object
	 * 
	 * @return none
	 * @since  XXX
	 */
	protected function buildReduce($query) {
		
	}

}