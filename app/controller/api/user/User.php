<?php

namespace app\controller\api\user;

use app\common\model\users\CheckIn;
use app\common\repositories\forum\ForumRepository;
use app\common\repositories\forum\ForumZanRepository;
use app\common\repositories\givLog\GivLogRepository;
use app\common\repositories\givLog\MineGivLogRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersBalanceLogRepository;
use app\common\repositories\users\UsersFoodLogRepository;
use app\common\repositories\users\UsersIntegralLogRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\users\UsersRepository;
use app\controller\api\Base;
use app\validate\users\LoginValidate;
use app\validate\users\UsersValidate;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;

class User extends Base
{
    protected $repository;

    public function __construct(App $app, UsersRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }

    /**
     * 获取用户信息
     *
     * @return mixed
     */
    public function logout()
    {
        $this->repository->clearToken();
        return app('api_return')->success([], '退出成功');
    }

    //  获取用户的税率
    public function get_rate()
    {
        // return app('api_return')->success($this->repository->showApiFilter($this->request->userInfo()));
        //  die();
        //  $userInfo = $this->request->userInfo();
        $res = $this->repository->showApiFilter($this->request->userInfo());
        return app('api_return')->success(['rate' => $res['rate']]);

        //  $user=Db::name('users')->where('id',$userInfo['id'])
        //  var_dump($userInfo['rate']);die();
    }

    /**
     * 获取用户信息
     *
     * @return mixed
     */
    public function getUserInfo()
    {
        $this->gameLoginOut();
        return app('api_return')->success($this->repository->showApiFilter($this->request->userInfo()));
    }

    /**
     * 修改头像
     */
    public function modifyAvatar(UploadFileRepository $uploadFileRepository)
    {
        $userInfo = $this->request->userInfo();
        $avatar = $this->request->param('avatar');

        $uploadFileRepository->whereDelete([
            'id' => $userInfo['head_file_id']
        ]);
        $avatar = $uploadFileRepository->getFileData($avatar, 4, $this->request->userId());
        $this->repository->update($userInfo['id'], [
            'head_file_id' => $avatar['id']
        ]);
        return app('api_return')->success('修改成功');
    }

    /**
     * 修改信息
     */
    public function modifyInfo()
    {
        $params = $this->request->param([
            'nickname' => '',
            'qq' => '',
            'wechat' => ''
        ]);
        foreach ($params as $key => $vo) if ($vo === '') unset($params[$key]);
        
        if ($this->repository->fieldExists('nickname', $params['nickname'])) {
            return app('api_return')->error('昵称已存在');
        }
        
        if (empty($params)) return $this->error('没有修改的数据！');
        $this->repository->update($this->request->userId(), $params);
        return app('api_return')->success('修改成功');
    }

    /**
     * 修改分销比例
     */
    public function modifyChildRate()
    {
        $params = $this->request->param([
            'rate' => '',
            'uid' => ''
        ]);
        foreach ($params as $key => $vo) if ($vo === '') unset($params[$key]);
        if (empty($params)) return $this->error('没有修改的数据！');
        if ($params['rate'] < 0 || $params['rate'] > 100) return $this->error('填写数据错误！');
        $this->repository->update($params['uid'], $params);
        return app('api_return')->success('修改成功');
    }

    /**
     * 修改手机号
     */
    public function modifyMobile()
    {
        $mobile = $this->request->param('mobile');
        $smsCode = $this->request->param('sms_code');

        $info = $this->request->userInfo();
        try {
            validate(UsersValidate::class)->scene('modifyPhone')->check($this->request->param());
        } catch (ValidateException $e) {
            return app('api_return')->error($e->getError());
        }
        if ($this->repository->fieldExists('mobile', $mobile)) {
            return app('api_return')->error('手机号已存在');
        }
        // 短信验证
        sms_verify($this->request->companyId, $mobile, $smsCode, config('sms.sms_type.MODIFY_MOBILE_VERIFY_CODE'));

        $res = $this->repository->update($info['id'], [
            'mobile' => $mobile
        ]);
        if ($res) {
            return app('api_return')->success('修改成功');
        } else {
            return app('api_return')->error('修改失败');
        }
    }

