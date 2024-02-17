<?php

namespace app\common\services\payment;


use app\common\repositories\pool\PoolShopOrder;
use app\common\repositories\users\UsersRepository;
use app\common\services\PaymentService;
use think\exception\ValidateException;

/**
 * 账号余额支付参数处理
 * Class BalancePyamentService
 * @package app\data\service\payment
 */
class BalancePyamentService extends PaymentService {
    /**
     * 订单信息查询
     * @param string $orderNo
     * @return array
     */
    public function query(string $orderNo): array {
        return [];
    }

    /**
     * 支付通知处理
     * @return string
     */
    public function notify(): string {
        return 'SUCCESS';
    }

    /**
     * 创建订单支付参数
     * @param string $openid 用户OPENID
     * @param string $orderNo 交易订单单号
     * @param string $payAmount 交易订单金额（元）
     * @param string $payTitle 交易订单名称
     * @param string $payRemark 订单订单描述
     * @param string $payReturn 完成回跳地址
     * @param string $payImage 支付凭证图片
     * @return array
     */
    public function create(string $openid, string $orderNo, string $payAmount, string $payTitle, string $payRemark, string $payReturn = '', string $payImage = '', int $companyId = null): array {

        /** @var PoolShopOrder $shopOrder */
        $shopOrder = $this->app->make(PoolShopOrder::class);
        $order = $shopOrder->search(['order_id' => $orderNo])->find();
        if ($order['status'] !== 1) throw new ValidateException("不可发起支付");

        // 检查能否支付
        /** @var UsersRepository $userRepository */
        $userRepository = $this->app->make(UsersRepository::class);
        $old = $userRepository->getDetail($order['uuid'])['food'];
        if ($payAmount > $old) throw new ValidateException("可抵扣余额不足");
        try {
            // 扣减用户余额
            $this->app->db->transaction(function () use ($order, $payAmount, $shopOrder, $userRepository, $companyId,$payTitle) {
                // 更新订单余额
                $shopOrder->editInfo($order, ['pay_type' => 'food','status' => 2, 'pay_time' => date('Y-m-d H:i:s')]);
                // 扣除余额金额
                $userRepository->batchFoodChange($order['uuid'], 3, '-' . $payAmount, ['remark' => $payTitle, 'company_id' => $companyId], 4, $companyId);
            });
            return ['code' => 1, 'info' => '支付完成'];
        } catch (\Exception $exception) {
            exception_log('支付处理失败', $exception);
            return ['code' => 0, 'info' => $exception->getMessage()];
        }
    }
}