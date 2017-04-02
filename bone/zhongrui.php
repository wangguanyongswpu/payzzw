<?php
/**
 * 中瑞第三方支付
 * website http://pay.7684.org
 * author bone.jin
 * date 22/2/2017 14:10 pm
*/

class zhongrui
{
    // 支付PID
    public $partnerid = '10007';
    // 支付KEY
    public $partnerkey = 'Z23e8m8N4NXjHsT4EYCPz8HPNh3j';
    // 接口地址
    public $apiurl = 'http://pay.7684.org/api/api_pay.php';
    // 网站订单号（唯一）
    public $out_trade_no;
    // 商品名称
    public $productname;
    // 商品描述
    public $productIntro;
    // 交易金额
    public $total_fee;
    // 异步回调地址
    public $no_url;
    // 同步回调地址
    public $re_url;
    // 签名校验码
    public $sign;
    // 支付通道
    public $pay_channel;


    public function __construct($configure = [])
    {
        if (!is_array($configure)) {
            return 'configure is array error.';
        }

        $this->out_trade_no = $configure['out_trade_no'];
        $this->productname = $configure['productname'];
        $this->productIntro = $configure['productIntro'];
        $this->total_fee = $configure['total_fee'];
        $this->no_url = $configure['no_url'];
        $this->re_url = $configure['re_url'];
        $this->pay_channel = 'weixin_pay';
        $this->sign = $this->createsign(
                $this->partnerid,
                $this->out_trade_no,
                $this->productname,
                $this->total_fee,
                $this->productIntro,
                $this->no_url,
                $this->re_url
            );
    }

    /**
     * 生成签名校验码
     * param $userid int 商户ID
     * param $out_trade_no string 订单号
     * param $productname string 商品名称
     * param $total_fee string 金额
     * param $productintro string 商品描述
     * param $no_url string 异步回调
     * param $re_url string 同步回调
     * return string
    */
    public function createsign($userid, $out_trade_no, $productname, $total_fee, $productintro, $no_url, $re_url)
    {
        $parameter = $userid.$out_trade_no.$productname.$total_fee.$productintro.$no_url.$re_url.$this->pay_channel;

        $sign = md5($parameter.'zhongruipayapi'.$this->partnerkey);

        return $sign;
    }

    /**
     * 发起支付
    */
    public function pay()
    {
        $data = [
            'input_charset' => 'UTF-8',
            'sign' => $this->sign,
            'OrderType' => 'API',
            'ProductIntro' => $this->productIntro,
            'Api_Out_OrderNo' => $this->out_trade_no,
            'UserId' => $this->partnerid,
            'ProductName' => $this->productname,
            'OrderAmount' => $this->total_fee,
            'pay_channel' => $this->pay_channel,
            'Api_No_Url' => $this->no_url,
            'Api_Re_Url' => $this->re_url,
            'BuyerName' =>'',
            'BuyerSex' => '',
            'BuyerAge' => '',
            'buyer_idcard' => '',
            'buyer_postcode' => '',
            'buyer_wechat' => '',
            'Buyer_qq' => '',
            'BuyerEmail' => '',
            'BuyerTel' => '',
            'BuyerAddress' => '',
            'buyer_liuyan' => '',
            'tuiguang_id' => '',
            'product_tuiguang_rate' => ''
        ];

        $code = $this->curl($this->apiurl, $data);

        return $code;
    }

    /**
     * 回调数据处理
     * param $seller_id string 商户编号
     * param $re_out_trade_no string 商户订单号
     * param $re_trade_no string 交易单号
     * param $re_total_fee string 交易金额
     * param $trade_status string 交易壮态
     * param $re_sign string 服务端校验码
     * return bool
    */
    public function callback($seller_id, $re_out_trade_no, $re_trade_no, $re_total_fee, $trade_status, $re_sign)
    {
        $msign = md5($seller_id.$re_out_trade_no.$re_trade_no.$re_total_fee.$trade_status.$this->partnerkey);

        if ($msign != $re_sign) {
            return false;
        }

        $status = strtolower($trade_status) == 'trade_success';

        return $status;
    }

    /**
     * CURL请求封装
     * param $url string 主求URL
     * param $posts array 请求数据
    */
    public function curl($url, $posts="")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_REFERER, 'http://pay.7684.org/');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)");
        curl_setopt($ch, CURLOPT_USERAGENT, "chrome.exe –user-agent=”Mozilla/5.0 (iPad; U; CPU OS 3_2_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B500 Safari/531.21.10");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, $posts ? 0 : 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $posts);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }
}