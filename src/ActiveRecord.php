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
 * @package   sweelix.nosql
 */

namespace sweelix\yii2\nosql;

use yii\base\Model; 
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use sweelix\yii2\nosql\riak\IndexType;
use sweelix\yii2\nosql\DataReader;
use sweelix\yii2\nosql\ActiveRelation;
use Basho\Riak\Exception;
use yii\base\InvalidConfigException;
use yii\base\InvalidCallException;
use yii\base\InvalidParamException;
use yii\base\UnknownMethodException;
use yii\base\NotSupportedException;

/**
 * Class ActiveRecord
 *
 * This class handle all the records and mimic classic
 * sql ActiveRecord management
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql
 * @since     XXX
 */
class ActiveRecord extends Model {

	/* @event Event an event that is triggered when the record is initialized via [[init()]]. */

	const EVENT_INIT = 'init';
	/* @event Event an event that is triggered after the record is created and populated with query result. */
	const EVENT_AFTER_FIND = 'afterFind';
	/* @event ModelEvent an event that is triggered before inserting a record. */
	const EVENT_BEFORE_INSERT = 'beforeInsert';
	/* @event Event an event that is triggered after a record is inserted. */
	const EVENT_AFTER_INSERT = 'afterInsert';
	/* @event ModelEvent an event that is triggered before updating a record. */
	const EVENT_BEFORE_UPDATE = 'beforeUpdate';
	/* @event Event an event that is triggered after a record is updated. */
	const EVENT_AFTER_UPDATE = 'afterUpdate';
	/* @event ModelEvent an event that is triggered before deleting a record. */
	const EVENT_BEFORE_DELETE = 'beforeDelete';
	/* @event Event an event that is triggered after a record is deleted. */
	const EVENT_AFTER_DELETE = 'afterDelete';

	/* @var boolean isNewRecord is true if record has not been saved. */

	/**
	 * @var string|int The key of object.
	 */
	public $key = null;

	/**
	 * @var string Object's vclock. Determine if the object is new.
	 */
	private $_vclock;
	
	/**
	 * <code>
	 * array(
	 * 	 'attr1',
	 *   'attr2',
	 *   'attr3',
	 *   'attr4',
	 *   'attr5' => array('autoIndex', IndexType::TYPE_BIN);
	 *   //etc...
	 * );
	 * </code>
	 * 
	 * @var array which contains names of attributes.
	 */
	protected static $_attributesName = array();
	
	/**
	 * <code>
	 * array(
	 *    'index1'
	 *    'index2' => IndexType::TYPE_INT,
	 *    'index3' => IndexType::TYPE_BIN,
	 * );
	 * </code>
	 * 
	 * By default, if the type of indexes isn't specified, it will be tretead as binary.
	 * 
	 * @var array which contains names of indexes
	 */
	protected static $_indexesName = array();
	
	/**
	 * <code>
	 * array(
	 *    'meta1',
	 *    'meta2',
	 * );
	 * </code>
	 *
	 * @var array which contains metadata name.
	 */
	protected static $_metadataName = array();	

	
	/**
	 * Is object key mandatory.
	 * 
	 * @var boolean
	 */
	protected static $_isKeyMendatory = true;
	
	/**
	 * @var array old attribute values indexed by attribute names.
	 */
	private $_oldAttributes;
	
	/**
	 * @var array which contains pair key (attribute name) value (attribute's value).
	 */
	private $_attributes = array();

	/**
	 * @var array metadata's object indexed by metadata names.
	 */
	private $_meta = array();

	/**
	 * @var array indexes's object indexed by indexes names 
	 */
	private $_indexes = array();

	/**
	 * @var array relation's object.
	 */
	private $_related = array();

	/**
	 * @var array sibling's object
	 */
	private $_siblings;

	/**
	 * @var array object's raw links.
	 */
	private $_rawLinks;
	
	/**
	 * vtag of siblings.
	 * 
	 * @var array
	 */
	private $_vtag;

