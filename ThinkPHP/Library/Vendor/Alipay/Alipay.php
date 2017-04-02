<?php
require_once("lib/alipay_submit.class.php");

/**
 * 支付宝支付类
 */
class Alipay
{
    public $config;

    public function __construct()
    {
        require_once("alipay.config.php");
        $this->config = $alipay_config;
    }

    /**
     * 调起支付
     * @param $param
     * @return mixed
     */
    public function pay($param)
    {
        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service"       => $this->config['service'],
            "partner"       => $this->config['partner'],
            "seller_id"  => $this->config['seller_id'],
            "payment_type"	=> $this->config['payment_type'],
            "notify_url"	=> $this->config['notify_url'],
            "return_url"	=> $this->config['return_url'],
            "_input_charset"	=> trim(strtolower($this->config['input_charset'])),
            "out_trade_no"	=> $param['out_trade_no'],
            "subject"	=> $param['subject'],
            "total_fee"	=> $param['total_fee'],
            "show_url"	=> '',
            "app_pay"	=> "Y",//启用此参数能唤起钱包APP支付宝
            "body"	=> isset($param['body']) ? $param['body'] : '',
        );

        //建立请求
        $alipaySubmit = new AlipaySubmit($this->config);
        $html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
        echo $html_text;

        return $html_text;
    }

    /**
     * 异步通知验签
     * @return bool
     */
    public function verifyNotify()
    {
        global $alipay_config;
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();

        if(!$verify_result){
            return false;
        }

        return true;
    }

    /**
     * 同步通知验签
     * @return bool
     */
    public function verifyReturn()
    {
        global $alipay_config;
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyReturn();

        if(!$verify_result){
            return false;
        }

        return true;
    }
}