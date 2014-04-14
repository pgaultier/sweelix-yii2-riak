<?php
/**
 * File ActiveRecordDynamicInterface.php
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

use yii\db\ActiveRecordInterface as BaseActiveRecordInterface;

/**
 * Class ActiveRecordDynamicInterface
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2014 Ibitux
 * @license   http://www.ibitux.com/license license
 * @version   XXX
 * @link      http://www.ibitux.com
 * @category  nosql
 * @package   sweelix.nosql.riak
 * @since     XXX
 */
interface ActiveRecordDynamicInterface extends BaseActiveRecordInterface
{
    public static function isKeyMandatory();

    public static function attributesName();

    public static function indexesName();

    public static function metadataName();
}
