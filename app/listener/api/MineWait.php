<?php
declare (strict_types=1);

namespace app\listener\api;



use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\mine\MineWaitRepository;

class MineWait
{
    public function handle($data)
    {
        /** @var MineUserRepository $mineUserRepository */
        $mineUserRepository = app()->make(MineUserRepository::class);
        $mineUser = $mineUserRepository->search([])->where(['id'=>$data['id']])->find();
        $product = get_rate($data['num'],$mineUser['company_id']);
        $time = time() - $mineUser['edit_time'];
        $rate = round((floor($time / 10) *$product['rate']),7);
        if($rate > 0){
            /** @var MineWaitRepository $mineWaitRepository */
            $mineWaitRepository =  app()->make(MineWaitRepository::class);
            $re = $mineWaitRepository->addInfo($mineUser['company_id'],['uuid'=>$mineUser['uuid'],'money'=>$rate,'status'=>2]);
            if($re){
                $mineUserRepository->editInfo($mineUser,['edit_time'=>time()]);
            }
        }
    }
}
