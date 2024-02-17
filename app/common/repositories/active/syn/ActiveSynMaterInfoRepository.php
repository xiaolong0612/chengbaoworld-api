<?php

namespace app\common\repositories\active\syn;

use app\common\dao\active\syn\ActiveSynMaterInfoDao;
use app\common\repositories\BaseRepository;
use think\facade\Db;

/**
 * Class ActiveSynMaterInfoRepository
 * @package app\common\repositories\active
 * @mixin ActiveSynMaterInfoDao
 */
class ActiveSynMaterInfoRepository extends BaseRepository
{

    public function __construct(ActiveSynMaterInfoDao $dao)
    {
        $this->dao = $dao;
    }


    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $query->with(['pool'=>function($query){
            $query->bind(['title']);
        }]);
        $list = $query->page($page, $limit)->order('id desc')
            ->select();
        return compact('count', 'list');
    }


    public function addInfo($companyId,$data)
    {

        return Db::transaction(function () use ($data,$companyId) {
            $data['company_id'] = $companyId;
            $data['add_time'] = date('Y-m-d H:i:s');
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