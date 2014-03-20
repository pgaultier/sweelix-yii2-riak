<?php

/**
 * File Client.php
 *
 * PHP version 5.3+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak-cs
 */

namespace sweelix\yii2\nosql\riakcs;

use yii\base\Component;
use sweelix\curl\Request;
use yii\web\HttpException;

/**
 * Class Client
 *
 * The class is handle request & response to DB (noSql) server
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql.riak-cs
 * @since     XXX
 */

class Client extends Component {
	const ACL_PRIVATE = 'private';
	const ACL_PUBLIC_READ = 'public-read';
	const ACL_PUBLIC_READ_WRITE = 'public-read-write';
	const ACL_AUTHENTICATED_READ = 'authenticated-read';
	
	/**
	 * @var string AWS URI
	 */
	private $_endPoint;
	
	/**
	 * @var string Proxy uri
	 */
	private $_proxy;
	
	/**
	 * @var string AWS accessKey
	 */
	private $_accessKey;
	
	/**
	 * @var string AWS secret key
	 */
	private $_secretKey;
	
	/**
	 * @var string SSl client key
	 */
	private $_sslKey;
	
	/**
	 * @var string SSl CLient Key
	 */
	private $_sslCert;
	
	/**
	 * @var string SSL CA cert (only required if you are having problems with your system CA cert)
	 */
	private $_sslCACert;
	
	/**
	 * @var boolean Connect using SSl ?
	 */
	private $_useSsl;
	
	/**
	 * @var boolean Use ssl validation ?
	 */
	private $_useSslValidation;
	
	/**
	 * @var boolean Use php exception
	 */
	private $_useExceptions;
	
	/**
	 * @var integer TimeOffset applied to time
	 */
	private $_timeOffset = 0;
	

	/**
	 * EndPoint setter. (Default : s3.amazonaws.com)
	 * 
	 * @return string
	 * @since  XXX
	 */
	public function getEndPoint() {
		return $this->_endPoint;
	}
	
	/**
	 * Endpoint setter
	 * 
	 * @param string $endPoint AWS URI
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function setEndPoint($endPoint) {
		$this->_endPoint = $endPoint;
	}
	
	/**
	 * Proxy getter
	 * 
	 * @return string
	 * @since  XXX
	 */
	public function getProxy() {
		return $this->_proxy;
	}

	/**
	 * Proxy setter (Do not include http:// ex: '192.168.1.123')
	 * 
	 * @param string $proxy Proxy URI
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function setProxy($proxy) {
		$this->_proxy = $proxy;
	}
	
	/**
	 * AccessKey getter
	 * 
	 * @return string
	 * @since  XXX
	 */
	public function getAccessKey() {
		return $this->_accessKey;
	}
	
	/**
	 * AccessKey setter
	 * 
	 * @param string $accessKey AWS AccessKey
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function setAccessKey($accessKey) {
		$this->_accessKey = $accessKey;
	}
	
	/**
	 * Secret key getter
	 * 
	 * @return string
	 * @since  XXX
	 */
	public function getSecretKey() {
		return $this->_secretKey;
	}
	
	/**
	 * SecretKey setter
	 * 
	 * @param string $secretKey AWS SecretKey
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function setSecretKey($secretKey) {
		$this->_secretKey = $secretKey;
	}
	
	/**
	 * SSLKey getter
	 * 
	 * @return string
	 * @since  XXX
	 */
	public function getSslKey() {
		return $this->_sslKey;
	}
	
	/**
	 * SSLKey setter
	 * 
	 * @param string $sslKey SslKey
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function setSslKey($sslKey) {
		$this->_sslKey = $sslKey;
	}
	
	/**
	 * SslCert getter
	 * 
	 * @return string
	 * @since  XXX
	 */
	public function getSslCert() {
		return $this->_sslCert;
	}
	
	/**
	 * SslCert setter
	 * 
	 * @param string $sslCert SSlCert
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function setSslCert($sslCert) {
		$this->_sslCert = $sslCert;
	}
	
	/**
	 * SslCaCert getter
	 * 
	 * @return string
	 * @since  XXX
	 */
	public function getSslCaCert() {
		return $this->_sslCACert;
	}
	
	/**
	 * SslCaCert setter
	 * 
	 * @param string $sslCACert SSlCaCert
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function setSslCaCert($sslCACert) {
		$this->_sslCACert = $sslCACert;
	}
	
	/**
	 * UseSsl getter
	 * 
	 * @return boolean
	 * @since  XXX
	 */
	public function getUseSsl() {
		return $this->_useSsl;
	}
	
	/**
	 * UseSsl setter
	 * 
	 * @param boolean $useSsl use SSl ?
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function setUseSsl($useSsl) {
		$this->_useSsl = $useSsl;
	}

	/**
	 * UseSslValidat getter
	 * 
	 * @return boolean
	 * @since  XXX
	 */
	public function getUseSslValidation() {
		return $this->_useSslValidation;
	}
	
