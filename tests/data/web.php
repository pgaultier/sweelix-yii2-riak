<?php
/**
 * web.php
 *
 * PHP version 5.3+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2014 Ibitux
 * @license   http://www.ibitux.com/license license
 * @version   XXX
 * @link      https://code.ibitux.net/projects/sweelix-yii2-nosql
 * @category  tests
 * @package   sweelix.yii2.nosql.tests
 */

$config = array(
    'components' => array(
        'riak' => array(
            'class' => 'sweelix\yii2\nosql\riak\Connection',
            'dsn' => 'riak:dsn=http://192.168.1.123:8098'
        ),

        'riakError' => array(
            'class' => 'sweelix\yii2\nosql\Connection',
            'dsn' => 'YOUSHALLNOTPASS'
        ),
        'nosqlError' => array(
            'class' => 'sweelix\yii2\nosql\riak\Connection',
            'dsn' => 'nosql:dsn=http://192.168.1.123:8098'
        ),

        'riakcs' => array(
            'class' => 'sweelix\yii2\nosql\riakcs\Connection',
            'proxy' => '192.168.1.123:8080',
            'accessKey' => 'STEIORR72X2J0HBFQ-GD',
            'secretKey' => 't_tfgGSvxYcJAmdjguGYqwJt8CuB8V8xNyfNqQ==',
            'useSsl' => false,
            'useExceptions' => false
        ),
        'riakcsError' => array(
            'class' => 'sweelix\yii2\nosql\riakcs\Connection',
            'proxy' => 'testForFail', // Will
                                      // fail
                                      // to
                                      // connect
            'accessKey' => 'STEIORR72X2J0HBFQ-GD',
            'secretKey' => 't_tfgGSvxYcJAmdjguGYqwJt8CuB8V8xNyfNqQ==',
            'useSsl' => false,
            'useExceptions' => false
        ),
        'log' => array(
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => array(
                array(
                    'class' => 'yii\log\FileTarget',
                    'levels' => array(
                        'error',
                        'warning',
                        'trace',
                        'info'
                    )
                )
            )
        )
    )
);

return $config;
