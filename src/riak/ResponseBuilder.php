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
        $response['bucket'] = $bucket;
        $response['key'] = $key;
        $response['vclock'] = $response->getHeaderField('X-Riak-Vclock');

        if ($response->getStatus() === 200) {
            $values['metadata'] = array(
                'Links' => self::buildLinks($response->getHeaderField('Link')),
                'X-Riak-Vtag' => $response->getHeaderField('Etag'),
                'content-type' => $response->getHeaderField('Content-Type'),
                'index' => self::buildIndexes($response->getHeaders()),
                'X-Riak-Last-Modified' => $response->getHeaderField('Last-Modified'),
                'X-Riak-Meta' => self::buildMetadata($response->getHeaders())
            );
            $values['data'] = $response->getRawData();
        }
        return $response;
    }

    public static function buildPutResponse()
    {
    }

    public static function buildBucketPropResponse()
    {

    }

    private static function buildLinks($rawLinks)
    {
        $ret = array();
        $links = explode(', ', $rawLinks);

        foreach ($links as $link) {
            if (preg_match(
                '/^<\/buckets\/(?<bucket>[^\/]+)\/keys\/(?<key>[^>]+)>; riaktag="(?<tag>[^"]+)"$/',
                $link,
                $matches
            ) > 0) {
                $ret[] = array($matches['bucket'], $matches['key'], $matches['tag']);
            }
        }
        return $ret;
    }

    private static function buildIndexes(array $headers)
    {
        $ret = null;
        foreach ($headers as $name => $value) {
            if (preg_match('/^X-Riak-index-(?<indexName>[^*$]+)$/i', $name, $matches) > 0) {
                $ret[$matches['indexName']] = $value;
            }
        }
        return $ret;
    }

    private static function buildMetadata(array $headers)
    {
        $ret = null;
        foreach ($headers as $name => $value) {
            if (preg_match('/^X-Riak-Meta-(?<metaName>[^*$]+)$/i', $name, $matches) > 0) {
                $ret[$matches['metaName']] = $value;
            }
        }
        return $ret;
    }
}
