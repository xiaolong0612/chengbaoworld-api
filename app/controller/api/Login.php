<?php

namespace app\controller\api;

use app\common\model\BaseModel;
use app\common\model\system\upload\UploadFileModel;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\users\UsersRepository;
use app\common\repositories\users\UsersTrackRepository;
use app\validate\users\LoginValidate;
use think\facade\Db;
class Login extends Base
{
    /**
     * 密码登录
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function passwordLogin(UsersRepository $repository)
    {
        $param = $this->request->param([
            'mobile' => '',
            'password' => '',
        ]);
        validate(LoginValidate::class)->scene('captchaLogin')->check($param);
        $userInfo = $repository->getUserByMobile($param['mobile'], $this->request->companyId);
        if (!$userInfo) {
            return app('api_return')->error('账号或密码错误');
        }
        
        if (!$repository->passwordVerify($param['password'], $userInfo['password'])) {
            return app('api_return')->error('账号或密码错误');
        }
        if ($userInfo['status'] != 1) {
            return app('api_return')->error('此账号已冻结3');
        }

        $res = $repository->createToken($userInfo);
        $userInfo->session_id = $res;
        // 用户登陆事件
        event('user.login', $userInfo);
        $data = [
            'token' => $res,
            'userInfo' => $repository->showApiFilter($userInfo)
        ];

        api_user_log($userInfo['id'], 1, $this->request->companyId, '账号密码登录');
        return app('api_return')->success($data);
    }

    /**
     * 短信登录
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function smsLogin(UsersRepository $repository)
    {
        validate(LoginValidate::class)->scene('smsLogin')->check($this->request->param());
        $mobile = $this->request->param('mobile');
        $smsCode = $this->request->param('sms_code');
        $userInfo = $repository->getUserByMobile($mobile, $this->request->companyId);

        // 短信验证
        sms_verify($this->request->companyId, $mobile, $smsCode, config('sms.sms_type.LOGIN_VERIFY_CODE'));

        if ($userInfo) {
            if ($userInfo['status'] != 1) {
                return app('api_return')->error('账号已被锁定');
            }
        } else {
            $userInfo = $repository->register(['mobile' => $mobile], $this->request->companyId);
            $userInfo = $repository->get($userInfo['id']);
        }

        $res = $repository->createToken($userInfo);
        $userInfo->session_id = $res;
        // 用户登陆事件
        event('user.login', $userInfo);

        $data = [
            'token' => $res,
            'userInfo' => $repository->showApiFilter($userInfo)
        ];
        api_user_log($userInfo['id'], 1, $this->request->companyId, '短信验证码登录');
        return app('api_return')->success($data);
    }

    public function register(UsersRepository $repository)
    {

        validate(LoginValidate::class)->scene('captchaRegister')->check($this->request->param());
        $mobile = $this->request->param('mobile');
        $smsCode = $this->request->param('sms_code');
        $users = $repository->search(['mobile'=>$mobile],$this->request->companyId)->find();
        if($users){
            return app('api_return')->error("该手机号已被注册");
        }
        // 短信验证
        sms_verify($this->request->companyId, $mobile, $smsCode, config('sms.sms_type.REGISTER_VERIFY_CODE'));

        $userInfo = $repository->register($this->request->param(), $this->request->companyId);
        $userInfo = $repository->get($userInfo['id']);
        $res = $repository->createToken($userInfo);
        $userInfo->session_id = $res;

        $data = [
            'token' => $res,
            'userInfo' => $repository->showApiFilter($userInfo)
        ];
        api_user_log($userInfo['id'], 2, $this->request->companyId, '用户注册');
        task($userInfo['id'], 1, $this->request->companyId);
        task($userInfo['id'], 1, $this->request->companyId, '' , 'invite');
        return app('api_return')->success($data);
    }

    /**
     * 忘记密码
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function forgertPassword(UsersRepository $repository)
    {
        $param = $this->request->param([
            'mobile' => '',
            'password' => '',
            'sms_code' => ''
        ]);
        $data['type'] = 1;
        $userInfo = $repository->getUserByMobile($param['mobile'], $this->request->companyId);
        if (!$userInfo) {
            return app('api_return')->error('账号不存在');
        }
        sms_verify($this->request->companyId, $param['mobile'], $param['sms_code'], config('sms.sms_type.MODIFY_PASSWORD'));

        if ($userInfo['status'] != 1) {
            return app('api_return')->error('此账号已冻结');
        }
        unset($param['sms_code']);
        unset($param['mobile']);
        $repository->editPasswordInfo($userInfo, $param);
        $res = $repository->createToken($userInfo);
        $userInfo->session_id = $res;
        // 用户登陆事件
        event('user.login', $userInfo);
        $data = [
            'token' => $res,
            'userInfo' => $repository->showApiFilter($userInfo)
        ];
        return app('api_return')->success($data);
    }

    /**
     * 用户忘记密码 判断验证码是否正确
     *
     * @param UsersRepository $repository
     * @return mixed
     */
    public function verifiCodeTrue(UsersRepository $repository)
    {
        $param = $this->request->param([
            'mobile' => '',
            'sms_code' => ''
        ]);
        $data['type'] = 1;
        $userInfo = $repository->getUserByMobile($param['mobile'], $this->request->companyId);
        if (!$userInfo) {
            return app('api_return')->error('账号不存在');
        }
        sms_verify($this->request->companyId, $param['mobile'], $param['sms_code'], config('sms.sms_type.MODIFY_PASSWORD'));
    }


