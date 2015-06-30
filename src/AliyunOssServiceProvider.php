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
            $client = OSSClient::factory(array(
                'AccessKeyId'       => $config['access_id'],
                'AccessKeySecret'   => $config['access_key'],
                'Endpoint'          => $config['endpoint']
            ));

            return new Filesystem(new AliyunOssAdapter($client, $config['bucket'], $config['prefix']));
        });
    }

    public function register()
    {
        //
    }
}