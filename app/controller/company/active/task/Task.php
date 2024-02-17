<?php

namespace app\controller\company\active\task;

use app\common\repositories\active\task\TaskRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\snapshot\SnapshotRepository;
use app\controller\company\Base;
use think\App;

class Task extends Base
{
    protected $repository;

    public function __construct(App $app, TaskRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function commons(){
        $pool = $this->app->make(PoolSaleRepository::class)->search(['is_number'=>1],$this->request->companyId)->field('id,title')->select();
        $snaphot = $this->app->make(SnapshotRepository::class)->search(['is_number'=>1],$this->request->companyId)->field('id,title')->select();
        $this->assign(['pool'=>$pool,'snaphot'=>$snaphot]);
    }



    public function list()
    {

        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'] ]);
        }
        return $this->fetch('/active/task/list', [
            'addAuth' => company_auth('companyActiveTaskAdd'),
            'editAuth' => company_auth('companyActiveTaskEdit'),
            'delAuth' => company_auth('companyActiveTaskDel'),
            'infoAuth' => company_auth('companyActiveTaskLog'),
        ]);
    }

    /**
     * 添加广告
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'title' => '',
                'type' => '',
                'buy_type'=>'',
                'goods_id'=>'',
                'send_type'=>'',
                'num'=>'',
                'send_pool_id'=>'',
                'start_time'=>'',
                'end_time'=>'',
                'task_type'=>'',
                'task_num'=>'',
                'send_snaphot' => '',
            ]);
            if(!$param['title']) return $this->error('请输入标题');
            if(!$param['type']) return $this->error('请选择类型');
            if(!$param['start_time']) return $this->error('请选择开始时间');
            if(!$param['end_time']) return $this->error('请选择结束时间');
            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                if ($res) {
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            $this->commons();
            return $this->fetch('active/task/add');
        }
    }

    /**
     * 编辑广告
     */
    public function edit()
    {
        $id = $this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->getDetail($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id'=>'',
                'title' => '',
                'type' => '',
                'buy_type'=>'',
                'goods_id'=>'',
                'send_type'=>'',
                'num'=>'',
                'send_pool_id'=>'',
                'start_time'=>'',
                'end_time'=>'',
                'task_type'=>'',
                'task_num'=>'',
                'send_snaphot' => '',
            ]);
            if(!$param['title']) return $this->error('请输入标题');
            if(!$param['type']) return $this->error('请选择类型');
            if(!$param['start_time']) return $this->error('请选择开始时间');
            if(!$param['end_time']) return $this->error('请选择结束时间');
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res !== false) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络失败');
            }
        } else {
            $this->commons();
            return $this->fetch('active/task/add', [
                'info' => $info,

            ]);
        }
    }

    /**
     * 删除广告
     */
    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                admin_log(4, '删除合成 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}