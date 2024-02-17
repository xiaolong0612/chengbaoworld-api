<?php

namespace api\sms\driver;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use think\facade\Log;

class yunMark
{

    // 短信内容
    private $content = '';
    // 发送状态
    private $sendStatus = false;
    // 错误信息
    private $errorMessage = '';
    // 配置
    private $config = [];

    // 错误码


    public function __construct($config = [])
    {
        $this->config = $config;
    }

    /**
     * 获取短信余额
     *
     * @return mixed
     */
    public function getSmsBalance()
    {
        return 0;
    }

    /**
     * 发送短信
     *
     * @return mixed
     */
    public function sendSms($phone, $data)
    {
        $host = "https://gyytz.market.alicloudapi.com";
        $path = "/sms/smsSend";
        $method = "POST";
        $appcode = $this->config['yunmark_sms_appcode'];
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);

//smsSignId（短信前缀）和templateId（短信模板），可登录国阳云控制台自助申请。参考文档：http://help.guoyangyun.com/Problem/Qm.html
        $querys = "mobile={$phone}&param=**code**%3A{$data['params']['code']}%2C**minute**%3A5&smsSignId=".$this->config['yunmark_sms_sign']."&templateId={$data['template_id']}";
        $bodys = "";
        $url = $host . $path . "?" . $querys;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);  //如果只想获取返回参数，可设置为false
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $retult = json_decode(curl_exec($curl),true);
        $this->setSendStatus($retult['code'] == '0');


        return $this->getSendStatus();
    }

    /**
     * 设置短信内容
     *
     * @param string $content 短信内容
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * 获取短信内容
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * 设置发送状态
     *
     * @param bool $status 发送状态
     */
    public function setSendStatus($status)
    {
        $this->sendStatus = $status;
    }

    /**
     * 获取发送状态
     *
     * @return mixed
     */
    public function getSendStatus()
    {
        return $this->sendStatus;
    }

    /**
     * 设置错误信息
     *
     * @param string $message 错误信息
     */
    public function setErrorMessage($message)
    {
        $this->errorMessage = $message;
    }

    /**
     * 获取错误信息
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

}