<?php

namespace app\controller\api\agent;

use app\common\repositories\agent\StoreManagerRepository;
use app\controller\api\Base;
use think\App;
use think\facade\Cache;

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
    public function addSecondManager(){
        $data = $this->request->param();
        if (!isset($data['p_id']) || $data['p_id']<=0)return $this->error('参数错误');
        if (!isset($data['second_user_id']) || $data['second_user_id']<=0)return $this->error('用户ID错误');
        return $this->success($this->repository->addInfo($this->request->companyId,$data));
    }

    //修改店长信息
    public function editManager()
    {
        $data = $this->request->param();
        if(!$data['id']) return $this->error('参数错误');
        return $this->success($this->repository->editInfo($data));
    }
}