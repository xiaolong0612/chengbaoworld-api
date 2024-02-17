<?php

namespace app\common\repositories\currency;

use app\common\dao\currency\EtcDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * Class EtcRepository
 * @package app\common\repositories\EtcRepository
 * @mixin EtcDao
 */
class EtcRepository extends BaseRepository
{

    public function __construct(EtcDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query
            ->with(['user'=>function($query){
                $query->bind(['mobile']);
            }])
            ->page($page, $limit)
            ->select();
        return compact('count', 'list');
    }



    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }

    public function editInfo($info, $data)
    {
        if($data['status'] == 3){
             /** @var UsersRepository  $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);
            $usersRepository->batchFoodChange($info['uuid'],4,$info['num'],['remark'=>'转换退还']);
        }
        return $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->where('id', $id)
            ->find();
        return $data;
    }

    /**
     * 删除
     */
    public function batchDelete(array $ids)
    {
        $list = $this->dao->selectWhere([
            ['id', 'in', $ids]
        ]);

        if ($list) {
            foreach ($list as $k => $v) {
                $this->dao->delete($v['id']);
            }
            return $list;
        }
        return [];
    }

    public function withdrawal($data,$userInfo,$companyId){
        $config = web_config($companyId, 'program');
        $is_open = isset($config['is_open']) ? $config['is_open'] : 2;
        if($is_open != 1) throw new ValidateException('币种转换暂未开启!');
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $verfiy = $usersRepository->passwordVerify($data['pay_password'], $userInfo['pay_password']);
        if (!$verfiy) throw new ValidateException('交易密码错误!');
        $tokens = web_config($companyId, 'site')['tokens'];
        if($userInfo['food'] < $data['num']) throw new ValidateException($tokens.'余额不足');
        return Db::transaction(function () use ($data, $userInfo, $companyId) {
                /** @var UsersRepository  $usersRepository */
                $usersRepository = app()->make(UsersRepository::class);
                $etc_rate = web_config($companyId,'program')['convert']['etc']['rate'];
                $lv = web_config($companyId,'program')['convert']['etc']['lv'];
                $rate = 0;
                if($etc_rate > 0){
                    $rate = bcmul($data['num'],$etc_rate,7);
                    $change =bcsub($userInfo['food'],$rate,7);
                }else{
                    $change =bcsub($userInfo['food'],$data['num'],7);
                }
                $usersRepository->batchFoodChange($userInfo['id'],3,'-'.$data['num'],['remark'=>'转换ETC']);
                $arr['uuid'] = $userInfo['id'];
                $arr['num'] = $data['num'];
                $arr['etc'] = bcmul($change,$lv,7);
                $arr['status'] = 1;
                $arr['rate'] = $rate;
                $arr['company_id'] = $companyId;
               $this->addInfo($companyId,$arr);
               return true;
        });
        throw new ValidateException('网络错误，提现失败！');
    }

    public function getApiList($page,$limit,$userInfo,$companyId){
        $query = $this->dao->search(['uuid'=>$userInfo['id']], $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->select();
        return compact('count', 'list');
    }

}