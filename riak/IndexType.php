<?php
/**
 * File IndexType.php
 *
 * PHP version 5.3+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql.riak
 * @package   sweelix.nosql.riak
 */

namespace sweelix\yii2\nosql\riak;

/**
 * Class IndexType
 *
 * Contains constant for index type (bin or int)
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

class IndexType {
	const TYPE_INTEGER = '_int';
	const TYPE_BIN = '_bin';
}
