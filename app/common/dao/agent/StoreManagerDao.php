<?php

namespace app\common\dao\agent;

use app\common\dao\BaseDao;
use app\common\model\agent\StoreManagerModel;

class StoreManagerDao extends BaseDao
{

    /**
     * @return StoreManagerModel
     */
    protected function getModel(): string
    {
        return StoreManagerModel::class;
    }
}