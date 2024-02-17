<?php

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 默认缓存驱动
    'default' => env('cache.driver', 'redis'),

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => '',
            // 缓存前缀
            'prefix'     => env('cache.prefix',''),
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        'user_api' => [
            // 驱动方式
            'type' => 'redis',
            // 缓存保存目录
            'path' => app()->getRuntimePath().'user_api/user_cache',
            // 缓存前缀
            'prefix' => env('cache.prefix','').'user',
            // 缓存有效期 0表示永久缓存
            'expire' => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize' => [],
        ],
        'redis' => [
            // 驱动方式
            'type' => 'redis',
            // 服务器地址
            'host' => env('cache.redis_host','127.0.0.1'),
            // 缓存前缀
            'prefix' => env('cache.prefix',''),
            // 缓存有效期 0表示永久缓存
            'expire' => 0,
            // 缓存标签前缀
            'tag_prefix' => env('cache.PREFIX','demo'),
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize' => [],
            'password' => env('cache.redis_password',123456),
            'select' => env('cache.redis_select',0)
        ]
        // 更多的缓存连接
    ],
];