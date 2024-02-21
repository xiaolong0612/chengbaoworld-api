<?php

namespace app\controller\api\user;

use app\common\repositories\users\UsersCertRepository;
use app\common\services\RealNameCertService;
use app\controller\api\Base;
use app\validate\users\UsersCertValidate;
use think\App;
use think\exception\ValidateException;

class UserCert extends Base
{
    protected $repository;

    public function __construct(App $app, UsersCertRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }

    /**
     * 申请个人认证
     */
    public function applyCert()
    {
        $info = $this->request->userInfo()->append(['personal_auth']);

        $param = $this->request->param([
            'username' => '',
            'number' => '',
            'idcard_front_photo' => '',
            'idcard_back_photo' => '',
        ]);
        $param['cert_status'] = 1;
        $param['review_status'] = 0;
        $param['user_id'] = $this->request->userId();

        validate(UsersCertValidate::class)->scene('add')->check($param);

        //  认证逻辑
        //验证年纪
        if (strlen($param['number']) != 16 && strlen($param['number']) != 18) return $this->error('身份证格式错误!');
        $age = (date('Y') - substr($param['number'], 6, 4) - 1) + (date('md') >= substr($param['number'], 10, 4) ? 1 : 0);
        if ($age < 20) {
            $this->error('未满20周岁不允许实名');
        }


        if ($info['personal_auth'] == 2) {
            return app('api_return')->success('审核成功，请勿重复提交');
        }
        if ($info['personal_auth'] == 1) {
            return app('api_return')->error('审核中，请耐心等待');
        }
        $certType = (int)web_config($this->request->companyId, 'program.cert.cert_type', 1);
        if ($certType == 2) {
            $result = RealNameCertService::init($this->request->companyId)->twoInfoCert($param['username'], $param['number']);
            if ($result['status'] != 1) {
                return $this->error($result['msg'] ?? '认证失败');
            }
            $param['cert_status'] = 2;
            $param['review_status'] = 1;
        }

        $res = $this->repository->addCert($param, $info, $this->request->companyId);
        if ($res) {
            return app('api_return')->success('提交成功');
        } else {
            return app('api_return')->error('提交失败');
        }
    }


    /**
     * 个人认证详情
     */
    public function userCertInfo()
    {
        $res = $this->repository->userCertInfo($this->request->userId());
        if (!$res) {
            return app('api_return')->error('数据不存在');
        }
        return app('api_return')->success($res);
    }

    /**
     * 是否人脸认证
     */
    public function isFaceCert()
    {
        $res = $this->repository->isFaceCert($this->request->userId());
        if ($res === false) {
            return app('api_return')->error('暂无实名信息');
        }
        return app('api_return')->success($res);
    }
}