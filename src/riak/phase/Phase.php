<?php
/**
 * File Phase.php
 *
 * PHP version 5.3+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak.phase
 */

namespace sweelix\yii2\nosql\riak\phase;

use yii\base\Component;
use sweelix\yii2\nosql\riak\interfaces\Phase as InterfacePhase;
use yii\web\HttpException;

/**
 * Class Phase
 *
 * This class encapsulate a phase phase
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak.phase
 * @since     XXX
 */
class Phase extends Component implements InterfacePhase {

	protected $_phase;
	
	/**
	 * Build current phase and return the correct data
	 * or null if phase is invalid
	 *
	 * @return array
	 * @since  XXX
	 */
	public function build() {
		$phase = null;
		if(($this->_phase !== null) && ($this->_language !== null) && ($this->_handler !== null)) {
			$operation = $this->_handler;
			$operation['language'] = $this->_language;
			if($this->_argument !== null) {
				$operation['arg'] = $this->_argument;
			}
			if($this->_keep !== null) {
				$operation['keep'] = $this->_keep;
			}
			$phase = array(
				$this->_phase => $operation,
			);
		}
		return $phase;
	}

	/**
	 * @var string language, can be javascript or erlang
	 */
	private $_language = 'javascript';

	/**
	 * Define language used by phase phase
	 *
	 * @param string $language function language
	 *
	 * @return void
	 * @since  XXX
	 */
	public function setLanguage($language) {
		$this->_language = $language;
	}

	/**
	 * Get language used by phase phase
	 *
	 * @return string
	 * @since  XXX
	 */
	public function getLanguage() {
		return $this->_language;
	}

	/**
	 * @var mixed static argument passed to phase phase
	 */
	private $_argument;

	/**
	 * Define static argument which will be passed to phase function
	 *
	 * @param mixed $argument static argument passed to phase function
	 *
	 * @return void
	 * @since  XXX
	 */
	public function setArgument($argument) {
		$this->_argument = $argument;
	}

	/**
	 * Get static argument which will be passed to phase function
	 *
	 * @return mixed
	 * @since  XXX
	 */
	public function getArgument() {
		return $this->_argument;
	}

	/**
	 * @var mixed static argument passed to phase phase
	 */
	private $_keep;

	/**
	 * Define if result should be kept or not
	 *
	 * @param mixed $keep check if result should be kept
	 *
	 * @return void
	 * @since  XXX
	 */
	public function setKeep($keep) {
		$this->_keep = $keep;
	}

	/**
	 * Get if result should be kept or not
	 *
	 * @return mixed
	 * @since  XXX
	 */
	public function getKeep() {
		return $this->_keep;
	}

	/**
	 * @var array the phase function definition
	 */
	private $_handler;

	/**
	 * Define raw function (in erlang / javascript)
	 *
	 * @param string $rawFunction raw script
	 *
	 * @return void
	 * @since  XXX
	 */
	public function setRawFunction($rawFunction) {
		$this->_handler = array(
			'source' => $rawFunction
		);
	}

	/**
	 * Get raw function or null if not defined
	 *
	 * @return string
	 * @since  XXX
	 */
	public function getRawFunction() {
		return (isset($this->_handler['source']) === true)?$this->_handler['source']:null;
	}

	/**
	 * Define builtin function to use
	 *
	 * @param string $namedFunction builtin function name
	 *
	 * @return void
	 * @since  XXX
	 */
	public function setNamedFunction($namedFunction) {
		$this->_handler = array(
			'name' => $namedFunction
		);
	}

	/**
	 * Get builtin function or null if not defined
	 *
	 * @return string
	 * @since  XXX
	 */
	public function getNamedFunction() {
		return (isset($this->_handler['name']) === true)?$this->_handler['name']:null;
	}

	/**
	 * Define stored procedure to use
	 *
	 * @param string $bucket bucket containing stored procedure
	 * @param string $key    key of the object containing stored procedure
	 *
	 * @return void
	 * @since  XXX
	 */
	public function setStoredProcedure($bucket, $key) {
		//TODO: check how to handle erlang version (module / function)
		$this->_handler = array(
			'bucket' => $bucket,
			'key' => $key,
		);
	}

	/**
	 * Get stored procedure or null if not defined
	 *
	 * @return string
	 * @since  XXX
	 */
	public function getStoredProcedure() {
		$procedure = null;
		if((isset($this->_handler['bucket']) === true) && (isset($this->_handler['key']) === true)) {
			$procedure = array_values($this->_handler);
		}
		return $procedure;
	}
	
	/**
	 * Define module to use (Not implemented yet)
	 * 
	 * @param string $module The module to set.
	 *
	 * @return void
	 * @since  XXX
	 */
	public function setModule($module) {
		throw new HttpException(501);
//		$this->_handler['module'] = $module;
	}
	
	/**
	 * Get the module to use or null if not defined (Not implemented yet)
	 * 
	 * @return Ambigous <NULL, multitype:>
	 */
	public function getModule() {
		throw new HttpException(501);
		return (isset($this->_handler['module']) === true) ? $this->_handler['module'] : null;
	}
}