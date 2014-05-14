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
 * @package   sweelix.nosql.riak
 */
namespace sweelix\yii2\nosql\riak;

use yii\base\InvalidCallException;
use yii\base\InvalidParamException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord as BaseActiveRecordYii;
use InvalidArgumentException;
use Exception;
use Yii;

/**
 * Class ActiveRecord
 *
 * This class handle all the records and mimic classic
 * sql ActiveRecord management
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak
 * @since     XXX
 */
abstract class ActiveRecord extends BaseActiveRecordYii implements ActiveRecordInterface
{
    /**
     * The object key
     *
     * @var string|int
     */
    public $key = null;

    private $vclock;

    private $indexes = [];

    private $meta = [];

    private $rawLinks = [];

    private $virtualAttributes = [];

    private $related = [];

    private $context = [];

    private $bucketName;

    private static $contextVars;

    public static function getContextVars()
    {
        self::isDynamicRecord();
        return self::$contextVars;
    }

    public function setContext(array $context)
    {
        foreach (self::getContextVars() as $contextVar) {
            if (!array_key_exists($contextVar, $context)) {
                throw new InvalidConfigException(
                    get_called_class($this) . ' : Missing context variable { ' . $contextVar . '}'
                );
            }
        }
        $this->context = $context;
    }

    public static function isDynamicRecord()
    {
        if (self::$contextVars === null) {
            $ret = true;
            $pattern = '/{([^}]+)}/';
            $res = preg_match_all($pattern, static::bucketName(), $matches);
            if ($res >= 1) {
                foreach ($matches[1] as $repKey) {
                    self::$contextVars[] = $repKey;
                }
            } else {
                self::$contextVars = [];
            }
        }
        return !empty(self::$contextVars);
    }

    public static function findAll($condition = null, $context = null)
    {
        $query = static::find($context);
        return $query->fromBucket(self::resolveBucketName($context))->withMapReduce()->genericMapping()->all(static::getDb());
    }

    public static function find($context = null)
    {
        if (static::isDynamicRecord()) {
            if ($context !== null) {
                return static::createQuery(get_called_class())->fromBucket(self::resolveBucketName($context));
            } else {
                throw new InvalidArgumentException(__METHOD__ . ' : expect context.');
            }

        } else {
            if ($context !== null) {
                Yii::warning('Context is not expected here.');
            }
            return static::createQuery(get_called_class());
        }

    }

    public static function findOne($key)
    {
        $query = static::find();
        return $query->withKey($key)->one();
    }

    /**
     * @inheritdoc
     */
    public static function findOneByIndex($indexName, $indexValue, $endValue = null)
    {
        $query = static::find();
        $indexType = self::indexType($indexName);

        if ($indexType === null) {
            throw new InvalidArgumentException($indexName.' is not and index.', 400);
        } else {
            return $query->withIndex($indexName, $indexValue, $endValue)->one(static::getDb());
        }
    }

    /**
     * @inheritdoc
     */
    public static function findAllByIndex($indexName, $indexValue, $indexEndValue)
    {
        $query = static::find();
        $indexType = self::indexType($indexName);

        if ($indexType === null) {
            throw new InvalidArgumentException($indexName.' is not and index.', 400);
        } else {
            return $query->withIndex($indexName, $indexValue, $indexEndValue, $indexType)->all(static::getDb());
        }
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        $attributes = [];
        foreach (static::attributeNames() as $key => $value) {
            if (is_int($key)) {
                $attributes[] = $value;
            } else {
                $attributes[] = $key;
            }
        }
        return $attributes;
    }

    /**
     * Returns an an array of autoIndex property
     *
     * @return array
     * @since  XXX
     */
    public function autoIndexes()
    {
        $autoIndexes = array();
        foreach (static::attributeNames() as $key => $value) {
            if (is_array($value) && isset($value['autoIndex']) === true) {
                if ($value['autoIndex'] === IndexType::TYPE_BIN || $value['autoIndex'] === IndexType::TYPE_INT) {
                    $autoIndexes[$key] = $value['autoIndex'];
                } else {
                    $autoIndexes[$key] = IndexType::TYPE_BIN;
                }
            }
        }
        return $autoIndexes;
    }

