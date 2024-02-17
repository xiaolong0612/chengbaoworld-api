<?php

namespace app\controller\company\pool;

use app\common\model\users\UsersPoolModel;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersRepository;
use app\controller\company\Base;
use app\validate\pool\SaleValidate;
use think\App;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;
use think\validate\ValidateRule;

class UserPool extends Base
{
    protected $repository;

    public function __construct(App $app, UsersPoolRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }
    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'mobile'=>'',
                'title'=>'',
                'no'=>'',
                'status'=>'',
                'type'=>'',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'] ]);
        }
        return $this->fetch('pool/user/list', [
            'saleAuth' => company_auth('companyUserPoolSale'),##
            'importFileAuth' => company_auth('companyGiveUserPoolSaleBatch'),##
            'importBlackFileAuth' => company_auth('companyGiveBlackUserPoolSaleBatch'),##
            'importBlackFileDestroyAuth' => company_auth('companyGiveBlackUserPoolSaleDestroy'),##
            'autoMarkAddAuth' => company_auth('companyUserPoolSaleAutoMarkAddBatch'),##
            'autoMarkEndAuth' => company_auth('companyUserPoolSaleAutoMarkEndBatch'),##
        ]);
    }

    public function sale()
    {
        $ids = (array)$this->request->param('ids');
        $is_sale = $this->request->param('is_sale');
        try {
            $data = $this->repository->updates($ids,['is_sale'=>$is_sale]);
            if ($data) {
                admin_log(4, '禁止会员卡牌寄售 ids:' . implode(',', $ids), $data);
                return $this->success('操作成功');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }


    /**
     * 空投用户卡牌
     * @return string|\think\response\Json|\think\response\View
     * @throws \Exception
     */
    public function giveUserPool()
    {
        $id = (array)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'pool_id' => '',
                'num' => '',
                'remark' => ''
            ]);
            if ($param['num'] <= 0) {
                return $this->error('空投数量必须大于0');
            }
            if($param['num'] > 500) return $this->error('单次空投数量为500');
            /** @var MineRepository $mineRepository */
            $mineRepository = app()->make(MineRepository::class);
            $mine = $mineRepository->search(['level'=>1,'status'=>1],$this->request->companyId)->find();
            if(!$mine || !isset(web_config($this->request->companyId, 'program')['output'])) throw new ValidateException('矿产参数错误');

            $poolSaleRepository = app()->make(PoolSaleRepository::class);
            $poolInfo = $poolSaleRepository->get($param['pool_id']);
            $totalNum = count($id) * $param['num'];
            if($poolInfo['stock'] < $totalNum) return $this->error('库存不足');
            $data = $this->repository->batchGiveUserPool($id,$param,$this->request->companyId);
            company_user_log(3, '批量空投闪卡 id:' . implode(',', $id), $param);
            return $this->success('投送成功');
        } else {
            /**
             * @var PoolSaleRepository $poolSaleRepository
             */
            $poolSaleRepository = app()->make(PoolSaleRepository::class);
            $poolData = $poolSaleRepository->getCascaderData($this->request->companyId);
            return $this->fetch('pool/user/give', [
                'poolData' => $poolData,
            ]);
        }
    }



    /**
     * 导入excel
     */
    public function giveUserPoolBatch()
    {
        $files = $this->request->file();
        validate(['file' => 'fileSize:102400|fileExt:xls,xlsx'])->check($files);
        $file = $files['file'] ?? null;
        if (!$file) {
            return $this->error('请上传文件');
        }
        $filePath = $file->getPathName();
        //载入excel文件
        $excel = \PHPExcel_IOFactory::load($filePath);
        //读取第一张表
        $sheet = $excel->getSheet(0);
        //获取总行数
        $row_num = $sheet->getHighestRow();
        //获取总列数
        $col_num = $sheet->getHighestColumn();
        $import_data = []; //数组形式获取表格数据
        for ($i = 2; $i <= $row_num; $i++) {
            $orderNo = trim($sheet->getCell("A" . $i)->getValue());
            $deviceNo = trim($sheet->getCell("B" . $i)->getValue());
            if ($orderNo && $deviceNo) {
                $import_data[] = [
                    'mobile' => trim($sheet->getCell("A" . $i)->getValue()),
                    'num' => force_to_string(trim($sheet->getCell("B" . $i)->getValue())),
                    'pool_id' => force_to_string(trim($sheet->getCell("C" . $i)->getValue())),
                ];
            }
        }
        $num = 0;
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        foreach ($import_data as $k => $v) {
            $userInfo = $usersRepository->getSearch([]) ->where('mobile', $v['mobile'])->find();
            if(!$userInfo){
                continue;
            }
            if ($v['num'] <= 0) {
                continue;
            }
            $param['pool_id'] = $v['pool_id'];
            $param['num'] = $v['num'];
            $this->repository->giveUserPoolInfo($userInfo,$param,$this->request->companyId);
            $num++;
        }
        return $this->success('成功投放' . $num . '个用户');
    }


    /**
     * 批量白名单
     */
    public function giveBlackUserPoolBatch()
    {
        $files = $this->request->file();
        validate(['file' => 'fileSize:102400|fileExt:xls,xlsx'])->check($files);
        $file = $files['file'] ?? null;
        if (!$file) {
            return $this->error('请上传文件');
        }
        $filePath = $file->getPathName();
        //载入excel文件
        $excel = \PHPExcel_IOFactory::load($filePath);
        //读取第一张表
        $sheet = $excel->getSheet(0);
        //获取总行数
        $row_num = $sheet->getHighestRow();
        //获取总列数
        $col_num = $sheet->getHighestColumn();
        $import_data = []; //数组形式获取表格数据
        for ($i = 2; $i <= $row_num; $i++) {
            $import_data[] = [
                'mobile' => trim($sheet->getCell("A" . $i)->getValue()),
                'is_sale' => $sheet->getCell("B" . $i)->getValue(),
            ];
        }
        $num = 0;
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $usersPoolRepository = app()->make(UsersPoolRepository::class);
        $no = app()->make(PoolOrderNoRepository::class);
        foreach ($import_data as $k => &$v) {
            $userInfo = $usersRepository->getSearch([]) ->where('mobile', $v['mobile'])->where(['company_id'=>$this->request->companyId])->find();
            if(!$userInfo){
                continue;
            }
            $userPool = $usersPoolRepository->getSearch([])
                ->where(['uuid'=>$userInfo['id'],'company_id'=>$this->request->companyId])
                ->select();
            foreach ($userPool as &$item)
            {
                if ($item['is_sale'] != $v['is_sale'])
                {
                    $usersPoolRepository->getSearch([])->where(['id'=>$item['id']])->update(['is_sale'=>$v['is_sale']]);
                }
            }
            $num++;
        }
        return $this->success('已为' . $num . '个用户已修改！');
    }

    /**
     * 批量销毁
     */
    public function giveBlackUserPoolDestroy()
    {
        $files = $this->request->file();
        validate(['file' => 'fileSize:102400|fileExt:xls,xlsx'])->check($files);
        $file = $files['file'] ?? null;
        if (!$file) {
            return $this->error('请上传文件');
        }
        $filePath = $file->getPathName();
        //载入excel文件
        $excel = \PHPExcel_IOFactory::load($filePath);
        //读取第一张表
        $sheet = $excel->getSheet(0);
        //获取总行数
        $row_num = $sheet->getHighestRow();
        //获取总列数
        $col_num = $sheet->getHighestColumn();
        $import_data = []; //数组形式获取表格数据
        for ($i = 2; $i <= $row_num; $i++) {
            $import_data[] = [
                'mobile' => trim($sheet->getCell("A" . $i)->getValue()),
                'pool_id' => $sheet->getCell("B" . $i)->getValue(),
                'no' => $sheet->getCell("C" . $i)->getValue(),
            ];
        }
        $num = 0;
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $usersPoolRepository = app()->make(UsersPoolRepository::class);
        $no = app()->make(PoolOrderNoRepository::class);
        foreach ($import_data as $k => &$v) {
            $userInfo = $usersRepository->getSearch([]) ->where('mobile', $v['mobile'])->find();
            if(!$userInfo){
                continue;
            }
            $userPool = $usersPoolRepository->getSearch([])
                ->where(['uuid'=>$userInfo['id'],'company_id'=>$this->request->companyId,'pool_id'=>$v['pool_id'],'no'=>$v['no']])
                ->update(['status'=>6]);
            $no->getSearch([])->where(['pool_id'=>$v['pool_id'],'no'=>$v['no']])->update(['chain_status'=>9]);
            $num++;
        }
        return $this->success('已销毁' . $num . '个卡牌');
    }

    public function autoMarkAdd()
    {
        $id = (array)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'auto_price' => '',
            ]);
            if ($param['auto_price'] <= 0) {
                return $this->error('金额必须大于0');
            }
            $data = $this->repository->batchAutoMarkAdd($id,$param['auto_price']);
            if ($data) {
                company_user_log(4, '开启自动上架 ids:' . implode(',', $id), $data);
                return $this->success('操作成功');
            } else {
                return $this->error('操作失败');
            }
        } else {
            return $this->fetch('pool/user/auto_mark');
        }
    }



    public function autoMarkEnd()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchAutoMarkEnd($ids);
            if ($data) {
                company_user_log(4, '关闭自动上架 ids:' . implode(',', $ids), $data);
                return $this->success('操作成功');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }
}