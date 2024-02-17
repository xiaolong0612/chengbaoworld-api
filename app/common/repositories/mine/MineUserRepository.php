<?php

namespace app\common\repositories\mine;

use app\common\dao\mine\MineUserDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\guild\GuildConfigRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class CardPackUserRepository
 * @package app\common\repositories\pool
 * @mixin MineUserDao
 */
class MineUserRepository extends BaseRepository
{

    public function __construct(MineUserDao $dao)
    {
        $this->dao = $dao;
    }


    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['userInfo' => function ($query)
            {
                $query->field('id,mobile,nickname');
                $query->bind(['mobile', 'nickname']);
            }, 'mineInfo'])
            ->order('id', 'desc')
            ->select();
        return compact('count', 'list');
    }


    public function addInfo($companyId, $data)
    {
        return Db::transaction(function () use ($data, $companyId)
        {
            $data['company_id'] = $companyId;
            return $this->dao->create($data);
        });


    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }


    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->where('id', $id)
            ->find();
        return $data;
    }

    public function batchDelete(array $ids)
    {
        $list = $this->dao->selectWhere([
            ['id', 'in', $ids]
        ]);

        if ($list)
        {
            foreach ($list as $k => $v)
            {
                $this->dao->delete($v['id']);
            }
            return $list;
        }
        return [];
    }

    public function getApiList($data, $page, $limit, $userInfo, $companyId = null)
    {
        $where['uuid'] = $userInfo['id'];
        $where['status'] = 1;
        $query = $this->dao->search($where, $companyId);
        if ($data['type'] == 1)
        {
            $usersPoolRepository = app()->make(UsersPoolRepository::class);
            $count = $usersPoolRepository->search(['uuid' => $userInfo['id'], 'status' => 1], $companyId)->where('is_dis', 1)->count('id');
            $mineLevel = $this->dao->search(['uuid' => $userInfo['id'], 'level' => 1], $companyId)->find();
            $maxLevel = $this->dao->search([])
                ->alias('mu')
                ->join('mine m', 'm.id = mu.mine_id')
                ->where(['mu.uuid' => $userInfo['id'], 'm.is_use' => 1])
                ->where('mu.level > 1')->sum('mu.dispatch_count');
            $bianliang = $count - $maxLevel <= 0 ? 0 : $count - $maxLevel;
            if ($bianliang != $mineLevel['dispatch_count'])
            {
                $this->editInfo($mineLevel, ['dispatch_count' => $bianliang]);
            }
            $query->where(['level' => 1]);
        }
        if ($data['type'] == 2) $query->where('level', '>', 1);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field('id,mine_id,product as total,rate as product,dispatch_count,status,get_time,total as alltotal')
            ->with(['mineInfo' => function ($query)
            {
                $query->field('id,name,file_id,output,level,day_output,output')->with(['fileInfo' => function ($query)
                {
                    $query->bind(['picture' => 'show_src']);
                }]);
            }])
            ->order('id', 'desc')
            ->select();
        if (count($list) > 0)
        {
            $list = $list->toArray();
            foreach ($list as $key => $value)
            {
                $list[$key]['mineInfo']['output'] = get_rate($value['dispatch_count'], $companyId);
                $is_get = 0;
                if (!$value['get_time'] || $value['get_time'] < strtotime(date('Y-m-d 00:00:00'))) $is_get = 1;
                $list[$key]['is_get'] = $is_get;
            }
        }
        return compact('count', 'list');
    }

    public function getRank($page, $limit, $userInfo, $companyId)
    {

        $query = app()->make(UsersRepository::class)->search([], $companyId)->field('id,food as total,mobile,nickname,wechat,qq,head_file_id');
        $count = $query->count();
        $query->with(['avatars' => function ($query)
        {
            $query->bind(['picture' => 'show_src']);
        }])->filter(function ($query)
        {
            $query['mobile'] = substr_replace($query['mobile'], '****', 3, 4);
            return $query;
        });
        $list = $query->order('total desc')->limit(100)->select();
        return compact('count', 'list');

//        $query = $this->dao->search([], $companyId)->field('id,uuid,sum(product) as total')->group('uuid');
//        $count = $query->count();
//        $list = $query
//            ->page($page, $limit)
//            ->with(['userInfo' => function ($query) {
//                $query->field('id,mobile,nickname,wechat,qq,head_file_id')->with(['avatars' => function ($query) {
//                    $query->bind(['picture' => 'show_src']);
//                }]);
//                $query->bind(['mobile', 'nickname', 'wechat', 'qq', 'picture']);
//                $query->filter(function ($query) {
//                    $query['mobile'] = substr_replace($query['mobile'], '****', 3, 4);
//                    return $query;
//                });
//            }])
//            ->order('total desc')
//            ->select();
//        return compact('count', 'list');
    }

    public function dispatch($data, $userInfo, $companyId)
    {
        $info = $this->dao->search(['level' => 1, 'uuid' => $userInfo['id']], $companyId)->find();
        if (!$info) throw new ValidateException('您选择的矿场不存在');
        $count = app()->make(UsersPoolRepository::class)
            ->search(['uuid' => $userInfo['id']], $companyId)
            ->where(['is_dis' => 2, 'status' => 1])
            ->count('id');
        if ($data['num'] > $count) throw new ValidateException('数量不足');
        $res1 = Cache::store('redis')->setnx('dispatch_' . $userInfo['id'], $userInfo['id']);
        Cache::store('redis')->expire('dispatch_' . $userInfo['id'], 3);
        if (!$res1) throw new ValidateException('操作频繁!');

        return Db::transaction(function () use ($data, $info, $companyId, $userInfo)
        {
            $list = app()->make(UsersPoolRepository::class)
                ->search([], $companyId)
                ->where(['uuid' => $userInfo['id'], 'is_dis' => 2, 'status' => 1])
                ->limit($data['num'])
                ->select();
            foreach ($list as $value)
            {
                $re = app()->make(UsersPoolRepository::class)->search([])
                    ->where('id', $value['id'])
                    ->where('is_dis', 2)
                    ->update(['is_dis' => 1]);
                if ($re)
                {
                    $this->dao->incField($info['id'], 'dispatch_count', 1);
                }
            }
        });
        return true;
    }

    public function getMyWite($user, $companyId)
    {
        $list = $this->dao->search(['uuid' => $user['id']], $companyId)->select();
        $rate = 0;
        $childs = 0;
        foreach ($list as $value)
        {
            $re = $this->getWiter($value['id'], $user, $companyId);
            $rate += $re['rate'];
            $childs += $re['child'];
        }
        return ['childs'=>$childs,'rate'=>$rate];
    }

        public function getWiter($id, $user, $companyId)
        {
            $info = $this->dao->search(['uuid' => $user['id']], $companyId)->where(['id' => $id])->find();
            if (!$info) return ['rate'=>0,'child'=>0];
            /** @var UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);
            switch ($info['level']){
                case 1:
                    $rate = 0;
                    if ($info['dispatch_count'] > 0){
                        if (!$info['edit_time']){
                            $userPool = app()->make(UsersPoolRepository::class)
                                ->search(['uuid' => $user['id']], $companyId)
                                ->where(['is_dis' => 1, 'status' => 1])
                                ->order('add_time desc')
                                ->find();
                            $change_time = $userPool ? strtotime($userPool['add_time']) : strtotime(date('Y-m-d 00:00:00'));
                        } else{
                            $change_time = $info['edit_time'];
                        }
                        $product = get_rate($info['dispatch_count'], $companyId);
                        $time = time() - $change_time;
                        $rate = round((floor($time / 10) * $product['rate']), 7);
                    }else{
                        if (!$info['edit_time']){
                            $userPool = app()->make(UsersPoolRepository::class)
                                ->search(['uuid' => $user['id']], $companyId)
                                ->where(['is_dis' => 1, 'status' => 1])
                                ->order('add_time desc')
                                ->find();
                            $change_time = $userPool ? strtotime($userPool['add_time']) : time();
                            //1702 1703433600
                            // $this->editInfo($info, ['edit_time' => $change_time, 'get_time' => $change_time]);
                            $time = time() - $change_time;
                        }else{
                            $time = time() - $info['edit_time'];
                        }
                    }

                    $child = $this->getChild($time,$user,$companyId);

                    /** @var MineWaitRepository $mineWaitRepository */
                    $mineWaitRepository = app()->make(MineWaitRepository::class);
                    $money = $mineWaitRepository->search(['uuid' => $user['id'], 'status' => 2], $companyId)->sum('money');

                    if ($rate <= 0 && $money <= 0) return ['rate'=>0,'child'=>$child];;
                    if($money && $money > 0){
                        $rate = round($rate + $money, 7);
                    }
//                    $r = $usersRepository->batchFoodChange($user['id'], 4, $rate, ['remark' => '领取矿洞收益']);
//                    if ($r){
//                        $mineWaitRepository->search(['uuid' => $user['id'], 'status' => 2], $companyId)->update(['status' => 1]);
//                        $new = round($info['product'] + $rate, 7);
//                        $this->editInfo($info, ['edit_time' => time(), 'get_time' => time(), 'product' => $new]);
//                    }
                    break;
                default:
                    if ($info['status'] == 2)return ['rate'=>0,'child'=>0];
                    if (!$info['edit_time']){
                        $change_time = strtotime($info['add_time']);
                    } else{
                        $change_time = $info['edit_time'];
                    }
                    $time = time() - $change_time;
                    /** @var MineRepository $mineRepository */
                    $mineRepository = app()->make(MineRepository::class);
                    $rt = $mineRepository->search([], $companyId)->where(['id' => $info['mine_id']])->find();
                    if ($rt){
                        /** @var GuildMemberRepository $GuildMemberRepository */
                        $GuildMemberRepository = app()->make(GuildMemberRepository::class);
                        $rate = bcmul(floor($time / 10), $rt['rate'], 7);
                        $guild = $GuildMemberRepository->search(['uuid' => $user['id']], $companyId)->with(['guild' => function ($query) use ($companyId)
                        {
                            $query->where(['company_id' => $companyId]);
                        }])->find();
                        if ($guild){
                            /** @var GuildConfigRepository $guildConfigRepository */
                            $guildConfigRepository = app()->make(GuildConfigRepository::class);
                            $add_rate = $guildConfigRepository->search(['level' => $guild['guild']['level']], $companyId)->value('rate');
                            $rate = bcadd($rate, bcmul($rate, $add_rate, 7), 7);
                        }
                        $new = $info['product'] + $rate;
                        if ($new >= $info['total']) $rate = $info['total'] - $info['product'];
                        $child = 0;
//                        $r = $usersRepository->batchFoodChange($user['id'], 4, $rate, ['remark' => '矿场挖矿']);
//                        if ($r){
//                            $status = 1;
//                            if ($new >= $info['total']) $status = 2;
//                            $this->editInfo($info, ['edit_time' => time(), 'get_time' => time(), 'product' => $new, 'status' => $status]);
//                        }
                    }
                    break;
            }
            return ['rate'=>$rate,'child'=>$child];
        }

    public function getChild($time,$user,$companyId){

        /** @var UsersPushRepository $usersPushRepository */
        $usersPushRepository = app()->make(UsersPushRepository::class);

        /** @var UsersPushRepository MineRepository */
        $mineRepository = app()->make(MineRepository::class);

        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);

        $praentList = $usersPushRepository->search(['parent_id'=>$user['id']],$companyId)->whereIn('levels',[1,2,3])->order('levels asc')->select();
        $child_of = 0;

        foreach ($praentList as $item){
            $mineUser = $this->dao->search(['uuid' => $item['user_id']], $companyId)->select();
            foreach ($mineUser as  $childs){
                switch ($childs['level']){
                    case 1:
                        $num = $childs['dispatch_count'] > 7 ? 7 : $childs['dispatch_count'];

                        $nodeP = get_rate($num, $companyId)['rate'];
                        $node_rate = round((floor($time / 10) * $nodeP), 7);
                        if($item['levels'] == 1){
                            $node_xia = web_config($companyId, 'program.node.one.rate', 0);
                        }elseif($item['levels'] == 2){
                            $node_xia = web_config($companyId, 'program.node.two.rate', 0);
                        }elseif($item['levels'] == 3){
                            $node_xia = web_config($companyId, 'program.node.three.rate', 0);
                        }
                        $node_change = $node_rate * $node_xia;
//                        $usersRepository->batchFoodChange($user['id'], 4, $node_change, ['remark' => '好友挖矿', 'is_frends' => 2,'child_id'=>$item['user_id']]);
                        break;
                    default:
                        $node_change = 0;
                        if($childs['status'] == 1){
                            $mine = $mineRepository->search(['level'=>$childs['level']])->find();
                            $node_rate = round((floor($time / 10) * $childs['rate']), 7);

                            if($item['levels'] == 1){
                                $node_xia = $mine['node1'];
                            }elseif($item['levels'] == 2){
                                $node_xia = $mine['node2'];
                            }elseif($item['levels'] == 3){
                                $node_xia = $mine['node3'];
                            }
                            $node_change = $node_rate * $node_xia;
//                            $usersRepository->batchFoodChange($user['id'], 4, $node_change, ['remark' => '好友挖矿', 'is_frends' => 2,'child_id'=>$item['user_id']]);
                        }
                        break;
                }
                $child_of += $node_change;
            }
        }
        return $child_of;
    }

    public function getWiteInofo($id, $user, $companyId)
    {
        $info = $this->dao->search(['uuid' => $user['id']], $companyId)->where(['id' => $id])->find();
        if (!$info) return 0;
         
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        switch ($info['level']){
            case 1:
                $rate = 0;
               
                if ($info['dispatch_count'] > 0){
                    if (!$info['edit_time']){
                        $userPool = app()->make(UsersPoolRepository::class)
                            ->search(['uuid' => $user['id']], $companyId)
                            ->where(['is_dis' => 1, 'status' => 1])
                            ->order('add_time desc')
                            ->find();
                        $change_time = $userPool ? strtotime($userPool['add_time']) : strtotime(date('Y-m-d 00:00:00'));
                    } else{
                        $change_time = $info['edit_time'];
                    }
                    $product = get_rate($info['dispatch_count'], $companyId);
                    $time = time() - $change_time;
                    $rate = round((floor($time / 10) * $product['rate']), 7);
                }else{
                    if (!$info['edit_time']){
                        $userPool = app()->make(UsersPoolRepository::class)
                            ->search(['uuid' => $user['id']], $companyId)
                            ->where(['is_dis' => 1, 'status' => 1])
                            ->order('add_time desc')
                            ->find();
                        $change_time = $userPool ? strtotime($userPool['add_time']) : time();
                    } else{
                        $change_time = $info['edit_time'];
                    }
                   $time = time() - $change_time;
                }

                $child = $this->getWiteChild($time,$user,$companyId);
  
                /** @var MineWaitRepository $mineWaitRepository */
                $mineWaitRepository = app()->make(MineWaitRepository::class);
                $money = $mineWaitRepository->search(['uuid' => $user['id'], 'status' => 2], $companyId)->sum('money');

                if ($rate <= 0 && $money <= 0 && $child <=0) return 0;
                if($money && $money > 0){
                    $rate = round($rate + $money, 7);
                }
              
                $r = $usersRepository->batchFoodChange($user['id'], 4, $rate, ['remark' => '基础挖矿获得']);
                if ($r){
                    $mineWaitRepository->search(['uuid' => $user['id'], 'status' => 2], $companyId)->update(['status' => 1]);
                    $new = round($info['product'] + $rate, 7);
                    $this->editInfo($info, ['edit_time' => time(), 'get_time' => time(), 'product' => $new]);
                }
                return $child + $rate;
                break;
            default:
                if ($info['status'] == 2) return 0;
                if (!$info['edit_time']){
                    $change_time = strtotime($info['add_time']);
                } else{
                    $change_time = $info['edit_time'];
                }
                $time = time() - $change_time;
                /** @var MineRepository $mineRepository */
                $mineRepository = app()->make(MineRepository::class);
                $rt = $mineRepository->search([], $companyId)->where(['id' => $info['mine_id']])->find();
                if ($rt){
                    /** @var GuildMemberRepository $GuildMemberRepository */
                    $GuildMemberRepository = app()->make(GuildMemberRepository::class);
                    $rate = bcmul(floor($time / 10), $rt['rate'], 7);
                    $guild = $GuildMemberRepository->search(['uuid' => $user['id']], $companyId)->with(['guild' => function ($query) use ($companyId)
                    {
                        $query->where(['company_id' => $companyId]);
                    }])->find();
                    if ($guild){
                        /** @var GuildConfigRepository $guildConfigRepository */
                        $guildConfigRepository = app()->make(GuildConfigRepository::class);
                        $add_rate = $guildConfigRepository->search(['level' => $guild['guild']['level']], $companyId)->value('rate');
                        $rate = bcadd($rate, bcmul($rate, $add_rate, 7), 7);
                    }
                    $new = $info['product'] + $rate;
                    if ($new >= $info['total']) $rate = $info['total'] - $info['product'];
                    $r = $usersRepository->batchFoodChange($user['id'], 4, $rate, ['remark' => '宝石挖矿获得']);
                    if ($r){
                        $status = 1;
                        if ($new >= $info['total']) $status = 2;
                        $this->editInfo($info, ['edit_time' => time(), 'get_time' => time(), 'product' => $new, 'status' => $status]);
                    }
                }
                return $rate;
                break;
        }
    }

    public function getWiteChild($time,$user,$companyId){

        /** @var UsersPushRepository $usersPushRepository */
        $usersPushRepository = app()->make(UsersPushRepository::class);

        /** @var UsersPushRepository MineRepository */
        $mineRepository = app()->make(MineRepository::class);

        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);

        $praentList = $usersPushRepository->search(['parent_id'=>$user['id']],$companyId)->whereIn('levels',[1,2,3])->order('levels asc')->select();
        $child_of = 0;

        foreach ($praentList as $item){
            $mineUser = $this->dao->search(['uuid' => $item['user_id']], $companyId)->select();
            foreach ($mineUser as  $childs){
                switch ($childs['level']){
                    case 1:
                        $num = $childs['dispatch_count'] > 7 ? 7 : $childs['dispatch_count'];

                        $nodeP = get_rate($num, $companyId)['rate'];
                        $node_rate = round((floor($time / 10) * $nodeP), 7);
                        if($item['levels'] == 1){
                            $node_xia = web_config($companyId, 'program.node.one.rate', 0);
                        }elseif($item['levels'] == 2){
                            $node_xia = web_config($companyId, 'program.node.two.rate', 0);
                        }elseif($item['levels'] == 3){
                            $node_xia = web_config($companyId, 'program.node.three.rate', 0);
                        }
                        $node_change = $node_rate * $node_xia;
                        $usersRepository->batchFoodChange($user['id'], 4, $node_change, ['remark' => '好友贡献', 'is_frends' => 2,'child_id'=>$item['user_id']]);
                        break;
                    default:
                        $node_change = 0;
                        if($childs['status'] == 1){
                            $mine = $mineRepository->search(['level'=>$childs['level']])->find();
                            $node_rate = round((floor($time / 10) * $childs['rate']), 7);

                            if($item['levels'] == 1){
                                $node_xia = $mine['node1'];
                            }elseif($item['levels'] == 2){
                                $node_xia = $mine['node2'];
                            }elseif($item['levels'] == 3){
                                $node_xia = $mine['node3'];
                            }
                            $node_change = $node_rate * $node_xia;
                            $usersRepository->batchFoodChange($user['id'], 4, $node_change, ['remark' => '好友贡献', 'is_frends' => 2,'child_id'=>$item['user_id']]);
                        }
                        break;
                }
                $child_of += $node_change;
            }
        }
        return $child_of;
    }

    public function getWitePrice($user, $companyId)
    {
        $res1 = Cache::store('redis')->setnx('getWitePrice_' . $user['id'], $user['id']);
        Cache::store('redis')->expire('getWitePrice_' . $user['id'], 1);
        if (!$res1) throw new ValidateException('操作频繁!!');
        $list = $this->dao->search(['uuid' => $user['id']], $companyId)->select();
        $num = 0;
        foreach ($list as $value){
            if ($value['get_time'] >= strtotime(date('Y-m-d 00:00:00'))) throw new ValidateException('今日已领取');
            $num += $this->getWiteInofo($value['id'], $user, $companyId);
        }
        return $num <= 0 ? '0.00' : $num;
    }

}