    /**
     * Returns all object's index name
     *
     * @return array
     * @since  XXX
     */
    public function indexes()
    {
        $indexes = $this->autoIndexes();
        foreach (static::indexNames() as $key => $value) {
            if (is_string($key)) {
                if ($value === IndexType::TYPE_BIN || $value == IndexType::TYPE_INTEGER) {
                    $indexes[$key] = $value;
                } else {
                    $indexes[$key] = IndexType::TYPE_BIN;
                }
            } else {
                $indexes[$value] = IndexType::TYPE_BIN;
            }
        }
        return $indexes;
    }

    /**
     * Returns all object's metadata names
     *
     * @return array
     * @since  XXX
     */
    public function metadata()
    {
        return static::metadataNames();
    }

    /**
     * save current record
     *
     * This method will call [[insert()]] when [[isNewRecord]] is true, or [[update()]]
     * when [[isNewRecord]] is false.
     *
     * @param boolean $runValidation
     *            whether to perform validation before saving the record.
     *            If the validation fails, the record will not be saved to database.
     * @param array $attributes
     *            list of attributes that need to be saved. Defaults to null,
     *            meaning all attributes that are loaded from DB will be saved.
     *
     * @return boolean whether the saving succeeds
     * @since XXX
     */
    public function save($runValidation = true, $attributes = null)
    {
        $result = null;
        if ((static::isKeyMandatory() && empty($this->key) === false) || ! static::isKeyMandatory()) {
            if ($this->isNewRecord) {
                $result = $this->insert($runValidation, $attributes);
            } else {
                $result = $this->update($runValidation, $attributes);
            }
        } else {
            throw new \Exception('Unable to save ActiveRecord (' . get_class($this) . ') whitout a key.');
        }
        return $result;
    }

    /**
     * This function is called by [[ActiveRecord]]::save() if the [[ActiveRecord]] isNewRecord.
     *
     * @param string $runValidation
     *            wheter to run validation.
     * @param string $attributes
     *            attribute's name to validate.
     *
     * @return boolean int if validation fail or an integer corresponding
     * to the number of row affected (numbers of siblings).
     * @since XXX
     */
    public function insert($runValidation = true, $attributes = null)
    {
        if ($runValidation && ! $this->validate($attributes)) {
            return false;
        }
        if (! $this->beforeSave(true)) {
            return false;
        }

        $command = $this->createCommand('insert');

        $ret = $command->execute();
        $this->afterSave(true);
        $obj = $ret->current();
        $this->oldAttributes = $this->attributes;
        if (! static::isKeyMandatory()) {
            $this->key = self::getKeyFromLocation($obj);
        }
        $this->vclock = $obj[DataReader::VCLOCK_KEY];

        return $ret->count();
    }

    /**
     * Update a row into te associated bucketName and object key.
     *
     * @param boolean $runValidation
     *            whether to run validation.
     * @param array $attributes
     *            attributes to validate.
     *
     * @return boolean int if validation fail or an integer corresponding
     * to the number of row affected (numbers of siblings).
     * @since  XXX
     */
    public function update($runValidation = true, $attributes = null)
    {
        if ($runValidation && ! $this->validate($attributes)) {
            return false;
        }

        if (! $this->beforeSave(false)) {
            return false;
        }

        $command = $this->createCommand('update');

        $ret = $command->execute();
        $this->afterSave(false);
        $affected = $ret->count();
        $this->oldAttributes = $this->attributes;

        if ($affected === 1) {
            $obj = $ret->current();
            $this->attributes = $obj[DataReader::DATA_KEY];
            $this->vtag = $obj[DataReader::SIBLINGS_KEY];
            $this->vclock = $obj[DataReader::VCLOCK_KEY];
            return 1;
        } else {
            $this->vclock = null;
            return $affected;
        }
    }

