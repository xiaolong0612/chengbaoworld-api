<?php

namespace app\common\repositories\mine;

use app\common\dao\mine\MineUserDispatchDao;
use app\common\repositories\system\SystemPactRepository;
use think\facade\Db;
use think\exception\ValidateException;
use app\common\repositories\BaseRepository;
use app\common\repositories\users\UsersRepository;

/**
 * Class MineUserDispatchRepository
 * @package app\common\repositories\MineUserDispatchRepository
 * @mixin MineUserDispatchDao
 */
class MineUserDispatchRepository extends BaseRepository
{

    public function __construct(MineUserDispatchDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['userInfo'=>function($query){
                $query->field('id,mobile,nickname');
                $query->bind(['mobile','nickname']);
            },'cardPacknfo'=>function($query){
                $query->field('id,file_id,name');
            }])
            ->order('id', 'desc')
            ->select();
        return compact('count', 'list');
    }


    public function addInfo($companyId,$data)
    {
        return Db::transaction(function () use ($data,$companyId) {
            $data['company_id'] = $companyId;
            return $this->dao->create($data);
        });


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





}