<?php
/**
 * File KeyFilter.php
 *
 * PHP version 5.3+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak
 */
namespace sweelix\yii2\nosql\riak;

use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;

/**
 * Class KeyFilter
 *
 * This is the builder for keyfiltering.
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak
 * @since     XXX
 *
 * @method    KeyFilter intToString();
 * @method    KeyFilter stringToInt();
 * @method    KeyFilter floatToString();
 * @method    KeyFilter stringToFloat();
 * @method    KeyFilter toUpper();
 * @method    KeyFilter toLower();
 * @method    KeyFilter tokenize($separtor, $part);
 * @method    KeyFilter urlDecode();
 * @method    KeyFilter greaterThan($number);
 * @method    KeyFilter greaterThanEq($number);
 * @method    KeyFilter lessThan($number);
 * @method    KeyFilter lessThanEq($number);
 * @method    KeyFilter between($min, $max, $inclusive = false);
 * @method    KeyFilter matches($value);
 * @method    KeyFilter notEquals($value);
 * @method    KeyFilter setMember(array $members);
 * @method    KeyFilter similarTo($value, $length);
 * @method    KeyFilter startsWith($value);
 * @method    KeyFilter endsWith($value);
 * @method    KeyFilter and();
 * @method    KeyFilter or();
 * @method    KeyFilter not();
 */
class KeyFilter
{
    public $bucketName;

    private $keyFilters = [];

    private $genericFunctions = [
        'intToString' => 'int_to_string',
        'stringToInt' => 'string_to_int',
        'floatToString' => 'float_to_string',
        'stringToFloat' => 'string_to_float',
        'toUpper' => 'to_upper',
        'toLower'=> 'to_lower',
        'tokenize' => 'tokenize',
        'urlDecode' => 'url_decode',
        'greaterThan' => 'greater_than',
        'greaterThanEq' => 'greater_than_eq',
        'matches' => 'matches',
        'notEquals' => 'neq',
        'equals' => 'eq',
        'similarTo' => 'similar_to',
        'startsWith' => 'starts_with',
        'endsWith' => 'ends_with'
    ];

    public function __construct($bucketName = null)
    {
        $this->bucketName = $bucketName;
    }


    public function __call($name, $arguments)
    {
        if (in_array($name, ['or', 'and', 'not']) === true) {
            $element = array_pop($this->keyFilters);
            $this->add([$name, [$element]]);
        } elseif ($name === 'between') {
            list($min, $max) = $arguments;
            $q = ['between', $min, $max, false];
/*            if (count($arguments) == 3) {
                $q[] = false;
            }*/
            $this->add($q);
        } elseif ($name === 'setMember') {
            $this->add(array_merge(['set_member'], $arguments[0]));
        } elseif (array_key_exists($name, $this->genericFunctions)) {
            $element[] = $this->genericFunctions[$name];
            foreach ($arguments as $argument) {
                $element[] = $argument;
            }
            $this->add($element);
        } else {
            throw new InvalidCallException($name . ' method, does not exist');
        }
        return $this;
    }

    private function add($element)
    {
        if (count($this->keyFilters) > 0) {
            $end = $this->keyFilters[count($this->keyFilters) - 1];
            if ($end[0] === 'or' || $end[0] === 'and' || $end[0] === 'not') {
                if ($element[0] == 'or' || $element[0] === 'and' || $element === 'not') {
                    throw new InvalidCallException('You can\'t chain those method (add(), orr(), not())');
                } else {
                    $el = array_pop($this->keyFilters);
                    $el[] = [$element];
                    $this->keyFilters[] = $el;
                }
            } else {
                $this->keyFilters[] = $element;
            }
        } else {
            $this->keyFilters[] = $element;
        }
    }

    public function build($jsonEncode = false)
    {
        if ($this->bucketName === null) {
            throw new InvalidConfigException('You should set the bucketName before build');
        }
        $ret['bucket'] = $this->bucketName;
        $ret['key_filters'] = $this->keyFilters;

        return $jsonEncode ? json_encode($ret) : $ret;
    }

    public function reset()
    {
        $this->keyFilters = [];
    }
}
