# Aliyun OSS adapter
Aliyun oss for Laravel5, also support flysystem adapter.

## Installation

This package can be installed through Composer.
```bash
composer require orzcc/aliyun-oss
```

This service provider must be registered.
```bash
// config/app.php

'providers' => [
    '...',
    'Orzcc\AliyunOss\AliyunOssServiceProvider',
];
```

At last, you can edit the config file: config/filesystem.php.

add a disk config to the config
```bash
'oss' => [
    'driver'       => 'oss',
  	'access_id'    => 'Your oss access id',
  	'access_key' 	 => 'Your oss access key',
  	'bucket' 	     => 'Your project bucket on oss',
  	'endpoint'     => '', // 青岛节点需要指定，杭州节点不需要
    'prefix'       => ''  // Path prefix, default can keep empty
],
```

change default to oss
```bash
'default' => 'oss';
```

## Usage

You can now use Laravel5's flysystem to upload or get file/directory from oss, follow the document, http://laravel.com/docs/5.0/filesystem
