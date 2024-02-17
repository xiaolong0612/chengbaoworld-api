<?php

namespace app\controller\api\system;

use api\sms\exception\SmsException;
use app\common\services\SmsService;
use app\controller\api\Base;
use think\captcha\facade\Captcha;

class Code extends Base
{
    /**
     * 生成图形验证码
     */
    public function createVerifyCode()
    {
        $imgData = Captcha::create();
        if ((int)$this->request->param('is_img') === 1) {
            return $imgData;
        }

        return $this->success('data:image/jpg;base64,' . base64_encode($imgData->getContent()));
    }


    public function getToken()
    {
        return $this->success('', 'ok');
    }

    /**
     * 发送手机验证码
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendPhoneVerifyCode()
    {
        $phone = $this->request->param('mobile', '', 'trim');
        $type = $this->request->param('type', '', 'intval');

        try {
            SmsService::init($this->request->companyId)->sendVerifyCode($phone, $type);
        } catch (SmsException $e) {
            if ($e->getCode() == 1001) {
                return $this->error($e->getMessage(), 400);
            } else {
                return $this->error($e->getMessage());
            }
        }

        return $this->successText('发送成功');
    }
}