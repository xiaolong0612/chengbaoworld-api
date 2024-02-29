<?php

namespace app\common\repositories\users;

use app\common\dao\users\UsersDao;
use app\common\dao\users\UsersMarkDao;
use app\common\model\BaseModel;
use app\common\model\mine\MineUserModel;
use app\common\repositories\agent\AgentRepository;
use app\common\repositories\BaseRepository;
use app\common\repositories\cochain\EbaoUserRepository;
use app\common\repositories\game\KillRepository;
use app\common\repositories\givLog\MineGivLogRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\tichain\TichainUserRepository;
use app\common\services\JwtService;
use app\common\services\SmsService;
use app\helper\SnowFlake;
use app\listener\api\UserMine;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class UsersRepository
 *
 * @mixin UsersDao
 */
class UsersRepository extends BaseRepository {
    public function __construct(UsersDao $dao) {
        $this->dao = $dao;
    }

    /**
     * 密码验证
     *
     * @param $password
     * @param string $userPassword 用户密码
     * @return string
     */
    public function passwordVerify($password, $userPassword) {
        return password_verify($password, $userPassword);
    }



    public function getList(array $where, $page, $limit, int $companyId = null) {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $integralLogRepository = app()->make(UsersIntegralLogRepository::class);
        $list = $query->page($page, $limit)
            ->with([
                'tjrInfo' => function ($query) {
                    $query->field('company_id,user_id,parent_id,levels')->with([
                        'tjrOne' => function ($query) {
                            $query->bind(['mobile','nickname']);
                        }
                    ]);
                },
                'cert' => function ($query) {
                    $query->field('id,cert_status,remark,username');
                },
            ])->withCount(['pool'=>function($query){
                $query->where(['status'=>1]);
            }])

            ->append(['zt_num'])
            ->order('id', 'desc')
            ->select();

        return compact('list', 'count');
    }

    public function getHoldList(array $where, $page, $limit, int $companyId = null) {

        $ids = app()->make(UsersPoolRepository::class)->search(['pool_id' => $where['pool_id']], $companyId)
            ->whereIn('status', [1, 2])
            ->group('uuid')->column('uuid');

        $query = $this->dao->search($where, $companyId)->whereIn('id', $ids)->field('id,company_id,mobile');
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->withCount(['pool' => function ($query) use ($where) {
                $query->where(['pool_id' => $where['pool_id']])->whereIn('status', [1, 2]);
            }])
            ->order('id', 'desc')
            ->select();

        return compact('list', 'count');
    }

    public function editInfo($info, $data) {

        if(isset($data['type'])){
            switch ((int)$data['type']) {
                case '1':
                    if (isset($data['password']) && $data['password'] !== '') {
                        $data['password'] = $this->passwordEncrypt($data['password']);
                    }
                    break;
                case '2':
                    if (isset($data['password']) && $data['password'] !== '') {
                        $data['pay_password'] = $this->passwordEncrypt($data['password']);
                        unset($data['password']);
                    }
                    break;
            }
        }
        if (isset($data['avatar']) && $data['avatar'] !== '') {
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['avatar'], 2, $info['company_id']);
            if ($fileInfo['id'] != $info['head_file_id']) {
                $data['head_file_id'] = $fileInfo['id'];
            }
        }
        unset($data['type']);
        unset($data['avatar']);
      