	/**
	 * This method is called when the AR object is created and populated with the result.
	 * The default implementation will trigger an [[EVENT_AFTER_FIND]] event.
	 * When overriding this method, make sure you call the parent implementation to ensure the
	 * event is triggered.
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function afterFind() {
		$this->trigger(self::EVENT_AFTER_FIND);
	}

	/**
	 * This method is invoked before deleting a record.
	 * The default implementation raises the [[EVENT_BEFORE_DELETE]] event.
	 * When overriding this method, make sure you call the parent implementation to ensure the
	 * event is triggered.
	 * 
	 * @return boolean whether the record should be deleted. Defaults to true.
	 * @since  XXX
	 */
	public function beforeDelete() {
		$this->trigger(self::EVENT_BEFORE_DELETE);
		return true;
	}

	/**
	 * This method is invoked after deleting a record.
	 * The default implementation raises the [[EVENT_AFTER_DELETE]] event.
	 * You may override this method to do post processing after the record is deleted.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function afterDelete() {
		$this->trigger(self::EVENT_AFTER_DELETE);
	}

	/**
	 * This method is called at the beginning of inserting or updating a record.
	 * The default implementation will trigger an [[EVENT_BEFORE_INSERT]] event when `$insert` is true,
	 * or an [[EVENT_BEFORE_UPDATE]] event if `$insert` is false.
	 * When overriding this method, make sure you call the parent implementation to ensure the
	 * event is triggered.
	 * 
	 * @param boolean $insert whether this method called while inserting a record.
	 * If false, it means the method is called while updating a record.
	 * 
	 * @return boolean whether the insertion or updating should continue.
	 * If false, the insertion or updating will be cancelled.
	 * @since  XXX
	 */
	public function beforeSave($insert) {
		$this->trigger(self::EVENT_BEFORE_INSERT);
		return true;
	}

	/**
	 * This method is called at the end of inserting or updating a record.
	 * The default implementation will trigger an [[EVENT_AFTER_INSERT]] event when `$insert` is true,
	 * or an [[EVENT_AFTER_UPDATE]] event if `$insert` is false.
	 * When overriding this method, make sure you call the parent implementation so that
	 * the event is triggered.
	 * 
	 * @param boolean $insert whether this method called while inserting a record.
	 * If false, it means the method is called while updating a record.
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function afterSave($insert) {
		$this->trigger(self::EVENT_AFTER_INSERT);	
	}

	/**
	 * Returns the database connection used by this model class.
	 * By default, the "nosql" application component is used as the database connection.
	 * You may override this method if you want to use a different database connection.
	 * 
	 * @return Connection the database connection used by this model class.
	 * @since  XXX
	 */
	public static function getNosql() {
		return \Yii::$app->nosql;
	}

	/**
	 * Return the name of the bucket associate with the current AR.
	 * You may have to override this method if bucketname is not named after this convention.
	 * Convention : example with an [[ActiveRecord]] named 'User', the default bucket name will be :
	 * "bck_user"
	 * 
	 * @return string The bucket name of this [[ActiveRecord]]
	 * @since  XXX
	 */
	public static function bucketName() {
		return 'bck_'.Inflector::camel2id(StringHelper::basename(get_called_class(), '_'));
	}
	
	/**
	 * Return an array of [[ActiveRecord]] attributes name.
	 * 
	 * @see \yii\base\Model::attributes()
	 *
	 * @return array An array of attributes names.
	 * @since  XXX
	 */
	public function attributes() {
		$attributes = array();
		foreach (static::$_attributesName as $key => $value) {
			if (is_array($value)) {
				$attributes[] = $key;
			} else {
				$attributes[] = $value;
			}
		}
		return $attributes;
	}

