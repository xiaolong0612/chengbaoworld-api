<?php

namespace app\controller\api\agent;

use app\common\repositories\agent\StoreManagerRepository;
use app\common\repositories\pool\PoolShopOrder;
use app\controller\api\Base;
use think\App;
use think\facade\Cache;
use think\facade\Db;

class StoreManager extends Base
{
    protected $repository;

    public function __construct(App $app, StoreManagerRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }


    // 店长详情
    public function getMyManagerInfo()
    {
        $agentId = $this->request->param('id',0);
        if($agentId <= 0) return $this->error('参数错误');
        return $this->success($this->repository->getDetail($agentId));
    }

    //添加一级店长
    public function addManager()
    {
        $data = $this->request->param();
        return $this->success($this->repository->addInfo($this->request->companyId,$data));
    }
    //添加二级店长
    public function addSecondManager()
    {
        $pid = $this->request->param('p_id',0);
        $userId = $this->request->param('user_id',0);
        if ($pid<=0)return $this->error('参数错误');
        if ($userId<=0)return $this->error('用户ID错误');
        return $this->success($this->repository->addInfo($this->request->companyId,
            ['p_id'=>$pid,'user_id'=>$userId]));
    }
    //获取成为二级店长需要购买闪卡数量
    public function getNum()
    {
        $company_code = $this->request->header('company-code');
        $card_num = web_config($company_code,'program.store_manager');
        return $this->success($card_num);
    }

    //修改店长信息
    public function editManager()
    {
        $data = $this->request->param();
        if(!$data['id']) return $this->error('参数错误');
        return $this->success($this->repository->editInfo($data));
    }

    //我的团队
    public function myTeam()
    {
        $userId = $this->request->param('user_id',0);
        if($userId <= 0) return $this->error('参数错误');
        //团队好友
        $team = $this->repository->getLevelUserId($userId);
        //我的好友
        $friend = Db::table('users_push')->where('parent_id',$userId)->column('user_id,levels');
        $friendIds = [];
        //直邀好友
        $directNum = 0;
        $indirectNum = 0;
        //间邀好友
        foreach ($friend as $value)
        {
            if ($value['levels'] == 1) $directNum++;
            if ($value['levels'] == 2) $indirectNum++;
            array_push($friendIds,$value['user_id']);
        }
        //累计产出宝石
        $totalNum = Db::table('users')->whereIn('id',array_merge($team,$friendIds))
            ->sum('food');

        //团队每日产出
        $temEverydayGem = Db::table('users_food_log')->whereIn('user_id',$team)
            ->where('add_time','>',date('Y-m-d'))
            ->where('add_time','<',date('Y-m-d 23:59:59'))
            ->sum('amount');
        //团队累计产出
        $temTotalGem = Db::table('users_food_log')->whereIn('user_id',$team)->sum('amount');
        //我的产出
        $myGem = Db::table('users_food_log')->where('user_id',$userId)
            ->where('add_time','>',date('Y-m-d'))
            ->where('add_time','<',date('Y-m-d 23:59:59'))
            ->sum('amount');

        $data = [
            'team_friends'=>count($team),//团队好友
            'my_friends'=>count($friend),//我的好友
            'total_num'=>$totalNum,//累计产出
            'tem_everyday_gem'=>$temEverydayGem,//团队每日产出
            'my_gem'=>$myGem, //我的每日产出
            'tem_total_num'=>$temTotalGem,//团队累计产出
            'team_num'=>count($team)+count($friend),//团队人数
            'directNum'=>$directNum,//直邀人数
            'indirectNum'=>$indirectNum,//间邀人数
        ];
        return $this->success($data);
    }
}