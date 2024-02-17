<?php

namespace app\common\repositories\users;

use app\common\dao\users\UsersPushDao;
use app\common\model\BaseModel;
use app\common\model\mine\MineUserModel;
use app\common\repositories\BaseRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\system\SystemPactRepository;
use app\common\repositories\users\UsersRepository;

/**
 * @mixin UsersPushDao
 */
class UsersPushRepository extends BaseRepository
{

    public function __construct(UsersPushDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, int $companyId = null)
    {
        $query = $this->dao->search($where,$companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with([
                'userInfo' => function ($query) {
                    $query->field('id,cert_id,mobile,nickname,add_time')
                        ->append(['zt_num'])->with([
                        'cert' => function ($query) {
                            $query->field('id,cert_status,remark,username');
                        }
                    ]);
                }
            ])
            ->field('user_id,parent_id,levels')
            ->order('user_id', 'desc')
            ->select();
        return compact('list', 'count');
    }

    public function tjr($user, $tjrId,$companyId)
    {
        $id = $user['id'];
        $this->dao->whereDelete(['user_id' => $id]);
        $tjrInfo = $this->dao->getByUserId($tjrId);
        $info = [[
            'user_id' => $id,
            'parent_id' => $tjrId,
            'levels' => 1,
            'company_id'=>$companyId,
            'user_mobile'=>$user['mobile'],
        ]];
        foreach($tjrInfo as $k => $v) {
            $info[] = [
                'user_id' => $id,
                'parent_id' => $v['parent_id'],
                'levels' => $v['levels'] + 1,
                'company_id'=>$companyId,
                'user_mobile'=>$user['mobile'],
            ];
        }

        $this->dao->insertAll($info);
    }

    /**
     * 修改用户推荐人
     *
     * @param int $id 用户ID
     * @param int $tjrId 推荐人ID
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function batchTjr($userInfo, $tjrId,$companyId)
    {
        $this->tjr($userInfo, $tjrId,$companyId);
        $list = $this->dao->search(['parent_id' => $userInfo['id']],$companyId)->select();
        foreach($list as $k => $v) {
            $this->tjr($v, $v['parent_id'],$companyId);
        }
    }

    public function getFrom(array $where, $page, $limit,int $uuid, int $companyId = null)
    {
        $query = $this->dao->search($where,$companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)->field('user_id,parent_id,levels,parent_id')
            ->with(['user'=>function($query)use($companyId){
                $query->field('id,mobile,nickname,user_code,head_file_id,cert_id,add_time,rate')->filter(function ($query)use($companyId){
                    $query['mobile'] = substr_replace($query['mobile'],'****',3,4);

                    $product = (new MineUserModel())
                        ->where(['uuid'=>$query['id']])
                        ->sum('product');

                    /** @var MineDispatchRepository $mineDispatchRepository */
                    $mineDispatchRepository = app()->make(MineDispatchRepository::class);
                    $product1 = $mineDispatchRepository->search(['uuid'=>$query['id']],$companyId)->value('product');
                    $query['total']=bcadd($product,$product1,2);
                    $query['pool'] = app()->make(UsersPoolRepository::class)->search(['uuid'=>$query['id'],'status'=>1])->count('id');
                    $parent = $this->dao->search(['levels'=>1,'user_id'=>$query['id']],$companyId)->find();
                    $query['parent'] = $query ? app()->make(UsersRepository::class)->search([],$companyId)
                        ->where('id',$parent['parent_id'])->with(['avatars'=>function($query){
                            $query->bind(['picture'=>'show_src']);
                        }])->field('nickname,id,user_code,head_file_id')->find() : null;
                    return $query;
                })
                ->with(['avatars'=>function($query){
                    $query->bind(['picture'=>'show_src']);
                }])
                ;
            }])
            ->order('user_id', 'desc')

            ->select();
        return compact('list', 'count');
    }
    public function getFriendMine($userInfo,$companyId){
        $count = $this->dao->search([])->alias('p')
            ->where(['p.parent_id'=>$userInfo['id'],'p.levels'=>1])
            // ->where('c.add_time', '>', date('Y-m-d H:i:s', strtotime('-30 day')))
            ->join('users u','u.id = p.user_id')
            ->join('users_cert c','c.id = u.cert_id')
            ->count();
        $systemPactRepository = app()->make(SystemPactRepository::class);
        $desc =  $systemPactRepository->getPactInfo(15,$companyId);

        /** @var MineDispatchRepository $mineDispatchRepository */
        $mineDispatchRepository  = app()->make(MineDispatchRepository::class);
        $info =$mineDispatchRepository->search(['uuid'=>$userInfo['id']],$companyId)->find();
        if(!$info){
            $info = $mineDispatchRepository->addInfo($companyId,['uuid'=>$userInfo['id'],'dispatch_count'=>$count]);
        }else{
            $mineDispatchRepository->editInfo($info,['dispatch_count'=>$count]);
        }
        if($count >=1){
            $rate =  get_rate1($count,$companyId,$userInfo['id'])['rate'];
        }else{
            $rate = 0;
        }

        return ['total'=>$count,'rate'=>round($rate,7),'totalProduct'=>$info['product'],'desc'=>$desc];
    }

}