    /**
     * Return the command to 'insert' or 'update' the current ActiveRecord.
     * It builds data (attributes), indexes (autoIndexes + normal indexes), metadata and links
     *
     * @param string $mode 'insert' or 'update'.
     *
     * @return Command $command The command with Links, indexes, and metada attached.
     * @since XXX
     */
    private function createCommand($mode)
    {
        $command = static::getDb()->createCommand(static::getDb());
        $data = array();
        foreach ($this->attributes() as $name) {
            $data[$name] = $this->$name;
        }

        $command instanceof Command;

        if ($this->getBucketName() !== null) {
            if ($mode === 'insert') {
                $command->insert($this->getBucketName(), $this->key, $data);
            } else {
                $command->update($this->getBucketName(), $this->key, $data)
                ->vclock($this->vclock);
            }
        } else {
            throw new InvalidConfigException('You should set the bucketName before saving', 500, null);
        }

        //METADATA
        foreach ($this->metadata() as $name) {
            $value = $this->$name;
            if (isset($value)) {
                $command = $command->addMetaData($name, $value);
            }
        }


        //Indexes
        foreach ($this->indexes() as $name => $type) {
            $value = $this->$name;
            if (isset($value)) {
                $command = $command->addIndex($name, $value, $type);
            }
        }

        //LINKS
        if ($this->rawLinks) {
            foreach ($this->rawLinks as $link) {
                if (preg_match('/<\/buckets\/(\w+)\/keys\/(\w+)>; riaktag="(\w+)"/', $link, $match) > 0) {
                    list ($all, $bucket, $key, $tag) = $match;
                    $command->addLink($bucket, $key, $tag);
                }
            }
        }
        return $command;
    }

    /**
     * Deletes the table row corresponding to this active record.
     *
     * This method performs the following steps in order:
     *
     * 1. call [[beforeDelete()]]. If the method returns false, it will skip the
     * rest of the steps;
     * 2. delete the record from the database;
     * 3. call [[afterDelete()]].
     *
     * In the above step 1 and 3, events named [[EVENT_BEFORE_DELETE]] and [[EVENT_AFTER_DELETE]]
     * will be raised by the corresponding methods.
     *
     * @return boolean wheter [[ActiveRecord]] has been deleted.
     * @since  XXX
     */
    public function delete()
    {
        $ret = false;
        if ($this->isNewRecord) {
            throw new Exception('Cannot delete ' . get_class($this) . ' wihtout key.');
        }
        if ($this->beforeDelete()) {
            $command = static::getDb()->createCommand();

            $response = $command->delete($this->getBucketName(), $this->key)->execute();

            $row = $response->current();
            if ($row[DataReader::RESPONSESTATUS_KEY] === 204) {
                $ret = true;
            }

            $this->oldAttributes = null;
            $this->vclock = null;
            $this->afterDelete();
        }
        return $ret;
    }

