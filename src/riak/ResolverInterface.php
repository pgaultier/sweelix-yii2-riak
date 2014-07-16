<?php
/**
 * File ResolverInterface.php
 *
 * PHP version 5.4+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.yii2.nosql.riak
 */

namespace sweelix\yii2\nosql\riak;

/**
 * Class Resolver defines function to resolve siblings conflicts
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.yii2.nosql.riak
 * @since     XXX
 */

interface ResolverInterface
{

    /**
     * Resolve or merge the conflicting objects and return one that should be store back into riak.
     *
     * @param ActiveRecord[] $records
     *
     * @return ActiveRecord The record the should be store back into riak DB
     * @since  XXX
     */
    public function resolve($records);
}