	/**
	 * UseSslValidation setter
	 * 
	 * @param boolean $useSslValidation use ssl validation ?
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function setUseSslValidation($useSslValidation) {
		$this->_useSslValidation = $useSslValidation;
	}
	
	/**
	 * UseExceptions getter
	 * 
	 * @return boolean
	 * @since  XXX
	 */
	public function getUseExceptions() {
		return $this->_useExceptions;
	}
	
	/**
	 * UseExceptions setter
	 * 
	 * @param boolean $useException use php exception ?
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function setUseExceptions($useException) {
		$this->_useExceptions = $useException;
	}
	
	/**
	 * Time offset getter
	 * 
	 * @return integer
	 * @since  XXX
	 */
	public function getTimeOffset() {
		return $this->_timeOffset;
	}
	
	/**
	 * TimeOffset setter
	 * 
	 * @param integer $timeOffset Time offset applied to time()
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function setTimeOffset($timeOffset) {
		$this->_timeOffset = $timeOffset;
	}
	
	/**
	 * Return a list of objects which is contained in the bucket ($bucket)
	 * An object is represented like that :
	 * array(
	 *    'key' => 'object key',
	 *    'Last-Modified' => 'Last modification date of object',
	 *    'etag' => 'object etag value',
	 *    'size' => 'object size',
	 *    'storage' => 'storage type',
	 *    'ownerId' => 'The object owner id',
	 *    'ownerName' => 'The object owner name',
	 * );
	 * 
	 * @param string $bucket     The bucket name wanted to get
	 * @param array  $parameters An array of key => value which represents paramName => paramValue
	 * @param array  $headers    An array of key => value which represents headerName => headerValue.
	 * 
	 * @return array|false Object array or false, if object not found.
	 * @since  XXX
	 */
	public function getBucket($bucket, $parameters = array(), $headers = array()) {
		try {
			\Yii::trace('Trace: '.__CLASS__.'::'.__FUNCTION__.'()', __METHOD__);
			$ret = false;
			$request = $this->createRequest('GET', $bucket, '', $headers, $parameters);
			
			$response = $request->execute();
			
			if ($response->getStatus() === 200) {
				$ret = array();
				$doc = new \DOMDocument('1.0', 'UTF-8');
				$doc->loadXML($response->getData());
				$contents = $doc->getElementsByTagName('Contents');
				foreach ($contents as $content) {
					$key = $content->getElementsByTagName('Key')->item(0)->textContent;
					$lastModified = $content->getElementsByTagName('LastModified')->item(0)->textContent;
					$etag = $content->getElementsByTagName('ETag')->item(0)->textContent;
					$size = $content->getElementsByTagName('Size')->item(0)->textContent;
					$storage = $content->getElementsByTagName('StorageClass')->item(0)->textContent;
					$ownerId = $content->getElementsByTagName('Owner')->item(0)->getElementsByTagName('ID')->item(0)->textContent;
					$displayName = $content->getElementsByTagName('Owner')->item(0)->getElementsByTagName('DisplayName')->item(0)->textContent;
					$ret[] = array(
							'key' => $key,
							'Last-Modified' => $lastModified,
							'etag' => $etag,
							'size' => $size,
							'storage' => $storage,
							'ownerId' => $ownerId,
							'ownerName' => $displayName,
					);
				}
			} else {
				throw new RiakException($response->getData(), $response->getStatus());
			}
			return $ret;
		} catch (\Exception $e) {
			return $this->handleException($e);
		}
	}
	
	/**
	 * Return the access control list of the specified bucket ($bucket)
	 * Access Control List are respresented like that :
	 * array(
	 *    'ownerId' => 'id of the object owner',
	 *    'ownerName' => 'Name of the owner',
	 *    'grants' => array(
	 *       array(
	 *          'userId' => 'Id of the user',
	 *          'username' => 'user name',
	 *          'permission' => 'Type of permission that user have on the object'
	 *       ),
	 *       //ETC...
	 *    )
	 * );
	 * 
	 * @param string $bucket     The bucket name wanted to get
	 * @param array  $parameters An array of key => value which represents paramName => paramValue
	 * @param array  $headers    An array of key => value which represents headerName => headerValue.
	 * 
	 * @return array|false
	 * @since  XXX
	 */
	public function getBucketAcl($bucket, $parameters = array(), $headers = array()) {
		try {
			\Yii::trace('Trace: '.__CLASS__.'::'.__FUNCTION__.'()', __METHOD__);
			$ret = false;
			$parameters = array_merge(array('acl' => ''), $parameters);
			$request = $this->createRequest('GET', $bucket, '', $headers, $parameters);
			
			$response = $request->execute();
			
			
			if ($response->getStatus() === 200) {
				$ret = $this->buildAcl($response->getData());
			} else {
				throw new RiakException($response->getData(), $response->getStatus());
			}
			return $ret;
		} catch (\Exception $e) {
			return $this->handleException($e);
		}
	}
	
