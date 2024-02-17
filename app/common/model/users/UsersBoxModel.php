<?php

namespace app\common\model\users;

use app\common\model\BaseModel;
use app\common\model\box\BoxSaleModel;
use app\common\model\pool\PoolFollowModel;
use app\common\model\pool\PoolSaleModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\product\ProductRepository;
use app\common\repositories\users\UsersRepository;

class UsersBoxModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'user_box';
    }


    public function box(){
        return $this->hasOne(BoxSaleModel::class,'id','box_id');
    }

    public function user(){
        return $this->hasOne(UsersModel::class,'id','uuid');
    }
    public function mark(){
        return $this->hasOne(UsersMarkModel::class,'sell_id','id');
    }

    public function getGoodsAttr($v,$data){
        switch ($data['open_type']){
            case 1:
               return app()->make(PoolSaleRepository::class)->search([],$data['company_id'])->where('id',$data['goods_id'])
                    ->field('id,title,file_id,num')
                    ->with(['cover'=>function($query){
                        $query->bind(['picture'=>'show_src']);
                    }])
                    ->find();
            case 2:
                return app()->make(ProductRepository::class)->search([],$data['company_id'])->where('id',$data['goods_id'])
                    ->field('id,title,picture')->find();
        }
    }


    public function isFollow(){
        return $this->hasMany(PoolFollowModel::class,'goods_id','box_id');
    }
}
