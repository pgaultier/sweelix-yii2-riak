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

use yii\db\ActiveRecord as BaseActiveRecord;
use Yii;
use yii\web\HttpException;
use yii\base\Model;

//use sweelix\yii2\nosql\ActiveRecord as BaseActiveRecord ;

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
 * @package   sweelix.nosql.riak
 * @since     XXX
 */
abstract class ActiveRecord extends BaseActiveRecord {

	abstract protected static $isKeyMandatory;

	abstract protected static $isBucketDynamic;

	abstract protected function attributesName();

	abstract protected function indexesName();

	abstract protected function metadataName();


	private $_key = null;

	private $_vclock;


	private $_bucketName;

	private $_indexes = [];

	private $_meta = [];

	private $_siblings;

	private $_vtag;

	private $_attributes;

	private $_virtualAttributes;


	public static function find() {
		$bucketName = null;
		$args = func_get_args();
		if (self::$isBucketDynamic === true) {
			if (isset($args[0]['bucketName']) === true) {
				$bucketName = $args[0]['bucketName'];
				unset($args[0]['bucketName']);
			}
		}
		$query = static::createQuery();

		if (self::$isBucketDynamic === true && isset($bucketName)) {
			$query->fromBucket($bucketName);
		}

		$q = reset($args);
		if (is_int($q) || is_string($q)) {
            return $query->withKey($q);
        } else {
        	//TODO : do real exception.
        	throw new InvalidConfigException('Arg should be a string or int');
        }

		return $query;
	}

	public static function primaryKey() {
		throw new NotSupportedException(__METHOD__ . ' is not supported.');
	}

	public function updateAll() {
		throw new NotSupportedException(__METHOD__ . ' is not supported.');
	}

	public function deleteAll() {
		throw new NotSupportedException(__METHOD__ . ' is not supported.');
	}

	public static function updateAllCounters($counters, $condition = '', $params = []) {
		throw new NotSupportedException(__METHOD__ . ' is not supported.');
	}

	public static function createQuery() {
		return new ActiveQuery(get_called_class());
	}

	public static function bucketName() {
		if (static::$isBucketDynamic === false) {
			return 'bck_'.Inflector::camel2id(StringHelper::basename(get_called_class(), '_'));
		} else {
			//TODO : create custom Exception
			throw new HttpException(500);
		}
	}

	public static function getDb() {
		return Yii::$app->nosql;
	}

	public function attributes() {
		if ($this->_attributes === null) {
			$attributes = [];
			foreach (static::attributesName() as $key => $value) {
				if (is_int($key)) {
					$attributes[] = $value;
				} else {
					$attributes[] = $key;
				}
			}
			$this->_attributes = $attributes;
		}
		return $this->_attributes;
	}


	public function autoIndexes() {
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

	public function indexes() {
		if ($this->_indexes === null) {
			$indexes = $this->autoIndexes();
			foreach (static::$indexesName as $key => $value) {
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
			$this->_indexes = $indexes;
		}
		return $this->_indexes;
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
		$this->_oldAttributes = $this->_attributes;
		if (!static::$isKeyMandatory) {
			$this->key = self::getKeyFromLocation($obj);
		}
		$this->_vclock = $obj[DataReader::VCLOCK_KEY];

		return $ret->count();
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
		if ( (static::$isKeyMandatory && empty($this->key) === false) || !static::$isKeyMandatory) {
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
		$this->_oldAttributes = $this->_attributes;
		if (!static::$isKeyMandatory) {
			$this->key = self::getKeyFromLocation($obj);
		}
		$this->_vclock = $obj[DataReader::VCLOCK_KEY];

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
	 * Returns the type of an index.
	 *
	 * @param string $indexName The indexname
	 *
	 * @return null|IndexType
	 * @since  XXX
	 */
	private static function indexType($indexName) {
		$indexType = null;
		if (in_array($indexName, static::$indexesName)) {
			$indexType = IndexType::TYPE_BIN;
		} else if (array_key_exists($indexName, static::$indexesName)) {
			$indexType = static::$indexesName[$indexName];
		} else if (array_key_exists($indexName, static::$attributesName)) {
			$tmp = static::$attributesName[$indexName];
			if (is_array($tmp) && array_key_exists('autoIndex', $tmp)) {
				$indexType = $tmp['autoIndex'];
			}
		}
		return $indexType;
	}

}