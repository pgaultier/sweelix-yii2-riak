<?php
/**
 * File Reduce.php
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

/**
 * Class Reduce
 *
 * This class encapsulate a reduce phase
 *
 * @author Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2013 Sweelix
 * @license http://www.sweelix.net/license license
 * @version XXX
 * @link http://www.sweelix.net
 * @category nosql
 * @package sweelix.nosql.riak.phase
 * @since XXX
 */
class Reduce extends Phase
{
    /**
     * @var string phase type
     */
    protected $phase = 'reduce';
}
