<?php
/**
 * File ActiveRecordInterface.php
 *
 * PHP version 5.4+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2014 Ibitux
 * @license   http://www.ibitux.com/license license
 * @version   XXX
 * @link      http://www.ibitux.com
 * @category  nosql
 * @package   sweelix.nosql.riak
 */
namespace sweelix\yii2\nosql\riak;

/**
 * Class ActiveRecordInterface
 *
 * @author Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2014 Ibitux
 * @license http://www.ibitux.com/license license
 * @version XXX
 * @link http://www.ibitux.com
 * @category nosql
 * @package sweelix.nosql.riak
 * @since XXX
 */
interface ActiveRecordInterface extends ActiveRecordDynamicInterface
{

    public static function bucketName();
}
