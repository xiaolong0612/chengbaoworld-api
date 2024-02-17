<?php

namespace app\common\repositories\users;

use app\common\repositories\box\BoxSaleGoodsListRepository;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\fraud\FraudRepository;
use app\common\repositories\givLog\GivLogRepository;
use app\common\repositories\order\OrderRepository;
use app\common\repositories\pool\PoolFollowRepository;
use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\pool\PoolTransferLogRepository;
use app\common\repositories\product\ProductRepository;
use app\common\repositories\users\UsersRepository;
use app\helper\SnowFlake;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use app\common\dao\users\UsersBoxDao;
use app\common\repositories\BaseRepository;
use think\facade\Log;

class UsersBoxRepository extends BaseRepository
{

    public function __construct(UsersBoxDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, int $companyId = null)
    {

        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['box' => function ($query) {
                $query->field('id,title,file_id')->with(['cover' => function ($query) {
                    $query->bind(['picture' => 'show_src']);
                }]);
            }, 'user' => function ($query) {
                $query->field('id,mobile,nickname');
                $query->bind(['mobile', 'nickname']);
            }])
            ->append(['goods'])
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }

    public function send(array $data, $user, int $companyId = null)
    {
        $res2 = Cache::store('redis')->setnx('giv_box_' . $user['id'], $user['id']);
        Cache::store('redis')->expire('giv_box_' . $user['id'], 1);
        if (!$res2) throw new ValidateException('禁止同时转增!');
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $uuid = $user['id'];
        $user = $usersRepository->search([], $companyId)
            ->where('id', $uuid)
            ->field('pay_password,cert_id')
            ->find();
        $verfiy = $usersRepository->passwordVerify($data['pay_password'], $user['pay_password']);
        if (!$verfiy) throw new ValidateException('交易密码错误!');

        if ($user['cert_id'] <= 0) throw new ValidateException('请先实名认证!');
        $getUser = $usersRepository->search(['mobile' => $data['phone']], $companyId)->field('id,mobile,cert_id')->find();
        if (!$getUser) throw new ValidateException('接收方账号不存在！');
        if ($getUser['cert_id'] == 0) throw new ValidateException('接收方未实名认证!');
        $userBox = $this->getDetail($data['id'], $companyId);
        if (!$userBox) throw new ValidateException('会员卡牌不存在!');
        $boxInfo = app()->make(BoxSaleRepository::class)->search([], $companyId)->where('id', $userBox['box_id'])->field('id,title,is_give')->find();
        if (!$boxInfo) {
            throw new ValidateException('未配置参数!');
        }
        if ($boxInfo['is_give'] != 1) throw new ValidateException('暂未开始转赠！');
        return Db::transaction(function () use ($data, $userBox, $getUser, $uuid, $companyId, $boxInfo) {
            $arr['uuid'] = $getUser['id'];
            $arr['box_id'] = $userBox['box_id'];
            $arr['add_time'] = date('Y-m-d H:i:s');
            $arr['status'] = 1;
            $arr['type'] = 3;
            $re = $this->addinfo($companyId, $arr);
            if ($re) {
                $givLogRepository = app()->make(GivLogRepository::class);
                $giv['uuid'] = $uuid;
                $giv['to_uuid'] = $getUser['id'];
                $giv['goods_id'] = $userBox['box_id'];
                $giv['buy_type'] = 2;
                $giv['sell_id'] = $userBox['id'];
                $givLogRepository->addInfo($companyId, $giv);
                $this->editInfo($userBox, ['status' => 4]);
            }
            api_user_log($uuid, 4, $companyId, '转赠盲盒:' . $boxInfo['title']);
            return true;
        });
    }

    public function getDetail(int $id, $companyId = null)
    {
        $with = [
            'box'
        ];
        $data = $this->dao->search([], $companyId)
            ->with($with)
            ->where('id', $id)
            ->find();
        return $data;
    }

    public function addInfo(int $companyId = null, array $data = [])
    {
        return Db::transaction(function () use ($data, $companyId) {
            if ($companyId) $data['company_id'] = $companyId;
            $userInfo = $this->dao->create($data);
            return $userInfo;
        });
    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    public function getApiMyList(array $where, $page, $limit, int $companyId = null, int $uuid)
    {
        $where['uuid'] = $uuid;
        $query = $this->dao->search($where, $companyId)->whereIn('status', [1, 2]);
        $list = $query->page($page, $limit)
            ->with(['box' => function ($query) {
                $query->field('id,title,file_id')->with(['cover' => function ($query) {
                    $query->field('id,show_src,width,height');
                }]);
            },
                'mark' => function ($query) {
                    $query->where(['buy_type' => 2])->whereIn('status', [1, 3])->field('id,price,buy_type,sell_id,status');
                }
            ])
            ->order('id', 'desc')->group('box_id')
            ->withAttr('mark_count', function ($v, $data) {
                $where['uuid'] = $data['uuid'];
                $where['box_id'] = $data['box_id'];
                $where['status'] = 2;
                return $this->getCount($where, $data['company_id']) ?? 0;
            })->withAttr('think_count', function ($v, $data) use ($uuid) {
                return $this->dao->search(['uuid' => $uuid, 'box_id' => $data['box_id']])->whereIn('status', [1, 2])->count('id');
            })->append(['mark_count', 'think_count'])
            ->select();
        foreach ($list as &$item)
        {
            $item['is_follow_count'] = app()->make(PoolFollowRepository::class)->search(['uuid' => $uuid], $companyId)->where('goods_id', $item['pool_id'])->count('id');
        }
        $count = $query->count();
        return compact('list', 'count');
    }

    public function getCount($where, int $companyId = null)
    {
        return $this->dao->search($where, $companyId)->count('id');
    }

    public function getApiMyListInfo(array $where, $page, $limit, int $companyId = null, int $uuid)
    {
        $where['uuid'] = $uuid;
        $query = $this->dao->search($where, $companyId)->whereIn('status', [1, 2]);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['box' => function ($query) {
                $query->field('id,title,file_id')->with(['cover' => function ($query) {
                    $query->field('id,show_src,width,height');
                }]);
            },
                'mark' => function ($query) {
                    $query->where(['buy_type' => 2])->whereIn('status', [1, 3])->field('id,price,buy_type,sell_id,status');
                }
            ])
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }


    public function getMyInfo(array $where, int $companyId = null, int $uuid)
    {
        $where['uuid'] = $uuid;
        $query = $this->dao->search($where, $companyId)
            ->with(['box' => function ($query) {
                $query->field('id,title,file_id,num,max_price,is_give,content')
                    ->with(
                        ['cover' => function ($query) {
                            $query->field('id,show_src,width,height');
                        }]
                    );
            }
            ])
            ->withCount(['is_follow' => function ($query) use ($uuid) {
                $query->where(['uuid' => $uuid, 'buy_type' => 2]);
            }])
            ->find();
        return $query;
    }

    public function open($data, $user, int $companyId = null)
    {
        $info = $this->dao->search(['uuid' => $user['id']], $companyId)->where(['id' => $data['id']])->find();
        if (!$info) throw new ValidateException('盲盒不存在！');
        $res2 = Cache::store('redis')->setnx('open_' . $data['id'], $data['id']);
        Cache::store('redis')->expire('open_' . $data['id'], 1);
        if (!$res2) throw new ValidateException('');
        if ($info['status'] == 2) throw new ValidateException('寄售中，不可开启!');
        if ($info['status'] != 1) throw new ValidateException('盲盒不存在！');
        $goodsRepository = app()->make(BoxSaleGoodsListRepository::class);
        $fraudRepository = app()->make(FraudRepository::class);

        $fraud = $fraudRepository->search(['fraud_type' => 1, 'pid' => $info['box_id'], 'uuid' => $user['id']],$companyId)->where('num', '>','0')->find();

        if ($fraud) {
            $prize_id = $fraud['pid'];
            $is_fraud = 1;
        } else {
            $list = $goodsRepository->search(['box_id' => $info['box_id']], $companyId)->withAttr('num', function ($v, $data) use ($fraudRepository) {
                return $data['num'] - $fraudRepository->search(['fraud_type' => 1, 'pid' => $data['id']])->sum('num');
            })->select();
            $item = [];
            foreach ($list as $key => $value) {
                if ($value['num'] > 0) {
                    $item[$value['id']] = $value['probability'];
                }
            }
            if (!$item) throw new ValidateException('当前开盒人数过多，请稍后重试');
            $prize_id = getRand($item);
            $is_fraud = 2;
        }

        $prize = $goodsRepository->search([], $companyId)->where(['id' => $prize_id])->find();
        if (!$prize) throw new ValidateException('奖品不存在!');
        return Db::transaction(function () use ($info, $user, $companyId, $prize, $goodsRepository, $is_fraud, $fraud, $fraudRepository) {
            $goods = [];
            $open_type = 1;
            $no = '';
            switch ($prize['goods_type']) {
                case 1: //卡牌
                    $usersPoolRepository = app()->make(UsersPoolRepository::class);
                    $no = app()->make(PoolOrderNoRepository::class);
                    $data['uuid'] = $user['id'];
                    $data['add_time'] = date('Y-m-d H:i:s');
                    $data['pool_id'] = $prize['goods_id'];
                    $data['no'] = $no->getNo($prize['goods_id'],$user['id']);
                    $data['price'] = 0.00;
                    $data['type'] = 6;
                    $re = $usersPoolRepository->addInfo($companyId, $data);
                    if ($re) {
                        $no = $data['no'];
                        Cache::store('redis')->rPop('goods_num_' . $prize['goods_id']);
                        $poolSaleRepository = app()->make(PoolSaleRepository::class);
                        $poolSaleRepository->search([], $companyId)->where(['id' => $prize['goods_id']])->field('id,stock')->dec('stock', 1)->update();
                        $goods = $poolSaleRepository->getCache($prize['goods_id'], $user['id'], $companyId);
                        if(!$goods){
                            throw new ValidateException('当前开盒人数过多，请稍后重试!');
                        }

                        $log['pool_id'] = $data['pool_id'];
                        $log['no'] = $data['no'];
                        $log['uuid'] = $user['id'];
                        $log['type'] = 7;
                        app()->make(PoolTransferLogRepository::class)->addInfo($companyId,$log);

                        $cover = $goods['img']['show_src'];
                    }
                    /*****************************开盲盒赠送 begin********************************/
                    /*****************************开盲盒赠送 end********************************/
                    break;
                case 2://商品
                    $open_type = 2;
                    $order['order_type'] = 1;
                    $order['express_type'] = 1;
                    $order['group_order_id'] = time() . $user['id'];
                    $order['order_sn'] = SnowFlake::createOnlyId('S');
                    $order['user_id'] = $user['id'];
                    $order['username'] = $user['mobile'];
                    $order['total_num'] = 1;
                    $order['total_price'] = 0.00;
                    $order['pay_price'] = 0.00;
                    $order['status'] = 1;
                    $order['pay_time'] = date('Y-m-d H:i:s');
                    $order['is_pay'] = 1;
                    $order['finish_time'] = date('Y-m-d H:i:s');
                    $order['express_type'] = 1;
                    $order['js_status'] = 2;
                    $order['js_status'] = 2;
                    $order['product_id'] = $prize['goods_id'];
                    $re = app()->make(OrderRepository::class)->addInfo($order, $companyId);
                    if ($re) {
                        $productRepository = app()->make(ProductRepository::class);
                        $goods = $productRepository->search([], $companyId)->where(['id' => $prize['goods_id']])->field('id,title,picture')
                            ->cache('goods_' . $prize['goods_id'])->find();
                        $productRepository->search([], $companyId)->where(['id' => $prize['goods_id']])->dec('stock', 1)->update();
                        $cover = $goods['picture'];
                    }
                    break;
            }
            $this->dao->search([], $companyId)->where('id', $info['id'])->update(['status' => 6, 'open_type' => $open_type, 'goods_id' => $prize['goods_id'], 'open_time' => date('Y-m-d H:i:s'),'no'=>$no]);
            $goodsRepository->search([], $companyId)->where(['id' => $prize['id']])->dec('num', 1)->update();
            if ($is_fraud == 1) {
                $fraudRepository->editInfo($fraud, ['num' => $fraud['num'] - 1]);
            }
            return ['buy_type' => 1, 'title' => $goods['title'], 'cover' => $cover];
        });
    }



    public function openAll($data, $user, int $companyId = null)
    {
        if(!$data['box_id']) throw new ValidateException('请选择要开启的盲盒');
        if(!$data['num']) throw new ValidateException('请选择开始数量');

        $list = $this->dao->search(['box_id'=>$data['box_id'],'uuid' => $user['id'],'status'=>1], $companyId)
            ->limit($data['num'])->select();

        if (count($list) < $data['num']) throw new ValidateException('您拥有的盲盒不足！');
        $key = $data['box_id'].'_'.$user['id'];
        $res2 = Cache::store('redis')->setnx('open_' .$key,$user['id']);
        Cache::store('redis')->expire('open_' .$key, 1);
        if (!$res2) throw new ValidateException('');

        $fraudRepository = app()->make(FraudRepository::class);
        $goodsRepository = app()->make(BoxSaleGoodsListRepository::class);
        $list = $goodsRepository->search(['box_id' => $data['box_id']], $companyId)
            ->withAttr('num', function ($v, $data) use ($fraudRepository) {
                 return $data['num'] - $fraudRepository->search(['fraud_type' => 1, 'pid' => $data['id']])->sum('num');
             })->select();

        $total = array_sum(array_column(json_decode($list,true),'num'));

        if($total < $data['num']) throw new ValidateException('盒没剩余物品不足!');

        $arr['uuid'] = $user['id'];
        $arr['company_id'] = $companyId;
        $arr['box_id'] = $data['box_id'];
        $arr['num'] = $data['num'];
        $arr['open_no'] = SnowFlake::createOnlyId("open");

        $rt = \think\facade\Queue::push(\app\jobs\BoxUserOpenAll::class, $arr);
        return ['order_id'=>$arr['open_no']];
    }

    public function openAllLog($data,$user,$companyId = null){
        $list = $this->dao->search(['open_no'=>$data['open_no'],'uuid'=>$user['id']],$companyId)
            ->append(['goods'])
            ->field('id,company_id,add_time,open_no,open_type,goods_id,open_time,no')
            ->select();
        return compact('list');

    }

    public function getApiOpenList(array $data, int $page, int $limit, int $uuid, int $companyId)
    {
        if (!$data['open_type']) {
            $data['uuid'] = $uuid;
        }
        $query = $this->dao->search($data, $companyId)->field('id,company_id,uuid,box_id,open_type,open_time,goods_id,no');
        $count = $query->count();
        $query->with(['box' => function ($query) {
            $query->field('id,title,file_id')->with(['cover' => function ($query) {
                $query->field('id,show_src,width,height');
            }]);
        }]);
        $query->append(['goods']);
        $list = $query->page($page, $limit)
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');

    }


    public function batchGiveUserBox(array $userId, array $data)
    {

        $usersRepository = app()->make(UsersRepository::class);
        $list = $usersRepository->selectWhere([
            ['id', 'in', $userId]
        ]);
        if ($list) {
            foreach ($list as $k => $v) {
                $this->giveUserBoxInfo($v, $data);
            }
            return $list;
        }
        return [];
    }


    public function giveUserBoxInfo($userInfo, $data)
    {
        $boxSaleRepository = app()->make(BoxSaleRepository::class);
        $boxInfo = $boxSaleRepository->get($data['box_id']);

        if ($boxInfo) {
            Db::transaction(function () use ($userInfo, $data, $boxInfo, $boxSaleRepository) {
                for ($i = 1; $i <= $data['num']; $i++) {
                    $givBoxData['type'] = 5;
                    $givBoxData['status'] = 1;
                    $givBoxData['uuid'] = $userInfo['id'];
                    $givBoxData['box_id'] = $data['box_id'];
                    $this->addInfo($userInfo['company_id'], $givBoxData);
                }
                if ($data['num'] > 0) {
                    $boxSaleRepository->update($data['box_id'], $boxSaleData);
                }
            });
        }
    }

}