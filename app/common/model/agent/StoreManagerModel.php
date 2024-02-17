<?php

namespace app\common\model\agent;

use app\common\model\BaseModel;
use app\common\model\users\UsersModel;

class StoreManagerModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'store_manager';
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','user_id');
    }

}
