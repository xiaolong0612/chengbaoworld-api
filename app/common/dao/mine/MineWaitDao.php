<?php

namespace app\common\dao\mine;

use app\common\dao\BaseDao;
use app\common\model\mine\MineConfModel;
use app\common\model\mine\MineWaitModel;

class MineWaitDao extends BaseDao
{

    /**
     * @return MineWaitModel
     */
    protected function getModel(): string
    {
        return MineWaitModel::class;
    }

    public function search(array $where, int $companyId = null)
    {
        return MineWaitModel::getDB()
            ->when($companyId !== null, function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
            })
            ->when(isset($where['uuid']) && $where['uuid'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('uuid', $where['uuid']);
            })
            ->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where,$companyId)  {
                $query->where('status', $where['status']);
            })
          ;
    }
}
