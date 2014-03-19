<?php

/**
 * File Connection.php
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

use sweelix\yii2\nosql\Connection as BaseConnection;

/**
 * Class Connection
 *
 * This class allow user to connect to a riak database
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
class Connection extends BaseConnection {
	/**
	 * @var integer the R-value for this connection
	 */
	private $_r=2;

	/**
	 * Get the R-value for this storage
	 * Returns the storage R-value if it is set,
	 * otherwise return the R-value for the transport.
	 *
	 * @return integer
	 * @since  XXX
	 */
	public function getR() {
		return $this->_r;
	}

	/**
	 * Set the R-value for this bucket
	 *
	 * @param integer $r The new R-value.
	 *
	 * @return void
	 * @since  XXX
	 */
	public function setR($r) {
		$this->_r = $r;
	}

	/**
	 * @var integer the W-value for this storage
	 */
	private $_w=2;

	/**
	 * Get the W-value for this storage
	 * Returns the storage W-value if it is set,
	 * otherwise return the W-value for the transport.
	 *
	 * @return integer
	 * @since  XXX
	 */
	public function getW() {
		return $this->_w;
	}

	/**
	 * Set the W-value for this bucket
	 *
	 * @param integer $w The new W-value.
	 *
	 * @return void
	 * @since  XXX
	 */
	public function setW($w) {
		$this->_w = $w;
	}

	/**
	 * @var integer the DW-value for this storage
	 */
	private $_dw=2;

	/**
	 * Get the DW-value for this storage
	 * Returns the storage DW-value if it is set,
	 * otherwise return the DW-value for the transport.
	 *
	 * @return integer
	 * @since  XXX
	 */
	public function getDw() {
		return $this->_dw;
	}

	/**
	 * Set the DW-value for this bucket
	 *
	 * @param integer $dw The new DW-value.
	 *
	 * @return void
	 * @since  XXX
	 */
	public function setDw($dw) {
		$this->_dw = $dw;
	}

}