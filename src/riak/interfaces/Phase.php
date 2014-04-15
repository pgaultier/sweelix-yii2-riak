<?php
/**
* File Phase.php
*
* PHP version 5.3+
*
* @author    Christophe	Latour <clatour@sweelix.net>
* @copyright 2010-2013 Sweelix
* @license   http://www.sweelix.net/license license
* @version   XXX
* @link      http://www.sweelix.net
* @category  nosql
* @package   sweelix.nosql.riak.phase
*/
namespace sweelix\yii2\nosql\riak\interfaces;

/**
 * Class Phase
 *
 * This class is an interface for mapReduce phase.
 *
 * @author Christophe Latour <clatour@sweelix.net>
 * @copyright 2010-2013 Sweelix
 * @license http://www.sweelix.net/license license
 * @version XXX
 * @link http://www.sweelix.net
 * @category nosql
 * @package sweelix.nosql.riak.phase
 * @since XXX
 */
interface Phase
{

    /**
     * This function build a setting array for Phase.
     *
     * @return array setting array phase.
     * @since XXX
     */
    public function build();
}
