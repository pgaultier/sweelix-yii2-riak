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

use yii\rest\Action as BaseAction;
use yii\web\NotFoundHttpException;

/**
 * ActiveController
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
class Action extends BaseAction
{

    /**
     * (non-PHPdoc)
     * @see \yii\rest\Action::findModel()
     *
     * @since  XXX
     */
    public function findModel($id)
    {
        if ($this->findModel !== null) {
            return call_user_func($this->findModel, $id, $this);
        }

        $modelClass = $this->modelClass;
        $model = $modelClass::findOne($id);

        if (isset($model)) {
            return $model;
        } else {
            throw new NotFoundHttpException('Object not found : '. $id);
        }
    }
}
