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
use yii\db\BaseActiveRecord as BaseActiveRecordYii;
use InvalidArgumentException;
use Exception;
use Yii;
use yii\base\UnknownMethodException;

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
 *
 * @property  boolean $isNewRecord
 * @property  array $metadata
 * @property  array $indexes
 */
abstract class ActiveRecord extends BaseActiveRecordYii implements ActiveRecordInterface
{
    /**
     * @var string|int the object key
     */
    public $key = null;

    /**
     * @var string the object vclock
     */
    private $vclock;

    /**
     * @var array
     */
    private $indexes = [];

    /**
     * @var array
     */
    private $meta = [];

    /**
     * @var array
     */
    private $rawLinks = [];

    /**
     * @var array
     */
    private $virtualAttributes = [];

    /**
     * @var array
     */
    private $related = [];

    /**
     * @var unknown
     */
    private $siblings = [];

    public static function find($context = null)
    {
        return static::createQuery(get_called_class())->fromBucket(static::bucketName());
    }

    /**
     * Retunrs the object with the key $key as an ActiveRecord
     *
     * @param string|int $key
     *
     * @return ActiveRecord
     * @since  XXX
     */
    public static function findOne($key)
    {
        $query = static::find();
        return $query->withKey($key)->accept('application/json , multipart/mixed')->one(static::getDb());
    }

    /**
     * @inheritdoc
     */
    public static function findByIndex($indexName, $indexValue, $endValue = null, $getFullObject = true)
    {
        $query = static::find();
        $indexType = self::indexType($indexName);
        $ret = [];

        if ($indexType === null) {
            throw new InvalidArgumentException($indexName.' is not and index.', 400);
        } else {
            $ret = $query->withIndex($indexName, $indexValue, $endValue, $indexType)->all(static::getDb());
            if ($getFullObject) {
                if (empty($ret) === false) {
                    $mapReduce = new MapReduce();
                    foreach ($ret as $key) {
                        $mapReduce->addInput(static::bucketName(), $key);
                    }
                    $mapReduce->addBasicMap();
                    $query = static::find();
                    $ret = $query->withMapReduce($mapReduce)->all(static::getDb());
                }
            }
        }
        return $ret;
    }

    public static function findByKeyFilter(KeyFilter $keyFilter)
    {
        $q = self::find();
        $keyFilter->bucketName = static::bucketName();

        $mapReduce = new MapReduce();
        $mapReduce->setKeyFilterInput($keyFilter)->addBasicMap();
        return $q->withMapReduce($mapReduce)->all(static::getDb());
    }