	/**
	 * NOT WORKING YET
	 * 
	 * @param string $bucket     The bucket name wanted to get
	 * @param array  $parameters An array of key => value which represents paramName => paramValue
	 * @param array  $headers    An array of key => value which represents headerName => headerValue.
	 * 
	 * @return void
	 * @since  XXX
	 */
	public function getBucketPolicy($bucket, $parameters = array(), $headers = array()) {
		throw new HttpException(501);
		$parameters = array_merge(array('policy' => ''), $parameters);
		$request = $this->createRequest('GET', $bucket, '', $headers, $parameters);
		
		$response = $request->execute();
	}
	
	/**
	 * Create a bucket name $bucket.
	 * 
	 * @param string $bucket  The bucket name wanted to get
	 * @param string $acl     The access control list to set to the bucket.
	 * @param array  $headers An array of key => value which represents headerName => headerValue.
	 * 
	 * @return boolean
	 * @since  XXX
	 */
	public function putBucket($bucket, $acl = self::ACL_PRIVATE, $headers = array()) {
		try {
			\Yii::trace('Trace: '.__CLASS__.'::'.__FUNCTION__.'()', __METHOD__);
			$ret = false;
			$headers['x-amz-acl'] = $acl;
			$request = $this->createRequest('PUT', $bucket, '', $headers);
	
			
			$response = $request->execute();
			
			if ($response->getStatus() === 200) {
				$ret = true;
			} else {
				throw new RiakException($response->getData(), $response->getStatus());
			}
			return $ret;
		} catch (\Exception $e) {
			return $this->handleException($e);
		}
	}
	
	/**
	 * Delete the bucket named $bucket
	 * 
	 * @param string $bucket The bucket name wanted to delete
	 * 
	 * @return boolean
	 * @since  XXX
	 */
	public function deleteBucket($bucket) {
		try {
			\Yii::trace('Trace: '.__CLASS__.'::'.__FUNCTION__.'()', __METHOD__);
			$ret = false;
			$request = $this->createRequest('DELETE', $bucket);
			
			$response = $request->execute();
			
			if ($response->getStatus() === 204) {
				$ret = true;
			} else {
				throw new RiakException($response->getData(), $response->getStatus());
			}
			return $ret;
		} catch (\Exception $e) {
			return $this->handleException($e);
		}
	}
	
	/**
	 * Return the representation of an object.
	 * array(
	 * 	 'headers' => 'object headers',
	 *   'data' => 'object data',
	 * )
	 * 
	 * @param string $bucket    The bucket which contains $objectKey.
	 * @param string $objectKey The object key wanted to get.
	 * 
	 * @return array|false
	 * @since  XXX
	 */
	public function getObject($bucket, $objectKey) {
		try {
			\Yii::trace('Trace: '.__CLASS__.'::'.__FUNCTION__.'()', __METHOD__);
			$object = false;
			$request = $this->createRequest('GET', $bucket, $objectKey);
			
			$response = $request->execute();
			
			if ($response->getStatus() === 200) {
				$object['headers'] = $response->getHeaders();
				$object['data'] = $response->getData();
			} else {
				throw new RiakException($response->getData(), $response->getStatus());
			}
			return $object;	
		} catch (\Exception $e) {
			return $this->handleException($e);
		}
	}
	
