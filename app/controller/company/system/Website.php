<?php

namespace app\controller\company\system;

use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\system\ConfigRepository;
use app\controller\company\Base;
use think\App;
use think\facade\Db;

class Website extends Base
{
    protected $repository;

    public function __construct(App $app, ConfigRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }

    /**
     * 网站设置
     *
     * @return string|\think\response\Json
     * @throws \Exception
     */
    public function siteInfo()
    {
        if ($this->request->isPost()) {
            $this->repository->modifyConfig('site', $this->request->post(), $this->request->companyId);
            company_user_log(2, '设置网站信息', $this->request->post());
            return json()->data([ 'code' => 0, 'msg' => '修改成功']);
        } else {
            return $this->fetch('system/website/site_info', [
                'info' => web_config($this->request->companyId, 'site'),
            ]);
        }
    }

    /**
     * 应用设置
     *
     * @return string|\think\response\Json
     * @throws \Exception
     */
    public function programInfo()
    {
        if ($this->request->isPost()) {
            $this->repository->modifyConfig('program', $this->request->post(), $this->request->companyId);
            company_user_log(2, '设置应用配置', $this->request->post());
            return json()->data([ 'code' => 0, 'msg' => '修改成功']);
        } else {
            $info = web_config($this->request->companyId, 'program');
          
            $info['sb']['people']= Db::table('users')->count();
            $info['sb']['baoshi']=Db::table('mine_user')->sum('product');
            $info['sb']['unsetNum']=$info['sb']['baoshi']-Db::table('users')->sum('food');
            $info['sb']['cityNum']=count(array_unique(Db::table('mine_user')->where('level','>',1)->column('uuid')));
            $info['sb']['cityTotal']= Db::table('mine_user')->where('level','>',1)->count();
            $imgs = isset($info['mine']['pool']['imgs']) &&  $info['mine']['pool']['imgs']? explode(',',$info['mine']['pool']['imgs']):[];
            return $this->fetch('system/website/program_info', [
                'info' =>$info,
                'imgs' =>$imgs,
                'productAuth' => company_auth('companyProductList'),
                'cardPackAuth' => company_auth('companyCardPackList'),
                'forumAuth' => company_auth('companyForumList'),
                'cochain' => config('cochain.default'),
                'cardAuth' => company_auth('companyCardPackList'),
                'companyId' => $this->request->companyId,
            ]);
        }
    }


    /**
     * 注册参数
     * @return string|\think\response\Json
     * @throws \Exception
     */
    public function registerInfo()
    {
        if ($this->request->isPost()) {
            $this->repository->modifyConfig('reg', $this->request->post(), $this->request->companyId);
            company_user_log(2, '设置应用配置', $this->request->post());
            return json()->data([ 'code' => 0, 'msg' => '修改成功']);
        } else {
            $info = web_config($this->request->companyId, 'reg');
            return $this->fetch('system/website/reg_info', [
                'info' =>$info,
            ]);
        }
    }

}