    public static function findByMapReduce($mapReduce)
    {
        $q = self::find();
        return $q->withMapReduce($mapReduce)->all(static::getDb());
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
        $this->oldAttributes = $this->attributes;
        if (! static::isKeyMandatory()) {
            $this->key = $ret['key'];
        }
//        $this->vclock = $ret['vclock'];
        self::populateRiakRecord($ret, $this);
        $ret = count($ret['values']);

        if ($ret > 1 && static::resolverClassName() !== null) {
            $resolver = Yii::createObject([ 'class' => static::resolverClassName() ]);

            $recordToSave = $resolver->resolve($this->siblings);
            $ret = $recordToSave->update();
        }
        return $ret;
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

        try {
            $row = $command->execute();
            $this->afterSave(false);
            $this->oldAttributes = $this->attributes;

            self::populateRiakRecord($row, $this);
            return count($row['values']);
        } catch (RiakException $e) {
            Yii::error('Update record (' . $this->key . ') from bucket ' . static::bucketName() . 'failed');
            return false;
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

        if (static::bucketName() !== null) {
            if ($mode === 'insert') {
                $command->insert(static::bucketName(), $this->key, $data);
            } else {
                $command->update(static::bucketName(), $this->key, $data)
                ->vclock($this->vclock);
            }
        } else {
            throw new InvalidConfigException(get_class($this) . '::bucketName() can\'t return null.', 500, null);
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
                $link = ResponseBuilder::buildLink($link);
                if ($link !== null) {
                    list($bucket, $key, $tag) = $link;
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
            throw new InvalidCallException('Cannot delete ' . get_class($this) . ' wihtout key.');
        }
        if ($this->beforeDelete()) {
            $command = static::getDb()->createCommand();

            $ret = $command->delete(static::bucketName(), $this->key)->execute();

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
        $rawLink = str_replace(
            ['{bucketName}', '{key}', '{tag}'],
            [$model::bucketName(), $model->key, $relation->link],
            ResponseBuilder::getLinkTemplate()
        );

        if (!in_array($rawLink, $this->rawLinks)) {
            if ($relation->primaryModel !== null) {
                $this->rawLinks[] = $rawLink;
                if ($this->isNewRecord || $model->isNewRecord) {
                    throw new InvalidCallException('Unable to link models: both models should not be new records.');
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
                        if ($add) {
                            $this->related[$name][] = $model;
                        }
                    } else {
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

        $link = str_replace(
            ['{bucketName}', '{key}', '{tag}'],
            [$model::bucketName(), $model->key, $relation->link],
            ResponseBuilder::getLinkTemplate()
        );

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
                unset($this->related[$name]);
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
        $this->findOne($this->key);
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
    * @param array $row The formatted response of response query.
    *
    * @return ActiveRecord the newly created active record.
    * @since  XXX
    */
    public static function populateRiakRecord($row, &$record = null)
    {
        \Yii::trace('Creating object with row : '.var_export($row, true)."\n", __CLASS__);
        //$record = null;
        if (is_array($row)) {
            if ($record === null) {
                $record = static::instantiate($row);
            }
            $record->meta = [];
            $record->attributes = [];
            $record->rawLinks = [];
            $record->related = [];
            $record->indexes = [];

            $record->vclock = $row['vclock'];
            $record->key = $row['key'];

            if (count($row['values']) === 1) {
                $object = $row['values'][0];
                if ($object['metadata']['content-type'] !== 'application/json') {
                    throw new Exception(
                        'Response content-type should be "application/json". Current content-type : "' .
                        $object['metadata']['content-type'] . "."
                    );
                }
                $attributes = json_decode($object['data'], true);
                $indexes = $object['metadata']['index'];
                foreach ($indexes as $name => $value) {
                    unset($indexes[$name]);
                    $name = substr($name, 0, strlen($name) - 4);
                    $indexes[$name] = $value;
                }
                $metadata = $object['metadata']['X-Riak-Meta'];
                foreach ($metadata as $name => $value) {
                    unset($metadata[$name]);
                    $name = str_replace('X-Riak-Meta-', '', $name);
                    $metadata[$name] = $value;
                }
                $links = $object['metadata']['Links'];

                foreach ($links as $link) {
                    $record->rawLinks[] = str_replace(
                        ['{bucketName}', '{key}', '{tag}'],
                        [$link[0], $link[1], $link[2]],
                        ResponseBuilder::getLinkTemplate()
                    );
                }
                $record->setAttributes($attributes);
                $record->setIndexes($indexes);
                $record->setMetadata($metadata);
            } else { //SIBLINGS
                //TODO : BUILD AR OR PASS RAW RESPONSE ?
                $data = $row['values'];
                $row['values'] = [];
                foreach ($data as $datum) {
                    $row['values'][0] = $datum;
                    $sibling = static::instantiate($row);
                    $record->siblings[] = self::populateRiakRecord($row, $sibling);
                }
            }
        }
        return $record;
    }


    /**
     * (non-PHPdoc)
     * @see \yii\db\BaseActiveRecord::hasMany()
     */
    public function hasMany($class, $link)
    {
        $query = self::find()->withKey($this->key);
//        $query->setMode('selectWithLink');
        $query->multiple = true;
        $query->link = $link;
        $query->linked($class::bucketName(), $link);
        return $query;
    }

    /**
     * (non-PHPdoc)
     * @see \yii\db\BaseActiveRecord::hasOne()
     */
    public function hasOne($class, $link)
    {
        $query = self::find()->withKey($this->key);
        $query->multiple = false;
        $query->link = $link;
        $query->linked($class::bucketName(), $link);
        return $query;
    }

    public function equals($record)
    {
        if ($this->isNewRecord || $record->isNewRecord) {
            return false;
        }
        return get_class($this) === get_class($record) && $this->vclock === $record->vclock;
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
        } elseif ($this->hasMetadata($name)) {
            return null;
        } else {
            $value = parent::__get($name);

            if ($value instanceof  ActiveQuery) {
                if ($value->multiple === true) {
                    $this->related[$name] = $value->all(static::getDb());
                } else {
                    $this->related[$name] = $value->one(static::getDb());
                }
                return $this->related[$name];
            } else {
                return $value;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if ($this->hasIndex($name)) {
            $this->setIndex($name, $value);
        } elseif ($this->hasMetadata($name)) {
            $this->setMetadata($name, $value);
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Throw Exception not used.
     *
     * (non-PHPdoc)
     * @see \yii\db\BaseActiveRecord::getRelation()
     */
    public function getRelation($name, $throwException = true)
    {
        $getter = 'get' . $name;
        try {
            // the relation could be defined in a behavior
            $relation = $this->$getter();
        } catch (UnknownMethodException $e) {
            throw new InvalidParamException(get_class($this) . ' has no relation named "' . $name . '".', 0, $e);
        }
        if (!$relation instanceof ActiveQuery) {
            throw new InvalidParamException(get_class($this) . ' has no relation named "' . $name . '".');
        }

        if (method_exists($this, $getter)) {
            // relation name is case sensitive, trying to validate it when the relation is defined within this class
            $method = new \ReflectionMethod($this, $getter);
            $realName = lcfirst(substr($method->getName(), 3));
            if ($realName !== $name) {
                throw new InvalidParamException('Relation names are case sensitive. ' . get_class($this) . " has a relation named \"$realName\" instead of \"$name\".");
            }
        }

        return $relation;
    }

    /**
     * @inheritdoc
     */
    public static function createQuery()
    {
        return new ActiveQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function findAll($condition)
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    /**
     * @inheritdoc
     */
    public static function primaryKey()
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    /**
     * (non-PHPdoc)
     * @see \yii\base\Model::attributes()
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
     * (non-PHPdoc)
     * @see \yii\db\BaseActiveRecord::setAttribute()
     */
    public function setAttribute($name, $value)
    {
        parent::setAttribute($name, $value);
        if ($this->hasIndex($name)) {
            $this->indexes[$name] = $value;
        }
    }

    /**
     * Returns an an array of autoIndex property
     *
     * @return array
     * @since  XXX
     */
    private function autoIndexes()
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
     * (non-PHPdoc)
     * @see \sweelix\yii2\nosql\riak\ActiveRecordInterface::indexes()
     */
    public function indexes()
    {
        $indexes = $this->autoIndexes();
        foreach (static::indexNames() as $key => $value) {
            if (is_string($key)) {
                if ($value === IndexType::TYPE_BIN || $value == IndexType::TYPE_INTEGER) {
                    $indexes[$key] = $value;
                } else {
                    throw new InvalidConfigException('Type of index named' . $key . ' is invalid.');
                }
            } else {
                $indexes[$value] = IndexType::TYPE_BIN;
            }
        }
        return $indexes;
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

    public function setIndexes($values)
    {
        foreach ($values as $name => $value) {
            $this->setIndex($name, $value);
        }
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
     * @see \sweelix\yii2\nosql\riak\ActiveRecordInterface::hasIndex()
     */
    public function hasIndex(&$name)
    {
        foreach ($this->indexes() as $indexName => $indexType) {
            if (strtolower($indexName) === $name) {
                $name = $indexName;
            }
        }
        return isset($this->indexes[$name]) || array_key_exists($name, $this->indexes());
    }

    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * (non-PHPdoc)
     * @see \sweelix\yii2\nosql\riak\ActiveRecordInterface::metadata()
     */
    public function metadata()
    {
        return static::metadataNames();
    }

    /**
     * (non-PHPdoc)
     * @see \sweelix\yii2\nosql\riak\ActiveRecordInterface::hasMetadata()
     */
    public function hasMetadata($name)
    {
        return isset($this->meta[$name]) || in_array($name, $this->metadata());
    }

    /**
     * (non-PHPdoc)
     * @see \sweelix\yii2\nosql\riak\ActiveRecordInterface::getMetadata()
     */
    public function getMetadata($name = null)
    {
        if ($name === null) {
            return $this->meta;
        } else {
            return isset($this->meta[$name]) ? $this->meta[$name] : null;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \sweelix\yii2\nosql\riak\ActiveRecordInterface::setMetadata()
     */
    public function setMetadata($name, $value = null)
    {
        if ($value !== null) {
            if ($this->hasMetadata($name)) {
                $this->meta[$name] = $value;
            } else {
                throw new InvalidParamException(get_class($this).' has not metadata named '.$name.'.');
            }
        } else {
            foreach ($name as $nam => $value) {
                if ($value === null) {
                    if ($this->hasMetadata($nam)) {
                        $this->meta[$nam] = null;
                    } else {
                        throw new InvalidParamException(get_class($this).' has not metadata named '. $nam . '.');
                    }
                } else {
                    $this->setMetadata($nam, $value);
                }
            }
        }
    }

    public function getSiblings()
    {
        return $this->siblings;
    }
}
