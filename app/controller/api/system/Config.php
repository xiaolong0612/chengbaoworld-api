<?php

namespace app\controller\api\system;

use app\common\repositories\users\UsersRepository;
use app\common\services\PaymentService;
use app\controller\api\Base;
use app\common\repositories\system\SystemPactRepository;
use app\common\repositories\system\sms\SmsConfigRepository;
use app\jobs\MineProductDJob;
use think\App;
use think\facade\Db;

class Config extends Base
{
    /**
     * 获取网站基本信息
     */
    public function getSiteInfo()
    {
        $config = web_config($this->request->companyId, 'site');
        return $this->success($config);
    }

    /**
     * 获取短信配置
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSmsConfig(SmsConfigRepository $repository)
    {
        $config = $repository->getSmsConfig($this->request->companyId);
        $data = [
            'verify_img_code' => $config['verify_img_code'] ?? '',
            'send_sms_time_out' => $config['send_sms_time_out'] ?? ''
        ];

        return $this->success($data);
    }

    /**
     * 获取平台协议
     */
    public function getPactInfo(SystemPactRepository $repository)
    {
        $type = (int)$this->request->param('type');
        $pactInfo = $repository->getPactInfo($type, $this->request->companyId);
        if (empty($pactInfo)) {
            return $this->error('协议不存在');
        }
        return $this->success($pactInfo['content'] ?? '');
    }

    /**
     * 获取APP版本信息
     */
    public function getversionConfig()
    {
        $config = web_config($this->request->companyId, 'program');
        if (empty($config)) {
            return $this->error('参数未设置');
        }
        $config = [
            'andior' => [
                'key' => $config['version']['andior']['key'],
                'down_url' => $config['version']['andior']['down_url'],
            ],'ios' => [
                'key' => $config['version']['ios']['key'],
                'down_url' => $config['version']['ios']['down_url'],
            ],'update' => [
                'updatetext' => $config['version']['update']['text'],
            ], 'upload' => [
                'iosUploadUrl' => $config['version']['upload']['iosUploadUrl'],
                'fileSize' => $config['version']['upload']['fileSize'],
                'androidUrl' => $config['version']['upload']['androidUrl'],
                'update_msg' => $config['version']['upload']['update_msg'],
                'version' => $config['version']['upload']['version'],
                'officialUrl' => $config['version']['upload']['officialUrl'],
                'isOpen' => $config['version']['upload']['isOpen'],
            ]
        ];
        return $this->success($config);
    }

    public function getMinePool(){
        $config = web_config($this->request->companyId, 'program');
        if (!isset($config['mine']['pool'])) {
            return $this->error('参数未设置');
        }
        if(isset($config['sb'])){
            if(empty($config['sb']['start_time'])){
               $sb_day=isset($config['sb'])?$config['sb']['day'] :0;
            }else{
                $sb_day=intval((time()- strtotime($config['sb']['start_time']))/86400);
            }
        }else{
            $sb_day=0;
        }
        //  isset($config['sb'])?$config['sb']['day'] :0
        // isset($config['sb'])?$config['sb']['people'] :0,
        // $config = [
        //     'price' => $config['mine']['pool']['price'] ?: '',
        //     'imgs' => $config['mine']['pool']['imgs'] ?: '',
        //     'one' => isset($config['node']['one']['rate']) ? $config['node']['one']['rate'] : 0,
        //     'two' => isset($config['node']['two']['rate']) ? $config['node']['two']['rate'] :0,
        //     'is_open' => isset($config['is_open']) ? $config['is_open'] : 2,
        //     'sb_people' =>  Db::table('users')->count(),
        //     'sb_day' =>$sb_day,
        //     'sb_baoshi' => isset($config['sb'])?$config['sb']['baoshi'] :0,
        //     'sb_unsetNum' => isset($config['sb'])?$config['sb']['unsetNum'] :0,
        //     'sb_cityNum' => isset($config['sb'])?$config['sb']['cityNum'] :0,
        //     'sb_cityTotal' => isset($config['sb'])?$config['sb']['cityTotal'] :0,
        //     'filings'=> web_config($this->request->companyId, 'site')['filings']
        // ];
         $config = [
            'price' => $config['mine']['pool']['price'] ?: '',
            'imgs' => $config['mine']['pool']['imgs'] ?: '',
            'one' => isset($config['node']['one']['rate']) ? $config['node']['one']['rate'] : 0,
            'two' => isset($config['node']['two']['rate']) ? $config['node']['two']['rate'] :0,
            'is_open' => isset($config['is_open']) ? $config['is_open'] : 2,
            'sb_people' => Db::table('users')->count(),
            'sb_day' =>$sb_day ,
            'sb_baoshi' =>round(Db::table('mine_user')->sum('product'),2),
            'sb_unsetNum' =>round(Db::table('mine_user')->sum('product')-Db::table('users')->sum('food'),2),
            'sb_cityNum' => count(array_unique(Db::table('mine_user')->where('level','>',1)->column('uuid'))),
            'sb_cityTotal' =>Db::table('mine_user')->where('level','>',1)->count(),
            'filings'=> web_config($this->request->companyId, 'site')['filings']
        ];
        return $this->success($config);
    }
    // 签名
    function get_sign($paramArray,$mchKey){
        $p =ksort($paramArray);
            
             if($p){
            
             $str = '';
            
             foreach ($paramArray as $k=>$val){
            
             $str  .=  $k  .'='  .  $val  .  '&';
            
                    }
            
             $strs = rtrim($str, '&');
            
                }
            
             $sign = md5($strs.'&key='.$mchKey);
             return $sign;
    }

