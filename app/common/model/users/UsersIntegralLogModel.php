<?php

namespace app\common\model\users;

use app\common\model\BaseModel;

class UsersIntegralLogModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'users_integral_log';
    }

    public function userInfo()
    {
        return $this->hasOne(UsersModel::class, 'id', 'user_id');
    }

}
