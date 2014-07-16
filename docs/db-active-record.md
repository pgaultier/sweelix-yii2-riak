Active Record
=============

See [Active Record](https://github.com/yiisoft/yii2/blob/master/docs/guide/db-active-record.md#active-record)

Declaring Active Record Classes
------------------------------
To declare an Active Record class you need to extend [[sweelix\yii2\nosql\riak\ActiveRecord]] and implement those folowing methods

	namespace app\models;
	
	use sweelix\yii2\nosql\riak\ActiveRecord;
	
	class User extends ActiveRecord
	{
	
		/**
		 * @return string returns the name of the bucket associated with this ActiveRecord class
		 */
		public static function bucketName()
		{
		    return 'users';
		}
		
		/**
		 * @return array of attributes you want to store for this ActiveRecord class
		 */
		public static function attributeNames()
		{
			return [
				'userFirstname',
				'userLastname'
				//ETC...
			];
		}
		
		/**
		 * @return array of indexes you want to store for this ActiveRecord class
		 */
		public static function indexNames()
		{
			return [
				'userLogin' => IndexType::TYPE_BIN,
				'userAge' => IndexType::TYPE_INT
				//ETC..
			];
		}
	
		/**
		 * @return array of metadata you want to store for this ActiveRecord class
		 */
		public static function metadataNames()
		{
			return [
				'userDateCreate'
				//ETC...
			];
		}
		
		/**
		 * @return boolean whether to manage the object key or auto assign on insert.
		 */
		public static function isKeyMandatory()
		{
			return true;
		}
		
		/**
		 * @return string the resolver class name (namespaced).
		 * Could be null if you want to not resolve conflicts on save. 
		 */
		public static function resolverClassName()
		{
			return MyResolver::className();
			//OR
			return 'app\models\resolvers\MyResolver';
		}
	}

Accessing Column Data
------------------------------
To access attributes, indexes, or metadata :

	$firstname = $user->userFirstname; //Access attribute named userFirstname
	$login = $user->userLogin; //Access index named userLogin
	$dateCreate = $user->userDateCreate; //Access metadata named userDateCreate

You do not have to precise if it's an attribute, index, or metadata.
The name of attribute, index, or metadata are case-sensitive.

To assign attributes, indexes, or metadata : 
	$user->userFirstname = 'Christophe';
	$user->userLogin = 'clatour';
	$user->userDateCreate = date('c');

Connecting to DataBase
------------------------------
Active Record uses a [[sweelix\yii2\nosql\riak\Connection|DB connection]] to exchange data with database. By default, it uses 
the db application component as the connection. As explained in Database basics, you may configure the db 
component in the application configuration file like follows.

	return [
		'components' => [
			'riak' => [
				'class' => 'sweelix\yii2\nosql\riak\Connection',
				'dsn' => 'riak:dsn=http://192.168.1.123:8098'
			]
		]
	];

If you are using multiple databases in your application and you want to use a different DB connection for your Active Record
class, you may override the [[sweelix\yii2\nosql\riak\ActiveRecord::getDb()|getDb()]] method:


	class User extends ActiveRecord
	{
	    // ...
	
	    public static function getDb()
	    {
	        return \Yii::$app->riak2;  // use "riak2" application component
	    }
	}

Querying Data from DataBase
---------------------------

Active Record provides four entry methods for building DB queries and populating data into Active Record instances

* [[sweelix\yii2\nosql\riak\ActiveRecord::findOne()]]
* [[sweelix\yii2\nosql\riak\ActiveRecord::findByIndex()]]
* [[sweelix\yii2\nosql\riak\ActiveRecord::findByKeyFilter()]]
* [[sweelix\yii2\nosql\riak\ActiveRecord::findByMapReduce()]]

### [[sweelix\yii2\nosql\riak\ActiveRecord::findOne()]] :

	//Returns the user with the key named 'clatour@sweelix.net'
	$user = User::findOne('clatour@sweelix.net');

[[sweelix\yii2\nosql\riak\ActiveRecord::findOne()]] returns a populated ActiveRecord or null if the key doesn't exist

### [[sweelix\yii2\nosql\riak\ActiveRecord::findByIndex()]] :

	$users = User::findByIndex('userLogin', 'clatour@sweelix.net'); //Will return array of user's ActiveRecord with the index 'userLogin' equals to 'clatour@sweelix.net'
	
	$users = User::findByIndex('userAge', 0, 18); //Will return an array of user's ActiveRecord with the index 'userAge' between 0 and 18.

[[sweelix\yii2\nosql\riak\ActiveRecord::findOne()]] returns an array of ActiveRecord or an empty array if index values not match any value.

### [[sweelix\yii2\nosql\riak\ActiveRecord::findByKeyFilter()]] :

	$keyFilter = new KeyFilter();
	$keyFilter->startsWith('clatour');
	$users = User::findByKeyFilter($keyFilter); //Will return an array of user's ActiveRecord with their keys begin with 'clatour'

[[sweelix\yii2\nosql\riak\ActiveRecord::findByKeyFilter()]] returns an array of ActiveRecord or an empty array if keyfilter request doesn't match any value
It takes a [[sweelix\yii2\nosql\riak\KeyFilter]] object in parameter

See [KeyFilter's functions and predicates](http://docs.basho.com/riak/latest/dev/references/keyfilters/)

### [[sweelix\yii2\nosql\riak\ActiveRecord::findByMapReduce()]] :

	$users = User::findByMapReduce($mapReduce);

NOTE : TODO.

Manipulating Data in Database
---------------------------

See [Manipulating Data in Database from yii2 guide](https://github.com/yiisoft/yii2/blob/master/docs/guide/db-active-record.md#manipulating-data-in-database)

Note : Those method below will return a NotSupportedException :
 
* [[sweelix\yii2\nosql\riak\ActiveRecord::updateCounter()]]
* [[sweelix\yii2\nosql\riak\ActiveRecord::updateAll()]]
* [[sweelix\yii2\nosql\riak\ActiveRecord::deleteAllCounters()]]
* [[sweelix\yii2\nosql\riak\ActiveRecord::deleteAll()]]

Data Input and Validation
---------------------------

See [Data Input and Validation from yii2 guide](https://github.com/yiisoft/yii2/blob/master/docs/guide/db-active-record.md#data-input-and-validation)
