<?php

namespace app\common\repositories\agent;

use app\common\model\agent\StoreManagerModel;
use think\facade\Db;
use app\common\repositories\BaseRepository;

/**
 * Class AgentRepository
 */
class StoreManagerRepository extends BaseRepository
{

    public function __construct(StoreManagerModel $dao)
    {
        $this->dao = $dao;
    }

    public function addInfo($companyId, $data)
    {
        return Db::transaction(function () use ($data, $companyId) {
            $data['company_id'] = $companyId;
            $data['status'] = 1;
            $info = $this->dao->create($data);
            return $info;
        });
    }

    public function editInfo($id, array $data)
    {
        return Db::transaction(function () use ($data, $id) {
            $res = $this->dao->update($id, $data);
            return ($res);
        });
    }

    public function getDetail($id)
    {
        $data = $this->dao
            ->where('id', $id)
            ->with(['user' => function ($query) {
            }])
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

    public function getApiList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->with(['user' => function ($query) {
            $with = [
                'avatars' => function ($query) {
                    $query->bind(['avatar' => 'show_src']);
                }
            ];
            $query->field('id,head_file_id,nickname')->with($with);
        }])
            ->field("*, (6378.138 * 2 * asin(sqrt(pow(sin((lat * pi() / 180 - {$where['lat']} * pi() / 180) / 2),2) + cos(lat * pi() / 180) * cos({$where['lat']} * pi() / 180) * pow(sin((lng * pi() / 180 - {$where['lng']} * pi() / 180) / 2),2))) * 1000) as distance")
            ->page($page, $limit)
            ->order("distance asc")
            ->page($page, $limit)
            ->select();
        return compact('count', 'list');
    }

}