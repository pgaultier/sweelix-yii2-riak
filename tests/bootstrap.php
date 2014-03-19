<?php

// ensure we get report on all possible php errors
error_reporting(-1);

define('YII_ENABLE_ERROR_HANDLER', false);
define('YII_DEBUG', true);
$_SERVER['SCRIPT_NAME'] = '/' . __DIR__;
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

require_once(dirname(__DIR__).'/vendor/autoload.php');
require_once(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');


Yii::setAlias('@sweelix/yii2/nosql/tests', __DIR__);

/*Yii::setAlias('@sweelix/yii2/nosql', __DIR__.'/../src/');
Yii::setAlias('@sweelix/curl', __DIR__.'/../vendor/sweelix/curl/');*/