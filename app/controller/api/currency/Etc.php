<?php

namespace app\controller\api\currency;

use app\common\repositories\currency\EtcRepository;
use app\controller\api\Base;

class Etc extends Base
{

    public function withdrawal(EtcRepository $repository){
        $data = $this->request->param(['num'=>'','pay_password'=>'','hash'=>'']);
        if(!$data['num'] || $data['num'] <0) return $this->error('数量错误!');
        if(!$data['pay_password']) return $this->error('请输入交易密码!');
        if(!$data['hash']) return $this->error('请输入hash地址!');
        return $this->success($repository->withdrawal($data,$this->request->userInfo(),$this->request->companyId));
    }

    public function getLog(EtcRepository $repository){
        [$page,$limit] = $this->getPage();
        return $this->success($repository->getApiList($page,$limit,$this->request->userInfo(),$this->request->companyId));
    }
}