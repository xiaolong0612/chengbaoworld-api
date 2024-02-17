<?php

namespace app\common\repositories\mine;

use app\common\dao\mine\MineDao;
use app\common\dao\mine\MineWaitDao;
use app\common\repositories\BaseRepository;

/**
 * Class MineWaitRepository
 * @package app\common\repositories\MineWaitRepository
 * @mixin MineWaitDao
 */
class MineWaitRepository extends BaseRepository
{

    public function __construct(MineWaitDao $dao)
    {
        $this->dao = $dao;
    }





    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }
}