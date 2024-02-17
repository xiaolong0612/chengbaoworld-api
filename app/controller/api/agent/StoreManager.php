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

    public function addManager()
    {
        $data = $this->request->param();
        return $this->success($this->repository->addInfo($this->request->companyId,$data));
    }
}