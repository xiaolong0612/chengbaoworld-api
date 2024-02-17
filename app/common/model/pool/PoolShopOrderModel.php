<?php

namespace app\common\model\pool;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\model\users\UsersModel;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\pool\PoolModeRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\pool\ShopOrderListRepository;
use app\common\repositories\users\UsersMarkRepository;
use think\facade\Log;

class PoolShopOrderModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pool_shop_order';
    }

    public function goods(){
        return  $this->hasOne(PoolSaleModel::class,'id','goods_id');
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }



}
