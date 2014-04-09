<?php
/**
 * File ActiveQuery.php
 *
 * PHP version 5.3+
 *
 * @author    Christophe Latour <pgaultier@sweelix.net>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak
 */

namespace sweelix\yii2\nosql\riak;

use sweelix\yii2\nosql\ActiveQuery as BaseActiveQuery;

/**
 * Class ActiveQuery
 *
 * This class handle all the queries (findByKey, mapreduce, ...)
 *
 * @author    Chrisotphe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak
 * @since     XXX
 */
class ActiveQuery extends BaseActiveQuery {
	/**
	 * @var string name of model(s) to return.
	 */
	public $modelClass;

	/**
	 * Initializing object.
	 * Setting the bucket query.
	 *
	 * @param unknown $config the class configuration
	 *
	 * @return void
	 * @since  XXX
	 */
	public function __construct($modelClass, $config = array()) {
		$this->modelClass = $modelClass;
		parent::__construct($config);
	}

	/**
	 * TODO
	 */
	public function bucketName() {
		if ($this->_bucket === null) {
			$modelClass = $this->modelClass;
			return $modelClass::bucketName();
		} else {
			return $this->_bucket;
		}
	}

	/**
	 * Set the map reduce mode.
	 * (non-PHPdoc)
	 *
	 * @param mixed $inputs string or array.
	 *  - string : a bucket name
	 *  - array : array(
	 *  	array(
	 *  		bucketName,
	 *  		objectKey,
	 *  		keydata
	 *      ),
	 *      //ETC...
	 *  )
	 *
	 * @see \sweelix\yii2\nosql\Query::withMapReduce()
	 *
	 * @return ActiveQuery The object itself.
	 * @since  XXX
	 */
	public function withMapReduce($inputs = null) {
		parent::withMapReduce($inputs);
		if ($this->_bucket === null) {
			$this->_bucket = $this->bucketName();
		}
		return $this;
	}

	/**
	 * Return one record (always a record or null).
	 *
	 * @param string $db the db to query.
	 *
	 * @return return array a record or null
	 * @since  XXX
	 */
	public function one($db = null) {
		static $i = 0;
		$model = null;
		$command = $this->createCommand($db);
		$class = $this->modelClass;
		$data = $command->queryOne();


		\Yii::info(var_export($data, true), __METHOD__);

		if (isset($data)) {
			$model = $class::create($data);
		}
		return $model;
	}

	/**
	 * Return all fetched records (always an array or null).
	 *
	 * @param string $db the db to query.
	 *
	 * @return array fetched records or null.
	 * @since  XXX
	 */
	public function all($db = null) {
		$command = $this->createCommand($db);
		$models = array();

		$class = $this->queryClass;

		$data = $command->queryAll();
		foreach ($data as $row) {
			$models[] = $class::create($row);
		}
		return $models;
	}

	/**
	 * This function add a map phase to the mapreduce.
	 * It map each objects with the DataReader object form.
	 *
	 * <code>
	 * 	obj = array(
	 * 		'.status' => 200, //statuscode
	 *      '.key' => 'test', //objectKey
	 *      '.vclock' => 'test', //the vclock object.
	 *      'data' => 'data', //object data.
	 *      '.link' => '</buckets/test/keys/test>; riaktag="test"', //object's links.
	 *      '.index' => array(
	 *         'indexName' => array('indexValue', 'indexType')
	 *      ),
	 *      '.meta' => array(
	 *        'metaName' => 'metaValue'
	 *     	)
	 *  );
	 * </code>
	 *
	 * NOTE : This function will be able to construct object only if the given object list hasn't been yet modified
	 * Rappel :
	 * The basic riak object is designed like that :
	 * <code>
	 * 	{
	 *     'bucket' : 'foo',
	 *     'key' : 'bar',
	 *     'vclock' : '...',
	 *     'values' : [
	 *       {
	 *          'metadata' : {...},
	 *          'data' : {...}
	 *       }
	 *     ]
	 *  }
	 * </code>
	 *
	 * @return ActiveQuery The ActiveQuery object itself.
	 * @since  XXX
	 */
	public function genericMapping() {
		$map = new Map();
		$map->setRawFunction('function(value, keydata, arg) {
			var data = JSON.parse(value[\'values\'][0][\'data\']);
			var keys = value.values[0].metadata.length;
			var meta = {};
			for (var i in value.values[0].metadata["X-Riak-Meta"]) {
				var tmp = i.replace("X-Riak-Meta-", "");
				meta[tmp] = value.values[0].metadata["X-Riak-Meta"][i];
			}

			var indexes = {};
			for (var i in value.values[0].metadata.index) {
				var tmp = i.split("_");
				indexes[tmp[0]] = [value.values[0].metadata.index[i], tmp[1]];
			}

			var links = [];
			for (var i in value.values[0].metadata.Links) {
				var bucket = value.values[0].metadata.Links[i][0];
				var key = value.values[0].metadata.Links[i][1];
				var tag = value.values[0].metadata.Links[i][2];
				links.push("</buckets/" + bucket + "/keys/" + key + ">; riaktag=\'"+ tag + "\'");
			}
			var obj = {};
			obj[".status"] = 200;
			obj[".key"] = value.key;
			obj[".vclock"] = value.vclock;
			obj["data"] = data;
			obj[".link"] = links;
			obj[".index"] = indexes;
			obj[".meta"] = meta;
			return [obj];
		}');
		if ($this->_mapReduce == null) {
			$this->_mapReduce = new MapReduce();
		}
		$this->_mapReduce->addPhase($map);
		return $this;
	}
}