	/**
	 * Return Access Control List of the object ($objectKey) in the bucket ($bucket).
	 * 
	 * @param string $bucket    The bucket which contains $objectKey.
	 * @param string $objectKey The object key wanted to get.
	 * 
	 * @return array|false
	 * @since  XXX
	 */
	public function getObjectAcl($bucket, $objectKey) {
		try {
			\Yii::trace('Trace: '.__CLASS__.'::'.__FUNCTION__.'()', __METHOD__);
			$acl = false;
			$request = $this->createRequest('GET', $bucket, $objectKey, array(), array('acl' => ''));
			
			$response = $request->execute();
			
			if ($response->getStatus() === 200) {
				//ISSUE : Content-Type = application/json
				//Should be application/xml
				//getData() returns null cause it tries to json_decode but it's xml...
				//So use getRawData() instead of getData()
				//var_dump($response->getRawData());
				//var_dump($response->getHeaderField('Content-Type'));
				//var_dump($response->getData());
				$data = $response->getRawData();
				if (!empty($data)) {
					$acl = $this->buildAcl($data);
				}
			} else {
				throw new RiakException($response->getData(), $response->getStatus());
			}
			return $acl;
		} catch (\Exception $e) {
			return $this->handleException($e);
		}
	}
	
	
	/**
	 * Store an object in the bucket ($bucket) with the key ($objectkey).
	 * metaHeaders and headers should be formatted like that :
	 * array(
	 * 		'metaName' => 'metaValue',
	 *		'headerFieldName' => 'headerFieldValue',
	 * );
	 * 
	 * @param string $bucket      The bucket which contains $objectKey.
	 * @param string $objectKey   The object key wanted to get.
	 * @param mixed  $data        The object data to insert in BDD (if is array, automatically json_encode + contentType = 'application/json')
	 * @param string $contentType The type of data to insert.
	 * @param string $acl         The acl for the object.
	 * @param array  $metaHeaders Additionnal meta to attach to the object.
	 * @param array  $headers     Additionnal headers
	 * 
	 * @return boolean Whether the object has been successfully uploaded. 
	 * @since  XXX
	 */
	public function putObject($bucket, $objectKey, $data, 
							$contentType = 'application/octet-stream',
							$metaHeaders = array(),
							$acl = self::ACL_PRIVATE,
							$headers = array()) {
		try {
			\Yii::trace('Trace: '.__CLASS__.'::'.__FUNCTION__.'()', __METHOD__);
			$headers['x-amz-acl'] = $acl;
			$ret = false;
			$body = null;
			if (is_array($data) === true) {
				$contentType = 'application/json';
				$body = json_encode($data);
			} elseif (is_string($data)){
				$body = $data;
			} else {
				throw new RiakException('The type of data shoud be a string or an array.', 400);
			}
		
			foreach ($metaHeaders as $key => $value) {
				$headers['x-amz-meta-'.$key] = $value;
			}
			$headers['Content-MD5'] = base64_encode(md5($body, true));
			$headers['Content-Type'] = $contentType;
			$headers['Content-Length'] = strlen($body);
			$headers['Expect'] = '100-continue';
			$request = $this->createRequest('PUT', $bucket, $objectKey, $headers);
			
			$request->setBody($body);
			
			$response = $request->execute();
			
			if ($response->getStatus() === 200) {
				$ret = true;
			} else {
				throw new RiakException($response->getData(), $response->getStatus());
			}
			return $ret;
		} catch (\Exception $e) {
			return $this->handleException($e);
		}
	}
	
	/**
	 * Returns information about the object or false if the request failed.
	 * Returns an array like that :
	 * array(
	 *  'etag' => The object etag
	 *  'Content-Type' => Object content type
	 *  'Content-Length' => object size
	 *  'Last-modified' => Last modification
	 * );
	 * 
	 * @param string $bucket    The bucket which contains $objectKey.
	 * @param string $objectKey The object key wanted to get info.
	 * 
	 * @return array|boolean The information array about the object, or false if an error occured.
	 * @since  XXX
	 */
	public function headObject($bucket, $objectKey) {
		try {
			\Yii::trace('Trace: '.__CLASS__.'::'.__FUNCTION__.'()', __METHOD__);
			$objectInfo = false;
			
			$request = $this->createRequest('HEAD', $bucket, $objectKey);
	
			$response = $request->execute();
			
			if ($response->getStatus() === 200) {
				$objectInfo = array(
						'etag' => $response->getHeaderField('Etag'),
						'Content-Type' => $response->getHeaderField('Content-Type'),
						'Content-Length' => $response->getHeaderField('Content-Length'),
						'Last-Modified' => $response->getHeaderField('Last-Modified'),
				);
			} else {
				throw new RiakException($response->getData(), $response->getStatus());
			}
			return $objectInfo;
		} catch (\Exception $e) {
			return $this->handleException($e);
		}
	}
	
	/**
	 * This function remove an object from bucket.
	 * 
	 * @param string $bucket    The bucket which contains $objectKey.
	 * @param string $objectKey The object key wanted to delete.
	 * 
	 * @return boolean Whether the object has been deleted.
	 * @since  XXX
	 */
	public function deleteObject($bucket, $objectKey) {
		try {
			$ret = false;
			$request = $this->createRequest('DELETE', $bucket, $objectKey);
			
			$response = $request->execute();
			
			if ($response->getStatus() === 204) {
				$ret = true;
			} else {
				throw new RiakException($response->getData(), $response->getStatus());
			}
			return $ret;
		} catch (\Exception $e) {
			return $this->handleException($e);
		}
	}
	
	/**
	 * This function upload a file in multiple part.
	 * The size of each part can be set with partsize. (Size is in MB).
	 * 
	 * @param string  $bucket    The bucket name where you want to upload the object.
	 * @param string  $objectKey The key of the object wanted to upload.
	 * @param string  $filename  The file wanted to upload.
	 * @param array   $metaData  Metadata to store with the object.
	 * @param string  $acl       The Access Control to set for the file.
	 * @param integer $partSize  The size of each part you want to upload (in MB).
	 * 
	 * @return bool Wheter the upload has been successfull.
	 * @since  XXX
	 */
	public function multiPartUpload($bucket, $objectKey, $filename, $partSize = 5, $metaData = array(), $acl = self::ACL_PRIVATE) {
		try {
			\Yii::trace('Trace: '.__CLASS__.'::'.__FUNCTION__.'()', __METHOD__);
			$ret = false;
			
			$uploadId = $this->initMultiPartUpload($bucket, $objectKey, $filename, $acl, $metaData);
			\Yii::info('Init multi part upload succeed (uploadId : '.$uploadId.'). Beginning upload.', __METHOD__);
			
			$parts = $this->uploadAllPart($bucket, $objectKey, $filename, $acl, $uploadId, $partSize);
			\Yii::info('Upload all parts succeed.', __METHOD__);
			
			$ret = $this->completeMultiPartUpload($bucket, $objectKey, $parts, $uploadId);
			\Yii::info('Complete upload part succeed', __METHOD__);
			
			return $ret;
		} catch (\Exception $e) {
			return $this->handleException($e);
		}
	}
	
