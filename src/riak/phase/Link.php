<?php
/**
 * File Link.php
 *
 * PHP version 5.3+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @author    Christophe Latour <clatour@ibitux.com> 
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

/**
 * Class Link
 *
 * This class encapsulate a link phase
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak.phase
 * @since     XXX
 */
class Link extends Component implements InterfacePhase {	
	/**
	 * @var string bucket name
	 */
	private $_bucket;
	
	/**
	 * @var mixed static argument passed to phase phase
	 */
	private $_keep;

	/**
	 * @var string tag name
	 */
	private $_tag;
	
	/**
	 * Build current phase and return the correct data
	 * or null if phase is invalid
	 *
	 * @return array
	 * @since  XXX
	 */
	public function build() {
		$operation = array();
		if($this->_bucket !== null) {
			$operation['bucket'] = $this->_bucket;
		}
		if($this->_keep !== null) {
			$operation['keep'] = $this->_keep;
		}
		if($this->_tag !== null) {
			$operation['tag'] = $this->_tag;
		}
		
		$phase = null;
		if (empty($operation) == false) {
			$phase = array(
				'link' => $operation,
			);
		}
		return $phase;
	}

	/**
	 * Define target bucket
	 *
	 * @param string $bucket bucket name
	 *
	 * @return void
	 * @since  XXX
	 */
	public function setBucket($bucket) {
		$this->_bucket = $bucket;
	}

	/**
	 * Get target bucket
	 *
	 * @return mixed
	 * @since  XXX
	 */
	public function getBucket() {
		return $this->_bucket;
	}

	/**
	 * Define related tag
	 *
	 * @param string $tag tag name
	 *
	 * @return void
	 * @since  XXX
	 */
	public function setTag($tag) {
		$this->_tag = $tag;
	}

	/**
	 * Get target tag name
	 *
	 * @return mixed
	 * @since  XXX
	 */
	public function getTag() {
		return $this->_tag;
	}


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
}