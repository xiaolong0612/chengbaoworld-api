<?php

namespace app\common\repositories\agent;

use app\common\model\agent\HelpCenterModel;
use app\common\repositories\BaseRepository;

class HelpCenterRepository extends BaseRepository
{
    public function __construct(HelpCenterModel $dao)
    {
        $this->dao = $dao;
    }

    //获取数据列表
    public function getList($type){
        return $this->dao->where('type',$type)->select();
    }
}