	/**
	 * Put a file ($filename) in the bucket ($bucket) with the key ($objectKey).
	 * If the file is larger than $partSize, it will use the multi part upload.
	 * Else it will use the standard put object.
	 * 
	 * @param string  $bucket    The bucket name where the object has to be stored.
	 * @param string  $objectKey The key of object to be stored.
	 * @param string  $filename  The file to upload.
	 * @param array   $metaData  The metaData to attach to the object.
	 * @param string  $acl       The access control list.
	 * @param integer $partSize  The size of each part to upload. (if partsize > objectSize, it will upload it in one part).
	 * 
	 * @return boolean Whether the upload has succeed.
	 * @since  XXX
	 */
	public function putFile($bucket, $objectKey, $filename, $metaData = array(), $acl = self::ACL_PRIVATE, $partSize = 5) {
		try {
			\Yii::trace('Trace: '.__CLASS__.'::'.__FUNCTION__.'()', __METHOD__);
			$infoFile = $this->inputFile($filename);
			$ret = false;
			
			if ($infoFile['size'] < $partSize * 1024 * 1024) {
				$f = @fopen($filename, 'r');
				if ($f === false) {
					throw new RiakException($filename.' : Can\'t open file', 404);
				} else {
					$data = fread($f, $infoFile['size']);
					fclose($f);
					$ret = $this->putObject($bucket, $objectKey, $data, $infoFile['type'], $metaData, $acl);
				}
			} else {
				$ret = $this->multiPartUpload($bucket, $objectKey, $filename, $partSize, $metaData, $acl);
			}
			return $ret;
		} catch (\Exception $e) {
			return $this->handleException($e);
		}
	}
	
	/**
	 * This function do the request to initialize the multi part upload.
	 * 
	 * @param string $bucket    The bucket name where you want to upload the object.
	 * @param string $objectKey The key of the object wanted to upload.
	 * @param string $filename  The file wanted to upload.
	 * @param string $acl       The Access Control to set for the file.
	 * @param array  $metaData  Metadata to store with the file.
	 *
	 * @return string Return the uploadId.
	 * @since  XXX
	 */
	public function initMultiPartUpload($bucket, $objectKey, $filename, $acl = self::ACL_PRIVATE, $metaData = array()) {
		\Yii::trace('Trace: '.__CLASS__.'::'.__FUNCTION__.'()', __METHOD__);
		
		$ret = false;
		$headers = array();
		
		$headers['Content-Type'] = $this->getFileType($filename);
		$headers['x-amz-acl'] = $acl;
		foreach ($metaData as $key => $value) {
			$headers['x-amz-meta-'.$key] = $value;
		}
		
		$request = $this->createRequest('POST', $bucket, $objectKey, $headers, array('uploads' => ''));
		$response = $request->execute();
		
		\Yii::info('Response of initMutliPartUpload :'.var_export($response, true), __METHOD__);
		
		if ($response->getStatus() === 200) {
			$doc = new \DOMDocument('1.0', 'UTF-8');
			$doc->loadXML($response->getData());

			$uploadIds = $doc->getElementsByTagName('UploadId');
			$ret = $uploadIds->item(0)->textContent;
		} else {
			\Yii::error('Init multi part upload failed.', __METHOD__);
			\Yii::error('Response :'.var_export($response, true), __METHOD__);
			throw new RiakException($response->getData(), $response->getStatus());
		}
				
		return $ret;
	}
	
