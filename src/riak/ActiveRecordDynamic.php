<?php
/**
 * File ActiveRecord.php
 *
 * PHP version 5.3+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak
 */
namespace sweelix\yii2\nosql\riak;

use sweelix\yii2\nosql\riak\ActiveRelation;
use sweelix\yii2\nosql\riak\DataReader;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\NotSupportedException;
use yii\base\UnknownMethodException;
use yii\db\ActiveRecord as BaseActiveRecord;
use Exception;
use InvalidArgumentException;
use Yii;

/**
 * Class ActiveRecord
 *
 * This class handle all the records and mimic classic
 * sql ActiveRecord management
 *
 * @author Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2013 Sweelix
 * @license http://www.sweelix.net/license license
 * @version XXX
 * @link http://www.sweelix.net
 * @category nosql
 * @package sweelix.nosql.riak
 * @since XXX
 */
abstract class ActiveRecordDynamic extends BaseActiveRecord implements ActiveRecordDynamicInterface
{

    /**
     * The object key
     *
     * @var string|int
     */
    public $key = null;

    /**
     * The current bucket name.
     *
     * @var string
     */
    private $bucket;

    /**
     * The object's vclock
     *
     * @var string
     */
    protected $vclock;

    /**
     * The object's indexes
     *
     * @var array
     */
    protected $indexes = [];

    /**
     * The object's metadata
     *
     * @var array
     */
    protected $meta = [];

    /**
     *
     * @var unknown
     */
//    protected $siblings;

    /**
     * The object's vtags
     *
     * @var string
     */
    protected $vtag;

    /**
     * The object's raw links
     *
     * @var array
     */
    protected $rawLinks;

    /**
     * The object's attributes
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The object's oldAttributes
     *
     * @var array
     */
    protected $oldAttributes = [];

    /**
     * The object's virtual attributes
     *
     * @var array
     */
    protected $virtualAttributes = [];

    /**
     * The object's raltion
     *
     * @var array
     */
    protected $related = [];

    /**
     * @inheritdoc
     */
    public static function getDb()
    {
        return Yii::$app->get('nosql');
    }

    /**
     * @inheritdoc
     */
    public static function find()
    {
        $args = func_get_args();
        if (empty($args)) {
            throw new InvalidArgumentException(get_called_class() . ' need first parameter to be a bucket.');
        }

        $bucketName = array_shift($args);
        if (! is_string($bucketName)) {
            throw new InvalidArgumentException(
                get_called_class() . '\'s first parameter need to be a string ($bucketName)'
            );
        }
        $query = static::createQuery();
        $query->fromBucket($bucketName);

        if (! empty($args)) {
            if (is_string($args[0]) || is_int($args[0])) {
                $query = $query->withKey($args[0])->one(static::getDb());
            } else {
                throw new InvalidArgumentException(
                    get_called_class() . ': 2nd argument needs to be a key (int or string)'
                );
            }
        }
        return $query;
    }

    /**
     * This function search object from his index.
     *
     * @param string     $indexName  The index name to search
     * @param string|int $indexValue The index value to search
     * @param string     $bucketName The bucketname where to search
     *
     * @return ActiveRecord|null
     * @since  XXX
     */
    public static function findOneByIndex($indexName, $indexValue, $bucketName = null)
    {
        $query = self::find($bucketName);
        $indexType = self::indexType($indexName);

        if ($indexType !== null) {
            $model = $query->withIndex($indexName, $indexValue, null, $indexType)->one(static::getDb());
        } else {
            throw new InvalidArgumentException($indexName . ' is not an index', 400);
        }
        return $model;
    }