	/**
	 * This function returns an array of all autoIndex formatted like key => value.
	 * Key represents the name of autoIndex
	 * Value represents the type of the autoIndex.
	 * An autoIndex can be declared in the [[ActiveRecord]]::$attributesName like that :
	 * <code>
	 * 	$attributesName = array(
	 * 		'email' => array('autoIndex' => IndexType::TYPE_INT), //email of type int.
	 *      'email' => array('autoIndex'), //By default, email will be of type bin.
	 *      'email' => array('autotIndex' => IndexType::TYPE_BIN) // same as above.
	 *  );
	 * </code>
	 * 
	 * An autoIndexes is at the same time an attribute and an index.
	 * When you modify the attribute, the index is upadated automatically.
	 * 
	 * @return array of autoIndexes array('indexName' => 'indexTye', ...)
	 * @since  XXX
	 */
	public function autoIndexes() {
		$autoIndexes = array();
		foreach (static::$_attributesName as $key => $value) {
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
	 * Returns an array of [[ActiveRecord]]'s indexes formatted as $key => $value (using static::$indexesName and autoIndexes).
	 * Key represents the index name.
	 * Value represents the index type (IndexType::TYPE_BIN || IndexType::TYPE_INT)
	 * 
	 * @return array An array of indexes names.
	 * @since  XXX
	 */
	public function indexes() {
		$autoIndexes = $this->autoIndexes();
		foreach (static::$_indexesName as $key => $value) {
			if (is_string($key)) {
				if ($value === IndexType::TYPE_BIN || $value == IndexType::TYPE_INTEGER) {
					$autoIndexes[$key] = $value;
				} else {
					$autoIndexes[$key] = IndexType::TYPE_BIN;
				}
			} else {
				$autoIndexes[$value] = IndexType::TYPE_BIN;
			}
		}
		return $autoIndexes;
		
	}

	/**
	 * Returns an array of metadata names declared in static::$metadataName.
	 * 
	 * @return array An array of indexes names.
	 * @since  XXX
	 */
	public function metadata() {
		return static::$_metadataName;
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
	public static function create($row) {
		\Yii::trace('Creating object with row : '.var_export($row, true)."\n", __CLASS__);
		$record = null;
		if ($row !== null && $row[DataReader::RESPONSESTATUS_KEY] !== 404) {
			$record = static::instantiate();
			
			
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
			
			$record->_vclock = $row[DataReader::VCLOCK_KEY];
			
			
			//ASSIGN ATTRIBUTES
			foreach ($row[DataReader::DATA_KEY] as $attributeName => $attributeValue) {
				if (in_array($attributeName, $attributes)) {
					$record->_attributes[$attributeName] = $attributeValue;
				} else {
					\Yii::warning($attributeName.' is not declared in static property $attributesName');
				}
			}
			//ASSIGN INDEXES
			foreach ($row[DataReader::INDEX_KEY] as $indexName => $indexValue) {
				foreach ($record->indexes() as $name => $type) {
					if (strtolower($indexName) === strtolower($name)) {
						$record->_indexes[$name] = $indexValue;
					} else {
						\Yii::warning($attributeName.' is not defined in static property $indexesName');
					}
				}
			}
			//ASSIGN METADATA
			foreach ($row[DataReader::META_KEY] as $metaName => $metaValue) {
				foreach ($record->metadata() as $name) {
					if (strtolower($metaName) === strtolower($name)) {
						$record->_meta[$name] = $metaValue;
					} else {
						\Yii::warning($metaName.' is undefined in the static property $metadataName');
					}
				}
			}
			
			//ASSIGN SIBLINGS
			if (isset($row[DataReader::SIBLINGS_KEY])) {
				$record->_vtag = $row[DataReader::SIBLINGS_KEY];
			}
			

			//ASSING LINKS
			$record->_rawLinks = $row[DataReader::LINK_KEY];

			$record->_oldAttributes = $record->_attributes;
			$record->afterFind();
		}
		return $record;
	}

	/**
	 * Return an object of child type.
	 * 
	 * @return ActiveRecord
	 * @since  XXX
	 */
	public static function instantiate() {
		return new static;
	}
	
	/**
	 * Creates an [[ActiveQuery]] instance.
	 * This method is called by [[one()]],[[all()]] and [[findByKey()]] to start a query.
	 * You may override this method to return a customized query
	 * 
	 * @return ActiveQuery the newly created [[ActiveQuery]] instance.
	 * @since XXX
	 */
	public static function createQuery() {
		return new ActiveQuery(array(
			'modelClass' => get_called_class(),
		));
	}

	/**
	 * Creates an [[ActiveQuery]] instance for query purpose.
	 *
	 * @param mixed $q the query parameter. This can be one of the followings:
	 *
	 *  - a scalar value (integer or string): query by a single key of bucket and return the
	 *    corresponding record.
	 *  - an array of keys to fetch: query by a set of key  and return a array record matching all of them.
	 *  - null: return a basic [[ActiveQuery]].
	 *
	 * Example of the three case :
	 * <code>
	 *  //SIMPLE GET with one key.
	 * 	ActiveRecord::find('user1'); -> return a single [[ActiveRecord]] which represents the 'user1' or null if 'user1' not found.
	 * 
	 *  //MULTIPLE GET.
	 *  ActiveRecord::find(array('user1', 'user2', 'user3'));
	 *  //Return an array of [[ActiveRecord]] which reprensents 'user1', 'user2', 'user3' (or empty array if all not found) :
	 * 	//	array(
	 *  //		'user1' => [[ActiveRecord]],
	 *  //  	'user2' => [[ActiveRecord]],
	 *  //  	'user3' => [[ActiveRecord]], //Or null if not found
	 * 	//	);
	 * 
	 * //Returning an ActiveQuery
	 * ActiveRecord::find(); -> return an [[ActiveQuery]].
	 * 
	 * //Seems legit to do following actions :
	 * ActiveRecord::find()->withKey('user1')->one();
	 * ActiveRecord::find()->map(...)->reduce(...)->all();
	 * </code>
	 *
	 * @see createQuery()
	 * 
	 * @return ActiveQuery|ActiveRecord|ActiveRecord[]|null When `$q` is null, a new [[ActiveQuery]] instance
	 * is returned; when `$q` is a scalar or an array, an ActiveRecord object matching it will be
	 * returned (null will be returned if there is no matching).
	 * @since XXX
	 */
	public static function find($q = null) {
		$query = static::createQuery();
		
		if (is_string($q) || is_int($q)) {
			$ar = $query->withKey($q)->one(static::getNosql());
			if ($ar) {
				$ar->key = $q;
			}
			return $ar;
		} elseif (is_array($q)) {
			$activeRecords = array();
			foreach ($q as $key) {
				$add = static::find($key);
				if ($add) {
					$add->key = $q;
				}
				$activeRecords[$key] = $add;  
			}
			return $activeRecords;
		}
		return $query;
	}	
		
	/**
	 * save current record
	 * 
	 * This method will call [[insert()]] when [[isNewRecord]] is true, or [[update()]]
	 * when [[isNewRecord]] is false.
	 *
	 * @param boolean $runValidation whether to perform validation before saving the record.
	 * If the validation fails, the record will not be saved to database.
	 * @param array   $attributes    list of attributes that need to be saved. Defaults to null,
	 * meaning all attributes that are loaded from DB will be saved.
	 * 
	 * @return boolean whether the saving succeeds
	 * @since  XXX
	 */
	public function save($runValidation = true, $attributes = null) {
		$result = null;
		if ( (static::$_isKeyMendatory && empty($this->key) === false) || !static::$_isKeyMendatory) {
			if ($this->isNewRecord) {
				$result = $this->insert($runValidation, $attributes);
			} else {
				$result = $this->update($runValidation, $attributes);
			}
		} else {
			throw new \Exception('Unable to save ActiveRecord ('.get_class($this).') whitout a key.');
		}
		return $result;
	}

	/**
	 * Resolve conflicts with the current [[ActiveRecord]].
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function resolve() {
		if (empty($this->_vclock)) {
			$this->refresh();
				
			if (!empty($this->_vtag)) {
				$this->_attributes = $this->_oldAttributes;
			}
		}
		
		if ($this->save() === 1) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * This function is called by [[ActiveRecord]]::save() if the [[ActiveRecord]] isNewRecord.
	 * 
	 * @param string $runValidation wheter to run validation.
	 * @param string $attributes    attribute's name to validate.
	 * 
	 * @return boolean|int false if validation fail or an integer corresponding to the number of row affected (numbers of siblings).
	 * @since  XXX
	 */
	public function insert($runValidation = true, $attributes = null) {
		if ($runValidation && !$this->validate($attributes)) {
			return false;
		}
		if (!$this->beforeSave(true)) {
			return false;
		}
			
		
		$command = $this->createCommand('insert');
				
		$ret = $command->execute();
		$this->afterSave(true);
		$obj = $ret->current();
		$this->_vclock = $obj[DataReader::VCLOCK_KEY];
		$this->_oldAttributes = $this->_attributes;

		return $ret->count();
	}
	
	/**
	 * Update a row into te associated bucketName and object key.
	 * 
	 * @param boolean $runValidation whether to run validation. 
	 * @param array   $attributes    attributes to validate.
	 * 
	 * @return boolean|int false if validation fail or an integer corresponding to the number of row affected (numbers of siblings).
	 * @since  XXX
	 */
	public function update($runValidation = true, $attributes = null) {
		if ($runValidation && !$this->validate($attributes)) {
			return false;
		}
		
		if (!$this->beforeSave(false)) {
			return false; 
		}
		
		$command = $this->createCommand('update');
		
		
		$ret = $command->execute();
		$this->afterSave(false);
		$affected = $ret->count();
		$this->_oldAttributes = $this->_attributes;
		if ($affected === 1) {
			$obj = $ret->current();
			$this->_attributes = $obj[DataReader::DATA_KEY];
			$this->_vtag = $obj[DataReader::SIBLINGS_KEY];
			$this->_vclock = $obj[DataReader::VCLOCK_KEY];
			return 1;
		} else {
			$this->_vclock = null;
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
	 * @since  XXX
	 */
	private function createCommand($mode) {
		$command = static::getNosql()->createCommand();
		$data = array();
		foreach ($this->attributes() as $name) {
			$data[$name] = $this->$name;
		}
		
		$command instanceof Command;
		
		
		if ($mode === 'insert') {
			$command->insert($this->bucketName(), $this->key, $data);
		} else {
			$command->update($this->bucketName(), $this->key, $data)->vclock($this->_vclock);
		}
		
		//Add MetaData.
		foreach ($this->metadata() as $name) {
			$value = $this->$name;
			if (isset($value)) {
				$command = $command->addMetaData($name, $value);
			}
		}
		
		//AddIndexes
		foreach ($this->indexes() as $name => $type) {
			$value = $this->$name;				
			if (isset($value)) {
				$command = $command->addIndex($name, $value, $type);
			}
		}
		
		if ($this->_rawLinks) {
			foreach ($this->_rawLinks as $link) {
				if (preg_match('/<\/buckets\/(\w+)\/keys\/(\w+)>; riaktag="(\w+)"/', $link, $match) > 0) {
					list($all, $bucket, $key, $tag) = $match;
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
	 *    rest of the steps;
	 * 2. delete the record from the database;
	 * 3. call [[afterDelete()]].
	 *
	 * In the above step 1 and 3, events named [[EVENT_BEFORE_DELETE]] and [[EVENT_AFTER_DELETE]]
	 * will be raised by the corresponding methods.
	 *
	 * @return boolean wheter [[ActiveRecord]] has been deleted.
	 * @since XXX 
	 */
	public function delete() {
		if ($this->key === null) {
			throw new Exception('Cannot delete '.get_class($this).' wihtout key.');
		}
		if ($this->beforeDelete()) {
			$this->deleteAll(array($this->key));
			$this->_oldAttributes = null;
			$this->_vclock = null;
			$this->afterDelete();
		}
		return true;
	}

	/**
	 * Returns the relation for the given $name
	 * 
	 * @param string $name The relation name to get
	 * 
	 * @return ActiveRelation
	 * @since XXX
	 */
	public function getRelation($name) {
		$getter = 'get' . $name;
		try {
			$relation = $this->$getter();
			if ($relation instanceof ActiveRelation) {
				return $relation;
			} else {
				throw new InvalidParamException(get_class($this) . ' has no relation named "' . $name . '".');
			}
		} catch (UnknownMethodException $e) {
			throw new InvalidParamException(get_class($this) . ' has no relation named "' . $name . '".', 0, $e);
		}
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
	public function hasOne($arClass, $riakTag) {
		return new ActiveRelation(array(
			'modelClass' => $this,
			'multiple' => false,
			'primaryModel' => $this->getNamespacedClass($arClass),
			'riakTag' => $riakTag,
		));
		
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
	public function hasMany($arClass, $riakTag) {
		return new ActiveRelation(array(
			'modelClass' => $this,
			'multiple' => true,
			'primaryModel' => $this->getNamespacedClass($arClass),
			'riakTag' => $riakTag,
		));
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
	public function link($name, ActiveRecord $model) {
		$relation = $this->getRelation($name);
		
		$rawLink = '</buckets/'.$model->bucketName().'/keys/'.$model->key.'>; riaktag="'.$relation->riakTag.'"';
		if (!in_array($rawLink, $this->_rawLinks)) {
			if ($relation->primaryModel !== null) {
				$this->_rawLinks[] = $rawLink;
				if ($this->isNewRecord && $model->isNewRecord) {
					throw new InvalidCallException('Unable to link models: both models must NOT be newly created.');
				} elseif (!$this->isNewRecord && $this->isNewRecord) {
					if ($model->save() === false) {
						throw new \Exception('An error has been occured, when trying to link model [['.$model->getBucketName().']]');
					}
					$model->refresh();
				}
				if ($relation->multiple) {
					$add = true;
					if (isset($this->_related[$name])) {
						foreach ($this->_related[$name] as $modelTmp) {
							if ($modelTmp->equals($model)) {
								$add = false;
								break;
							}
						}
						$this->_related[$name][] = $model;
					}
				} else {
					$this->_related[$name] = $model;
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
	public function unlink($name, ActiveRecord $model) {
		$relation = $this->getRelation($name);
		
		$link = '</buckets/'.$model->bucketName().'/keys/'.$model->key.'>; riaktag="'.$relation->riakTag.'"';
		foreach ($this->_rawLinks as $i => $rawLink) {
			if ($rawLink === $link) {
				unset($this->_rawLinks[$i]);
			}
		}
		
		if (isset($this->_related[$name]) === true) {
			if ($relation->multiple === true) {
				foreach ($this->$name as $i => $obj) {					
					if ($model->equals($obj)) {
						unset($this->_related[$name][$i]);
					}
				}
			} else {
				$this->_related[$name] = null;
			}
		}
	}
	
	/**
	 * Updates one or several counter key->value for the current AR object.
	 *
	 * @param array $counters the counters to be updated (key name => increment value)
	 * Use negative values if you want to decrement the counters.
	 * 
	 * @return boolean whether the saving is successful
	 * @since XXX
	 */
	public function updateCounter($counters) {
		throw new NotSupportedException(__METHOD__ . ' is not supported.');
	}

	/**
	 * Returns a value indicating whether the given active record is the same as the current one.
	 * The comparison is made by comparing the table names and the primary key values of the two active records.
	 * 
	 * @param ActiveRecord $record record to compare to
	 * 
	 * @return boolean whether the two active records refer to the same row in the same database table.
	 */
	public function equals($record) {
		return $this->_vclock === $record->_vclock;
	}

	/**
	 * Delete all records which his keys contained in $keys
	 * 
	 * Example :
	 * <code>
	 * 	ActiveRecord::deleteAll(array(
	 *    'user1',
	 *    'user2',
	 *    'user3',
	 * )); -> will delete objects with key 'user1', 'user2' and 'user3' 
	 * </code>
	 * 
	 * @param array $keys array of object's key to delete.
	 * 
	 * @return int Number of object affected.
	 * @since  XXX
	 */
	public static function deleteAll(array $keys) {
		$command = static::getNosql()->createCommand();
		$nbAffected = 0;
		foreach ($keys as $key) {
			$response = $command->delete(static::bucketName(), $key)->execute();
			$row = $response->current();
			if ($row[DataReader::RESPONSESTATUS_KEY] === 204) {
				$nbAffected++;
			}
		}
		return $nbAffected;
	}

	/**
	 * update all of record
	 * It is remaining. This methods are not needed in first release
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function updateAll() {
		throw new NotSupportedException(__METHOD__ . ' is not supported.');
	}
	
	/**
	 * update all of selected counters
	 * It is remaining. This methods are not needed in first release
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function updateAllCounters() {
		throw new NotSupportedException(__METHOD__ . ' is not supported.');
	}

	/**
	 * Refresh the current row
	 * If current row has siblings, just _attributes is updated.
	 * The object will be separated in two part.
	 * The data before you save the object (_oldAttributes contains object data, _indexes contains indexes, _meta and _link same)
	 * Then you have the siblings part. (_vtag contains _vtag of siblings).
	 * You can get all siblings (as [[ActiveRecord]]) like doing this : 
	 * 	[[ActiveRecord]]->siblings; -> will return an array of siblings
	 * 
	 * @return boolean wheter the resolve has been executed successfully.
	 * @since  XXX
	 */
	public function refresh() {
		$this->_oldAttributes = $this->_attributes;
		$record = $this->find($this->key);
		if ($record === false) {
			return false;
		}
		if (!empty($record->_vtag)) {
			$this->_attributes = $record->_attributes;
		} else {
			$this->_attributes = $record->_attributes;
			$this->_indexes = $record->_indexes;
			$this->_meta = $record->_meta;
		}
		$this->_vtag = $record->_vtag;
		$this->_vclock = $record->_vclock;
		return true;
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
	protected static function getNamespacedClass($class) {
		if (strpos($class, '\\') === false) {
			$reflector = new \ReflectionClass(static::className());
			return $reflector->getNamespaceName() . '\\' . $class;
		} else {
			return $class;
		}
	}

	/**
	 * Returns indexes of object. 
	 * An array of pair key (name) - value. 
	 * 
	 * @return array indexes of object
	 * @since  XXX
	 */
	public function getIndexes() {
		return $this->_indexes;
	}
	
	/**
	 * Returns metadata of object.
	 * An array of pair key (name) - value.
	 * 
	 * @return array metadata of object. 
	 * @since  XXX
	 */
	public function getMetadata() {
		return $this->_meta;
	}

	/**
	 * Returns the named attribute value.
	 * If this record is the result of a query and the attribute is not loaded,
	 * null will be returned.
	 * 
	 * @param string $name the attribute name
	 * 
	 * @return mixed the attribute value. Null if the attribute is not set or does not exist.
	 * @since  XXX
	 */
	public function getAttribute($name) {
		return isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
	}
	
	/**
	 * Returns an [[ActiveRecord]] array.
	 * 
	 * @return array of [[ActiveRecord]]. Each row represent a version of the object.
	 * @since  XXX
	 */
	public function getSiblings() {
		$model = get_class($this);
		$obj = $model::find($this->key);
		if ($this->_siblings === null) {
			if (empty($obj->_vtag) === false) {
				$obj->_siblings = array();
				foreach ($obj->_vtag as $vtag) {
					$ar = $this->find()->withKey($this->key)->vtag($vtag)->one(static::getNosql());
					$ar->key = $this->key;
					$obj->_siblings[] = $ar;
				}
				$this->_siblings = $obj->_siblings;
			} else {
				$this->_siblings = array();
			}			
		}
		return $this->_siblings;
	}
	
	/**
	 * Return whether is new record.
	 * 
	 * @return boolean whether is a new record.
	 * @since  XXX
	 */
	public function getIsNewRecord() {
		return !isset($this->_vclock);
	}

	/**
	 * Returns wheter attribute exists.
	 * 
	 * @param unknown $name The attribute name to test.
	 * 
	 * @return boolean
	 * @since  XXX
	 */
	public function hasAttribute($name) {
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
	public function __get($name) {
		if (isset($this->_attributes[$name]) || array_key_exists($name, $this->_attributes)) {
			return $this->_attributes[$name];
		} elseif (in_array($name, $this->attributes())) {
			return null;
		} elseif (isset($this->_indexes[$name]) || array_key_exists($name, $this->_indexes)) {
			return $this->_indexes[$name];
		} elseif (in_array($name, $this->indexes())) {
			return null;
		} elseif (isset($this->_meta[$name]) || array_key_exists($name, $this->_meta)) {
			return $this->_meta[$name];
		} elseif (in_array($name, $this->metadata())) {
			return null;
		} else {
			if (isset($this->_related[$name]) || array_key_exists($name, $this->_related)) {
				return $this->_related[$name];
			}
			
			$value = parent::__get($name);
			
			if ($value instanceof ActiveRelation) {
				$this->_related[$name] = ($value->multiple ? $value->all(static::getNosql()) : $value->one(static::getNosql()));
				return $this->_related[$name];
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
	public function __set($name, $value) {
		if (in_array($name, $this->attributes())) {
			$this->_attributes[$name] = $value;
			$indexes = $this->indexes();
			if (array_key_exists($name, $indexes)) {
				$this->_indexes[$name] = $value;				
			}
		} elseif (in_array($name, $this->metadata())) {
			$this->_meta[$name] = $value;
		} elseif (array_key_exists($name, $this->indexes())) {
			$this->_indexes[$name] = $value;
		} else {
			parent::__set($name, $value);
		}
		
	}
}