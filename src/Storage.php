<?php
/**
 * File Storage.php
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

use yii\base\Component;

/**
 * Class Storage
 *
 * This class handle all the properties of storage stuff
 * bucket in Riak, collection in Mongo, ...
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
class Storage extends Component {

	/**
	 * @var Connection database connection.
	 */
	protected $_noSql;

	/**
	 * Get the database connection.
	 *
	 * @return Connection
	 * @since  XXX
	 */
	public function getNoSql() {
		return $this->_noSql;
	}

	/**
	 * Set the database connection.
	 *
	 * @param Connection $noSql the noSql database connection
	 *
	 * @return void
	 * @since  XXX
	 */
	public function setNoSql($noSql) {
		$this->_noSql = $noSql;
	}


	/**
	 * @var string name of storage space
	 */
	protected $_name;

	/**
	 * Get the storage name.
	 *
	 * @return string
	 * @since  XXX
	 */
	public function getName() {
		return $this->_name;
	}

	/**
	 * Set the storage name.
	 *
	 * @param string $name storage name
	 *
	 * @return void
	 * @since  XXX
	 */
	public function setName($name) {
		$this->_name = $name;
	}

}