    // 获取余额
    public function getbalance()
    {
        $data = input('param.');
        if (empty($data['username'])) {
            return $this->error('请输入正确的用户');
        }
        if (empty($data['sign'])) {
            return $this->error('签名不存在');
        }
        $paramArray = array(
            "username" => $data['username'], //商户ID
            //支付产品ID
        );
        $mchKey = 'oemiE4NK4g4FGE2d4Gg2G457ge1DG';
        $sign = $this->get_sign($paramArray, $mchKey);
        if ($sign != $data['sign']) {
            return $this->error('签名不正确');
        }
        $user = Db::table('users')->where('unquied', $data['username'])->find();
        if (!empty($user)) {
            return $this->success(['source'=>$user['food']]);
        } else {
            return $this->error('没有找到用户');
        }
    }

    //余额变动及分销
    public function addbalance()
    {
        $data = input('param.');
        if (empty($data['username'])) {
            return $this->error('请输入正确的用户');
        }
        if (empty($data['sign'])) {
            return $this->error('签名不存在');
        }
        $paramArray = array(
            "username" => $data['username'], //商户ID
            'name' => $data['name'],
            'amount' => $data['amount'],
            'type' => $data['type']

        );
        $mchKey = 'oemiE4NK4g4FGE2d4Gg2G457ge1DG';


        $sign = $this->get_sign($paramArray, $mchKey);

        if ($sign != $data['sign']) {
            return $this->error('签名不正确');
        }
        $config = web_config($this->request->companyId, 'program');
        $user = Db::table('users')->where('unquied', $data['username'])->find();
        if (empty($user)) {
            return $this->error('没有找到用户');
        }
        if ($data['type'] == 1) {
            //   加宝石扣除平台的利益
            $plat_rate = $config['mine']['tokens']['plat_rate'];
            $plat_amount = sprintf("%01.2f", $data['amount'] * $plat_rate / 100);
            $food = sprintf("%01.2f", $data['amount'] - $plat_amount);
            // 用户加宝石开始

            // Db::table('users')->where('unquied',$data['username'])->setInc('food',$food);
            $log = array(
                'company_id' => $user['company_id'],
                'user_id' => $user['id'],
                'amount' => $food,
                'before_change' => $user['food'],
                'after_change' => $user['food'] + $food,
                'log_type' => '',
                'remark' => '游戏收入',
                'track_port' => '',
                'add_time' => date('Y-m-d H:i:s', time()),
                'is_frends' => 0
            );

            // Db::name('users_food_log')->insert($log);
            //用户加宝石结束
            // 判断该用户的上级
            // 判断自己的类型
            $agent = Db::name('agent')->where('uuid', $user['id'])->find();
            if (!empty($agent)) {
                //自己是代理
                //   重复下方的逻辑

            } else {
                //自己不是代理
                $parent_id = Db::name('users_push')->where('user_id', $user['id'])->value('parent_id');
                // var_dump($parent_id);die();
                if (!empty($parent_id)) {
                    //判断上级
                    $parent_member = Db::name('users')->where('id', $parent_id)->find();
                    $parent_agent = Db::name('agent')->where('uuid', $parent_id)->find();
                    // var_dump($agent);die();
                    if (!empty($parent_agent)) {
                        //上级是店长
                        // 在判断上二级
                        $parent_two = Db::name('users_push')->where('user_id', $parent_member['id'])->value('parent_id');
                        if (!empty($parent_two_id)) {
                            //  判断上二级类型是否是店长
                            $parent_agent_two = Db::name('agent')->where('uuid', $parent_two_id)->find();
                            if (!empty($parent_agent_two)) {
                                //  上二级为店长
                                $parent_two_member = Db::name('users')->where('id', $parent_two_id)->find();
                                $all_parent_food = sprintf("%01.2f", $plat_amount * $parent_two_member['rate'] / 100);

                                $parent_food = sprintf("%01.2f", $all_parent_food * $parent_member['rate'] / 100);
                                $parent_two_food = sprintf("%01.2f", $all_parent_food - $parent_food);
                                //两个上级都操作宝石余额
                                //  $parent_food=sprintf("%01.2f",$plat_amount*$parent_member['rate']/100);
                                // Db::table('users')->where('id',$parent_id)->setInc('food',$food);
                                // $log=array(
                                //     'company_id'=>$parent_member['company_id'],
                                //     'user_id'=>$parent_member['id'],
                                //     'amount'=>$parent_food,
                                //     'before_change'=>$parent_member['food'],
                                //     'after_change'=>$parent_member['food']+$parent_food,
                                //     'log_type'=>'',  
                                //     'remark'=>'游戏收入',
                                //     'track_port'=>'',
                                //     'add_time'=>date('Y-m-d H:i:s',time()),
                                //     'is_frends'=>0
                                //     );
                                // Db::name('users_food_log')->insert($log);
                            } else {
                                //上二级位普通用户
                                $parent_food = sprintf("%01.2f", $plat_amount * $parent_member['rate'] / 100);
                                // Db::table('users')->where('id',$parent_id)->setInc('food',$food);
                                $log = array(
                                    'company_id' => $parent_member['company_id'],
                                    'user_id' => $parent_member['id'],
                                    'amount' => $parent_food,
                                    'before_change' => $parent_member['food'],
                                    'after_change' => $parent_member['food'] + $parent_food,
                                    'log_type' => '',
                                    'remark' => '游戏收入',
                                    'track_port' => '',
                                    'add_time' => date('Y-m-d H:i:s', time()),
                                    'is_frends' => 0
                                );
                                // Db::name('users_food_log')->insert($log);
                            }

                        } else {
                            //不存在上二级
                            $parent_food = sprintf("%01.2f", $plat_amount * $parent_member['rate'] / 100);
                            // Db::table('users')->where('id',$parent_id)->setInc('food',$food);
                            $log = array(
                                'company_id' => $parent_member['company_id'],
                                'user_id' => $parent_member['id'],
                                'amount' => $parent_food,
                                'before_change' => $parent_member['food'],
                                'after_change' => $parent_member['food'] + $parent_food,
                                'log_type' => '',
                                'remark' => '游戏收入',
                                'track_port' => '',
                                'add_time' => date('Y-m-d H:i:s', time()),
                                'is_frends' => 0
                            );
                            // Db::name('users_food_log')->insert($log);
                        }
                    } else {
                        //上级是普通用户

                        //普通用户的宝石加
                        $parent_food = sprintf("%01.2f", $plat_amount * $parent_member['rate'] / 100);
                        // Db::table('users')->where('id',$parent_id)->setInc('food',$food);
                        $log = array(
                            'company_id' => $parent_member['company_id'],
                            'user_id' => $parent_member['id'],
                            'amount' => $parent_food,
                            'before_change' => $parent_member['food'],
                            'after_change' => $parent_member['food'] + $parent_food,
                            'log_type' => '',
                            'remark' => '游戏收入',
                            'track_port' => '',
                            'add_time' => date('Y-m-d H:i:s', time()),
                            'is_frends' => 0
                        );
                        // Db::name('users_food_log')->insert($log);


                    }
                }
            }


        } else {
            //减宝石

            // 用户减宝石开始
            Db::startTrans();
            try {
                $food = $data['amount'];
                if ($user['food'] < $food) {
                    return $this->error('该用户宝石不足');
                }
                // Db::table('users')->where('unquied',$data['username'])->setDec('food',$food);
                $log = array(
                    'company_id' => $user['company_id'],
                    'user_id' => $user['id'],
                    'amount' => $food,
                    'before_change' => $user['food'],
                    'after_change' => $user['food'] + $food,
                    'log_type' => '',
                    'remark' => '游戏收入',
                    'track_port' => '',
                    'add_time' => date('Y-m-d H:i:s', time()),
                    'is_frends' => 0
                );


                // Db::name('users_food_log')->insert($log);
                Db::commit();
                echo json_encode(array('code' => 1, 'msg' => '余额变动成功'));
                die();
                //用户加宝石结束
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }

        }
        //   if($data[''])
        //   var_dump('加减用户的宝石操作');
    }
    
}