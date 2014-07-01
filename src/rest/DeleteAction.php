<?php
/**
 * DeleteAction.php
 *
 * PHP version 5.5+
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

use Yii;

/**
 * DeleteAction
 *
 * PHP version 5.5+
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
class DeleteAction extends Action
{
    /**
     * Deletes a model.
     */
    public function run($id)
    {
        $model = $this->findModel($id);

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }

        $model->delete();

        Yii::$app->getResponse()->setStatusCode(204);
    }
}
