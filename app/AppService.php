<?php
declare (strict_types = 1);

namespace app;

use app\command\BuRate;
use app\command\ChildRate;
use app\command\DayRate;
use app\command\EndLog;
use app\command\MineNode;
use app\command\ProductEnd;
use app\command\Test;
use app\command\vipRate;
use think\Service;

/**
 * 应用服务类
 */
class AppService extends Service
{
    public function register()
    {
        // 服务注册
    }
    public function boot()
    {
        // 服务启动
        $this->commands(
            [
                'ProductEnd'=>ProductEnd::class,
                'EndLog'=>EndLog::class,
                'DayRate'=>DayRate::class,
                'Test'=>Test::class,
                'MineNode'=>MineNode::class,
            ]
        );
    }
}
