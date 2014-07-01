<?php
/**
 * CreateAction.php
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
use yii\base\Model;
use yii\helpers\Url;

/**
 * CreateAction
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
class CreateAction extends Action
{

    /**
     * @var string the scenario to be assigned to the new model before it is validated and saved.
     */
    public $scenario = Model::SCENARIO_DEFAULT;

    /**
     * @var string the name of the view action. This property is need to create the
     * URL when the mode is successfully created.
     */
    public $viewAction = 'view';


    /**
     * (non-PHPdoc)
     * @see \yii\rest\CreateAction::run()
     */
    public function run()
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id);
        }

        $model = new $this->modelClass([
            'scenario' => $this->scenario
        ]);

        $data = Yii::$app->getRequest()->getBodyParams();
        if (isset($data['key'])) {
            $model->key = $data['key'];
            unset($data['key']);
        }
        $model->load($data, '');

        if ($model->save()) {
            $response = Yii::$app->getResponse();
            if (!empty($model->siblings)) {
                $response->setStatusCode(300);
            } else {
                $response->setStatusCode(201);
            }
            $id = $model->key;
            $response->getHeaders()->set('Location', Url::toRoute([$this->viewAction, 'id' => $id], true));
        }

        return $model;
    }
}
