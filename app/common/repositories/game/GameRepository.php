<?php

namespace app\common\repositories\game;

use app\common\dao\game\GameDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use think\facade\Db;

class GameRepository extends BaseRepository {
    public function __construct(GameDao $dao) {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit){
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->order('sort', 'desc')
            ->select();
        return compact('list', 'count');
    }

    public function addInfo(array $data = []) {
        return $this->dao->create($data);
    }

    public function getDetail(int $id) {
        return $this->dao->search([])
            ->where('id', $id)
            ->find();
    }

    public function editInfo($info, $data) {
        return $this->dao->update($info['id'], $data);
    }

    public function delGame($ids) {
        foreach ($ids as $k => $v) {
            $res = $this->dao->delete($v);
        }
        return $res;
    }
}