<?php
/**
 * File ActiveRecord.php
 *
 * PHP version 5.3+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql
 */
namespace sweelix\yii2\nosql\riak;

use sweelix\yii2\nosql\riak\ActiveQuery;

/**
 * Class ActiveRelation
 *
 * This class handle relation between [[ActiveRecord]]
 *
 * @author Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license http://www.sweelix.net/license license
 * @version XXX
 * @link http://www.sweelix.net
 * @category nosql
 * @package sweelix.nosql
 * @since XXX
 */
class ActiveRelation extends ActiveQuery
{

    /**
     *
     * @var boolean whether this relation should populate all query results into AR instances.
     *      If false, only the first row of the results will be retrieved.
     */
    public $multiple;

    /**
     *
     * @var ActiveRecord the primary model that this relation is associated with.
     *      This is used only in lazy loading with dynamic query options.
     */
    public $primaryModel;

    /**
     *
     * @var sting $riakTag The tag associate with object.
     */
    public $riakTag;

    /**
     * @var $bucketName The bucket where to search.
     */
    public $bucketName;

    /**
     * The constructor
     *
     * @param array $config
     *            The config
     *
     * @return void
     * @since XXX
     */
    public function __construct($config = array())
    {
        parent::__construct(null, $config);


        $model = $this->primaryModel;
        $this
        ->fromBucket($this->primaryModel->getBucket())
        ->withKey($this->primaryModel->key)
        ->linked($this->bucketName, $this->riakTag, 1);
    }

    /**
     * Returns the model to create.
     *
     * @return string The model class to create.
     * @since XXX
     */
    protected function getQueryClass()
    {
        return $this->primaryModel;
    }
}
