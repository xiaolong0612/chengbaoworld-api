<?php

namespace app\controller\company\pool;

use app\common\repositories\pool\PoolOrderLockRepository;
use app\validate\pool\OrderLockValidate;
use app\controller\company\Base;
use think\App;

class OrderNoLock extends Base
{
    protected $repository;

    public function __construct(App $app, PoolOrderLockRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords'=>'',
                'username'=>'',
                'add_time'=>'',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'] ]);
        }
        return $this->fetch('mark/lock/list',[
            'addAuth' => company_auth('companyLockOrderAdd'),
            'addBatchAuth' => company_auth('companyLockOrderAddBatch'),
            'editAuth' => company_auth('companyLockOrderEdit'),
            'delAuth' => company_auth('companyLockOrderDel'),
        ]);
    }

    /**
     * 添加广告
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'username' => '',
                'lock_time' => '',
                'secure_time' => '',
                'status' => '',
                'remark' => '',
            ]);
            $param['status'] = 2;
            validate(OrderLockValidate::class)->scene('add')->check($param);
            $res = $this->repository->addInfo($this->request->companyId,$param);
            if ($res) {
                return $this->success('添加成功');
            } else {
                return $this->error('添加失败');
            }
        } else {
            return $this->fetch('mark/lock/add');
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
        $info = $this->repository->get($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'lock_time' => '',
                'secure_time' => '',
                'status' => '',
                'remark' => '',
            ]);
            validate(OrderLockValidate::class)->scene('edit')->check($param);
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络失败');
            }
        } else {
            return $this->fetch('mark/lock/edit', [
                'info' => $info,
            ]);
        }
    }


    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                company_user_log(4, '删除肓盒专辑 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }
}