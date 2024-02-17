<?php
declare (strict_types=1);

namespace app\command;

use app\common\repositories\guild\GuildConfigRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\users\UsersBalanceLogRepository;
use app\common\repositories\users\UsersFoodLogRepository;
use app\common\repositories\users\UsersFoodTimeRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersRepository;
use http\Client;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use Workerman\Lib\Timer;

class EndLog extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('EndLog')
            ->setDescription('产出记录结算');
    }

    protected function execute(Input $input, Output $output)
    {

        /** @var UsersFoodLogRepository $usersFoodLogRepository */
        $usersFoodLogRepository = $this->app->make(UsersFoodLogRepository::class);
        /** @var MineUserRepository $mineUserRepository */
        $mineUserRepository = $this->app->make(MineUserRepository::class);
        $uuids = $mineUserRepository->search(['status'=>1])->group('uuid')->column('uuid');
        /** @var UsersRepository $usersRepository */
        $usersRepository = $this->app->make(UsersRepository::class);
        foreach ($uuids as $v){
            $re =  $this->httpsPost('http://127.0.0.1:8010/zyss/getRedisLog',['Uuid'=>$v,'type'=>1]);
            if($re && $re['price'] > 0){
                $user = $usersRepository->search([])->where(['id'=>$v])->find();
                switch ($re['type']){
                    case 1:
                        $remark = '挖矿收入';
                        break;
                    case 2:
                        $remark = '好友挖矿';
                        break;
                }
                $usersFoodLogRepository->addLog($v, $re['price'], 4, array_merge(['remark'=>$remark], [
                    'before_change' => $user['food'],
                    'after_change' => round($user['food'] + $re['price'],7),
                    'is_frends' => $re['type'],
                    'company_id' => $user['company_id'],
                ]));
            }
        }

        /** @var MineDispatchRepository $mineDispatchRepository */
        $mineDispatchRepository = $this->app->make(MineDispatchRepository::class);
        $uuids1 = $mineDispatchRepository->search([])->group('uuid')->column('uuid');
        /** @var UsersRepository $usersRepository */
        $usersRepository = $this->app->make(UsersRepository::class);
        foreach ($uuids1 as $v1){
            $re1 =  $this->httpsPost('http://127.0.0.1:8010/zyss/getRedisLog',['Uuid'=>$v1,'type'=>2]);
            if($re1 && $re1['price'] > 0){
                    $user = $usersRepository->search([])->where(['id'=>$v1])->find();
                switch ($re1['type']){
                    case 1:
                        $remark = '挖矿收入';
                        break;
                    case 2:
                        $remark = '好友挖矿';
                        break;
                }
                $usersFoodLogRepository->addLog($v1, $re1['price'], 4, array_merge(['remark'=>$remark], [
                    'before_change' => $user['food'],
                    'after_change' => round($user['food'] + $re1['price'],7),
                    'is_frends' => $re['type'],
                    'company_id' => $user['company_id'],
                ]));
            }

        }

    }

    public  function httpsPost($url, $data)
    {
        $curl = curl_init(); //初始化curl
        curl_setopt($curl, CURLOPT_URL, $url); //抓取指定网页
        curl_setopt($curl, CURLOPT_HEADER, 0); //设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_POST, 1); //post提交方式?
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($curl);
        curl_close($curl);
        return json_decode($data, true);
    }

}