	/**
	 * This function upload all part of the file ($filename) using the uploadId generates by initMultiPartUpload.
	 * It returns an array like this :
	 * array(
	 *    partNumber => ETag,
	 *    //ETC
	 * );
	 * PartNumber begins at 1.
	 * 
	 * @param string  $bucket    The bucket name where you want to upload the object.
	 * @param string  $objectKey The key of the object wanted to upload.
	 * @param string  $filename  The file wanted to upload.
	 * @param string  $acl       The Access Control to set for the file.
	 * @param string  $uploadId  The uploadId generates by Client::initMultiPartUpload().
	 * @param integer $partSize  The size of each part wanted to upload.
	 * 
	 * @return array An array which contains partNumber (key) => ETag of the part (value).
	 * @since  XXX
	 */
	public function uploadAllPart($bucket, $objectKey, $filename, $acl, $uploadId, $partSize) {
		try {
			\Yii::trace('Trace: '.__CLASS__.'::'.__FUNCTION__.'()', __METHOD__);
			$parts = false;
			
			$f = @fopen($filename, 'r');
			if ($f === false) {
				$this->abortUpload($bucket, $objectKey, $uploadId);
				throw new RiakException($filename.' : No such file/Permission denied', 404);
			} else {
				$partNumber = 1;
				$partSize *= 1024 * 1024;
				$headers['Content-Type'] = $this->getFileType($filename);
				while (!feof($f)) {
					$parts = array();
					$data = fread($f, $partSize);
			
					\Yii::info('Uploading part '.$partNumber.' of size '.strlen($data), __METHOD__);
			
					$headers['Content-Length'] = strlen($data);
					$headers['x-amz-acl'] = $acl;
					$request = $this->createRequest('PUT', $bucket, $objectKey, $headers, array(
							'partNumber' => $partNumber,
							'uploadId' => $uploadId,
					));
					$request->setBody($data);
					$response = $request->execute();
			
					\Yii::trace('Response of upload part ('.$partNumber.') :'.var_export($response, true), __METHOD__);
			
			
					if ($response->getStatus() === 200) {
						$parts[$partNumber] = $response->getHeaderField('ETag');
					} else {
						$this->abortUpload($bucket, $objectKey, $uploadId);
						fclose($f);
						throw new RiakException($response->getData(), $response->getStatus());
					}
					$partNumber++;
				}
				fclose($f);
					
			}
			return $parts;
		} catch (\Exception $e) {
			return $this->handleException($e);
		}
	}
	
	/**
	 * This function complete the upload.
	 * 
	 * @param string $bucket    The bucket name where you want to upload the object.
	 * @param string $objectKey The key of the object wanted to upload.
	 * @param array  $parts     All parts (partNumber => Etag value) generates by uploadAllPart.
	 * @param string $uploadId  The current uploadId (generates by initMultiPartUpload).
	 * 
	 * @return boolean Whether the complete did succeed.
	 * @since  XXX
	 */
	public function completeMultiPartUpload($bucket, $objectKey, $parts, $uploadId) {
		try {
			\Yii::trace('Trace: '.__CLASS__.'::'.__FUNCTION__.'()', __METHOD__);
			
			$ret = false;
			
			$xmlBody = $this->createXmlCompleteUpload($parts);
			$headers['Content-Length'] = strlen($xmlBody);
			$headers['Content-Type'] = 'application/json';
			
			$request = $this->createRequest('POST', $bucket, $objectKey, $headers, array('uploadId' => $uploadId));
			$request->setBody($xmlBody);
			$response = $request->execute();
			
			if ($response->getStatus() === 200) {
				$ret = true;
				\Yii::trace('Complete multi part upload response : '.var_export($response, true), __METHOD__);
			} else {
				\Yii::error('Complete multipart upload failed. : '.var_export($response, true), __METHOD__);
				$this->abortUpload($bucket, $objectKey, $uploadId);
				throw new RiakException($response->getData(), $response->getStatus());
			
			}
			return $ret;
		} catch (\Exception $e) {
			return $this->handleException($e);
		}

	}
	
	/**
	 * Create the xml object from $parts (which is generate by uploadAllParts).
	 * It will be used to complete the upload.
	 * 
	 * @param array $parts An array which contains partNumber => ETag of the part (This array is generated by uploadAllParts)
	 * 
	 * @return string The xml representation of $parts. It will be used to complete the upload.
	 * @since  XXX
	 */
	private function createXmlCompleteUpload($parts) { 
		$doc = new \DOMDocument('1.0', 'UTF-8');
		$root = $doc->createElement('CompleteMultipartUpload');
		foreach ($parts as $partIndex => $etag) {
			$part = $doc->createElement('Part');
			$partNumber = $doc->createElement('PartNumber', $partIndex);
			$partEtag = $doc->createElement('ETag', $etag);
		
			$part->appendChild($partNumber);
			$part->appendChild($partEtag);
			$root->appendChild($part);
		}
		$doc->appendChild($root);
		$xml = $doc->saveXML();
		
		return $xml;
	}
	
	/**
	 * Create input info array for putObject()
	 *
	 * @param string $file Input file
	 * 
	 * @return array | false
	 */
	public function inputFile($file) {
		if (!file_exists($file) || !is_file($file) || !is_readable($file)) {
			throw new RiakException($file.': No such file/Permission denied', 404);
		}
		return array('file' => $file, 'type' => $this->getFileType($file), 'size' => filesize($file));
	}
	
	/**
	 * Returns the mime type of the file.
	 * 
	 * @param string $filename Path of the resource.
	 * 
	 * @return string The mime type of the file. (ex : application/json)
	 * @since  XXX
	 */
	private function getFileType($filename) {
		$finfo = new \finfo(FILEINFO_MIME);
		$type = $finfo->file($filename);
		if ($type !== false) {
			return $type;
		} else {
			throw new RiakException($filename.' : No such file/Permission denied', 404);
		}
	}
	
