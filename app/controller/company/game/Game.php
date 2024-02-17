<?php

namespace app\controller\company\game;
use app\common\repositories\game\GameRepository;
use app\controller\company\Base;
use app\validate\game\GameValidate;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
class Game extends Base
{
    public function __construct(App $app, GameRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->assign([
            'addAuth' => company_auth('companyGameAdd'),
            'editAuth' => company_auth('companyGameEdit'),
            'delAuth' => company_auth('companyGameDel'),
            'companyUsersBlack' => company_auth('companyUsersBlack'),
        ]);
    }

    //列表
    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => ''
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count'] ]);
        } else {
            return $this->fetch('game/list', []);
        }
    }

    //添加
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id'=> '',
                'game_name' => '',
                'gamename' => '',
                'image' => '',
                'status' => '',
                'game_url' => '',
                'game_api' => '',
                'game_key' => '',
            ]);
            $param['add_time'] = date('Y-m-d H:i:s');
            $param['edit_time'] = date('Y-m-d H:i:s');
            try {
                validate(GameValidate::class)->scene('add')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            try {
                $res = $this->repository->addInfo($param);
                if ($res) {
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        }else{
            return $this->fetch('game/add');
        }
    }

    //修改
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

        if ($this->request->isPost()) {
            $param = $this->request->param([
                'game_name' => '',
                'gamename' => '',
                'image' => '',
                'status' => '',
                'game_url' => '',
                'game_api' => '',
                'game_key' => '',
            ]);
            $param['edit_time'] = date('Y-m-d H:i:s');
            validate(GameValidate::class)->scene('add')->check($param);

            try {
                $res = $this->repository->editInfo($info,$param);

                if ($res) {
                    company_user_log(3, '编辑游戏:' . $id);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                exception_log('修改失败', $e);
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('game/edit', [
                'info' => $info,
            ]);
        }
    }

    public function del()
    {
        $ids = $this->request->param('ids');
        $res = $this->repository->delGame($ids);
        if($res) {
            company_user_log(4, '删除游戏:' . implode(',', $ids));
            return $this->success('删除成功');
        } else {
            return $this->error('删除失败');
        }
    }
    public function switchStatus()
    {
        $info['id'] = $this->request->param('id');
        $status = $this->request->param('status', 2) == 1 ? 1 : 2;
        $res = $this->repository->editInfo($info, ['status'=>$status]);
        if ($res) {
            company_user_log(4, '修改游戏状态 id:' . $info['id'], [
                'status' => $status
            ]);
            return $this->success('修改成功');
        } else {
            return $this->error('修改失败');
        }
    }
}