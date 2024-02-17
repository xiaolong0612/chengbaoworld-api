<?php

namespace app\common\dao\game;

use app\common\dao\BaseDao;
use app\common\model\game\GameModel;

class GameDao extends BaseDao
{

    protected function getModel(): string
    {
        // TODO: Implement getModel() method.
        return GameModel::class;
    }

    public function search(array $where)
    {
        return GameModel::getDB()
            ->when(isset($where['keywords']) && $where['keywords'] !== '', function ($query) use ($where) {
                $query->where('game_name', 'like', '%' . trim($where['keywords']) . '%');
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
                $query->where('status', (int)$where['status']);
            })
            ->when(isset($where['id']) && $where['id'] !== '', function ($query) use ($where) {
                $query->where('id', (int)$where['id']);
            });
    }
}