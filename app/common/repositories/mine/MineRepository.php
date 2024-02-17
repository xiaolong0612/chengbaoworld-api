<?php

namespace app\common\repositories\mine;

use app\common\dao\mine\MineDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class MineRepository
 * @package app\common\repositories\MineRepository
 * @mixin MineDao
 */
class MineRepository extends BaseRepository
{

    public function __construct(MineDao $dao)
    {
        $this->dao = $dao;
    }


    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['fileInfo'=>function($query){
                $query->bind(['picture' => 'show_src']);
            }])
            ->order('id desc')
            ->select();
        return compact('count', 'list');
    }


    public function addInfo($companyId,$data)
    {
        if($data['cover']){
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1,0);
            if($fileInfo){
                $data['file_id'] = $fileInfo['id'];
            }
        }
        unset($data['cover']);
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }

    public function editInfo($info, $data)
    {
        if($data['cover']){
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1,0);
            if($fileInfo){
                if($fileInfo['id'] != $info['id']){
                    $data['file_id'] = $fileInfo['id'];
                }
            }
        }
        unset($data['cover']);
        return $this->dao->update($info['id'], $data);
    }


    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->where('id', $id)
            ->with(['fileInfo'=>function($query){
                $query->bind(['picture' => 'show_src']);
            }])
            ->append(['source_info'])
            ->find();
        return $data;
    }



    public function batchDelete(array $ids)
    {
        $list = $this->dao->selectWhere([
            ['id', 'in', $ids]
        ]);

        if ($list) {
            foreach ($list as $k => $v) {
                $this->dao->delete($v['id']);
            }
            return $list;
        }
        return [];
    }

    public function getAll(int $company_id = null){
        return  $this->dao->selectWhere(['company_id'=>$company_id],'id,name');
    }

    public function getCascaderData($companyId = 0,$status = '')
    {
        $list = $this->getAll($companyId,$status);
        $list = convert_arr_key($list, 'id');
        return formatCascaderData($list, 'name', 0, 'pid', 0, 1);
    }


    public function getApiList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId)->where('level','>',1);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['fileInfo'=>function($query){
                $query->bind(['picture' => 'show_src']);
            }])
            ->field('id,name,output,people,price,file_id,is_use')
            ->withAttr('people',function ($v,$data){
                switch ($data['is_use']){
                    case 1:
                        return $data['people'];
                    case 2:
                        return 0;
                }
            })
            ->order('level asc')
            ->select();
        return compact('count', 'list');
    }

    public function getApiDetail(int $id)
    {
        $query = $this->dao->search([])->where('id',$id);
        $info = $query
            ->with(['fileInfo'=>function($query){
                $query->bind(['picture' => 'show_src']);
            }])
            ->field('id,name,file_id')
            ->find();
        return $info;
    }


    public function develop($data,$userInfo,$companyId){

            $res1 = Cache::store('redis')->setnx('develop_' . $userInfo['id'], $userInfo['id']);
            Cache::store('redis')->expire('develop_' . $userInfo['id'], 1);
            if (!$res1) throw new ValidateException('操作频繁!!');
            $mine = $this->dao->search(['status'=>1])->where(['id'=>$data['mine_id']])->find();
            if(!$mine) throw new ValidateException('您选择的矿场不存在');
            if($mine['level'] == 1) throw new ValidateException('当前矿场无法通过开矿获取!');
            if($userInfo['cert_id'] <= 0 ) throw new ValidateException('请先实名认证!');
            if ($userInfo['food'] < 0 || !$userInfo['food'] || $userInfo['food'] < $mine['price']){
                $tokens = web_config($companyId, 'site')['tokens'];
                throw new ValidateException('您的'.$tokens.'余额不足');
            }
            /** @var MineUserRepository $mineUserRepository */
            $mineUserRepository = app()->make(MineUserRepository::class);

            $mineUserCount = $mineUserRepository->search(['uuid'=>$userInfo['id'],'mine_id'=>$data['mine_id'],'status'=>1],$companyId)->count('id');
            if($mineUserCount >= $mine['num']) throw new ValidateException('当前矿场开启数量已达到最大');

             $mine_id = app()->make(MineRepository::class)->search(['level'=>1,'status'=>1],$companyId)->value('id');
             if(!$mine_id) throw new ValidateException('矿区配置不完善!');

            $mineUserK =  $mineUserRepository->search(['uuid'=>$userInfo['id'],'status'=>1,'level'=>1],$companyId)->find();
            if($mine['is_use'] == 1 && $mineUserK['dispatch_count'] < $mine['people']) throw new ValidateException('您的卡牌不足！');

            return Db::transaction(function () use ($data,$mine,$userInfo,$companyId,$mineUserK,$mineUserRepository,$mine_id) {
                /** @var UsersRepository $usersRepository */
                $usersRepository = app()->make(UsersRepository::class);
                $usersRepository->batchFoodChange($userInfo['id'], 3,  '-'.$mine['price'], [
                    'remark' => '开启矿场'
                ],4);
                $add['mine_id'] = $data['mine_id'];
                $add['uuid'] = $userInfo['id'];
                $add['unquide'] = $companyId.$userInfo['id'].$data['mine_id'];
                $add['total'] = $mine['output'];
                $add['level'] = $mine['level'];
                $add['rate'] = $mine['rate'];
                $add['dispatch_count'] = $mine['people'];
                $re = $mineUserRepository->addInfo($companyId,$add);
                if($mine['is_use'] == 1) {
                    $mineUserRepository->decField($mineUserK['id'],'dispatch_count',$mine['people']);
                    event('mine.wait', ['id'=>$mineUserK['id'],'num'=>$mineUserK['dispatch_count']]);

                }
//                $event['uuid'] = $userInfo['id'];
//                $event['mine_id'] = $mine_id;  //矿洞id
//                $event['companyId'] = $companyId;
//                $event['type'] = 1;
//                $event['mine'] = $mine; //要开的矿
//                $event['mine_user_id'] = $re['id']; //开出来的个人矿场id
//                \think\facade\Queue::push(\app\jobs\GrantMine::class, $event,'GrantMine');
                return $re;
            });
     }
     public function getIntroduce($id,$companyId){
        return $this->dao->search([],$companyId)->where(['id'=>$id])->field('id,content')->find();
     }
}