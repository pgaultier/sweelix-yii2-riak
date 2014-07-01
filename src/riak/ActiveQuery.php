<?php
/**
 * File ActiveQuery.php
 *
 * PHP version 5.3+
 *
 * @author    Christophe Latour <pgaultier@sweelix.net>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak
 */
namespace sweelix\yii2\nosql\riak;

use sweelix\yii2\nosql\riak\Query;
use Yii;

/**
 * Class ActiveQuery
 *
 * This class handle all the queries (findByKey, mapreduce, ...)
 *
 * @author    Chrisotphe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak
 * @since     XXX
 */
class ActiveQuery extends Query
{

    /**
     * @var string name of model(s) to return.
     */
    public $primaryModel;


    /**
     * @var unknown
     */
    public $link;

    /**
     * @var boolean
     */
    public $multiple;

    /**
     * Initializing object.
     * Setting the bucket query.
     *
     * @param unknown $config
     *            the class configuration
     *
     * @return void
     * @since  XXX
     */
    public function __construct($modelClass, $config = array())
    {
        $this->primaryModel = $modelClass;
        parent::__construct($config);
    }

    /**
     * Return one record (always a record or null).
     *
     * @param string $db
     *            the db to query.
     *
     * @return return array a record or null
     * @since XXX
     */
    public function one($db = null)
    {
        $model = null;
        $command = $this->createCommand($db);
        $class = $this->getQueryClass();
        try {
            $data = $command->queryOne();
            \Yii::info(var_export($data, true), __METHOD__);
            if (isset($data)) {
                $model = $class::populateRiakRecord($data);
            }
        } catch (RiakException $e) {
            $model = null;
        }
        return $model;
    }

    /**
     * Return all fetched records (always an array or null).
     *
     * @param string $db
     *            the db to query.
     *
     * @return array fetched records or null.
     * @since XXX
     */
    public function all($db = null)
    {
        $command = $this->createCommand($db);
        $models = array();

        $class = $this->getQueryClass();

        $data = $command->queryAll();
        if ($command->mode !== 'selectWithIndex') {
            foreach ($data as $row) {
                $model = $class::populateRiakRecord($row);
                $models[] = $model;
            }
            return $models;
        } else {
            return $data;
        }

    }

    /**
     * @inheritdoc
     */
    protected function getQueryClass()
    {
        return $this->primaryModel;
    }
}
