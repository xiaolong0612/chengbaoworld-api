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
        $data = $this->request->param();
        $company_code = $this->request->header('company-code');
        $card_num = web_config($company_code,'program.store_manager');
        if (!isset($data['p_id']) || $data['p_id']<=0)return $this->error('参数错误');
        if (!isset($data['user_id']) || $data['user_id']<=0)return $this->error('用户ID错误');
        $num = Db::table('pool_shop_order')->where('uuid',$data['user_id'])->sum('num');
        if ($num<$card_num['card_num'])return $this->error('不满足成为二级店长的条件');
        return $this->success($this->repository->addInfo($this->request->companyId,$data));
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
}