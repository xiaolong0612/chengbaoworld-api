<?php

namespace app\common\model\agent;

use app\common\model\BaseModel;

class HelpCenterModel extends BaseModel
{
    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'help_center';
    }

    public function help(){
        return $this->hasMany(HelpStatisticalResultModel::class,'h_id','id');
    }
}