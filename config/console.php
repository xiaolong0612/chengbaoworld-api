<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'develop' => 'app\command\Develop',
        'test' => 'app\command\Test',
        'product' => 'app\command\Product',
        'production' => 'app\command\production',
        'level' => 'app\command\Level'
    ],
];
