<?php

namespace app\common\repositories\givLog;

use app\common\dao\givLog\GivLogDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\mine\MineUserRepository;

/**
 * Class GivLogRepository
 * @package app\common\repositories\givLog
 * @mixin GivLogDao
 */
class GivLogRepository extends BaseRepository
{

    public function __construct(GivLogDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->select();
        return compact('count', 'list');
    }



    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        $data['add_time'] = date('Y-m-d H:i:s');
        return $this->dao->create($data);
    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->where('id', $id)
            ->find();

        return $data;
    }

    /**
     * 删除
     */
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


    public function getApiList(array $where,$type, $page, $limit, $companyId = null,int $uuid)
    {
        switch ($type){
            case 1:

                $where['to_uuid'] = $uuid;
                $with = ['sendUser'=>function($query){
                    $query->field('id,nickname,head_file_id,user_code');
                }];
                break;
            case 2:
                $with = ['getUser'=>function($query){
                    $query->field('id,nickname,head_file_id,user_code');
                }];
                $where['uuid'] = $uuid;
                break;
        }
        $query = $this->dao->search($where, $companyId)->field('*,count(id) as num');
        $count = $query->count();
        $query->withAttr('goods')->append(['goods']);
        $query->with($with);
        $list = $query->group('order_no')->page($page, $limit)->order('id desc')
            ->select();
        api_user_log($uuid,3,$companyId,'转赠记录查看');
        return compact('count', 'list');
    }

    public function getTransferEnd($data,$user,$companyId = null){
        $info = $this->dao->search(['uuid'=>$user['id'],'order_no'=>$data['order_no']],$companyId)->find();
        if($info){
            /** @var MineUserRepository $mineUserRepository */
            $mineUserRepository =  app()->make(MineUserRepository::class);
            $mineUser = $mineUserRepository->search(['uuid'=>$user['id'],'level'=>1],$companyId)->find();
            if(!$mineUser) $mineUserRepository->editInfo($mineUser,['edit_time'=>time()]);
        }
        return $info;
    }

}