    public function frendsRawd(UsersFoodLogRepository $repository)
    {

        return $this->success($repository->frendsRawd($this->request->userId(), $this->request->companyId));
    }

    /**
     * 修改登录密码
     */
    public function modifyLoginPassword()
    {
        $smsCode = $this->request->param('sms_code');

        $info = $this->request->userInfo();

        $param = $this->request->param([
            // 确认密码
            'password' => '',
            //确认密码
            'repassword' => '',
            'sms_code' => ''
        ]);

        try {
            validate(UsersValidate::class)->scene('editPassword')->check($this->request->param());
        } catch (ValidateException $e) {
            return app('api_return')->error($e->getError());
        }

        // 短信验证
        sms_verify($this->request->companyId, $info['mobile'], $smsCode, config('sms.sms_type.MODIFY_PASSWORD'));
        $res = $this->repository->update($info['id'], [
            'password' => $this->repository->passwordEncrypt($param['password'])
        ]);

        if ($res) {
            return app('api_return')->success('修改成功');
        } else {
            return app('api_return')->error('修改失败');
        }
    }

    public function modifyLoginPayPassword()
    {
        $smsCode = $this->request->param('sms_code');

        $info = $this->request->userInfo();

        $param = $this->request->param([
            // 确认密码
            'pay_password' => '',
            //确认密码
            'repassword' => '',
            'sms_code' => ''
        ]);

        try {
            if (!$param['pay_password']) return $this->error('交易密码不能为空');
            if (!$param['repassword']) return $this->error('确认密码不能为空');
            if ($param['pay_password'] != $param['repassword']) return $this->error('两次密码不一样!');
        } catch (ValidateException $e) {
            return app('api_return')->error($e->getError());
        }

        // 短信验证
        sms_verify($this->request->companyId, $info['mobile'], $smsCode, config('sms.sms_type.MODIFY_PASSWORD'));
        $res = $this->repository->update($info['id'], [
            'pay_password' => $this->repository->passwordEncrypt($param['pay_password'])
        ]);

        if ($res) {
            return app('api_return')->success('修改成功');
        } else {
            return app('api_return')->error('修改失败');
        }
    }

    public function getFood(UsersRepository $repository)
    {
        return $this->success($repository->getFood($this->request->userInfo()['id']));
    }

    public function givTokens(UsersRepository $repository)
    {
        $data = $this->request->param(['user_code' => '', 'num' => '', 'pay_password' => '']);
        if (!$data['user_code']) return $this->error('请输入接收人!');
        if (!$data['num']) return $this->error('请输入数量!');

        $has = Db::table('mine_giv_log')->where('uuid', $this->request->userInfo()['id'])->where('type', 1)->order('id desc')->find();
        if (!empty($has) && strtotime($has['create_at']) >= time() - 10) {
            return $this->error('操作太快,请稍后再试');
            die();
        }
        // return $this->error($has['create_at']);die();
        // die();
        // var_dump($has);die();
        return $this->success($repository->giv($data, $this->request->userInfo(), $this->request->companyId));

    }

    public function getFrom(UsersPushRepository $repository)
    {
        $data = $this->request->param(['limit' => '', 'page' => '', 'parent_id' => $this->request->userId(), 'levels' => 1, 'level_code' => '']);
        return $this->success($repository->getFrom($data, $data['page'], $data['limit'], $this->request->userInfo()['id'], $this->request->companyId));

    }

    /**
     * 获取宝石日志
     */
    public function foodLogList(UsersFoodLogRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        $data = $this->request->param(['type' => '']);
        return app('api_return')->success($repository->foodLogList($data['type'], $page, $limit, $this->request->userId()));
    }

    public function queryUser()
    {
        validate(LoginValidate::class)->scene('transfer')->check($this->request->param());
        $data = $this->request->param(['mobile' => '', 'hash' => '']);
        if (!isset($data['mobile']) && !isset($data['hash'])) $this->error('请填写转赠用户');
        return app('api_return')->success($this->repository->queryUser($data, $this->request->userId(), $this->request->companyId));
    }

    public function getGivLog(GivLogRepository $givLogRepository)
    {
        $data = $this->request->param(['limit' => '', 'page' => '', 'buy_type' => '']);
        $type = $this->request->param(['type' => '']);
        return $this->success($givLogRepository->getApiList($data, $type['type'], $data['page'], $data['limit'], $this->request->companyId, $this->request->userId()));
    }

