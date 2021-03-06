<?php
/**
 * CreateAction.php
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

use yii\base\Model;
use yii\helpers\Url;
use yii\web\HttpException;
use Yii;

/**
 * CreateAction
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

        if ($model::isKeyMandatory()) {
            throw new HttpException(
                405,
                'Create object ['
                . get_class($model)
                . '] without key is not allowed. Use PUT instead.'
            );
        }

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
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
