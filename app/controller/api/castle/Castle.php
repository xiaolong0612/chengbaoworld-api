<?php

namespace app\controller\api\castle;

use app\common\dao\users\UsersFoodLogDao;
use app\common\dao\users\UsersPushDao;
use app\common\model\users\UsersModel;
use app\common\repositories\users\UsersIntegralLogRepository;
use app\common\repositories\users\UsersRepository;
use app\controller\api\Base;
use think\facade\Db;

class Castle extends Base
{

    //日产宝石明细
    public function nissanGemDetail(UsersPushDao $userPush,UsersFoodLogDao $userFoodLog)
    {
        $uid = $this->request->param('user_id',0);
        if ( $uid <= 0 ) $this->error('参数错误');
        $parentId = $userPush->getParentId($uid);
        //游戏日产宝石

        //团队日产
        $userData = Db::table('store_manager')->where('p_id',$uid)->column('user_id');
        $team = $userFoodLog->searchFood($userData);
        //好友已产宝石
        $friend = $userFoodLog->searchFood($parentId);
        //我的日产
        $own = $userFoodLog->searchFood($uid);
        return $this->success(['team'=>$team,'friend'=>$friend,'total_game'=>($own+$team+$friend)]);
    }

    //我的好友
    public function myFriend(UsersPushDao $users)
    {
        $uid = $this->request->param('user_id',0);
        if ( $uid <= 0 ) $this->error('参数错误');
        return $this->success(['count'=>$users->getMyFriendCount($uid)]);
    }

    //基础矿产（我的工人）
    public function basicsMyWorker()
    {
        $uid = $this->request->param('user_id',0);
        if ( $uid <= 0 ) $this->error('参数错误');
        $data = UsersModel::where('id',$uid)->withCount(['pool'=>function($query){
            $query->where(['status'=>1]);
        }])->find();
        return $this->success(['pool_count'=>$data['pool_count']]);
    }

}