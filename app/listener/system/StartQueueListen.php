<?php
declare (strict_types=1);

namespace app\listener\system;

use Swoole\Process;
use Swoole\Server;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * 启动队列
 */
class StartQueueListen
{
    /**
     * 事件监听处理
     *
     * @return mixed
     */
    public function handle($event)
    {
        $process = new Process(function (Process $process) {
            $process->exec((new PhpExecutableFinder)->find(false), [
                dirname(__DIR__, 3) . '/think', 'queue:listen'
            ]);
        }, false, 0, true);
        app()->make(Server::class)->addProcess($process);
    }
}