    /**
     * Establishes the relationship between two models.
     *
     * The relationship is established by setting the foreign key value(s) in one model
     * to be the corresponding primary key value(s) in the other model.
     * The model with the foreign key will be saved into database without performing validation.
     *
     *
     * Note that this method requires that the primary key value is not null.
     *
     * @param string       $name  the case sensitive name of the relationship
     * @param ActiveRecord $model the model to be linked with the current one.
     *
     * @return void
     * @since  XXX
     */
    public function link($name, $model, $extraColumns = [])
    {
        $relation = $this->getRelation($name);

        $rawLink = '</buckets/'.$model->getBucketName().'/keys/'.$model->key.'>; riaktag="'.$relation->riakTag.'"';
        if (!in_array($rawLink, $this->rawLinks)) {
            if ($relation->primaryModel !== null) {
                $this->rawLinks[] = $rawLink;
                if ($this->isNewRecord && $model->isNewRecord) {
                    throw new InvalidCallException('Unable to link models: both models must NOT be newly created.');
                } elseif (!$this->isNewRecord && $this->isNewRecord) {
                    if ($model->save() === false) {
                        throw new Exception(
                            'An error has been occured, when trying to link model [['.$model->getBucketName().']]'
                        );
                    }
                    $model->refresh();
                }
                if ($relation->multiple) {
                    $add = true;
                    if (isset($this->related[$name])) {
                        foreach ($this->related[$name] as $modelTmp) {
                            if ($modelTmp->equals($model)) {
                                $add = false;
                                break;
                            }
                        }
                        $this->related[$name][] = $model;
                    }
                } else {
                    $this->related[$name] = $model;
                }
            } else {
                throw new InvalidConfigException('Configuration of relation \''.$name.'\' isn\'t correct.');
            }
        } else {
            \Yii::warning("Trying to add an existing link\n", 'sweelix.nosql.riak');
        }
    }

    /**
     * Unlink the relationship between current [[ActiveRecord]] and the given [[ActiveRecord]] $model
     * for the named reltion $name
     *
     * @param string       $name  the case sensitive name of the relationship
     * @param ActiveRecord $model the model to be unlinked with the current one
     *
     * @return void
     * @since  XXX
     */
    public function unlink($name, $model, $delete = false)
    {
        $relation = $this->getRelation($name);

        $link = '</buckets/'.$model->getBucketName().'/keys/'.$model->key.'>; riaktag="'.$relation->riakTag.'"';
        foreach ($this->rawLinks as $i => $rawLink) {
            if ($rawLink === $link) {
                unset($this->rawLinks[$i]);
            }
        }

        if (isset($this->related[$name]) === true) {
            if ($relation->multiple === true) {
                foreach ($this->$name as $i => $obj) {
                    if ($model->equals($obj)) {
                        unset($this->related[$name][$i]);
                    }
                }
            } else {
                $this->related[$name] = null;
            }
        }
    }

    /**
     * Refresh the current row
     * If current row has siblings, just _attributes is updated.
     * The object will be separated in two part.
     * The data before you save the object (_oldAttributes contains object data, _indexes contains indexes,
     * _meta and _link same)
     * Then you have the siblings part. (_vtag contains _vtag of siblings).
     * You can get all siblings (as [[ActiveRecord]]) like doing this :
     * 	[[ActiveRecord]]->siblings; -> will return an array of siblings
     *
     * @return boolean wheter the resolve has been executed successfully.
     * @since  XXX
     */
    public function refresh()
    {
        $this->oldAttributes = $this->attributes;
        $record = $this->find($this->key);
        if ($record === false) {
            return false;
        }
        if (!empty($record->vtag)) {
            $this->attributes = $record->attributes;
        } else {
            $this->attributes = $record->attributes;
            $this->indexes = $record->indexes;
            $this->meta = $record->meta;
            $this->virtualAttributes = $record->virtualAttributes;
        }
        $this->vtag = $record->vtag;
        $this->vclock = $record->vclock;
        return true;
    }

    /**
     * Returns the type of an index.
     *
     * @param string $indexName The indexname
     *
     * @return null|IndexType
     * @since XXX
     */
    public static function indexType($indexName)
    {
        $indexType = null;
        $indexesName = static::indexNames();
        $attributesName = static::attributeNames();
        if (in_array($indexName, $indexesName)) {
            $indexType = IndexType::TYPE_BIN;
        } elseif (array_key_exists($indexName, $indexesName)) {
            $indexType = $indexesName[$indexName];
        } elseif (array_key_exists($indexName, $attributesName)) {
            $tmp = $attributesName[$indexName];
            if (is_array($tmp) && array_key_exists('autoIndex', $tmp)) {
                $indexType = $tmp['autoIndex'];
            }
        }
        return $indexType;
    }


