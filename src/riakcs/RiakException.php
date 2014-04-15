<?php

/**
 * File RiakException.php
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

/**
 * Class RiakException handles exception for riakcs.
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
class RiakException extends \Exception
{

    /**
     * Check if message is xml format.
     * Then format message.
     *
     * @param string $message
     *            The exception message
     * @param string $code
     *            The code
     * @param string $previous
     *            The previous exeption.
     *
     * @return void
     * @since XXX
     */
    public function __construct($message = null, $code = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $doc = new \DOMDocument('1.0', 'UTF-8');
        try {
            $doc->loadXML($message);
            if ($doc->getElementsByTagName('Error')->length > 0) {
                $this->message = 'Error : ' . $doc->getElementsByTagName('Message')->item(0)->textContent;
                $this->message .= "\nResource : " . $doc->getElementsByTagName('Resource')->item(0)->textContent;
                $this->message .= "\nCode : " . $doc->getElementsByTagName('Code')->item(0)->textContent;
            }
        } catch (\Exception $e) {
            $this->message = $message;
        }
    }
}
