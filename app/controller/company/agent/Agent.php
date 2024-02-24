<?php

namespace app\controller\company\agent;

use app\common\repositories\agent\AgentRepository;
use app\common\repositories\users\UsersRepository;
use think\App;
use app\controller\company\Base;
use think\facade\Db;

class Agent extends Base
{
    protected $repository;

    public function __construct(App $app, AgentRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->assign([
            'addAuth' => company_auth('companyAgentAdd'),##
            'editAuth' => company_auth('companyAgentEdit'),##
            'delAuth' => company_auth('companyAgentDel'),##
            'statusAuth' => company_auth('companyAgentStatus'),##
        ]);
    }

    protected function commonParams()
    {
        /**
         * @var UsersRepository $usersRepository
         */
        $usersRepository = app()->make(UsersRepository::class);
        $userList = $usersRepository->search([],$this->request->companyId)
            ->field('id,mobile,nickname')->select();
        $this->assign([
            'userList' => $userList,
        ]);
    }

    public function list()
    {
        if($this->request->isAjax()) {
            $where = $this->request ->param([
                'keywords' => ''
            ]);
            [$page,$limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit, $this->request->companyId);
            
            return json()->data(['code' => 0,'data' => $data['list'],'count' => $data['count']]);
        }
        return $this->fetch('agent/agent/list');
    }

    /**
     * 添加常见问题
     */
    public function add()
    {
        if($this->request->isPost()){
            $param = $this->request->param([
                'uuid' => '',
                'wechat' => '',
                'qq' => '',
                'address' => '',
                'lng' => '',
                'lat' => '',
                'level' => '',
                'rate'=>''
            ]);

            try {
                // 平台质押宝石数
                $frozenNum = web_config($this->request->companyId,'program')['mine']['tokens']['frozen_num'];
                $userInfo = Db::name('users')->where('id',$param['uuid'])->find();
                if($userInfo['food'] < $frozenNum) {
                    return $this->error('店长质押宝石数不足');
                }

                // 修改用户质押宝石数
                $upData = [
                    'frozen_food' => $frozenNum,
                    'food' => bcsub($userInfo['food'],$frozenNum,7),
                ];

                Db::name('users')->where('id',$param['uuid'])->update($upData);

                $res = $this->repository->addInfo($this->request->companyId,$param);
                Db::table('store_manager')->create($param);
                company_user_log(4, '添加店长', $param);
                if($res) {
                    return $this->success('添加成功');
                } else{
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络失败');
            }
        } else {
            $this->commonParams();
            return $this->fetch('agent/agent/add');
        }
    }

    /**
     * 编辑
     */

    public function edit()
    {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->getDetail($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        $info['rate']=Db::name('users')->where('id',$info['uuid'])->value('rate');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'uuid' => '',
                'wechat' => '',
                'qq' => '',
                'address' => '',
                'lng' => '',
                'lat' => '',
                'level' => '',
            ]);
            
            $rate=input('post.rate');
            // Db::name('users')->where('id',$info['uuid'])->update(['rate'=>$rate]);
            try {
                $res = $this->repository->editInfo($id, $param,$info);
                if ($res) {
                    company_user_log(4, '编辑代理 id:' . $info['id'], $param);
                    Db::name('users')->where('id',$info['uuid'])->update(['rate'=>$rate]);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            $this->commonParams();
            return $this->fetch('agent/agent/edit', [
                'info' => $info,
            ]);
        }
    }

    /**
     * 删除常见问题
     */
    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                company_user_log(4, '删除常见问题 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

    /**
     * 常见问题显示开关
     */
    public function switchStatus()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status', 2) == 1 ? 1 : 2;
        $res = $this->repository->editInfo($id, ['status'=>$status]);
        if ($res) {
            company_user_log(4, '修改代理状态 id:' . $id, [
                'status' => $status
            ]);
            return $this->success('修改成功');
        } else {
            return $this->error('修改失败');
        }
    }

}