    /**
    * Called by ActiveQuery to populate the result into array
    * Creates an object using a row of data.
    * This method is called internally to populate the query results
    * into Model. It is not meant to be used to create new records.
    *
    * @param DataReader $row The formatted response ([[DataReader]]) of the query.
    *
    * @return ActiveRecord the newly created active record.
    * @since  XXX
    */
    public static function create($row)
    {
        \Yii::trace('Creating object with row : '.var_export($row, true)."\n", __CLASS__);
        $record = null;
        if ($row !== null && $row[DataReader::RESPONSESTATUS_KEY] !== 404) {
            $record = static::instantiate($row);

            if (isset($row[DataReader::OBJECT_KEY])) {
                $record->key = $row[DataReader::OBJECT_KEY];
            } else {
                if (isset($row[DataReader::HEADERS_KEY]) === true && isset($row[DataReader::HEADERS_KEY]['Location']) === true) {
                    if (preg_match('/\/buckets\/\w+\/keys\/(\w+)/', $row[DataReader::HEADERS_KEY]['Location'], $matches) > 0) {
                        $record->key = $matches[1];
                    }
                }
            }
            $attributes = $record->attributes(); //ATTRIBUTES NAME
            $indexes = $record->indexes(); //INDEXES NAME
            $metadata = $record->metadata(); //META NAME

            $record->vclock = $row[DataReader::VCLOCK_KEY];

            //ASSIGN ATTRIBUTES
            foreach ($row[DataReader::DATA_KEY] as $attributeName => $attributeValue) {
                if (in_array($attributeName, $attributes)) {
                    $record->$attributeName = $attributeValue;
                } else {
                    \Yii::warning($attributeName.' is not declared in static property $attributesName');
                }
            }

            //ASSIGN INDEXES
            foreach ($row[DataReader::INDEX_KEY] as $indexName => $indexValue) {
                foreach ($record->indexes() as $name => $type) {
                    if (strtolower($indexName) === strtolower($name)) {
                        $record->$name = $indexValue[0];
                    } else {
                        \Yii::warning($attributeName.' is not defined in static property $indexesName');
                    }
                }
            }
            //ASSIGN METADATA
            foreach ($row[DataReader::META_KEY] as $metaName => $metaValue) {
                foreach ($record->metadata() as $name) {
                    if (strtolower($metaName) === strtolower($name)) {
                        $record->$name = $metaValue;
                    } else {
                        \Yii::warning($metaName.' is undefined in the static property $metadataName');
                    }
                }
            }

            $record->rawLinks = $row[DataReader::LINK_KEY];

            $record->afterFind();
        }
        return $record;
    }


    /**
     * Return the object key using his header (Location)
     *
     * @param array $obj
     *
     * @return null string object key
     * @since XXX
     */
    private static function getKeyFromLocation($obj)
    {
        $key = null;
        if (isset($obj[DataReader::HEADERS_KEY]) === true &&
            isset($obj[DataReader::HEADERS_KEY]['Location']) === true) {
            if (preg_match('/\/buckets\/\w+\/keys\/(\w+)/', $obj[DataReader::HEADERS_KEY]['Location'], $matches) > 0) {
                $key = $matches[1];
            }
        }
        return $key;
    }

    /**
     * (non-PHPdoc)
     * @see \sweelix\yii2\nosql\riak\ActiveRecordInterface::hasMetadatum()
     */
    public function hasMetadatum($name)
    {
        return isset($this->meta[$name]) || in_array($name, $this->metadata());
    }

    /**
     * (non-PHPdoc)
     * @see \sweelix\yii2\nosql\riak\ActiveRecordInterface::getMetadatum()
     */
    public function getMetadatum($name)
    {
        return isset($this->meta[$name]) ? $this->meta[$name] : null;
    }

