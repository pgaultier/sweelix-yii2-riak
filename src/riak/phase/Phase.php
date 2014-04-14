<?php
/**
 * File Phase.php
 *
 * PHP version 5.3+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2013 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak.phase
 */
namespace sweelix\yii2\nosql\riak\phase;

use sweelix\yii2\nosql\riak\interfaces\Phase as InterfacePhase;
use yii\base\Component;
use yii\web\HttpException;

/**
 * Class Phase
 *
 * This class encapsulate a phase phase
 *
 * @author Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2013 Sweelix
 * @license http://www.sweelix.net/license license
 * @version XXX
 * @link http://www.sweelix.net
 * @category nosql
 * @package sweelix.nosql.riak.phase
 * @since XXX
 */
class Phase extends Component implements InterfacePhase
{

    protected $phase;

    /**
     * Build current phase and return the correct data
     * or null if phase is invalid
     *
     * @return array
     * @since XXX
     */
    public function build()
    {
        $phase = null;
        if (($this->phase !== null) && ($this->language !== null) && ($this->handler !== null)) {
            $operation = $this->handler;
            $operation['language'] = $this->language;
            if ($this->argument !== null) {
                $operation['arg'] = $this->argument;
            }
            if ($this->keep !== null) {
                $operation['keep'] = $this->keep;
            }
            $phase = array(
                $this->phase => $operation
            );
        }
        return $phase;
    }

    /**
     *
     * @var string language, can be javascript or erlang
     */
    private $language = 'javascript';

    /**
     * Define language used by phase phase
     *
     * @param string $language
     *            function language
     *
     * @return void
     * @since XXX
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Get language used by phase phase
     *
     * @return string
     * @since XXX
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     *
     * @var mixed static argument passed to phase phase
     */
    private $argument;

    /**
     * Define static argument which will be passed to phase function
     *
     * @param mixed $argument
     *            static argument passed to phase function
     *
     * @return void
     * @since XXX
     */
    public function setArgument($argument)
    {
        $this->argument = $argument;
    }

    /**
     * Get static argument which will be passed to phase function
     *
     * @return mixed
     * @since XXX
     */
    public function getArgument()
    {
        return $this->argument;
    }

    /**
     *
     * @var mixed static argument passed to phase phase
     */
    private $keep;

    /**
     * Define if result should be kept or not
     *
     * @param mixed $keep
     *            check if result should be kept
     *
     * @return void
     * @since XXX
     */
    public function setKeep($keep)
    {
        $this->keep = $keep;
    }

    /**
     * Get if result should be kept or not
     *
     * @return mixed
     * @since XXX
     */
    public function getKeep()
    {
        return $this->keep;
    }

    /**
     *
     * @var array the phase function definition
     */
    private $handler;

    /**
     * Define raw function (in erlang / javascript)
     *
     * @param string $rawFunction
     *            raw script
     *
     * @return void
     * @since XXX
     */
    public function setRawFunction($rawFunction)
    {
        $this->handler = array(
            'source' => $rawFunction
        );
    }

    /**
     * Get raw function or null if not defined
     *
     * @return string
     * @since XXX
     */
    public function getRawFunction()
    {
        return (isset($this->handler['source']) === true) ? $this->handler['source'] : null;
    }

    /**
     * Define builtin function to use
     *
     * @param string $namedFunction
     *            builtin function name
     *
     * @return void
     * @since XXX
     */
    public function setNamedFunction($namedFunction)
    {
        $this->handler = array(
            'name' => $namedFunction
        );
    }

    /**
     * Get builtin function or null if not defined
     *
     * @return string
     * @since XXX
     */
    public function getNamedFunction()
    {
        return (isset($this->handler['name']) === true) ? $this->handler['name'] : null;
    }

    /**
     * Define stored procedure to use
     *
     * @param string $bucket
     *            bucket containing stored procedure
     * @param string $key
     *            key of the object containing stored procedure
     *
     * @return void
     * @since XXX
     */
    public function setStoredProcedure($bucket, $key)
    {
        // TODO: check how to handle erlang version (module / function)
        $this->handler = array(
            'bucket' => $bucket,
            'key' => $key
        );
    }

    /**
     * Get stored procedure or null if not defined
     *
     * @return string
     * @since XXX
     */
    public function getStoredProcedure()
    {
        $procedure = null;
        if ((isset($this->handler['bucket']) === true) && (isset($this->handler['key']) === true)) {
            $procedure = array_values($this->handler);
        }
        return $procedure;
    }

    /**
     * Define module to use (Not implemented yet)
     *
     * @param string $module
     *            The module to set.
     *
     * @return void
     * @since XXX
     */
    public function setModule($module)
    {
        throw new HttpException(501);
        // $this->_handler['module'] = $module;
    }

    /**
     * Get the module to use or null if not defined (Not implemented yet)
     *
     * @return Ambigous <NULL, multitype:>
     */
    public function getModule()
    {
        throw new HttpException(501);
        return (isset($this->handler['module']) === true) ? $this->handler['module'] : null;
    }
}
