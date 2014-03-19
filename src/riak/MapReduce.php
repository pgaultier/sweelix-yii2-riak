<?php
/**
 * File MapReduce.php
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

use yii\base\Component;
use sweelix\yii2\nosql\riak\phase\Phase;
use sweelix\yii2\nosql\riak\phase\Link;
use yii\log\Logger;
use Basho\Riak\Exception;

/**
 * Class MapReduce
 *
 * This class encapsulate a map/reduce request
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @author    Christophe Latour <clatour@ibitux.net>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak
 * @since     XXX
 */
class MapReduce extends Component {
	private $_inputs;
	private $_phases = array();
	private $_timeout;
	
	/**
	 * This function is used to define inputs.
	 * If input is only a bucket, use this once.
	 * If input is a bucket-key (or bucket-key-keyData), use this as much as you have inputs.
	 * 
	 * @param string $bucket  The bucket name
	 * @param string $key     The key of object
	 * @param string $keyData The keyData of object
	 * 
	 * @return \sweelix\yii2\nosql\riak\MapReduce
	 * @since  XXX
	 */
	public function addInput($bucket, $key=null, $keyData=null) {
		//Check 
		if (is_string($bucket) === false) {
			throw new Exception('Bucket should be a string');
		}
		if ($key !== null && is_string($this->_inputs) === true) {
			\Yii::log('Trace: '.__METHOD__.'()', Logger::LEVEL_WARNING, 'application.sweelix.nosql.riak');
			$this->_inputs = array();
		}
		
		//Assignements
		if ($key == null && $keyData == null) {
			$this->_inputs = $bucket;
		} elseif ($key != null) {
			$this->inputs[] = array($bucket, $key);
		} elseif ($key != null && $keyData != null) {
			$this->inputs[] = array($bucket, $key, $keyData);
		}
		return $this;
	}
	
	/**
	 * Set the indexInput of the current reduceMap
	 * 
	 * @param string $bucket    The bucket name
	 * @param string $indexName The object key
	 * @param string $value     The object value (or startValue if endValue not null).
	 * @param string $endValue  The endValue of search
	 * 
	 * @return \sweelix\yii2\nosql\riak\MapReduce
	 * @since  XXX
	 */
	public function setIndexedInput($bucket, $indexName, $value, $endValue=null) {
		$this->_inputs = array(
			'bucket' => $bucket, 
			'index' => $indexName
		);
		
		if ($endValue === null) {
			$this->_inputs['key'] = $value;
		} else {
			$this->_inputs['start'] = $value;
			$this->_inputs['end'] = $endValue;
		}
		return $this;
	}

	/**
	 * This function allows to add phase (Phase, Link, Reduce or Map) to the current mapReduce
	 * It will execute the different phases in function of the order that user added phase
	 * 
	 * @param Phase $phase The phase to add
	 * 
	 * @return \sweelix\yii2\nosql\riak\MapReduce
	 * @since  XXX
	 */
	public function addPhase(Phase $phase) {
		if(($phase instanceof Phase) || ($phase instanceof Link)) {
			$this->_phases[] = $phase;
		}
		return $this;
	}
	
	/**
	 * The timeout setter
	 * 
	 * @param string $timeout in milliseconds.
	 *
	 * @return void
	 * @since  XXX
	 */
	public function setTimeout($timeout) {
		$this->_timeout = $timeout;
	}
	
	/**
	 * Build inputs, phases and timeout, and return the correct data
	 * or null if no data was setted
	 * If encoded ret is setted to true, it will return the json_encoded data.
	 * 
	 * @param boolean $encodeRet whether to json_encode the result
	 * 
	 * @return mixed $ret The builded mapReduce
	 * @since  XXX
	 */
	public function build($encodeRet = false) {
		$ret = null;
		if (isset($this->_inputs) === true) {
			$ret['inputs'] = $this->_inputs;
		}
		
		foreach ($this->_phases as $phase) {
			$ret['query'][] = $phase->build();
		}
		
		if (isset($this->_timeout) === true) {
			$ret['timeout'] = $this->_timeout;
		}
		return $encodeRet === true ? json_encode($ret) : $ret;
	}
}