        return $this->dao->update($info['id'], $data);
    }

    /**
     * 密码加密
     *
     * @param $password
     * @return string
     */
    public function passwordEncrypt($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public function editPasswordInfo($info, $data) {
        if (isset($data['password']) && $data['password'] !== '') {
            $data['password'] = $this->passwordEncrypt($data['password']);
        }
        return $this->dao->update($info['id'], $data);
    }

    public function batchSetUser(array $userId, array $data) {
        $list = $this->dao->selectWhere([
            ['id', 'in', $userId]
        ]);
        if ($list) {
            foreach ($list as $k => $v) {
                $this->dao->update($v['id'], $data);
            }
            return $list;
        }
        return [];
    }

    public function getDetail(int $id) {
        $with = [
            'tjrInfo' => function ($query) {
                $query->with([
                    'tjrOne' => function ($query) {
                        $query->bind(['mobile', 'nickname']);
                    }
                ]);
            },
            'cert' => function ($query) {
                $query->field('id,front_file_id,back_file_id,username,number,cert_status,remark');
            },
            'avatars' => function ($query) {
                $query->field('id,show_src');
                $query->bind(['avatar' => 'show_src']);
            }
        ];
        $data = $this->dao->search([])
            ->with($with)
            ->where('id', $id)
            ->hidden(['avatars'])
            ->find();
        event('user.mine', $data);
        return $data;
    }

    /**
     * 产出币种变动
     * @param $userId
     * @param $type
     * @param $amount
     * @param array $data
     * @param $trackPort
     * @return \app\common\dao\BaseDao|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function foodChange($userId, $type, $amount, $data = [], $trackPort) {
        $userInfo = $this->dao->get($userId);
        if ($userInfo) {
            $beforeChange = $userInfo->food;
            $userInfo->food = bcadd($userInfo->food, $amount, 2);
            $userInfo->save();
            $afterChange = $userInfo->food;
            /**
             * @var UsersBalanceLogRepository $balanceLogRepository
             */
            $balanceLogRepository = app()->make(UsersBalanceLogRepository::class);
            return $balanceLogRepository->addLog($userInfo['id'], $amount, $type, array_merge($data, [
                'before_change' => $beforeChange,
                'after_change' => $afterChange,
                'track_port' => $trackPort,
            ]));
        }
    }

    /**
     * 批量设置代币变动
     *
     * @param array $userId 用户ID
     * @param int $type 变动类型
     * @param float $amount 变动金额
     * @param array $data 其他数据 note:备注 link_id:关联ID
     * @return \app\common\dao\BaseDao|\think\Model|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function batchFoodChange($userId, $type, $amount, $data = [], $trackPort = 1) {
        $userInfo = $this->dao->selectWhere([
            ['id', 'in', $userId]
        ]);
        if ($userInfo) {
            foreach ($userInfo as $k => $v) {
                $beforeChange[] = $v->food ?: 0.00;
                $v->food = bcadd($v->food, $amount, 7);
                $v->save();
                $afterChange[] = $v->food;
            }
            /**
             * @var UsersFoodLogRepository $usersFoodLogRepository
             */
            $usersFoodLogRepository = app()->make(UsersFoodLogRepository::class);
            return $usersFoodLogRepository->batchAddLog($userInfo, $amount, $type, $data, $beforeChange, $afterChange, $trackPort);
        }

    }


    public function batchFood($userId, $type, $amount, $data = [], $trackPort = 1) {
        $userInfo = $this->dao->selectWhere([
            ['id', 'in', $userId]
        ]);
        if ($userInfo) {
            /** @var UsersFoodTimeRepository $usersFoodTimeRepository */
            $usersFoodTimeRepository =app()->make(UsersFoodTimeRepository::class);
            foreach ($userInfo as $k => $v) {
                if($this->incField($v->id,'food',$amount)){
                    $afterChange[] = $v->food;
                    $arr['uuid'] = $v['id'];
                    $arr['price'] = $amount;
                    $arr['status'] = 2;
                    $arr['type'] = $data['type'];
                    $arr['dis_id'] = $data['dis_id'];
                    $usersFoodTimeRepository->addInfo($v['company_id'],$arr);
                }

            }
            return true;
        }

    }


    /**
     * 余额变动
     *
     * @param int $userId 用户ID
     * @param int $type 变动类型
     * @param float $amount 变动金额
     * @param array $data 其他数据 note:备注 link_id:关联ID
     * @return \app\common\dao\BaseDao|\think\Model|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function balanceChange($userId, $type, $amount, $data = [], $trackPort) {
        $userInfo = $this->dao->get($userId);
        if ($userInfo) {
            $beforeChange = $userInfo->balance;
            $userInfo->balance = bcadd($userInfo->balance, $amount, 2);
            $userInfo->save();
            $afterChange = $userInfo->balance;
            /**
             * @var UsersBalanceLogRepository $balanceLogRepository
             */
            $balanceLogRepository = app()->make(UsersBalanceLogRepository::class);
            return $balanceLogRepository->addLog($userInfo['id'], $amount, $type, array_merge($data, [
                'before_change' => $beforeChange,
                'after_change' => $afterChange,
                'track_port' => $trackPort,
            ]));
        }
    }

    /**
     * 批量设置余额变动
     *
     * @param array $userId 用户ID
     * @param int $type 变动类型
     * @param float $amount 变动金额
     * @param array $data 其他数据 note:备注 link_id:关联ID
     * @return \app\common\dao\BaseDao|\think\Model|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function batchBalanceChange($userId, $type, $amount, $data = [], $trackPort = 1) {
        $userInfo = $this->dao->selectWhere([
            ['id', 'in', $userId]
        ]);
        if ($userInfo) {
            foreach ($userInfo as $k => $v) {
                $beforeChange[] = $v->balance;
                $v->balance = bcadd($v->balance, $amount, 2);
                $v->save();
                $afterChange[] = $v->balance;
            }

            /**
             * @var UsersBalanceLogRepository $balanceLogRepository
             */
            $balanceLogRepository = app()->make(UsersBalanceLogRepository::class);
            return $balanceLogRepository->batchAddLog($userInfo, $amount, $type, $data, $beforeChange, $afterChange, $trackPort);
        }

    }
    public function batchFrozenChange($userId, $type, $amount, $data = [], $trackPort = 1) {
        $userInfo = $this->dao->selectWhere([
            ['id', 'in', $userId]
        ]);
        if ($userInfo) {
            foreach ($userInfo as $k => $v) {
                $beforeChange[] = $v->food ?: 0.00;
                $v->food = bcadd($v->food, -$amount, 7);
                $v->frozen_food = bcadd($v->frozen_food, $amount, 7);
                $v->save();
                $afterChange[] = $v->food;
            }
            /**
             * @var UsersFoodLogRepository $usersFoodLogRepository
             */
            $usersFoodLogRepository = app()->make(UsersFoodLogRepository::class);
            return $usersFoodLogRepository->batchAddLog($userInfo, $amount, $type, $data, $beforeChange, $afterChange, $trackPort);
        }
    }
    /**
     * 生成随机用户名
     * 2021年5月10日 13:54:39
     * author Turbo
     * @return str|string
     */
    public function generateUsername()
    {
        $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $charsLength = bcsub(strlen($chars), 1);
        $username = "";
        for ( $i = 0; $i < 6; $i++ )
        {
            $username .= $chars[mt_rand(0, $charsLength)];
        }
        // 打乱顺序
        return str_shuffle($username.str_shuffle(time()));
    }
    /**
     * 注册账号
     *
     * @param int $companyId 企业ID
     * @param array $data 用户数据
     * @return \app\common\dao\BaseDao|\think\Model
     */
    public function register(array $data, int $companyId) {
        if (empty($data['nickname']) && isset($data['mobile'])) {
            $data['nickname'] = $this->generateUsername();
            $userInfo = Db::name('users')->where('nickname', $data['nickname'])->find();
            if (!empty($userInfo)){
                $this->register($data, $companyId);
            }
        }

        if (empty($data['password'])) $data['password'] = substr($data['mobile'], 3, 6);
        if (empty($data['pay_password'])) $data['pay_password'] = substr($data['mobile'], 3, 6);

        $tjRegCodeType = (int)web_config($companyId, 'reg.tj_reg_code_type');
        if ($tjRegCodeType == 1 && empty($data['user_code'])) {
            throw new ValidateException('邀请码不能为空!');
        }
        if (isset($data['user_code']) && $data['user_code']) {
            $tjr_account = $this->dao->getWhere(['user_code' => $data['user_code']], 'mobile');
            if (!$tjr_account) throw new ValidateException('邀请码不存在!');
            $data['tjr_account'] = $tjr_account['mobile'];

            $tjr_account_id = $this->dao->getWhere(['user_code' => $data['user_code']], 'id');
        }
        unset($data['user_code']);
        $user = $this->addInfo($companyId, $data);
        if(isset($tjr_account_id)){
          $key =  'invitation_rewards:'.$tjr_account_id;
          Cache::store('redis')->sadd($key,$user['id']);
        }
        // TODO END
        return $user;
    }

    public function addInfo(int $companyId = null, array $data = []) {

        return Db::transaction(function () use ($data, $companyId) {
            if ($companyId) $data['company_id'] = $companyId;
            if (isset($data['password']) && $data['password'] !== '') {
                $data['password'] = $this->passwordEncrypt($data['password']);
            }
            if (isset($data['pay_password']) && $data['pay_password'] !== '') {
                $data['pay_password'] = $this->passwordEncrypt($data['pay_password']);
            }
            $data['regist_ip'] = Request()->ip();
            $data['unquied'] = $companyId . $data['mobile'];
            $data['is_mine'] =2;
            $userInfo = $this->create($data,$companyId);
            if (isset($data['tjr_account']) && $data['tjr_account']) {
                $tjrInfo = $this->dao->getUserByMobile($data['tjr_account'], $companyId);
                /**
                 * @var UsersPushRepository $usersPushRepository
                 */
                $usersPushRepository = app()->make(UsersPushRepository::class);
                $usersPushRepository->batchTjr($userInfo, $tjrInfo['id'], $companyId);
            }
            event('user.mine', $userInfo);
            return $userInfo;
        });
    }

    public function create(array $data,$companyId) {
        if (!isset($data['unquied']) || !$data['unquied']) {
            $unquied = time() . rand(1000, 9999) . rand(1000, 9999);
            $unquied = str_split($unquied);
            shuffle($unquied);
            $unquied = implode('', $unquied);
            $data['unquied'] = $unquied;
        }
        if (!isset($data['user_code']) || !$data['user_code']) {
            $userCode = $this->makeInviterCode($companyId);
            $data['user_code'] = $userCode;
        }
        return $this->dao->create($data);
    }

    public  function makeInviterCode($companyId,$start=0,$end=9,$length=8) {
        //初始化变量为0
        $connt = 0;
        //建一个新数组
        $temp = array();
        while($connt < $length){
            //在一定范围内随机生成一个数放入数组中
            $temp[] = mt_rand($start, $end);
            //$data = array_unique($temp);
            //去除数组中的重复值用了“翻翻法”，就是用array_flip()把数组的key和value交换两次。这种做法比用 array_unique() 快得多。
            $data = array_flip(array_flip($temp));
            //将数组的数量存入变量count中
            $connt = count($data);
        }
        //为数组赋予新的键名
        shuffle($data);
        //数组转字符串
        $str=implode(",", $data);
        //替换掉逗号
        $number=str_replace(',', '', $str);
        $code = $this->dao->search(['user_code'=>$number],$companyId)->find();
        if($code) return  $this->makeInviterCode($companyId);
        return $number;

    }

    /**
     * 创建token
     *
     * @param User $userInfo 用户信息
     * @return string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function createToken($userInfo): string {
        $service = new JwtService();
        $res = $service->createToken($userInfo['id'], 'user', strtotime('+365 day'));
        $token = sha1($res['token']);

        Cache::store('redis')->set('redis_' . $token, $res['token']);
        Cache::store('redis')->set('token_' . $userInfo['id'], $token);
        return $token;
    }

    /**
     * 更新token
     *
     * @param string $token
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function updateToken(string $token) {
        Cache::store('redis')->set('redis_' . $token, $this->getToken(), 86400);
    }

    /**
     * 生成token
     *
     * @param string $token token
     * @return string
     */
    public function getToken($token = '') {
        if ($token) {
            $token = 'redis_' . $token;
        } else {
            $token = 'redis_' . app('request')->header('Token');
        }
        return Cache::store('redis')->get($token);
    }

    /**
     * 清除token
     *
     */
    public function clearToken() {
        Cache::store('redis')->delete('redis_' . app('request')->header('Token'));
    }

    /**
     * 过滤显示的用户信息
     *
     * @param array userInfo 用户信息
     * @return array
     */
    public function showApiFilter($userInfo) {
        $data = $userInfo
            ->append(['personal_auth','queue'])
            // ->append(['labels'])
            ->hidden([
                'password', 'session_id', 'pay_password'
            ]);
        $pushInfos = app()->make(UsersPushRepository::class)->search(['levels'=>1,'parent_id'=>$userInfo['id']],$userInfo['company_id'])->count();
        $data['invitedCount'] = $pushInfos;
        $tokens_rate = web_config($data['company_id'],'program');
        if(isset($tokens_rate['mine']['tokens']['rate']) && $tokens_rate['mine']['tokens']['rate']){
            $data['tokens_rate'] = $tokens_rate['mine']['tokens']['rate'];
        }
        $tokens_rate = web_config($data['company_id'],'program');
        if(isset($tokens_rate['convert']['etc']['rate']) && $tokens_rate['convert']['etc']['rate']){
            $data['etc_rate'] = $tokens_rate['convert']['etc']['rate'];
        }
        $tokens_rate = web_config($data['company_id'],'program');
        if(isset($tokens_rate['convert']['etc']['lv']) && $tokens_rate['convert']['etc']['lv']){
            $data['lv'] = $tokens_rate['convert']['etc']['lv'];
        }
        /** @var AgentRepository $agentRepository */
        $agentRepository = app()->make(AgentRepository::class);
        $agent = $agentRepository->search(['uuid'=>$data['id']])->find();
        $data['is_agent'] = !$agent ? 0: 1;
        $data['agent_level'] = $agent ? $agent['level'] : -1;
        /** @var UsersPoolRepository $usersPoolRepository */
        $usersPoolRepository = app()->make(UsersPoolRepository::class);
        $data['countPool'] = $usersPoolRepository->search(['uuid'=>$userInfo['id'],'status'=>1])->count('id');

        $total = 0;
        $product = (new MineUserModel())->alias('mu')
            ->where(['mu.status'=>1,'mu.uuid'=>$data['id']])
            ->where('mu.level','>',1)
            ->join('mine m','mu.mine_id = m.id')
            ->whereExp('mu.dispatch_count', '> 0')
            ->sum('m.day_output');
        if($product) $total += $product;
        $chuji = (new MineUserModel())->alias('mu')
            ->where(['mu.status'=>1,'mu.level'=>1,'mu.uuid'=>$data['id']])
            ->join('mine m','mu.mine_id = m.id')
            ->whereExp('mu.dispatch_count', '> 0')
            ->find();
        if($chuji){
            $tatal2 =  get_rate($chuji['dispatch_count'],$userInfo['company_id']);
            if($tatal2) $total +=$tatal2['total'];

        }

        /** @var MineDispatchRepository $mineDispatchRepository */
        $mineDispatchRepository = app()->make(MineDispatchRepository::class);
        $dis = $mineDispatchRepository->search(['uuid'=>$data['id']])
            ->whereExp('dispatch_count', '> 0')
            ->find();
        $data['childs'] = 0;
        if($dis){
            $tatal1 =  get_rate1($dis['dispatch_count'],$userInfo['company_id'],$data['id']);//rate * round((floor($time / 10) * $product['rate']), 7);
            if($tatal1) {
                $data['childs'] = $tatal1['total'];
//                $total += $tatal1['total'];
            }
        }
        $data['product'] = round($total,7);
        $data['team_total'] = app()->make(UsersPushRepository::class)->search(['parent_id' => $userInfo['id']],$userInfo['company_id'])->count();
        return $data;
    }

    public function giv($data,$userInfo,$companyId){
        $rate = web_config($companyId,'program.mine.tokens.rate');
        if(!$rate) throw new ValidateException('请先设置手续费比例');


    //   $has=Db::table('giv_log')->where('uuid',$userInfo['id'])->find();
       
        $res1 = Cache::store('redis')->incr('giv_'.$userInfo['id']);
        if ( $res1 > 1 ){
            throw new ValidateException('操作太快,请稍后再试');
        }
        Cache::store('redis')->expire('giv_'.$userInfo['id'],3);

        $is_giv = $userInfo['is_giv'];
        if($is_giv != 1) throw new ValidateException('转增暂未开启!');

        $change = $data['num'] * $rate;


        $giv_pwd = web_config($companyId,'program.giv_pwd');
        if($giv_pwd != 2){
            if(!$data['pay_password']) throw new ValidateException('请输入转赠密码!');
            /** @var UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);
            $verfiy = $usersRepository->passwordVerify($data['pay_password'], $userInfo['pay_password']);
            if (!$verfiy) throw new ValidateException('交易密码错误!');
        }


        if( ($data['num'] + $change ) > $userInfo['food']) throw new ValidateException('余额不足!');



        $givUser = $this->search([],$companyId)->where(['user_code'=>$data['user_code']])->find();
        if(!$givUser) throw new ValidateException('转赠对象不存在!');
        return Db::transaction(function () use ($data,$userInfo,$companyId,$givUser,$change) {
            $this->batchFoodChange($userInfo['id'], 5,  '-'.$data['num'], [
                'remark' => '转赠'
            ],4);
            $this->batchFoodChange($userInfo['id'], 5,  '-'.$change, [
                'remark' => '转赠手续费'
            ],4);
            $order_no = SnowFlake::createOnlyId();
            $arr = [
                ['company_id'=>$companyId,'uuid'=>$userInfo['id'],'num'=>$data['num'],'create_at'=>date('Y-m-d H:i:s'),'get_uuid'=>$givUser['id'],'type'=>1,'order_sn'=>$order_no],
                ['company_id'=>$companyId,'uuid'=>$givUser['id'],'num'=>$data['num'],'create_at'=>date('Y-m-d H:i:s'),'get_uuid'=>$userInfo['id'],'type'=>2,'order_sn'=>$order_no],
            ];
            /** @var MineGivLogRepository $mineGivLogRepository */
            $mineGivLogRepository = app()->make(MineGivLogRepository::class);
            $mineGivLogRepository->insertAll($arr);
            $this->batchFoodChange($givUser['id'],4,$data['num'],['remark'=>'用户'.substr_replace($userInfo['mobile'],'****',3,4).'转赠']);
            return true;
        });
        throw new ValidateException('网络错误，转赠失败!');
    }


    /**
     * 删除
     * @param array $ids 用户ID
     */
    public function delUser($ids) {
        /** @var UsersPushRepository $usersPushRepository */
        $usersPushRepository = app()->make(UsersPushRepository::class);

        $parent = [];
        foreach ($ids as $k => $v) {
            $parent[] = $usersPushRepository->getUserId($v);
        }

        foreach ($parent as $k => $v) {
            if (!empty($v)) {
                throw new ValidateException('请先处理下级推荐人');
            }
        }

        foreach ($ids as $k => $v) {
            $usersPushRepository->whereDelete(['user_id' => $v]);

            $res = $this->dao->delete($v);
        }
        return $res;
    }

    /******************大逃杀**************************/
    public function getGameDetail($userInfo){
        $data['userId'] = $userInfo['id'];
        $data['avatar'] = $userInfo['avatar'];
        $data['nickName'] = $userInfo['nickname'];
        $data['status'] = $userInfo['status'];;
        $data['isIdentity'] = $userInfo['cert_id'] > 0 ? 1 : 0;
        $data['isBlack'] = 0;
        $data['isCancellation'] = 1;
        return $data;
    }
    public function getGameBalance($param){
        $userInfo = app()->make(UsersRepository::class)->search([])->where(['id'=>$param['uuid']])->find();
        if(!$userInfo) throw new ValidateException('账号不存在');
        $data['id'] = $userInfo['id'];
        $data['userId'] = $userInfo['id'];
        $data['balanceDiamond'] = $userInfo['food'];
        $data['nickname'] = $userInfo['nickname'];
        return $data;
    }

    public function decbalance($data){
        return Db::transaction(function () use ($data) {
            $userInfo = app()->make(UsersRepository::class)->search([])->where(['id'=>$data['userId']])->lock(true)->find();
            if($userInfo['food'] < $data['changePoints']) throw new ValidateException('账户余额不足');
            $affectedRows = app()->make(UsersRepository::class)->search([])->where(['id'=>$data['userId']])
                ->where('food','>=',$data['changePoints'])
                ->update(['food' => $userInfo['food'] - $data['changePoints']]);
            if (!$affectedRows) {
                throw new ValidateException('账户余额不足');
            }
            /** @var UsersFoodLogRepository $balanceLogRepository */
            $balanceLogRepository = app()->make(UsersFoodLogRepository::class);
            $balanceLogRepository->addLog($userInfo['id'], $data['changePoints'], 3, array_merge($data, [
                'before_change' => $userInfo['food'],
                'after_change' => $userInfo['food'] - $data['changePoints'],
                'track_port' => 1,
                'remark'=>'大逃杀投入',
                'company_id'=>$userInfo['company_id']
            ]));
            $log['uuid'] = $userInfo['id'];
            $log['requestNo'] = $data['requestNo'];
            $log['coinType'] = $data['coinType'];
            $log['gameName'] = $data['gameName'];
            $log['changePoints'] = $data['changePoints'];
            $log['gameDate'] = $data['gameDate'];
            $log['batch_no'] = $data['batch_no'];
            $log['type'] = 1;
            app()->make(KillRepository::class)->addInfo($userInfo['company_id'],$log);
            return [];
        });
        throw new ValidateException('投入失败');

    }

    public function incbalance($data){
        return Db::transaction(function () use ($data) {
            /** @var KillRepository $KillRepository */
            $KillRepository = app()->make(KillRepository::class);
            /** @var  UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);

               foreach ($data['inComeDetails'] as $key => $value){
//                   $log['requestNo'] = $data['requestNo'];
                   $log['coinType'] = $data['coinType'];
                   $log['gameName'] = $data['gameName'];
                   $log['changePoints'] = $data['changePoints'];
                   $log['gameDate'] = date("Y-m-d H:i:s");
                   $log['detail'] = json_encode($data['inComeDetails']);
                   $log['type'] = 2;
                   $userInfo = $usersRepository->search([])->where(['id'=>$value['userId']])->find();
                   if($userInfo){
                       $log['uuid'] = $userInfo['id'];
                       $re = $KillRepository->addInfo($userInfo['company_id'],$log);
                       $kill = $KillRepository->search(['uuid'=>$value['userId'],'type'=>1,'batch_no'=>$data['batch_no']],$userInfo['company_id'])->find();
                       if($kill){
                           $this->batchFoodChange($value['userId'],4,$value['changePoints'],['remark'=>'成功躲避杀手']);
                           $param_rate = web_config($userInfo['company_id'],'program.mine.tokens.parm_rate');
                           if($param_rate){
                               $number = $KillRepository->search([])->where(['batch_no'=>$data['batch_no'],'uuid'=>$value['userId'],'type'=>1])->sum('changePoints');
                               $newNumber = $value['changePoints'] - $number;
                               if(($newNumber * $param_rate) > 0){
                                   $parent = app()->make(UsersPushRepository::class)->search(['uuid'=>$value['userId'],'levels'=>1],$userInfo['company_id'])->find();
                                   if($parent){
                                       $this->batchFoodChange($parent['parent_id'],4,$newNumber * $param_rate,['remark'=>'下级成功躲避杀手']);
                                   }
                               }
                           }

                       }
                   }
            }
        });
        throw new ValidateException('投入失败');

    }
    /******************大逃杀**************************/

    /******************赛跑**************************/
    public function getGameInfo($userInfo){
        $data['userId'] = $userInfo['id'];
        $data['avatar'] = $userInfo['avatar'];
        $data['nickName'] = $userInfo['nickname'];
        $data['food'] = $userInfo['food'];
        return $data;
    }


    public function decRace($data,$user){
        if(!$data['gameName']) throw new ValidateException('游戏名称错误!');
        return Db::transaction(function () use ($data,$user) {
            $userInfo = app()->make(UsersRepository::class)->search([])->where(['id'=>$user['id']])->lock(true)->find();
            if($userInfo['food'] < $data['changePoints']) throw new ValidateException('账户余额不足');
            $affectedRows = app()->make(UsersRepository::class)->search([])->where(['id'=>$user['id']])
                ->where('food','>=',$data['changePoints'])
                ->update(['food' => $userInfo['food'] - $data['changePoints']]);
            if (!$affectedRows) {
                throw new ValidateException('账户余额不足');
            }
            /** @var UsersFoodLogRepository $balanceLogRepository */
            $balanceLogRepository = app()->make(UsersFoodLogRepository::class);
            $balanceLogRepository->addLog($userInfo['id'], $data['changePoints'], 3, array_merge($data, [
                'before_change' => $userInfo['food'],
                'after_change' => $userInfo['food'] - $data['changePoints'],
                'track_port' => 1,
            ]));




            $log['uuid'] = $userInfo['id'];
            $log['coinType'] = $data['coinType'];
            $log['gameName'] = $data['gameName'];
            $log['changePoints'] = $data['changePoints'];
            $log['gameDate'] = date('Y-m-d H:i:s');
            $log['batch_no'] = $data['batch_no'];
            $log['type'] = 1;
            app()->make(KillRepository::class)->addInfo($userInfo['company_id'],$log);
            return [];
        });
        throw new ValidateException('投入失败');

    }
    public function incRace($data,$userInfo){
        return Db::transaction(function () use ($data,$userInfo) {
            /** @var KillRepository $KillRepository */
            $KillRepository = app()->make(KillRepository::class);
            foreach ($data['list'] as $key => $value){
                $log['coinType'] = $data['coinType'];
                $log['gameName'] = $data['gameName'];
                $log['changePoints'] = $data['changePoints'];
                $log['gameDate'] = date('Y-m-d H:i:s');
                $log['detail'] = json_encode($data['list']);
                $log['type'] = 2;
                if($userInfo){
                    $log['uuid'] = $userInfo['id'];
                    $KillRepository->addInfo($userInfo['company_id'],$log);
                    $kill = $KillRepository->search(['uuid'=>$value['userId'],'type'=>1,'batch_no'=>$data['batch_no']],$userInfo['company_id'])->find();
                    if($kill){
                        $this->batchFoodChange($value['userId'],4,$value['changePoints'],['remark'=>'成功躲避杀手']);
                    }
                }
            }

        });
        throw new ValidateException('盈利失败');

    }

    /******************赛跑**************************/


    public function getFood($id){
        return $this->dao->search([])->where(['id'=>$id])->value('food');
    }

}