<?php

namespace app\controller\api\agent;

use app\common\repositories\agent\HelpCenterRepository;
use app\controller\api\Base;
use think\App;
use think\facade\Db;

class HelpCenter extends Base
{
    protected $repository;

    public function __construct(App $app, HelpCenterRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    //帮助中心和使用技巧列表
    public function getHelpCenter(){
        $type = $this->request->param('type',1);
        if($type <= 0 || $type>2) return $this->error('参数错误');
        return $this->success($this->repository->getList($type));
    }

    //是否解决问题
    public function solve(){
        $data = $this->request->param();
        if (!$data) return $this->error('参数错误');
        return $this->success(Db::table('help_statistical_result')->insert($data));
    }



}