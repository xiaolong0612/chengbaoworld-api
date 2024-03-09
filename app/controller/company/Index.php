<?php

namespace app\controller\company;

use app\common\repositories\company\user\CompanyUserRepository;
use app\common\repositories\mark\UsersMarkReportRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\property\bill\PropertyRoomBillRepository;
use app\common\repositories\property\PropertyRepository;
use app\common\repositories\user\UserRepository;
use app\common\repositories\users\UsersLabelRepository;
use app\common\repositories\users\UsersMarkRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersFoodLogRepository;
use app\common\repositories\users\UsersRepository;
use app\common\services\CacheService;
use think\facade\Cache;

class Index extends Base
{

    /**
     * 首页
     *
     * @return string
     * @throws \Exception
     */
    public function index()
    {
        /**
         * @var CompanyUserRepository $adminUserRepository
         */
        $adminUserRepository = app()->make(CompanyUserRepository::class);
        $adminUserInfo = $adminUserRepository->getLoginUserInfo();

        return $this->fetch('index/index', compact('adminUserInfo'));
    }

    /**
     * 获取目录
     *
     * @return \think\response\Json
     */
    public function getMenu()
    {
        /**
         * @var CompanyUserRepository $adminUserRepository
         */
        $adminUserRepository = app()->make(CompanyUserRepository::class);
        $data = $adminUserRepository->getMenus();

        return json()->data($data);
    }

    /**
     * 欢迎页
     *
     * @return string
     * @throws \Exception
     */
    public function welcome()
    {
        $tokens = web_config($this->request->companyId, 'site.tokens','代币');
        return $this->fetch('index/welcome', [
            'userNum' => 1,
            'todayUserNum' => 2,
            'todayUserOrderListNum' => 3,
            'todayUserWithdrawNum' => 4,
             'tokens' =>$tokens
        ]);
    }

    /**
     * 退出登陆
     *
     * @return \think\response\Json
     */
    public function signOut()
    {
        event('company.logout');
        return $this->success('退出成功');
    }

    /**
     * 清除 缓存
     *
     * @return \think\response\Json
     */
    public function clearCache()
    {
        CacheService::init($this->request->companyId)->clear();
        return $this->success('缓存清除成功');
    }



    public function statistics()
    {

        $data = Cache::remember('company_dashboard_statistics_' . $this->request->companyId, function () {
            /** @var  UsersPoolRepository $usersPoolRepository */
            $usersPoolRepository = app()->make(UsersPoolRepository::class);
            /** @var UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);
            /** @var PoolSaleRepository $poolSaleRepository */
            $poolSaleRepository = app()->make(PoolSaleRepository::class);
            
            $usersFoodLogRepository = app()->make(UsersFoodLogRepository::class);
            
            $startDate = time(); // 本周开始日期
            // $endDate = date('Y-m-t', strtotime($startDate)); // 获取当前月份最后一天的日期
             
            // 创建一个包含从开始到结束日期之间所有日期的数组
            $datesArray = [];
            $collection = [];// 卡牌发售量
            $user_add = [];// 每日用户新增
            $wk_jc = [];// 挖矿
            $wk_bs = [];// 挖矿
            $dtstr_sr = [];// 每日大逃杀投入
            $dtsdb_sr = [];// 每日大逃杀躲避杀手
            $user_friend = [];// 每日好友贡献
            $user_win = [];// 每日用户盈亏
            $user_trx = [];// 每日手续费
            for ($i=1; $i<=7; $i++){
                $day = date('Y-m-d' ,strtotime( '+' . ($i-7) .' days', $startDate));
                
                array_push($datesArray, date('m-d', strtotime($day)));
                
                array_push($collection, $usersPoolRepository->search(['status'=>1], $this->request->companyId)->whereDay("add_time", $day)->count());
                array_push($user_add, $usersRepository->search([], $this->request->companyId)->whereDay("add_time", $day)->count());
                
                $today_jc = $usersFoodLogRepository->search(['log_type' => 4, 'remark' => '基础挖矿获得'], $this->request->companyId)->whereDay("add_time", $day)->sum('amount');
                $today_bs = $usersFoodLogRepository->search(['log_type' => 4, 'remark' => '宝石挖矿获得'], $this->request->companyId)->whereDay("add_time", $day)->sum('amount');
                // $today_friend = $usersFoodLogRepository->search(['remark' => '好友贡献'], $this->request->companyId)->whereDay("add_time", $day)->sum('amount');
                $today_dtstr = $usersFoodLogRepository->search(['log_type' => 3, 'remark' => '大逃杀投入'], $this->request->companyId)->whereDay("add_time", $day)->sum('amount');
                $today_dtsdb = $usersFoodLogRepository->search(['log_type' => 4, 'remark' => '成功躲避杀手'], $this->request->companyId)->whereDay("add_time", $day)->sum('amount');
                $today_trx = $usersFoodLogRepository->search(['log_type' => 5, 'remark' => '转赠手续费'], $this->request->companyId)->whereDay("add_time", $day)->sum('amount');
                array_push($wk_jc, $today_jc);
                array_push($wk_bs, $today_bs);
                array_push($dtstr_sr, $today_dtstr);
                array_push($dtsdb_sr, []);
                array_push($user_friend, []);
                array_push($user_win, $today_dtstr - $today_dtsdb);
                array_push($user_trx, abs($today_trx));
            }
            return[
                'time_area' => $datesArray,
                'echart' => [
                    'collection' => $collection,
                    'user_add' => $user_add,
                    'wk_jc' => $wk_jc,
                    'wk_bs' => $wk_bs,
                    'dtstr_sr' => $dtstr_sr,
                    'dtsdb_sr' => $dtsdb_sr,
                    'user_friend' => $user_friend,
                    'user_win' => $user_win,
                    'user_trx' => $user_trx
                ],
                'collection_data' => [
                    'collection_total' => $poolSaleRepository->search([], $this->request->companyId)->sum('num'), // 卡牌数
                    'collection_num' => $usersPoolRepository->search(['status'=>1], $this->request->companyId)->count(), // 卡牌数
                ],
                'user_data' => [
                    'food_num' => $usersRepository->search([], $this->request->companyId)->sum('food'),
                    // 'today_user' => $usersRepository->search([], $this->request->companyId)
                    //     ->whereTime('add_time', 'today')
                    //     ->count('id'),
                    'total_user' => $usersRepository->search([], $this->request->companyId)
                        ->count('id'),
                    'is_cert_user' => $usersRepository->search([], $this->request->companyId)
                        ->where('cert_id > 0')
                        ->count('id'),
                    // 'no_cert_user' => $usersRepository->search([], $this->request->companyId)
                    //     ->where('cert_id',' 0')
                    //     ->count('id'),
                ]
            ];
        }, 60 * 60 * 2);
        return json()->data([
            'code' => 0,
            'data' => $data
        ]);
    }


}