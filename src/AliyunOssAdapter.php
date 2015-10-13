<?php namespace Orzcc\AliyunOss;

use Aliyun\OSS\OSSClient;
use Aliyun\OSS\Models\OSSObject;
use Aliyun\OSS\Exceptions\OSSException;
use Aliyun\Common\Exceptions\ClientException;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use League\Flysystem\Util;

class AliyunOssAdapter extends AbstractAdapter
{
    /**
     * @var array
     */
    protected static $metaOptions = [
        'CacheControl',
        'Expires',
        'UserMetadata',
        'ContentType',
        'ContentLanguage',
        'ContentEncoding'
    ];

    /**
     * @var string bucket name
     */
    protected $bucket;

    /**
     * @var OSSClient client
     */
    protected $client;

    /**
     * Constructor.
     *
     * @param OSSClient     $client
     * @param string        $bucket
     * @param string        $prefix
     */
    public function __construct(OSSClient $client, $bucket, $prefix = null) {
        $this->client  = $client;
        $this->bucket  = $bucket;
        $this->setPathPrefix($prefix);
    }

    /**
     * Get the OSSClient bucket.
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Get the OSSClient instance.
     *
     * @return OSSClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Get an object.
     *
     * @param string $path
     *
     * @return OSSObject
     */
    protected function getObject($path)
    {
        try {
            $options = $this->getOptions($path);
            return $this->client->getObject($options);
        } catch (OSSException $e) {
            return false;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        $object = $this->getObject($path);
        if($object) {
            return $this->normalizeObject($object);
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        $options = $this->getOptions(
            $path,
            [
                'Content'       => $contents,
                'ContentType'   => Util::guessMimeType($path, $contents),
                'ContentLength' => Util::contentSize($contents),
            ],
            $config
        );

        try {
            if($this->client->putObject($options) === false) {
                return false;
            }
            return true;
        } catch (OSSException $e) {
            return false;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        $options = $this->getOptions(
            $path,
            [
                'Content'       => $resource,
                'ContentType'   => Util::guessMimeType($path, $resource),
                'ContentLength' => Util::getStreamSize($resource),
            ],
            $config
        );

        try {
            if($this->client->putObject($options) === false) {
                return false;
            }
            return true;
        } catch (OSSException $e) {
            return false;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        $object = $this->getObject($path);
        if($object) {
            $data = $this->normalizeObject($object);
        } else {
            return false;
        }
        $data['contents'] = (string) $object->__toString();
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        $object = $this->getObject($path);
        if($object) {
            $data = $this->normalizeObject($object);
        } else {
            return false;
        }
        $data['stream'] = $object->__toString();
        $object->__destruct();
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        $options = [
            'SourceBucket'  => $this->bucket,
            'SourceKey'     => $this->applyPathPrefix($path),
            'DestBucket'    => $this->bucket,
            'DestKey'       => $this->applyPathPrefix($newpath)
        ];

        try {
            $this->client->copyObject($options);
            $this->delete($path);
            return true;
        } catch (OSSException $e) {
            return false;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        $options = [
            'SourceBucket'  => $this->bucket,
            'SourceKey'     => $this->applyPathPrefix($path),
            'DestBucket'    => $this->bucket,
            'DestKey'       => $this->applyPathPrefix($newpath)
        ];

        try {
            $this->client->copyObject($options);
            return true;
        } catch (OSSException $e) {
            return false;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $options = $this->getOptions($path);

        try {
            $this->client->deleteObject($options);
            return ! $this->has($path);
        } catch (OSSException $e) {
            return false;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($path)
    {
        // The V2 SDK can't support delete matching objects
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($path, Config $config)
    {
        $result = $this->write(rtrim($path, '/').'/', '', $config);
        if (! $result) {
            return false;
        }
        return ['path' => $path, 'type' => 'dir'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        $object = $this->getObject($path);
        return $object->getMetadata();
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        $object = $this->getObject($path);
        return $object->getContentType();
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        $object = $this->getObject($path);
        //return $this->getContentLength($object);
        return ['size' => $object->getContentLength()];
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        $object = $this->getObject($path);
        return $this->getLastModified($object)->getTimestamp();
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        // The V2 SDK can't support objects' ACL
        if($this->has($path)) {
            return 'public';
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($path, $visibility)
    {
        // The V2 SDK can't support objects' ACL
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        $response = [];
        $marker = null;
        $location = $this->applyPathPrefix($directory);
        try {
            while(true) {
                $objectList = $this->client->listObjects(['Bucket' => $this->bucket, 'Prefix' => $location, 'Marker' => $marker, 'MaxKeys' => 100]);
                $objectSummarys = $objectList->getObjectSummarys();
                if (!$objectSummarys || count($objectSummarys) === 0) {
                    break;
                }
                foreach($objectSummarys as $summary) {
                    if($summary) {
                        $object = $this->getObject($this->removePathPrefix($summary->getKey()));
                        if(!$object) {
                            continue;
                        }
                        $response[] = $object;
                        $marker = $object->getKey();
                    }
                }
            }
            return Util::emulateDirectories(array_map([$this, 'normalizeObject'], $response));
        } catch (OSSException $e) {
            return false;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * Normalize a result from OSS.
     *
     * @param OSSObject  $object
     *
     * @return array file metadata
     */
    protected function normalizeObject(OSSObject $object)
    {
        $name = $object->getKey();
        $name = $this->removePathPrefix($name);
        $mimetype = explode('; ', $object->getContentType());

        return [
            'type'      => 'file',
            'dirname'   => Util::dirname($name),
            'path'      => $name,
            'timestamp' => $object->getLastModified()->getTimestamp(),
            'mimetype'  => reset($mimetype),
            'size'      => $object->getContentLength(),
        ];
    }

    /**
     * Get options for a OSS call.
     *
     * @param string $path
     * @param array  $options
     * @param Config $config
     *
     * @return array OSS options
     */
    protected function getOptions($path, array $options = [], Config $config = null)
    {
        $options['Key']    = $this->applyPathPrefix($path);
        $options['Bucket'] = $this->bucket;
        if ($config) {
            $options = array_merge($options, $this->getOptionsFromConfig($config));
        }
        return $options;
    }

    /**
     * Retrieve options from a Config instance.
     *
     * @param Config $config
     *
     * @return array
     */
    protected function getOptionsFromConfig(Config $config)
    {
        $options = [];
        foreach (static::$metaOptions as $option) {
            if (! $config->has($option)) {
                continue;
            }
            $options[$option] = $config->get($option);
        }
        if ($mimetype = $config->get('mimetype')) {
            // For local reference
            $options['mimetype'] = $mimetype;
            // For external reference
            $options['ContentType'] = $mimetype;
        }
        return $options;
    }
}
