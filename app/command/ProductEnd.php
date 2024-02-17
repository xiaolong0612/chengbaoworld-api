<?php
declare (strict_types=1);

namespace app\command;

use app\common\model\users\UsersPoolModel;
use app\common\repositories\agent\AgentRepository;
use app\common\repositories\guild\GuildConfigRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersRepository;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use Workerman\Lib\Timer;

class ProductEnd extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('ProductEnd')
            ->setDescription('旷工到期');
    }

    protected function execute(Input $input, Output $output)
    {
        while (true){
        try {

            $userPool = (new UsersPoolModel());
            $total = $userPool->alias('up')
                ->where(['up.status'=>1])
                ->where('p.ageing','>',0)
                ->join('pool_sale p','p.id = up.pool_id')
                ->count('up.id');
            $limit = 100;
            /** @var MineUserRepository $mineUserRepository */
            $mineUserRepository = $this->app->make(MineUserRepository::class);
            $page = ceil($total / $limit);
            for ($i = 0; $i < $page; $i++) {
                $offset = $i * $limit;
                $list = $userPool->alias('up')
                    ->where(['up.status'=>1])
                    ->where('p.ageing','>',0)
                    ->join('pool_sale p','p.id = up.pool_id')
                    ->field('up.*,p.ageing')->limit($offset,$limit)->select();
                foreach ($list as $key => $value){
                    if($value['ageing'] > 0){
                        $end =date('Y-m-d H:i:s',strtotime('+'.$value['ageing'].' day',strtotime($value['add_time'])));
                        $is_agent = app()->make(AgentRepository::class)->search(['uuid'=>$value['uuid']])->find();
                        if(!$is_agent){
                           if($end <= date('Y-m-d H:i:s')){
                               $value->status = 10;
                               $info = $mineUserRepository->search(['uuid'=>$value['uuid'],'status'=>1,'level'=>1],$value['company_id'])->where('dispatch_count','>',0)->find();
                               if($info && $value->save()){
                                   $mineUserRepository->decField($info['id'],'dispatch_count',1);
                                   event('mine.wait', ['id'=>$info['id'],'num'=>$info['dispatch_count']]);
                               }
                           }
                       }

                    }

                }
            }
         } catch (\Exception $e) {
            dump($e);
                exception_log('旷工到期运行失败', $e);
                $output->writeln('运行失败');
            }
            sleep(3);
        }

    }
}