    /**
     *  TODO:
     *       应客户要求  在用户未注册的情况下  输入账号和密码直接注册并且登陆成功！
     * @param UsersRepository $repository
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function MobileLoginNew(UsersRepository $repository)
    {
        $param = $this->request->param([
            'mobile' => '',
        ]);
        $userInfo = $repository->getUserByMobile($param['mobile'], $this->request->companyId);
        if (!$userInfo) {
            /***************************2023.06.19  begin***********************************************/
            $userInfo = $repository->register($this->request->param(), $this->request->companyId);
            $userInfo = $repository->get($userInfo['id']);
            $res = $repository->createToken($userInfo);
            $userInfo->session_id = $res;
            $data = [
                'token' => $res,
                'userInfo' => $repository->showApiFilter($userInfo)
            ];
            api_user_log($userInfo['id'], 2, $this->request->companyId, '用户注册');
            task($userInfo['id'], 1, $this->request->companyId);
            return app('api_return')->success($data);
            /***************************2023.06.19  end***********************************************/
            //return app('api_return')->error('账号或密码错误');
        }
        if ($userInfo['status'] != 1) {
            return app('api_return')->error('此账号已冻结3');
        }
        $res = $repository->createToken($userInfo);
        $userInfo->session_id = $res;
        // 用户登陆事件
        event('user.login', $userInfo);
        $data = [
            'token' => $res,
            'userInfo' => $repository->showApiFilter($userInfo)
        ];
        api_user_log($userInfo['id'], 1, $this->request->companyId, '手机号一键登录');
        return app('api_return')->success($data);
    }

    public function queryUserCode(UsersRepository $repository)
    {
        $param = $this->request->param([
            'user_code' => '',
        ]);
        if(!isset($param['user_code']))  return app('api_return')->error('请输入邀请码');
        $userInfo = $repository->search([])->where(['user_code'=>$param['user_code']])
            ->with(['avatars' => function ($query) {
                $query->bind(['avatar' => 'show_src']);
            }])
            ->withAttr('mobile',function ($v,$data){
                return substr_replace($data['mobile'],'****',3,4);
            })
            ->field('id,nickname,head_file_id,user_code,mobile')->find();
        return app('api_return')->success($userInfo);
    }

    public function wechatOn(UsersRepository $repository){
        $res = $this->request->param();
        $data['openid'] = $res['openid'];
        if (!$res['openid']) return $this->error('openid错误');
        $is_user = app()->make(UsersRepository::class)->getWhere(['openid' => $data['openid']], 'id,mobile,openid');
        if (!$is_user) {
            if(!$res['mobile']) return $this->error('请绑定手机号');
            sms_verify($this->request->companyId, $res['mobile'], $res['sms_code'], config('sms.sms_type.MODIFY_MOBILE_VERIFY_CODE'));
            $getuSerInfo = app()->make(UsersRepository::class)->getWhere(['mobile'=>$res['mobile']],'id,mobile,openid');
            if ($getuSerInfo) {
                if($getuSerInfo['openid'])  {
                    return $this->error('手机号已绑定');
                }else{
                    $repository->update($getuSerInfo['id'], [
                        'openid' => $res['openid']
                    ]);
                    $userInfo = $repository->get($getuSerInfo['id']);
                }
            }else{
                $data['mobile'] = $res['mobile'];
                $data['user_code'] = $res['user_code'];
                $userInfo = $repository->register($data, $this->request->companyId);
                $userInfo = $repository->get($userInfo['id']);
            }
        } else{
            if (!$is_user['mobile']) {
                if(!$res['mobile']) return $this->error('请绑定手机号');
                sms_verify($this->request->companyId, $res['mobile'], $res['sms_code'], config('sms.sms_type.MODIFY_MOBILE_VERIFY_CODE'));
                $getuSerInfo = app()->make(UsersRepository::class)->getWhere(['mobile'=>$res['mobile']],'id,mobile,openid');
                if ($getuSerInfo) {
                    if($getuSerInfo['openid']) {
                        return $this->error('手机号已绑定');
                    }
                    app()->make(UsersRepository::class)->search(['openid'=>$res['openid']],$this->request->companyId)->delete();
                    $repository->update($getuSerInfo['id'], [
                        'openid' => $res['openid']
                    ]);
                    $userInfo = $repository->get($getuSerInfo['id']);
                }else{
                    $repository->update($is_user['id'], [
                        'mobile' => $res['mobile']
                    ]);
                    $userInfo = $repository->get($is_user['id']);
                }
            }else{
                $userInfo = $repository->get($is_user['id']);
            }
        }


        $repository->update($userInfo['id'], [
            'head_file_id' => $this->tu($userInfo,$res['head_file']),'nickname'=>$res['nickname']
        ]);
        $result = $repository->createToken($userInfo);
        $userInfo->session_id = $result;
        $data = [
            'token' => $result,
            'userInfo' => $repository->showApiFilter($userInfo)
        ];
        api_user_log($userInfo['id'], 2, $this->request->companyId, '用户微信登录');
        return app('api_return')->success($data);
    }

    public function tu($userInfo,$head_file)
    {
        $uoloads = new UploadFileModel();
        $is_file = $uoloads->where([
            'company_id' => $userInfo['company_id'],
            'group_id' => -1,
            'upload_path' =>$head_file,
            'file_name' => $head_file,
        ])->find();
      if(!$is_file){
          $file_id = $uoloads->insertGetId([
              'company_id' => $userInfo['company_id'],
              'group_id' => -1,
              'show_src' => $head_file,
              'upload_path' => $head_file,
              'storage_type' => 'local',
              'file_name' => $head_file,
              'file_type' => 'image/jpeg',
              'file_size' => 0,
              'source' => 'api',
              'file_md5' => '',
              'admin_id' => 0,
              'add_time' => date('Y-m-d H:i:s')
          ]);
          return $file_id;
      }else{
          return $is_file['id'];
      }
    }
    /**
     * 微信登录
     *
     * @param UsersRepository $repository
     * @return mixed
     */
    public function wechatLogin(UsersRepository $repository)
    {
        $data = $this->request->param();
        if(isset($data['code'])){
            $code = $data['code'];
            $res = $this->getWechatInfoByAPP($code);
            if ($res['code'] != 200) {
                $res = $res['msg'];
                return $this->error($res);
            }else{
                $data['openid'] = $res['data']['openid'];
                $data['nickname'] = $res['data']['nickname'];
                $data['unionid'] = $res['data']['unionid'];
                $is_user = app()->make(UsersRepository::class)->getWhere(['openid' => $data['openid']], 'id,mobile');
                if($is_user) {
                    $parent_id = app()->make(UsersPushRepository::class)->search(['user_id'=>$is_user['id'],'levels'=>1],$this->request->companyId)->value('parent_id');
                    return $this->success([
                        'open_id'=>$res['data']['openid'],
                        'is_phone' => $is_user['mobile'] ? 1 : 0,
                        'is_parent' => $parent_id ? 1: 0,
                    ]);
                }
                return $this->success(['open_id'=>$res['data']['openid'],'is_phone'=>0,'is_parent'=>0]);
            }
        }
        if(isset($data['openid'])){
            $is_user = app()->make(UsersRepository::class)->getWhere(['openid' => $data['openid']], 'id,mobile');
            if($is_user && $is_user['mobile']) {
                $parent_id = app()->make(UsersPushRepository::class)->search(['user_id'=>$is_user['id'],'levels'=>1],$this->request->companyId)->value('parent_id');
                return $this->success([
                    'open_id' => $data['openid'],
                    'is_phone' => $is_user['mobile'] ? 1 : 0,
                    'is_parent' => $parent_id ? 1: 0,
                ]);
            }
            return $this->success(['open_id'=>$data['openid'],'is_phone'=>0,'is_parent'=>0]);
        }
    }

    /**
     * 获取微信用户信息
     */
    protected function getWechatInfoByAPP($code)
    {
        if (!$code) $this->error('请填写正确的code');

        $app_id = web_config($this->request->companyId,'program.wechat.appid',''); // 开放平台APP的id
        $app_secret = web_config($this->request->companyId,'program.wechat.secret','');
        if(!$app_id) return ['code' => "502", 'msg' => "微信参数未配置"];
        if(!$app_secret) return ['code' => "502", 'msg' => "微信参数未配置"];
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$app_id}&secret={$app_secret}&code={$code}&grant_type=authorization_code";
        $data = $this->curl_get($url);

        if ($data['code'] != 200 || !isset($data['data'])) {
            return ['code' => "500", 'msg' => "登录错误" . $data['errmsg']];
        }
        $data = $data['data'];
        if (isset($data['errcode']) && $data['errcode']) {
            return ['code' => "502", 'msg' => "code错误," . $data['errmsg']];
        }
        // 请求用户信息
        $info_url = "https://api.weixin.qq.com/sns/userinfo?access_token={$data['access_token']}&openid={$data['openid']}";
        $user_info = $this->curl_get($info_url);
        file_put_contents('wechat_login.txt', var_export('user_info', true), FILE_APPEND);
        file_put_contents('wechat_login.txt', var_export($user_info, true), FILE_APPEND);

        if ($user_info['code'] != 200 || !isset($user_info['data'])) {
            return ['code' => "500", 'msg' => "登录错误" . $user_info['errmsg']];
        }
        $data = $user_info['data'];
        if (!isset($data['openid']) || !isset($data['nickname']) || !isset($data['headimgurl'])) {
            return ['code' => "500", 'msg' => "APP登录失败,网络繁忙"];
        }
        return ['code' => 200, 'data' => $data];
    }

// curl get请求
    protected function curl_get($url)
    {
        $header = [
            'Accept: application/json',
        ];
        $curl = curl_init();
        // 设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        // 设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, false);
        // 超时设置,以秒为单位
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);

        // 超时设置，以毫秒为单位
        // curl_setopt($curl, CURLOPT_TIMEOUT_MS, 500);

        // 设置请求头
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        // 设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        // 执行命令
        $data = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        // 显示错误信息
        if ($error) {
            return ['code' => 500, 'msg' => $error];
        } else {
            return ['code' => 200, 'msg' => 'success', 'data' => json_decode($data, true)];
        }
    }


}