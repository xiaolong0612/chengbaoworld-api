<?php

namespace app\controller\company\poster;

use app\common\repositories\poster\PosterRecordRepository;
use app\common\repositories\poster\PosterSiteRepository;
use app\validate\poster\PosetrRecordValidate;
use app\controller\company\Base;
use think\App;

class PosterRecord extends Base
{
    protected $repository;

    public function __construct(App $app, PosterRecordRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }

    protected function commonParams()
    {
        $posterSiteData = $this->repository->getIsType();
        $this->assign([
            'posterSiteData' => $posterSiteData
        ]);
    }

    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0,'data' => $data['list'],'count' => $data['count'] ]);
        }
        return $this->fetch('poster/record/list', [
            'addAuth' => company_auth('companyPosterRecordAdd'),
            'editAuth' => company_auth('companyPosterRecordEdit'),
            'delAuth' => company_auth('companyPosterRecordDel')
        ]);
    }

    /**
     * 添加广告
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'is_show' => '',
                'open_type' => '',
                'open_url' => '',
                'ad_picture' => '',
                'site_id' => '',
                'sort' => '',
                'start_time',
                'expire_time',
            ]);
            validate(PosetrRecordValidate::class)->scene('add')->check($param);
            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                if ($res) {
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            $this->commonParams();
            return $this->fetch('poster/record/add');
        }
    }

    /**
     * 编辑广告
     */
    public function edit()
    {
        $id = $this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->getDetail($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'is_show' => '',
                'open_type' => '',
                'open_url' => '',
                'ad_picture' => '',
                'site_id' => '',
                'sort' => '',
                'start_time',
                'expire_time',
            ]);
            validate(PosetrRecordValidate::class)->scene('edit')->check($param);
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->commonParams();
            return $this->fetch('poster/record/edit', [
                'info' => $info,
            ]);
        }
    }

    /**
     * 删除广告
     */
    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}