    /**
     * This function search objects from their index.
     *
     * @param string     $indexName     The index name to search
     * @param string|int $indexValue    The index start value to search
     * @param string|int $indexEndValue The index end value to search
     * @param string     $bucketName    The bucket name where to search
     *
     * @return ActiveRecord[]
     * @since  XXX
     */
    public static function findAllByIndex($indexName, $indexValue, $indexEndValue, $bucketName = null)
    {
        $indexType = self::indexType($indexName);

        if ($indexType !== null) {
            $models = $query->withIndex($indexName, $indexValue, $indexEndValue, $indexType)->all(static::getDb());
        } else {
            throw new InvalidArgumentException($indexName . ' is not an index', 400);
        }
        return $models;
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

            $response = $command->delete($this->getBucket(), $this->key)->execute();

            $row = $response->current();
            if ($row[DataReader::RESPONSESTATUS_KEY] === 204) {
                $ret = true;
            }

            $this->_oldAttributes = null;
            $this->_vclock = null;
            $this->afterDelete();
        }
        return $ret;
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        $attributes = [];
        foreach (static::attributesName() as $key => $value) {
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
        foreach (static::attributesName() as $key => $value) {
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
        foreach (static::indexesName() as $key => $value) {
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
        return static::metadataName();
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
     * Returns the type of an index.
     *
     * @param string $indexName
     *            The indexname
     *
     * @return null IndexType
     * @since XXX
     */
    private static function indexType($indexName)
    {
        $indexType = null;
        $indexesName = static::indexesName();
        $attributesName = static::attributesName();
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

        if ($this->getBucket() !== null) {
            if ($mode === 'insert') {
                $command->insert($this->getBucket(), $this->key, $data);
            } else {
                $command->update($this->getBucket(), $this->key, $data)
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
            $record = static::instantiate($row[DataReader::DATA_KEY]);

            if (isset($row[DataReader::OBJECT_KEY])) {
                $record->key = $row[DataReader::OBJECT_KEY];
            } else {
                $record->key = self::getKeyFromLocation($row);
            }

            $attributes = $record->attributes(); //ATTRIBUTES NAME
            $indexes = $record->indexes(); //INDEXES NAME
            $metadata = $record->metadata(); //META NAME*/

            $record->vclock = $row[DataReader::VCLOCK_KEY];

            //ASSIGN ATTRIBUTES
            foreach ($row[DataReader::DATA_KEY] as $attributeName => $attributeValue) {
                if (in_array($attributeName, $attributes)) {
                    $record->attributes[$attributeName] = $attributeValue;
                } else {
                    $record->virtualAttributes[$attributeName] = $attributeValue;
                }
            }



            foreach ($row[DataReader::INDEX_KEY] as $indexName => $indexValue) {
                foreach ($indexes as $name => $type) {
                    $realIndexName = substr($indexName, 0, strlen($indexName) - 4);
                    if (strtolower($realIndexName) === strtolower($name)) {
                        $record->indexes[$name] = $indexValue;
                    } /*else {
                        \Yii::warning($attributeName.' is not defined in static property $indexesName');
                    }*/
                }
            }

            //ASSIGN METADATA
            foreach ($row[DataReader::META_KEY] as $metaName => $metaValue) {
                foreach ($metadata as $name) {
                    if (strtolower($metaName) === strtolower($name)) {
                        $record->meta[$name] = $metaValue;
                    }/* else {
                        \Yii::warning($metaName.' is undefined in the static property $metadataName');
                    }*/
                }
            }

            //ASSIGN SIBLINGS
            if (isset($row[DataReader::SIBLINGS_KEY])) {
                $record->vtag = $row[DataReader::SIBLINGS_KEY];
            }


            //ASSING LINKS
            $record->rawLinks = $row[DataReader::LINK_KEY];
            $record->oldAttributes = $record->attributes;
            $record->afterFind();
        }
        return $record;
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

        $rawLink = '</buckets/'.$model->getBucket().'/keys/'.$model->key.'>; riaktag="'.$relation->riakTag.'"';
        if (!in_array($rawLink, $this->rawLinks)) {
            if ($relation->primaryModel !== null) {
                $this->rawLinks[] = $rawLink;
                if ($this->isNewRecord && $model->isNewRecord) {
                    throw new InvalidCallException('Unable to link models: both models must NOT be newly created.');
                } elseif (!$this->isNewRecord && $this->isNewRecord) {
                    if ($model->save() === false) {
                        throw new Exception(
                            'An error has been occured, when trying to link model [['.$model->getBucket().']]'
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

        $link = '</buckets/'.$model->getBucket().'/keys/'.$model->key.'>; riaktag="'.$relation->riakTag.'"';
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
     *
     * @param string $arClass    The model name linked
     * @param string $riakTag    The tag link
     * @param string $bucketName The bucket where the linked object is.
     *
     * @return \sweelix\yii2\nosql\riak\ActiveRelation
     * @since  XXX
     */
    public function hasOneInBucket($arClass, $riakTag, $bucketName)
    {
        return new ActiveRelation(array(
            'modelClass' => $this,
            'multiple' => true,
            'primaryModel' => $this->getNamespacedClass($arClass),
            'riakTag' => $riakTag,
            'bucketName' => $bucketName,
        ));
    }

    public function hasManyInBucket($arClass, $riakTag, $bucketName)
    {
        return new ActiveRelation(array(
            'modelClass' => $this,
            'multiple' => true,
            'primaryModel' => $this->getNamespacedClass($arClass),
            'riakTag' => $riakTag,
            'bucketName' => $bucketName,
        ));
    }

    /**
     * Declares a `has-one` relation.
     * The declaration is returned in terms of link object instance
     * through which the related record can be queried and retrieved back.
     * A `has-one` relation means that there is at most one related record matching
     * the criteria set by this relation, e.g., a customer has one country.
     *
     * @param string $arClass the bucket name of the related record
     * @param array  $riakTag the defined link name.
     *
     * @return ActiveRelation the relation object (link object).
     * @since  XXX
     */
    public function hasOne($arClass, $riakTag)
    {
        $tmp = new $arClass();
        if ($arClass instanceof ActiveRecord) {
            return new ActiveRelation(array(
                'modelClass' => $this,
                'multiple' => false,
                'primaryModel' => $this->getNamespacedClass($arClass),
                'bucketName' => $arClass::bucketName(),
                'riakTag' => $riakTag,
            ));
        } else {
            throw new NotSupportedException('You must use haOneInBucket() to link an ActiveRecordDynamic', 500);
        }
    }


    /**
     * Declares a `has-many` relation.
     * The declaration is returned in terms of link object instance
     * through which the related record can be queried and retrieved back.
     *
     * @param string $arClass the bucket name of the related record
     * @param array  $riakTag the defined link name.
     *
     * @return ActiveRelation the relation object.
     * @since  XXX
     */
    public function hasMany($arClass, $riakTag)
    {
        $tmp = new $arClass();
        if ($tmp instanceof ActiveRecord) {
            return new ActiveRelation(array(
                'modelClass' => $this,
                'multiple' => true,
                'primaryModel' => $this->getNamespacedClass($arClass),
                'bucketName' => $arClass::bucketName(),
                'riakTag' => $riakTag,
            ));
        } else {
            throw new NotSupportedException('You must use hasManyInBucket() to link an ActiveRecordDynamic', 500);
        }
    }

    /**
     * Changes the given class name into a namespaced one.
     * If the given class name is already namespaced, no change will be made.
     * Otherwise, the class name will be changed to use the same namespace as
     * the current AR class.
     *
     * @param string $class the class name to be namespaced
     *
     * @return string the namespaced class name
     * @since  XXX
     */
    protected static function getNamespacedClass($class)
    {
        if (strpos($class, '\\') === false) {
            $reflector = new \ReflectionClass(static::className());
            return $reflector->getNamespaceName() . '\\' . $class;
        } else {
            return $class;
        }
    }

    /**
     * Returns indexes as ['indexName' => 'indexValue']
     *
     * @return array
     * @since  XXX
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * Returns metadata as ['metaName' => 'metaValue']
     *
     * @return array
     * @since  XXX
     */
    public function getMetadata()
    {
        return $this->meta;
    }

    /**
     * Is new record ?
     *
     * (non-PHPdoc)
     * @see \yii\db\BaseActiveRecord::getIsNewRecord()
     *
     * @return boolean
     * @since  XXX
     */
    public function getIsNewRecord()
    {
        return !isset($this->vclock);
    }

    /**
     * The bucketName setter.
     *
     * @param $bucketName The bucketname to set
     *
     * @return void
     * @since XXX
     */
    public function setBucket($bucketName)
    {
        $this->bucket = $bucketName;
    }

    /**
     * Bucket getter
     *
     * @return string|int The bucketName of the current activeRecord
     * @since  XXX
     */
    public function getBucket()
    {
        return $this->bucket;
    }


    /**
     * Returns the relation for the given $name
     *
     * @param string $name The relation name to get
     *
     * @return ActiveRelation
     * @since XXX
     */
    public function getRelation($name, $throwException = true)
    {
        $ret = null;
        $getter = 'get' . $name;
        try {
            $relation = $this->$getter();
            if ($relation instanceof ActiveRelation) {
                $ret = $relation;
            } else {
                if ($throwException) {
                    throw new InvalidParamException(get_class($this) . ' has no relation named "' . $name . '".');
                }
            }
        } catch (UnknownMethodException $e) {
            if ($throwException === true) {
                throw new InvalidParamException(get_class($this) . ' has no relation named "' . $name . '".', 0, $e);
            }
        }
        return $ret;
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
     * Returns wheter attribute exists.
     *
     * @param unknown $name The attribute name to test.
     *
     * @return boolean
     * @since  XXX
     */
    public function hasAttribute($name)
    {
        return in_array($name, $this->attributes());
    }


    /**
     * Checks if $name is an attribute, an index, a metadata, or a link.
     * If not it will call parent::__get.
     *
     * @param string $name The property wanted to get.
     *
     * @see \yii\base\Component::__get()
     *
     * @return mixed The value of the property ($name)
     * @since  XXX
     */
    public function __get($name)
    {
        if (in_array($name, $this->attributes())) {

            if (isset($this->attributes[$name])) {
                return $this->attributes[$name];
            } else {
                return null;
            }
        } elseif (array_key_exists($name, $this->indexes())) {
            if (isset($this->indexes[$name])) {
                return $this->indexes[$name];
            } else {
                return null;
            }
        } elseif (in_array($name, $this->metadata())) {
            if (isset($this->meta[$name])) {
                return $this->meta[$name];
            } else {
                return null;
            }
        } elseif (array_key_exists($name, $this->virtualAttributes)) {
            return $this->virtualAttributes[$name];
        } else {
            if (isset($this->related[$name]) || array_key_exists($name, $this->related)) {
                return $this->related[$name];
            }

            $value = parent::__get($name);

            if ($value instanceof ActiveRelation) {
                $this->related[$name] = ($value->multiple ?
                    $value->all(static::getDb()) :
                    $value->one(static::getDb()));
                return $this->related[$name];
            } else {
                return $value;
            }
        }
    }

    /**
     * Set the attribute.
     * Checks if it's an attribute, an index, a metadata.
     * If not it will call parent::__set().
     *
     * @param string $name  The attribute name to set.
     * @param mixed  $value The value to set.
     *
     * @see \yii\base\Component::__set()
     *
     * @return void
     * @since  XXX
     */
    public function __set($name, $value)
    {
        if (in_array($name, $this->attributes())) {
            $this->attributes[$name] = $value;
            $indexes = $this->indexes();
            if (array_key_exists($name, $indexes)) {
                $this->indexes[$name] = $value;
            }
        } elseif (in_array($name, $this->metadata())) {
            $this->meta[$name] = $value;
        } elseif (array_key_exists($name, $this->indexes())) {
            $this->indexes[$name] = $value;
        } else {
            $this->virtualAttributes[$name] = $value;
        }
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

    /**
     * @inheritdoc
     */
    public static function createQuery()
    {
        return new ActiveQuery(get_called_class());
    }
}