    /**
     * (non-PHPdoc)
     * @see \sweelix\yii2\nosql\riak\ActiveRecordInterface::setMetadata()
     */
    public function setMetadata($name, $value)
    {
        if ($this->hasMetadatum($name)) {
            $this->meta[$name];
        } else {
            throw new InvalidParamException(get_class($this).' has not metadata named '.$name.'.');
        }
    }

    /**
     * (non-PHPdoc)
     * @see \sweelix\yii2\nosql\riak\ActiveRecordInterface::getMetadata()
     */
    public function getMetadata()
    {
        return $this->meta;
    }

    /**
     * (non-PHPdoc)
     * @see \sweelix\yii2\nosql\riak\ActiveRecordInterface::hasIndex()
     */
    public function hasIndex($name)
    {
        return isset($this->indexes[$name]) || array_key_exists($name, $this->indexes());
    }

    /**
     * (non-PHPdoc)
     * @see \sweelix\yii2\nosql\riak\ActiveRecordInterface::getIndex()
     */
    public function getIndex($name)
    {
        return isset($this->indexes[$name]) ? $this->indexes[$name] : null;
    }

    /**
     * (non-PHPdoc)
     * @see \sweelix\yii2\nosql\riak\ActiveRecordInterface::getIndexes()
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * (non-PHPdoc)
     * @see \sweelix\yii2\nosql\riak\ActiveRecordInterface::setIndex()
     */
    public function setIndex($name, $value)
    {
        if ($this->hasIndex($name)) {
            $this->indexes[$name] = $value;
            if ($this->hasAttribute($name)) {
                $this->setAttribute($name, $value);
            }
        } else {
            throw new InvalidParamException(get_class($this).' has not index named '.$name.'.');
        }
    }

    /**
     * (non-PHPdoc)
     * @see \yii\db\BaseActiveRecord::getIsNewRecord()
     */
    public function getIsNewRecord()
    {
        return !isset($this->vclock);
    }

    /**
     *
     * @param unknown $context
     * @throws InvalidParamException
     * @return mixed
     */
    public static function resolveBucketName($context)
    {
        $bucketName = static::bucketName();
        if (self::isDynamicRecord()) {
            $diff = array_diff(array_keys($context), self::getContextVars());
            if (empty($diff) && !empty($context)) {
                foreach ($context as $name => $replace) {
                    $bucketName = str_replace('{' . $name . '}', $replace, $bucketName, $count);
                }
            } else {
                throw new InvalidParamException('Context not valid.');
            }
        }
        return $bucketName;
    }

    /**
     * (non-PHPdoc)
     * @see \sweelix\yii2\nosql\riak\ActiveRecordInterface::getBucketName()
     */
    public function getBucketName()
    {
        if ($this->bucketName === null) {
            $this->bucketName = static::resolveBucketName($this->context);
        }
        return $this->bucketName;
    }

    /**
     * @param unknown $bucket
     */
    public function setBucketName($bucket)
    {
        $this->bucketName = $bucket;
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if (isset($this->indexes[$name]) || array_key_exists($name, $this->indexes)) {
            return $this->indexes[$name];
        } elseif ($this->hasIndex($name)) {
            return null;
        } elseif (isset($this->meta[$name]) || array_key_exists($name, $this->meta)) {
            return $this->meta[$name];
        } elseif ($this->hasMetadatum($name)) {
            return null;
        } else {
            return parent::__get($name);
        }
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if ($this->hasIndex($name)) {
            $this->setIndex($name, $value);
        } elseif ($this->hasMetadatum($name)) {
            $this->setMetadata($name, $value);
        } else {
            parent::__set($name, $value);
        }
    }

    public static function createQuery()
    {
        return new ActiveQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function primaryKey()
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    /**
     * @inheritdoc
     */
    public static function updateAll($attributes, $condition = '')
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    /**
     * @inheritdoc
     */
    public static function deleteAll($condition = null)
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    /**
     * @inheritdoc
     */
    public static function updateAllCounters($counters, $condition = '', $params = [])
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }
}
