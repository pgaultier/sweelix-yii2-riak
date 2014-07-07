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

use yii\base\Model;
use Yii;

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
class UpdateAction extends Action
{
    /**
     * @var string the scenario to be assigned to the model before it is validated and updated.
     */
    public $scenario = Model::SCENARIO_DEFAULT;

    /**
     * Updates an existing model.
     * @param string $id the primary key of the model.
     * @return \yii\db\ActiveRecordInterface the model being updated
     * @throws \Exception if there is any error when updating the model
     */
    public function run($id)
    {

        $modelClass = $this->modelClass;
        $model = $modelClass::findOne($id);
        if ($model === null) {
            $model = new $modelClass([
                'scenario' => $this->scenario
            ]);
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            $model->save();
            $response = Yii::$app->getRequest();
            if (!empty($model->siblings)) {
                $response->setStatusCode(300);
            } else {
                $response->setStatusCode(201);
            }
        } else {
            if ($this->checkAccess) {
                call_user_func($this->checkAccess, $this->id, $model);
            }
            $model->scenario = $this->scenario;
            $model->load(Yii::$app->getRequest()->getBodyParams(), '');
            $model->save();
        }


        return $model;
    }
}
