<?php

namespace app\common\model\agent;

use app\common\model\BaseModel;

class HelpStatisticalResultModel extends BaseModel
{

     public static function tablePk(): string
     {
         return 'id';
     }

    public static function tableName(): string
    {
        return 'help_statistical_result';
    }
}