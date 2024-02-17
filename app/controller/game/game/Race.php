<?php

namespace app\controller\game\game;

use app\common\repositories\users\UsersRepository;
use app\controller\api\Base;

class Race extends Base
{

    public function getUserInfo(UsersRepository $repository){
        $data = $repository->getGameInfo($this->request->userInfo());
        return json(['code'=>1,'msg'=>'','data'=>$data]);
    }
    public function decbalance(UsersRepository $repository)
    {
        $param = $this->request->param(['coinType'=>'','gameName'=>'','changePoints'=>'','batch_no'=>'']);
        if(!$param['changePoints'] || $param['changePoints']  <= 0) return json(['code'=>400,'message'=>'投注金额错误！']);
        $data = $repository->decRace($param,$this->request->userInfo());
        return json(['code'=>1,'message'=>'投入成功','data'=>$data]);
    }

    public function incbalance(UsersRepository $repository)
    {
        $param = $this->request->param(['requestNo'=>'','list'=>'','coinType'=>'','gameName'=>'','changePoints'=>'','batch_no'=>'']);
        if(!$param['coinType']) return json(['code'=>400,'message'=>'类型错误！']);
        $data = $repository->incRace($param,$this->request->userInfo());
        return json(['code'=>1,'message'=>'','data'=>$data]);
    }


}