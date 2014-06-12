<?php

/**
 * File Connection.php
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

use yii\base\Component;
use yii\base\InvalidConfigException;
use Exception;
use Yii;

/**
 * Class Connection
 *
 * This class allow user to connect to a riak database
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
class Connection extends Component
{

    /**
     * @event Event an event that is triggered after a DB connection is established
     */
    const EVENT_AFTER_OPEN = 'afterOpen';

    /**
     *
     * @var string the Data Source Name, or DSN, contains the information required to connect to the riak server.
     *      DSN was created to be the most similar to PDO ones
     *      riak:host=myhost;port=8098
     */
    public $dsn;

    /**
     *
     * @var Client the client system
     */
    public $client;

    /**
     *
     * @var array client drivers
     */
    public $driverMap = array(
        'riak' => 'sweelix\yii2\nosql\riak\Client'
    );

    /**
     * Establishes a DB connection.
     * It does nothing if a DB connection has already been established.
     *
     * @return void
     * @since XXX
     */
    public function open()
    {
        if ($this->client === null) {
            if (empty($this->dsn)) {
                throw new InvalidConfigException('Connection::dsn cannot be empty.');
            }
            $token = 'Opening NoSql connection: ' . $this->dsn;
            try {
                Yii::trace($token, __METHOD__);
                Yii::beginProfile($token, __METHOD__);
                $this->client = $this->createClientInstance();
                $this->initConnection();
                Yii::endProfile($token, __METHOD__);
            } catch (Exception $e) {
                Yii::endProfile($token, __METHOD__);
                throw new Exception($e->getMessage()); // , $e->errorInfo, (int) $e->getCode(), $e);
            }
        }
    }

    /**
     * Closes the currently active Riak connection.
     * It does nothing if the connection is already closed.
     *
     * @return void
     * @since XXX
     */
    public function close()
    {
        if ($this->client !== null) {
            Yii::trace('Closing NoSql connection: ' . $this->dsn, __METHOD__);
            $this->client = null;
        }
    }

    /**
     * Returns a value indicating whether the DB connection is established.
     *
     * @return boolean whether the DB connection is established
     */
    public function getIsActive()
    {
        return $this->client !== null;
    }

    /**
     * Creates the Riak client instance.
     * This method is called by [[open]] to establish a DB connection.
     * The default implementation will create a PHP PDO instance.
     * You may override this method if the default PDO needs to be adapted for certain DBMS.
     *
     * @return Riak
     * @since XXX
     */
    protected function createClientInstance()
    {
        $matches = array();
        if (preg_match('#(?P<driver>[^:]+):dsn=(?P<dsn>.*)#', $this->dsn, $matches) > 0) {

            if (isset($this->driverMap[$matches['driver']]) === true) {
                return Yii::createObject(array(
                    'class' => $this->driverMap[$matches['driver']],
                    'dsn' => $matches['dsn']
                ));
            } else {
                throw new InvalidConfigException('Connection::dsn driver "' . $matches['driver'] . '"is invalid');
            }
        } else {
            throw new InvalidConfigException('Connection::dsn is invalid');
        }
    }

    /**
     * Initializes the Riak connection.
     * This method is invoked right after the Riak connection is established.
     * It then triggers an [[EVENT_AFTER_OPEN]] event.
     *
     * @return void
     * @since XXX
     */
    protected function initConnection()
    {
        $this->trigger(self::EVENT_AFTER_OPEN);
    }

    /**
     * Create a new query builder
     *
     * @return QueryBuilder
     * @since XXX
     */
    public function getQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    /**
     * Create a Command instance
     *
     * @param array $commandData
     *            The setting array for command.
     *
     * @return Command
     * @since XXX
     */
    public function createCommand($commandData = array())
    {
        $this->open();
        $command = new Command(array(
            'commandData' => $commandData,
            'noSqlDb' => $this
        ));
        return $command;
    }
}