	/**
	 * Abort an upload using his id (uploadId).
	 * 
	 * @param string $bucket    The bucket name.
	 * @param string $objectKey The object key.
	 * @param string $uploadId  THe uploadId wanted to stop.
	 * 
	 * @return boolean Whether the abort has succeed.
	 * @since  XXX
	 */
	public function abortUpload($bucket, $objectKey, $uploadId) {
		try {
			\Yii::trace('Trace: '.__CLASS__.'::'.__FUNCTION__.'()', __METHOD__);
			
			$ret = false;
			$request = $this->createRequest('DELETE', $bucket, $objectKey, array(), array('uploadId' => $uploadId));
			
			$response = $request->execute();
			if ($response->getStatus() === 204) {
				\Yii::trace('Abort upload succeed', __METHOD__);
				$ret = true;
			} else {
				throw new RiakException($response->getData(), $response->getStatus());
			}
			return $ret;
		} catch (\Exception $e) {
			return $this->handleException($e);
		}
	}	
	
	/**
	 * This function list all active uploads (all that are not completed/aborted) for the given bucket ($bucket).
	 * Returns an array with this format :
	 * array(
	 *    array(
	 *    	'key' => 'key of the object',
	 *      'uploadId' => 'The uploadId',
	 *     	'date' => 'Begining of the upload',
	 *    )
	 * );
	 * 
	 * @param string $bucket The bucket name.
	 * 
	 * @return array|false Array of active uploads or false if an error occurred.
	 * @since  XXX
	 */
	public function listMultiPartUploads($bucket) {
		try {
			\Yii::trace('Trace: '.__CLASS__.'::'.__FUNCTION__.'()', __METHOD__);
			
			$request = $this->createRequest('GET', $bucket, '', array(), array('uploads' => ''));
			
			$response = $request->execute();

			if ($response->getStatus() === 200) {
				$multiParts = array();
				
				$doc = new \DOMDocument('1.0', 'UTF-8');
				$doc->loadXML($response->getData());
				
				$uploads = $doc->getElementsByTagName('Upload');
				
				$listUploads = array();
				foreach ($uploads as $upload) {
					$uploadId = $upload->getElementsByTagName('UploadId')->item(0)->textContent;
					$key = $upload->getElementsByTagName('Key')->item(0)->textContent;
					$date = $upload->getElementsByTagName('Initiated')->item(0)->textContent;
					$listUploads[] = array(
						'uploadId' => $uploadId,
						'key' => $key,
						'date' => $date,
					);
				}
			} else {
				throw new RiakException($response->getData(), $response->getStatus());
			}
			return $listUploads;
		} catch (\Exception $e) {
			return $this->handleException($e);
		}
	} 
	
	/**
	 * This function create a signed request.
	 * 
	 * @param string $verb       The verb to use.
	 * @param string $bucket     The bucket name to use.
	 * @param string $objectKey  The objcet key.
	 * @param array  $headers    The request headers (pair key => value).
	 * @param array  $parameters The request parameters (pair key => value).
	 * 
	 * @return \sweelix\curl\Request The signed request.
	 * @since  XXX
	 */	
	private function createRequest($verb, $bucket,$objectKey = '', $headers = array(), $parameters = array()) {
		//URL CREATE
		$baseUrl = ($this->useSsl === true ? 'https://' : 'http://').$bucket.'.'.$this->endPoint.'/';
		$tmpParam = array();
		foreach ($parameters as $key => $param) {
			$tmpParam[$key] = urlencode($param);
		}
		$request = new Request($baseUrl.$objectKey);
		$request->setUrlParameters($parameters);
		\Yii::info('Creating request '.$verb.' '.$baseUrl.$objectKey.$this->buildParams($parameters), __METHOD__);
		$request->setMethod($verb);
		
		//HEADERS GENERATION
		$headers['x-amz-date'] = gmdate('D, d M Y H:i:s O');
		$contentMd5 = isset($headers['Content-MD5']) === true && strlen($headers['Content-MD5']) > 0? $headers['Content-MD5'] : '';
		$contentType = (isset($headers['Content-Type']) === true && strlen($headers['Content-Type']) > 0) ? $headers['Content-Type'] : '';
		
		$headers['Authorization'] = $this->getSignature($verb, $bucket, $objectKey, $headers, $parameters);
		
		$request->setProxy($this->getProxyUrl());
		
		$request->setHeaders($headers);
		\Yii::info('Headers : '.var_export($headers, true), __METHOD__);
		return $request;
	}

