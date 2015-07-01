<?php namespace Orzcc\AliyunOss;

use Storage;
use Aliyun\OSS\OSSClient;
use Orzcc\AliyunOss\AliyunOssAdapter;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class AliyunOssServiceProvider extends ServiceProvider {

 	public function boot()
    {
        Storage::extend('oss', function($app, $config)
        {
            $ossconfig = [
                'AccessKeyId'       => $config['access_id'],
                'AccessKeySecret'   => $config['access_key']
            ];

            if (isset($config['endpoint']) && !empty($config['endpoint']))
                $ossconfig['Endpoint'] = $config['endpoint'];

            $client = OSSClient::factory($ossconfig);

            return new Filesystem(new AliyunOssAdapter($client, $config['bucket'], $config['prefix']));
        });
    }

    public function register()
    {
        //
    }
}
