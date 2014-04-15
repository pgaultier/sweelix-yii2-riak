<?php
/**
 * File Connection.php
*
* PHP version 5.3+
*
* @author    Christophe Latour <clatour@ibitux.com>
* @copyright 2010-2014 Sweelix
* @license   http://www.sweelix.net/license license
* @version   XXX
* @link      http://www.sweelix.net
* @category  nosql
* @package   sweelix.nosql.riakcs
*/
namespace sweelix\yii2\nosql\riakcs;

use \Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\Component;

/**
 * Class Connection
 *
 * This class allow user to connect to a riak database
 *
 * @author Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license http://www.sweelix.net/license license
 * @version XXX
 * @link http://www.sweelix.net
 * @category nosql
 * @package sweelix.nosql.riakcs
 * @since XXX
 */
class Connection extends Component
{

    /**
     * @event Event an event that is triggered after a DB connection is established
     */
    const EVENT_AFTER_OPEN = 'afterOpen';

    public $endPoint = 's3.amazonaws.com';

    public $proxy = null;

    public $accessKey = null;

    public $secretKey = null;

    public $sslKey = null;

    public $sslCert = null;

    public $sslCACert = null;

    public $useSsl = false;

    public $useSslValidation = true;

    public $useExceptions = false;

    public $timeOffset = 0;

    /**
     *
     * @var Client the client system
     */
    private $client;

    /**
     *
     * @var array client drivers
     */
    private $clientClass = 'sweelix\yii2\nosql\riakcs\Client';

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
            $token = 'Opening riakCs connection';
            try {
                Yii::trace($token, __METHOD__);
                Yii::beginProfile($token, __METHOD__);
                $this->client = $this->createClientInstance();
                $this->initConnection();
                Yii::endProfile($token, __METHOD__);
            } catch (Exception $e) {
                Yii::endProfile($token, __METHOD__);
                throw new RiakException($e->getMessage());
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
            Yii::trace('Closing NoSql connection: ' . $this->endPoint . ' (proxy : ' . $this->proxy . ')', __METHOD__);
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
        if (empty($this->proxy) === false) {
            if (preg_match('/[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}:[0-9]{2,5}/', $this->proxy) == false) {
                throw new InvalidConfigException('Connection to proxy ' . $this->proxy . ' is invalid');
            }
        }

        return Yii::createObject(array(
            'class' => $this->clientClass,
            'endPoint' => $this->endPoint,
            'proxy' => $this->proxy,
            'accessKey' => $this->accessKey,
            'secretKey' => $this->secretKey,
            'sslKey' => $this->sslKey,
            'sslCert' => $this->sslCert,
            'sslCACert' => $this->sslCACert,
            'useSsl' => $this->useSsl,
            'useSslValidation' => $this->useSslValidation,
            'useExceptions' => $this->useExceptions,
            'timeOffset' => $this->timeOffset
        ));
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
     * Return the current client instance.
     *
     * @return \sweelix\yii2\nosql\riakcs\Client
     * @since XXX
     */
    public function getClient()
    {
        if (isset($this->client) === false) {
            $this->open();
        }
        return $this->client;
    }
}