	/**
	 * Return a valid signature for s3 api.
	 * 
	 * @param string $verb       The verb to use.
	 * @param string $bucket     The bucket name to use.
	 * @param string $objectKey  The objcet key.
	 * @param array  $headers    The request headers (pair key => value).
	 * @param array  $parameters The request parameters (pair key => value).
	 * 
	 * @return string The valid signature for S3 api.
	 * @since  XXX
	 */
	private function getSignature($verb, $bucket, $objectKey, $headers, $parameters) {
		$contentMd5 = isset($headers['Content-MD5']) === true ? $headers['Content-MD5'] : '';
		$contentType = isset($headers['Content-Type']) === true ? $headers['Content-Type'] : '';

		$amzHeaders = array();
		foreach ($headers as $key => $value) {
			$amz = strtolower(substr($key, 0, 6));
			if ($amz === 'x-amz-') {
				if (isset($amzHeaders[strtolower($key)]) === true) {
					$amzHeaders[strtolower($key)] .= ','.$value;
				} else {
					$amzHeaders[strtolower($key)] = $value;
				}
			}
		}
		ksort($amzHeaders);
		$rawAmz = array();
		foreach ($amzHeaders as $key => $value) {
			$rawAmz[] = $key.':'.$value;
		}
		
		$resource = '/'.$bucket.'/';
		if (empty($objectKey) === false) {
			$resource .= $objectKey	;
		}
		$i = 0;
		$resource .= $this->buildParams($parameters);
		
		
		$amz = trim(implode("\n", $rawAmz));
		$amz = (empty($amz) === false ? "\n".$amz : '');
		$stringToSign = $verb."\n".$contentMd5."\n".$contentType."\n".''.$amz."\n".$resource;
		\Yii::info('StringToSign : '.preg_replace("/\n/", '\n', $stringToSign), __METHOD__);
		return 'AWS '.$this->accessKey.':'.$this->getHash($stringToSign);
	}
	
	/**
	 * Return the hash of a string for the S3 API.
	 * 
	 * @param string $string The string to hash.
	 * 
	 * @return string The string hashed.
	 * @since  XXX
	 */
	private function getHash($string) {
		return base64_encode(hash_hmac('sha1', $string, $this->secretKey, true));
	}
	
	/**
	 * Build the request parameters
	 * 
	 * @param array $parameters ParameterName => parameterValue
	 * 
	 * @return string Return the Get Parameters to append to the url.
	 * @since  XXX
	 */
	private function buildParams($parameters) {
		$i = 0;
		$ret = '';
		foreach ($parameters as $key => $value) {
			$append = $key.(empty($value) === false ? '='.$value : '');
			if ($i == 0) {
				$ret .= '?'.$append;
			} else {
				$ret .= '&'.$append;
			}
			$i++;
		}
		return $ret;
	}
	
	/**
	 * Build the acl data
	 * Formatted like that :
	 * array(
	 *    'ownerId' => 'id of the object owner',
	 *    'ownerName' => 'Name of the owner',
	 *    'grants' => array(
	 *       array(
	 *          'userId' => 'Id of the user',
	 *          'username' => 'user name',
	 *          'permission' => 'Type of permission that user have on the object'
	 *       ),
	 *       //ETC...
	 *    )
	 * );
	 * 
	 * @param string $data xml acl data
	 * 
	 * @return array
	 * @since  XXX
	 */
	private function buildAcl($data) {
		$ret = array();
		$doc = new \DOMDocument('1.0', 'UTF-8');
			
		$doc->loadXML($data);
			
		$ownerId = $doc->getElementsByTagName('Owner')->item(0)->getElementsByTagName('ID')->item(0)->textContent;
		$ownerName = $doc->getElementsByTagName('Owner')->item(0)->getElementsByTagName('DisplayName')->item(0)->textContent;
		$ret['ownerId'] = $ownerId;
		$ret['ownerName'] = $ownerName;
			
		$grants = $doc->getElementsByTagName('Grant');
		$grantsArray = array();
			
		foreach ($grants as $grant) {
			$userId = $grant->getElementsByTagName('Grantee')->item(0)->getElementsByTagName('ID')->item(0)->textContent;
			$username = $grant->getElementsByTagName('Grantee')->item(0)->getElementsByTagName('DisplayName')->item(0)->textContent;
			$permission = $grant->getElementsByTagName('Permission')->item(0)->textContent;
			$grantsArray[] = array(
					'userId' => $userId,
					'username' => $username,
					'permission' => $permission,
			);
		}
		$ret['grants'] = $grantsArray;
		return $ret;
	}
	
	private function handleException(\Exception $e, $retValue = false, $method = __METHOD__) {
		if ($e instanceof RiakException) {
			if ($this->useExceptions) {
				throw $e;
			} else {
				return $retValue;
			}
		} else {
			throw $e;
		}
	}
	
	/**
	 * Get The proxy URL
	 * 
	 * @return string The proxy url
	 * @since  XXX
	 */
	public function getProxyUrl() {
		return ($this->useSsl === true ? 'https://' : 'http://').$this->proxy;
	}
	
}