<?php
/**
 * ActiveController.php
 *
 * PHP version 5.4+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @author    Philippe Gaultier <pgaultier@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  controllers
 * @package   application
 */

namespace sweelix\yii2\nosql\rest;

/**
 * ActiveController
 *
 * PHP version 5.5+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @author    Philippe Gaultier <pgaultier@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  controllers
 * @package   application
 * @since     XXX
 */
class ViewAction extends Action
{
    /**
     * Displays a model.
     *
     * @param string $id the primary key of the model.
     *
     * @return \sweelix\yii2\nosql\riak\ActiveRecord the model being displayed
     * @since  XXX
     */
    public function run($id)
    {
        $model = $this->findModel($id);
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }
        return $model;
    }
}
