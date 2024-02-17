<?php

namespace app\common\model\pool;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\users\UsersModel;

class PoolTransferLogModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pool_transfer_log';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }

    public function getTypeNameAttr($v,$data){
        switch ($data['type']){
            case 1:
                return '铸造';
            case 2:
                return '空投';
            case 3:
                 return '抢购';
            case 4:
                return '寄售';
            case 5:
                return '市场购买';
            case 6:
                return '合成';
            case 7:
                return '开盒';
        }
    }
}
