<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2020 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace app\validate\company;

use think\Validate;

class LoginValidate extends Validate
{
    protected $rule = [
        'username' => 'require',
        'password' => 'require',
        'verify_code' => 'require|captcha',
    ];
    protected $message = [
        'username.require' => ['code' => -1, 'msg' => '登录账号不能为空'],
        'password.require' => ['code' => -1, 'msg' => '登录密码不能为空'],
        'verify_code.require' => ['code' => -1, 'msg' => '请输入验证码'],
        'verify_code.captcha' => ['code' => -2, 'msg' => '验证码错误'],
    ];
    protected $scene = [
        'login' => ['username', 'password'],
        'captchalogin' => ['username', 'password', 'verify_code']
    ];
}