    public function mineGivLog(MineGivLogRepository $repository)
    {
        $data = $this->request->param(['type' => '']);
        [$page, $limit] = $this->getPage();
        return $this->success($repository->getApiList($data, $page, $limit, $this->request->userId(), $this->request->companyId));
    }


    public function getShareRanking()
    {
        /** @var UsersPushRepository $usersPushRepository */
        $usersPushRepository = app()->make(UsersPushRepository::class);

        $list = $usersPushRepository->getSearch([])->alias('up')
            ->join('users u', 'u.id = up.parent_id')
            ->where('up.levels', 1)
            ->group('up.parent_id')
            ->field('count(up.user_id) zt_num,u.nickname,u.mobile,u.head_file_id')
            ->order('zt_num desc')
            ->limit(100)
            ->withAttr('mobile', function ($value) {
                return mb_substr_replace($value, '*****', 2, 7);
            })
            ->select()->toArray();
        /*            foreach ($list as &$item)
                    {
                        $item['avatar'] =
                    }*/
        return $this->success($list);
    }

    // 游戏登录
    public function gameLogin()
    {
        $userInfo = $this->request->userInfo();
        $username = $userInfo['nickname'];
        if (empty($username)) {
            return $this->error('用户不存在');
        }
        $key = $this->request->param('gamename');
        $game = Db::table('game')->where('gamename', $key)->find();
        
        if($game['status']==2){
            return $this->error('游戏维护中');
        }
        $score = floor($userInfo['food']*10000)/10000 ?? 0;

        if (!empty($game)) {
            $api = $game['game_api'];
            $gameaname = $key;
            if ($key == 'dts') {
                $api = $game['game_url'];

                $data = $api . '?company-code=' . $this->request->companyId . '&token=' . $this->request->header()['token'];
            } else {
                $game_balance_log = ['uid' => $userInfo['id'], 'game_type' => $key, 'username' => $username, 'before_balance' => $score, 'after_balance' => 0, 'balance_change' => 0, 'status' => 1, 'last_time' => date('Y-m-d H:i:s')];
                $insert = Db::name('game_balance_log')->insert($game_balance_log);
                if (!$insert) {
                    return $this->error('添加到游戏记录表失败，请重试');
                }
                $paramArray = array(
                    "uid" => $userInfo['id'],
                    "username" => $userInfo['id'],
                    "gamename" => $gameaname,
                    "avatar" => $userInfo['avatar'],// 头像
                    'score' => $score//宝石
                );
                $mchKey = $game['game_key'];
                $paramArray['sign'] = $this->get_sign($paramArray, $mchKey);
                $curl = curl_init();
                //运动会拼上login，其他游戏直接走game_api
                if($key == 'sports'){
                    $api = $api.'/login';
                }
                $json_string = json_encode($paramArray);
                curl_setopt($curl, CURLOPT_URL, $api);
                curl_setopt($curl, CURLOPT_POST, TRUE);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $json_string);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

                $data = curl_exec($curl);

                $data = json_decode($data, true)['data'];
                curl_close($curl);

                if (empty($data)) {
                    return $this->error('游戏登陆失败，请重试');
                }
            }
            return $this->success($data ?? []);
        } else {
            return $this->error('没有找到此游戏');
        }
    }

    //游戏登出获取余额
    public function gameLoginOut()
    {
        $userInfo = $this->request->userInfo();
        $uid = $userInfo['id'];
        if (empty($uid)) {
            return $this->error('用户不存在');
        }
        $key = $this->request->param('gamename');
        $game = Db::table('game')->where('gamename', $key)->find();
        if (!empty($game)) {
            $api = $game['game_api'];
            $gameaname = $key;
            if ($key !== 'dts') {
                $game_balance_where = ['uid' => $userInfo['id'], 'game_type' => $gameaname, 'status' => 1];
                $game_log = Db::name('game_balance_log')->where($game_balance_where)->order('id desc')->find();
                if (empty($game_log)) {
                    return $this->error('游戏记录表中无数据，请重试');
                }
                $paramArray = array(
                    "username" => $userInfo['id'], //商户ID
                );
                $mchKey = $game['game_key'];
                $paramArray['sign'] = $this->get_sign($paramArray, $mchKey);
                $json_string = json_encode($paramArray);
                $game_balance_where = ['uid' => $userInfo['id'], 'game_type' => $gameaname, 'id' => $game_log['id']];
                $this->clearBalance($paramArray,$game_log,$userInfo,$api,$game_balance_where);
            }
            return $this->success([]);
        } else {
            $game_balance_where = ['uid' => $userInfo['id'], 'status' => 1];
            // $game_log = Db::name('game_balance_log')->where($game_balance_where)->select()->toArray();
            $game_log = Db::name('game_balance_log')->where($game_balance_where)->order('id desc')->find();
            if (empty($game_log)) {
                return $this->error('游戏记录表中无数据，请重试');
            }else{
            //     foreach ($game_log as $item){
                    $paramArray = array(
                        "username" => $userInfo['id'], //商户ID
                    );
                    $mchKey = $game_log['game_type'];
                    $game = Db::table('game')->where('gamename', $mchKey)->find();
                    $api = $game['game_api'];
                    $paramArray['sign'] = $this->get_sign($paramArray, $mchKey);
                    $curl = curl_init();

                    $json_string = json_encode($paramArray);
                    
                    $game_balance_where = ['uid' => $userInfo['id'], 'status' => 1, 'id' => $game_log['id']];
                    $this->clearBalance($paramArray,$game_log,$userInfo,$api,$game_balance_where);
                    return $this->success([]);
                // }
            }
        }
    }
    protected function clearBalance($paramArray,$game_log,$userInfo,$api,$game_balance_where){
        $curl = curl_init();
        $json_string = json_encode($paramArray);
        curl_setopt($curl, CURLOPT_URL, $api . '/getbalance');
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_string);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $data = curl_exec($curl);
        $data = json_decode($data, true);
        curl_close($curl);
        if (empty($data['data'])) {
            return $this->error('获取游戏余额失败，请重试');
        }
        $score = $data['data']['score'];
        $food1 = bcsub($score, $game_log['before_balance'], 7);
        $food2 = bcadd($food1, $userInfo['food'], 7);
        // 启动事务
        Db::startTrans();
        try {
            $game_balance_update = ['after_balance' => $score, 'balance_change' => abs($food1), 'status' => 0, 'last_time' => date('Y-m-d H:i:s')];
            $game_update = Db::name('game_balance_log')->where($game_balance_where)->update($game_balance_update);
            if (!$game_update) {
                return $this->error('修改游戏记录表失败，请重试');
            }
            //此时用户什么也没干
            //当前余额和用户余额金额相同时不执行修改，因为数据相同(会报错)   比如表中id = 5  food = 50  执行修改时也是  where id = 5  update  food = 50
            if ($score != $game_log['before_balance']) {
                $user_update_data = ['food' => $food2];
                $user_update = Db::name('users')->where('id', $userInfo['id'])->data($user_update_data)->update($user_update_data);
                if (!$user_update) {
                    return $this->error('修改用户表失败，请重试');
                }
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
    }

    /**
     *
     * 修改用户宝石 updateUserGem
     * @author ninetyseven.
     * @date 2024/2/2
     */
    public function updateUserGem()
    {
        $paramArr = $this->request->param();
        if (empty($paramArr)) {
            return $this->error('参数错误');
        }
        // 启动事务
        Db::startTrans();
        try {
            $result = [];
            foreach ($paramArr as $item) {
                // 加宝石
                $balance = Db::name('users')->where('id', $item['user_id'])->value('food');
                if ($item['type'] == 1) {
                    $food = $this->settlement($item);
                    $afterBalance = bcadd($balance, $food, 7);
                } else {
                    // 减宝石
                    $afterBalance = bcsub($balance, $item['variable_balance'], 7);
                }
                $user_update_data = ['food' => $afterBalance];

                $user_update = Db::name('users')->where('id', $item['user_id'])->update($user_update_data);
                if (!$user_update) {
                    return $this->error('修改用户表失败，请重试');
                }

                // 获取用户信息
                $userInfo = Db::name('users')->where('id', $item['user_id'])->find();

                // 添加宝石记录
                $logData = [
                    'uid' => $userInfo['id'],
                    'game_type' => $item['game_key'],
                    'username' => $userInfo['nickname'],
                    'before_balance' => $balance,
                    'after_balance' => $afterBalance,
                    'balance_change' => abs(bcsub($balance, $afterBalance, 7)),
                    'status' => 0,
                    'last_time' => date('Y-m-d H:i:s')
                ];
                $log = Db::name('game_balance_log')->insert($logData);
                if (!$log) {
                    return $this->error('添加宝石记录失败，请重试');
                }

                // 查询用户最新宝石
                $balance = Db::name('users')->where('id', $item['user_id'])->value('food');
                $result[] = ['uid' => $item['user_id'], 'food' => $balance, 'game_key' => $item['game_key']];
            }
            // 提交事务
            Db::commit();

            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    function get_sign($paramArray, $mchKey)
    {
        $p = ksort($paramArray);

        if ($p) {

            $str = '';

            foreach ($paramArray as $k => $val) {

                $str .= $k . '=' . $val . '&';

            }

            $strs = rtrim($str, '&');

        }

        $sign = md5($strs . '&key=' . $mchKey);
        return $sign;
    }

    /**
     *  游戏分佣结算数据
     *
     * @param $item
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function settlement($item)
    {
        $config = web_config($this->request->companyId, 'program');
        $user   = Db::table('users')->where('id', $item['user_id'])->find();
        // 游戏总返利
        $platRate = $config['mine']['tokens']['plat_rate'];
        $platAmount         = sprintf('%01.2f', $item['variable_balance'] * $platRate / 100);
        $allParentAmount    = 0;
        $parentAfterChange  = 0;
        $parentBeforeChange = 0;
        $parentId           = 0;
        $amount             = $item['variable_balance'];
        $parent             = Db::table('users_push')->where('user_id', $user['id'])->find();
        if(!empty($parent)) {
            $parentQuery = Db::table('users')->where('id', $parent['parent_id'])->find();
            if(!empty($parentQuery)) {

                $allParentAmount    = sprintf('%01.2f', $platAmount * $user['rate'] / 100);
                $parentId           = $parentQuery['id'];
                $parentAfterChange  = $parentQuery['food'] + $allParentAmount;
                $parentBeforeChange = $parentQuery['food'];
            }
        }
        $food        = $item['variable_balance'] - $platAmount - $allParentAmount;

        $this->addBalances($user, $food, $parent, $parentAfterChange, $platAmount, $allParentAmount, $parentBeforeChange, $amount, $parentId);

        return $food;
    }

    /**
     *  游戏分佣结算落地
     *
     * @param $user
     * @param $food
     * @param $parent
     * @param $parentAfterChange
     * @param $platAmount
     * @param $allParentAmount
     * @param $parentBeforeChange
     * @param $amount
     * @param $parentId
     * @return array
     */
    public function addBalances($user, $food, $parent, $parentAfterChange, $platAmount, $allParentAmount, $parentBeforeChange, $amount, $parentId)
    {
        try {
            $afterChange = $user['food'] + $food;
            DB::transaction(function () use ($user, $afterChange, $parent, $parentAfterChange, $platAmount, $allParentAmount, $food, $parentBeforeChange, $amount, $parentId) {
                Db::name('users')->where('id', $user['id'])->update(['food' => $afterChange]);
                if(!empty($parent)) {
                    Db::name('users')->where('id', $parentId)->update(['food' => $parentAfterChange]);
                }
                $log = [
                    'company_id'           => $user['company_id'],
                    'user_id'              => $user['id'],
                    'parent_id'            => $parentId,
                    'plat_amount'          => $platAmount,
                    'parent_amount'        => $allParentAmount,
                    'amount'               => $amount,
                    'food'                 => $food,
                    'parent_before_change' => $parentBeforeChange,
                    'parent_after_change'  => $parentAfterChange,
                    'before_change'        => $user['food'],
                    'after_change'         => $afterChange,
                    'log_type'             => 1,
                    'remark'               => '游戏收入',
                    'add_time'             => date('Y-m-d H:i:s', time()),
                ];
                Db::table('users_distribution_log')->insert($log);
            });

            return true;
        } catch (Exception $e) {

            return false;
        }
    }
}