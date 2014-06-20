<?php

/**
 * Response builder.php
 *
 * PHP version 5.3+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.yii2.nosql.riak
 */
namespace sweelix\yii2\nosql\riak;

use sweelix\curl\Response;

/**
 * Class Command
 *
 * This class is used to build response from \sweelix\curl\Response
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.yii2.nosql.riak
 * @since     XXX
 */
class ResponseBuilder
{
    public static function buildGetResponse(Response $response, $bucket, $key)
    {
        if ($response->getStatus() >= 400) {
            throw new RiakException($response->getData(), $response->getStatus());
        }
        $ret['bucket'] = $bucket;
        $ret['key'] = $key;
        $ret['vclock'] = $response->getHeaderField('X-Riak-Vclock');

        if ($response->getStatus() === 200 || $response->getStatus() === 204 || $response->getIsMultipart() === false) {
            $ret['values'][] = self::buildValues($response);
        } else {
            foreach ($response->extractMultipartDataAsResponse() as $multiResponse) {
                $ret['values'][] = self::buildValues($multiResponse);
            }
        }
        return $ret;
    }

    public static function buildPutResponse(Response $response, $bucket = null, $key = null)
    {
        if ($response->getStatus() >= 400) {
            throw new RiakException($response->getRawData(), $response->getStatus());
        }
        $ret = array();
        if ($bucket && $key) {
            $ret['bucket'] = $bucket;
            $ret['key'] = $key;
        }
        $ret['vclock'] = $response->getHeaderField('X-Riak-Vclock');

        switch ($response->getStatus()) {
            case 200:
                if (!$bucket && !$key) {
                    self::resolveBucketAndKey($response, $ret);
                }
                $ret['values'][] = self::buildValues($response);
                break;
            case 201:
                self::resolveBucketAndKey($response->getHeaderField('Location'), $ret);
                $ret['values'][] = self::buildValues($response);
                break;
            case 300:
                foreach ($response->extractMultipartDataAsResponse() as $multiResponse) {
                    $ret['values'][] = self::buildValues($multiResponse);
                }
                break;
            default:
                throw new RiakException('Unknow status code', 500);
                break;
        }
        return $ret;
    }

    public static function buildGetBucketPropResponse(Response $response)
    {
        $ret = $response->getData();
        return $ret['props'];
    }

    public static function buildPutBucketPropResponse(Response $response)
    {
        if ($response->getStatus() === 400) {
            throw new RiakException('Submitted JSON is invalid', 400);
        }
        if ($response->getStatus() === 415) {
            throw new RiakException('Submitted JSON is invalid', 415);
        }

        return true;
    }

    public static function buildPutCounterResponse(Response $response)
    {
        if ($response->getStatus() === 204) {
            return true;
        } else {
            throw new RiakException('Counters require bucket property \'allow_mult=true\'');
        }
    }

    public static function buildGetCounterResponse(Response $response)
    {
        if ($response->getStatus() === 200) {
            return intval($response->getRawData());
        } else {
            throw new RiakException('Counters require bucket property \'allow_mult=true\'');
        }
    }

    public static function buildIndexResponse(Response $response)
    {
        if ($response->getStatus() === 400) {
            throw new RiakException('Index name or index value is invalid.');
        }
        if ($response->getStatus() === 500) {
            throw new RiakException('Internal Server Error.', 500);
        }
        if ($response->getStatus() === 503) {
            throw new RiakException('Service Unavailable.', 503);
        }

        $ret = $response->getData();
        return $ret['keys'];
    }

    public static function buildMapReduceResponse(Response $response)
    {
        if ($response->getStatus() === 400) {
            throw new RiakException('Bad request. Invalid Job Submitted', 400);
        } elseif ($response->getStatus() === 500) {
            var_dump($response);
            throw new RiakException('Internal Server Error.', 500);
        } elseif ($response->getStatus() === 503) {
            throw new RiakException('Service Unavailable.', 503);
        }

        return $response->getData();
    }

    public static function buildLinkResponse(Response $response, $bucket, $key)
    {
        if ($response->getStatus() === 400) {
            throw new RiakException('The query is invalid.', 400);
        }
        if ($response->getStatus() === 404) {
            throw new RiakException('The origin object not found.', 404);
        }

        $ret = array();
        //If we arrive there, response is mutlipart (200 OK)

        $responses = $response->extractMultipartDataAsResponse();
        if (empty($responses) === false) {

            foreach ($responses as $resp) {
                $obj = [];
                self::resolveBucketAndKey($resp->getHeaderField('Location'), $obj);
                $obj['vclock'] = $resp->getHeaderField('X-Riak-Vclock');
                $obj['values'][] = self::buildValues($resp);
                $ret[] = $obj;
            }
        }
        return $ret;
    }

    private static function buildValues(Response $response)
    {
        $values['metadata'] = array(
            'Links' => self::buildLinks($response->getHeaderField('Link')),
            'X-Riak-Vtag' => $response->getHeaderField('Etag'),
            'content-type' => $response->getHeaderField('Content-Type'),
            'index' => self::buildIndexes($response->getHeaders()),
            'X-Riak-Last-Modified' => $response->getHeaderField('Last-Modified'),
            'X-Riak-Meta' => self::buildMetadata($response->getHeaders())
        );
        $values['data'] = $response->getRawData();
        return $values;
    }

    private static function buildLinks($rawLinks)
    {
        $ret = array();
        $links = explode(', ', $rawLinks);

        foreach ($links as $link) {
            $link = static::buildLink($link);
            if ($link !== null) {
                $ret[] = $link;
            }
        }
        return $ret;
    }

    /**
     * Returns an array
     * [
     *     'bucket' => 'nameOfBucketLink',
     *     'key' => 'linkedKey',
     *     'tag' => 'riakTag'
     * ]
     *
     * @param string $rawLink
     *
     * @return array
     * @since  XXX
     */
    public static function buildLink($rawLink)
    {
        $ret = null;
        if (preg_match(
            '/^<\/buckets\/(?<bucket>[^\/]+)\/keys\/(?<key>[^>]+)>; riaktag="(?<tag>[^"]+)"$/',
            $rawLink,
            $matches
        ) > 0) {
            $ret = [urldecode($matches['bucket']), urldecode($matches['key']), urldecode($matches['tag'])];
        }
        return $ret;
    }

    public static function getLinkTemplate()
    {
        return '</buckets/{bucketName}/keys/{key}>; riaktag="{tag}"';
    }

    private static function buildIndexes(array $headers)
    {
        $ret = array();
        foreach ($headers as $name => $value) {
            if (preg_match('/^X-Riak-index-(?<indexName>[^*$]+)$/i', $name, $matches) > 0) {
                $ret[$matches['indexName']] = $value;
            }
        }
        return $ret;
    }

    private static function buildMetadata(array $headers)
    {
        $ret = array();
        foreach ($headers as $name => $value) {
            if (preg_match('/^X-Riak-Meta-(?<metaName>[^*$]+)$/i', $name, $matches) > 0) {
                $ret['X-Riak-Meta-'.$matches['metaName']] = $value;
            }
        }
        return $ret;
    }

    private static function resolveBucketAndKey($locationHeader, &$ret)
    {
        if (preg_match('/\/buckets\/(?<bucket>[^\/]+)\/keys\/(?<key>[^*$]+)$/', $locationHeader, $matches) > 0) {
            $ret['bucket'] = urldecode($matches['bucket']);
            $ret['key'] = urldecode($matches['key']);
        } else {
            throw new RiakException(500, 'Cant\'t resolve bucketName/objectKey');
        }
    }
}
