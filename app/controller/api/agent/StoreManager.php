<?php

namespace app\controller\api\agent;

use app\common\repositories\agent\StoreManagerRepository;
use app\controller\api\Base;
use think\App;
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

        //我的好友
        $friend = Db::table('users_push')->where('parent_id',$userId)
            ->column('user_id,levels');
        $friendIds = [];
        //直邀好友
        $directNum = 0;
        //间邀好友
        $indirectNum = 0;
        foreach ($friend as $value)
        {
            if ($value['levels'] == 1) $directNum++;
            if ($value['levels'] == 2) $indirectNum++;
            array_push($friendIds,$value['user_id']);
        }
        //累计产出宝石 所有好友矿产总和
        $totalNum = Db::table('mine_user')->whereIn('uuid',$friendIds)
            ->sum('product');
        //我的矿场  我自己的矿场的总和
        $myGem = Db::table('mine_user')->whereIn('uuid',$userId)
            ->sum('product');
        //我的分佣  前一天的分佣
        $distribution = Db::table('users_distribution_log')
            ->where('add_time','>',date('Y-m-d 00:00:00', strtotime('-1 day')))
            ->where('add_time','<',date('Y-m-d 23:59:59', strtotime('-1 day')))
            ->sum('amount');
//            ->select();
        $balance = Db::table('users_balance_log')
            ->where('add_time','>',date('Y-m-d 00:00:00', strtotime('-1 day')))
            ->where('add_time','<',date('Y-m-d 23:59:59', strtotime('-1 day')))
            ->sum('amount');

        //团队累计产出
        $temTotalGem = Db::table('mine_user')
            ->whereIn('uuid',array_push($friendIds,$userId))
            ->sum('product');

        $data = [
            'team_friends'=>(string)count($friend),//团队好友:
            'my_friends'=>(string)$directNum,//我的好友
            'total_num'=>(string)$totalNum,//累计产出
            'tem_everyday_gem'=>(string)($distribution+$balance),//我的分佣
            'my_gem'=>(string)$myGem, //我的矿场
            'tem_total_num'=>(string)$temTotalGem,//团队累计产出
            'team_num'=>(string)count($friendIds),//团队人数
            'directNum'=>(string)$directNum,//直邀人数
            'indirectNum'=>(string)$indirectNum,//间邀人数
        ];
        return $this->success($data);
    }
}