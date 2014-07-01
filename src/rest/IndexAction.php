<?php
/**
 * ActiveController.php
 *
 * PHP version 5.4+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://code.ibitux.net/redlix/
 * @category  controllers
 * @package   application
 */
namespace sweelix\yii2\nosql\rest;

use yii\base\NotSupportedException;

/**
 * ActiveController
 *
 * PHP version 5.3+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://code.ibitux.net/redlix/
 * @category  controllers
 * @package   application
 * @since     XXX
 */
class IndexAction extends Action
{
    public function run()
    {
        throw new NotSupportedException('List all bucket is not supported', 500);
    }
}
