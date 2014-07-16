<?php
/**
 * File Link.php
 *
 * PHP version 5.4+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @author    Philippe Gaultier <pgaultier@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak.phase
 */
namespace sweelix\yii2\nosql\riak\phase;

use sweelix\yii2\nosql\riak\interfaces\Phase as InterfacePhase;
use yii\base\Component;

/**
 * Class Link
 *
 * This class encapsulate a link phase
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @author    Philippe Gaultier <pgaultier@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak.phase
 * @since XXX
 */
class Link extends Component implements InterfacePhase
{

    /**
     *
     * @var string bucket name
     */
    private $bucket;

    /**
     *
     * @var mixed static argument passed to phase phase
     */
    private $keep;

    /**
     *
     * @var string tag name
     */
    private $tag;

    /**
     * Build current phase and return the correct data
     * or null if phase is invalid
     *
     * @return array
     * @since XXX
     */
    public function build()
    {
        $operation = array();
        if ($this->bucket !== null) {
            $operation['bucket'] = $this->bucket;
        }
        if ($this->keep !== null) {
            $operation['keep'] = $this->keep;
        }
        if ($this->tag !== null) {
            $operation['tag'] = $this->tag;
        }

        $phase = null;
        if (empty($operation) == false) {
            $phase = array(
                'link' => $operation
            );
        }
        return $phase;
    }

    /**
     * Define target bucket
     *
     * @param string $bucket
     *            bucket name
     *
     * @return void
     * @since XXX
     */
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;
    }

    /**
     * Get target bucket
     *
     * @return mixed
     * @since XXX
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Define related tag
     *
     * @param string $tag
     *            tag name
     *
     * @return void
     * @since XXX
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    /**
     * Get target tag name
     *
     * @return mixed
     * @since XXX
     */
    public function getTag()
    {
        return $this->tag;
    }

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
}
