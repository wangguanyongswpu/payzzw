<?php
/**
 * 日    期：2016-07-21
 * 版    本：1.0.0
 * 功能说明：微信支付接口
 **/
namespace Index\Controller;
use Think;
use Common\Controller\BaseController;
use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order;

class PayApiController extends BaseController
{
    protected $data;
    protected $WEIXIN_PAY_API;
    protected $options;
    protected $OrderInfo; //订单信息
    protected $money;  //价格
    protected $appid;  //appid
    protected $rurl;   //第3方回调地址
    protected $openid; //会员openid
    protected $uid;    //会员id
    protected $pay_openid; //支付会员openid
    protected $paymentCallbackURl; //微信回调地址
    protected $reqHandler;

    public function _initialize()
    {
        $this->data = I('get.');
        Think\Log::record('get params: '.json_encode($this->data),'DEBUG',true);
        $get = session("get");
        Think\Log::record('session params: '.json_encode($get),'DEBUG',true);
        if ($get) {
            $this->data = array_merge($this->data,$get);
            if(!empty($_GET['money'])) $this->data['money']=$_GET['money'];
        }
        /*if(!in_array(ACTION_NAME, ['link','postpayinfo','paybank','bankcallback']) && !$this->checkSign()){
            $this->error("签名错误!");
            echo $this->formatResponse(array('ret' => '20', 'msg' => '签名错误！'));
            exit;
        }else{*/

            session("get",$this->data);
        //}
Think\Log::record('money1:' .$this->data['money'],'DEBUG',true);
        if(empty($this->data['money']) && !$get){
            $this->data['money']=39;
        }
        Think\Log::record('money11:' .$this->data['money'],'DEBUG',true);
        $this->data['money']=empty(trim($this->data['money']))?39:intval($this->data['money'])<5?39:$this->data['money'];
        Think\Log::record('money2:' .$this->data['money'],'DEBUG',true);
        Think\Log::record('uiver:' .$this->data['uiver'],'DEBUG',true);

        Think\Log::record('money3:' .$this->data['money'],'DEBUG',true);
        $source = C('cps_api');
        $web_id = $source[$this->data['app_id']]['web_id'];
        empty($web_id) && $web_id = 1;
        $test_pay = M('pay_wechat')->where('status=1 AND web_id='.$web_id)->find();
        $this->WEIXIN_PAY_API = C("WEIXIN_PAY_API");
        if($test_pay['id']){
            $this->WEIXIN_PAY_API = [
                'appid' => $test_pay['app_id'],
                'secret' => $test_pay['secret'],
                'mchid' => $test_pay['merchant_id'],
                'serve' => $test_pay['key'],
            ];
        }
        //测试支付接口
        if($this->data['test']){
            $id=$this->data['test'];
            $online_pay = M('pay_wechat')->where("id=$id")->find();
            $code=md5($id."+".$online_pay['api_url']."test");
            // if($this->data['tokencode']==$code){
                $this->WEIXIN_PAY_API = [
                    'appid' => $online_pay['app_id'],
                    'secret' => $online_pay['secret'],
                    'mchid' => $online_pay['merchant_id'],
                    'serve' => $online_pay['key'],
                ];
                //$this->data['money'] = 0.01;
            // }
        }
        $this->options        = array(
            'app_id'         => $this->WEIXIN_PAY_API['appid'],
            'secret'         => $this->WEIXIN_PAY_API['secret'],
            'payment'        => array(
                'merchant_id'        => $this->WEIXIN_PAY_API['mchid'],
                'key'                => $this->WEIXIN_PAY_API['serve'],
            ),
        );
        $this->OrderInfo      = array(
            'name'          => '客服电话:17081089402',
            'detail'        => '客服电话:17081089402',
            'serial'        =>  time().rand(1000,9999), //订单号
            'total_fee'     =>  $this->data['money'],                  //价格
        );
        $this->openid         = $this->data['openid'];
        $this->rurl           = $this->data['rurl'];
        $this->uid            = $this->data['uid'];
        $this->money          = $this->data['money'];
        $this->appid          = $this->data['appid'];
        $this->paymentCallbackURl = C("paymentCallbackURl");
    }


    /**
     * 校验签名
     * @return bool
     */
    protected function checkSign()
    {
        if (ACTION_NAME == 'callback') {
            return true;
        }
        $promotion = C('cps_api');
        $app_id  = $this->data['app_id'];
        $app_key = $promotion[$app_id]['app_key'];
        $sign = md5($app_id . '+' . $this->data['uid'] . '+' . $this->data['refid'] . '+' . $this->data['timestamp'] . '+' . $app_key);//签名规则
        if($this->data['sign'] !== $sign){
            return false;
        }
        return true;
    }


    /**
     * 格式化响应信息
     * @param $param array 返回的信息数组
     * @return json
     */
    protected function formatResponse($param)
    {
        $res = json_encode($param);
        return $res;
    }


    /**
     * 其他支付
     *
     *
     */
    public function paybank()
    {
        //UC不能开启加速功能，数据会丢失

        Think\Log::record("bank_start: ".json_encode($_GET),'DEBUG',true);
        //if(empty($this->data['orderid'])){
            $this->data['orderid'] = "v".time().rand(10000,999999);
        //}
        /*if( M("payapi_log")->where(array("orderid"=>$this->data['orderid'],'status'=>1))->find() ){
            $this->error("已支付");
        }*/

        $notify_url=C("paybankCallbackURl");//异步地址
        $return_url = 'http://www.drxwh.com/wap.wumaiqi.com/9dv12/daog.html';  //通知地址
        $pay_amt=$this->money;
        //测试用金额
        //$pay_amt='0.01'; //金额
        $pay_code="";
        $agent_id=C('AGENT_ID');
        $sign_key=C('SIGN_KEY');
        $agent_bill_id=$this->data['orderid'];//订单编号
        $pay_type = '20';//银行支付
        $pay_type = '30';//微信支付
        $goods_name="会员充值"; //商品名
        $goods_num='1';//商品数量
        $remark=time();//自定义信息

        $wxpay_type = 1;//0:微信扫码支付;1:微信WAP支付;2:微信公众号支付

        $extra_return_param="";//回传参数（name^zhangsan|sex^male）
        $payapi_log = array(
            'orderid'     => $agent_bill_id,
            'pay_orderid' => $agent_bill_id,
            'openid'      =>  time(),
            'uid'         =>  'bank',
            'refid'       =>  $this->data['refid'],
            'ad_app_id'   =>  $this->data['ad_app_id'],
            'ad_app_uid'  =>  $this->data['ad_app_uid'],
            'gid'         =>  '1',
            'time'        =>  time(),
            'status'      =>  0,
            'money'       => $pay_amt,
            'ip'          =>  get_ip(),
            'call_url'    => $notify_url,
        );
        Think\Log::record("bank_log: ".json_encode($payapi_log),'DEBUG',true);
        if( M("payapi_log")->add($payapi_log) == false){
            $this->error("支付发起错误!");
        }
        include './PayDemo/post_action.php';
    }

    /**
     *  其他支付异步回调
     */
    public function bankcallback(){

        Think\Log::record("bankcallback: ".json_encode($_GET),'DEBUG',true);

        $result=$_GET['result'];
        $pay_message=$_GET['pay_message'];
        $agent_id=$_GET['agent_id'];
        $jnet_bill_no=$_GET['jnet_bill_no'];
        $agent_bill_id=$_GET['agent_bill_id'];
        $pay_type=$_GET['pay_type'];
        $pay_amt=$_GET['pay_amt'];
        $remark=$_GET['remark'];
        $returnSign=$_GET['sign'];
        //商户的KEY
        $key = C('SIGN_KEY');

        $signStr='';
        $signStr  = $signStr . 'result=' . $result;
        $signStr  = $signStr . '&agent_id=' . $agent_id;
        $signStr  = $signStr . '&jnet_bill_no=' . $jnet_bill_no;
        $signStr  = $signStr . '&agent_bill_id=' . $agent_bill_id;
        $signStr  = $signStr . '&pay_type=' . $pay_type;
        $signStr  = $signStr . '&pay_amt=' . $pay_amt;
        $signStr  = $signStr .  '&remark=' . $remark;
        $signStr = $signStr . '&key=' . $key;
        //$sign='';
        $sign=md5($signStr);
        //请确保 notify.php 和 return.php 判断代码一致
        if($sign==$returnSign){

            $order= M("payapi_log")->where(array("orderid"=>$agent_bill_id,'status'=>0))->find();
            $order= M("payapi_log")->where(array("orderid"=>$agent_bill_id,'status'=>0))->find();
            if(!empty($order)&&$pay_amt==$order['money']){
                $_order['call_status']=1;
                if (!$pay_message) {
                    $_order['status'] = 1;
                } else {
                    $_order['status'] = 2;
                }
            }

            $_order['pay_time'] = $remark;
            $_order['transaction_id'] = $jnet_bill_no;

            if( M("payapi_log")->where(array("orderid"=>$agent_bill_id) )->save($_order) !== false){

                if(isset($order)&&$_order['status']==1) {
                    Think\Log::record("order: ".json_encode($order),'DEBUG',true);
                    $this->add_pay($order['id']);
                }
            }

            $response="ok";
        }else{
            Think\Log::record("bank_err: ".json_encode($pay_message),'DEBUG',true);
            $response="error";
        }

        echo $response;
    }

    /**
     * 支付宝支付
     *
     *
     */
    public function alipay()
    {
        //UC不能开启加速功能，数据会丢失

        Think\Log::record("alipay_start: ".json_encode($_REQUEST),'DEBUG',true);
        //if(empty($this->data['orderid'])){
        $this->data['orderid'] = "v".time().rand(10000,999999);
        //}
        /*
        if( M("payapi_log")->where(array("orderid"=>$this->data['orderid'],'status'=>1))->find() ){
            $this->error("已支付");
        }*/

        $notify_url=C("alipayCallbackURl");//异步地址
        $return_url = "";  //通知地址
        $agent_bill_id=$this->data['orderid'];//订单编号
        $pay_amt=$this->money;
        //测试用金额
        $pay_amt='48'; //金额
        $goods_name="moive"; //商品名
        $remark=time();//自定义信息
        $payapi_log = array(
            'orderid'     => $agent_bill_id,
            'pay_orderid' => $agent_bill_id,
            'openid'      =>  time(),
            'uid'         =>  'bank',
            'refid'       =>  $this->data['refid'],
            'ad_app_id'   =>  $this->data['ad_app_id'],
            'ad_app_uid'  =>  $this->data['ad_app_uid'],
            'gid'         =>  '1',
            'time'        =>  time(),
            'status'      =>  0,
            'money'       => $pay_amt,
            'ip'          =>  get_ip(),
            'call_url'    => $notify_url,
        );

        Think\Log::record("alipay_log: ".json_encode($payapi_log),'DEBUG',true);
        if( M("payapi_log")->add($payapi_log) == false){
            $this->error("支付发起错误!");
        }
        include './alipay/req.php';
    }

    /**
     *  支付宝回调地址
     */
    public function alipayback(){

        Think\Log::record("alipay_callback: ".json_encode($_GET),'DEBUG',true);
        include './alipay/yeepayCommon.php';

#   只有支付成功时华联支付才会通知商户.
##支付成功回调有两次，都会通知到在线支付请求参数中的p8_Url上：浏览器重定向;服务器点对点通讯.

#   解析返回参数.
        $return = getCallBackValue($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType,$hmac);

#   判断返回签名是否正确（True/False）
        $bRet = CheckHmac($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType,$hmac);

        if($bRet){

            if($r1_Code=="1"){

                #   需要比较返回的金额与商家数据库中订单的金额是否相等，只有相等的情况下才认为是交易成功.
                #   并且需要对返回的处理进行事务控制，进行记录的排它性处理，在接收到支付结果通知后，判断是否进行过业务逻辑处理，不要重复进行业务逻辑处理，防止对同一条交易重复发货的情况发生.

                $agent_bill_id= $r6_Order;
                $pay_amt=$r3_Amt;
                $remark=$r8_MP;
                $jnet_bill_no=$r2_TrxId;
                $order= M("payapi_log")->where(array("orderid"=>$agent_bill_id,'status'=>0))->find();
                if(!empty($order)&&$pay_amt==$order['money']){
                    $_order['call_status']=1;
                    $_order['status']=1;
                    $_order['pay_time'] = $remark;
                    $_order['transaction_id'] = $jnet_bill_no;
                    if( M("payapi_log")->where(array("orderid"=>$agent_bill_id) )->save($_order) !== false){
                        if(isset($order)&&$_order['status']==1) {
                            Think\Log::record("alipay_order: ".json_encode($order),'DEBUG',true);
                            $flog=$this->add_pay($order['id']);
                            if($flog&&$r9_BType=="1"){
                                echo "交易成功";
                                echo  "<br />在线支付页面返回";
                            }elseif($flog&&$r9_BType=="2"){
                                #如果需要应答机制则必须回写流,以success开头,大小写不敏感.
                                echo "success";
                                echo "<br />交易成功";
                                echo  "<br />在线支付服务器返回";
                            }
                        }
                    }

                }else{
                    Think\Log::record("alipay_err: ".json_encode($pay_message),'DEBUG',true);
                    $response="error";
                }
            }
        }else{
            echo "交易信息被篡改";
        }
        if($flog&&$r9_BType=="2"){
            #如果需要应答机制则必须回写流,以success开头,大小写不敏感.
            echo "success";
        }else{
            $url = 'http://now.qq.com/h5/index.html?roomid=6628544&_bid=&_wv=&from=';
            header("Location:".$url);
            die();
        }
    }


    /**
     *  支付API接口
     */
    public function index(){

        return $this->link();
        exit;
        $data["weixin_pay_api"] = $this->WEIXIN_PAY_API;
        if(empty($this->data['orderid'])){
            $this->data['orderid'] = "v".time().rand(10000,999999);
        }
        /*if( M("payapi_log")->where(array("orderid"=>$this->data['orderid'],'status'=>1))->find() ){
            $this->error("已支付");
        }*/
        $user_info = $this->Wexin_Get_Code();
        $open_id   =  $user_info['openid'];
        $data = array(
            "pay_openid"  => $open_id,
            'openid'      => $this->openid,
        );
        import("Lib/WeiXinPay/Autoload");
        $app        = new Application($this->options);
        $payment    = $app->payment;
        $orderInfo  = $this->OrderInfo;
        $attributes = [
            'trade_type'       => 'JSAPI', // JSAPI，NATIVE，APP...
            'body'             => $orderInfo['name'],
            'detail'           => $orderInfo['detail'],
            'out_trade_no'     => $this->data['orderid'], //订单号
            'total_fee'        => intval($orderInfo['total_fee']*100),
            'notify_url'       => $this->paymentCallbackURl, // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'openid'           => $open_id,
        ];
        $order = new Order($attributes);
        $result = $app->payment->prepare($order);
        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
            $prepayId = $result->prepay_id;
        }
        $data['js']= $app->js->config(array('onMenuShareQQ', 'onMenuShareWeibo','chooseWXPay'), false);
        $data['config'] = $payment->configForJSSDKPayment($prepayId);
        $data['order_info'] = array(
            'orderid'=>$this->data['orderid'],
            'money'  =>$orderInfo['total_fee'],
        );
        $payapi_log = array(
            'prepayid'    =>  $prepayId,
            'orderid'     =>  $this->data['orderid'],
            'pay_orderid' =>  $open_id,
            'openid'      =>  time(),
            'uid'         =>  'cps',
            'refid'       =>  $this->data['refid'],
            'gid'         =>  '1',
            'time'        =>  time(),
            'status'      =>  0,
            'money'       =>  $this->data["money"],
            'ip'          =>  get_ip(),
            'call_url'    =>  'ceshi',
        );
        if( M("payapi_log")->add($payapi_log) == false){
            $this->error("支付发起错误!");
        }
        $register_log = [
            'refid' => $this->data['refid'],
            'openid' => $open_id,
            'sex' => 1,
            'cps_app_id' => '1000000001',
            'reg_type' => 1,
            'reg_time' => time(),
            'reg_ip' => get_iplong(),
        ];
        $this->add_register($register_log);

        $data['call_url'] = $this->data['call_url'];
        session("get",null);
        $this->assign($data);
        $this->display('index');
    }

    /**
     *  支付API接口
     */
    public function link()
    {

        $wftUrl = M('setting')->where('k="wftpayurl"')->getField('v');
        if(empty($_COOKIE['wechatjs']) && empty(I('get.code'))){
            if(time() > strtotime('2016-10-19 09:50:00') && time() < strtotime('2016-10-19 12:50:00') && mt_rand(1,100) > 50){
                header("Location: ".'http://'.$wftUrl.'/PayApi/wechatjs?'.$_SERVER['QUERY_STRING'].'&wechat=1');
                exit;
            } else{
                setcookie('wechatjs', 1, 300);
            }
        }
        $pauTypeValue = M('setting')->where('k="paytype"')->getField('v');
        if($pauTypeValue == 2)
        {
            header("Location: ".'http://'.$wftUrl.'/PayApi/wechatjs?'.$_SERVER['QUERY_STRING'].'&wechat=1');
            exit;
        }elseif($pauTypeValue == 3){
            $pay70url = M('setting')->where('k="pay70url"')->getField('v');
            header("Location: ".'http://'.$pay70url.'/PayApi/pay70?'.$_SERVER['QUERY_STRING']);
            exit;
        }

        import("Lib/WeiXinPay/Autoload");
        $app = new Application($this->options);
        try {
            $accessToken = $app->access_token;
            $token = $accessToken->getToken();
            Think\Log::record("payment-wechat-token: ".$token,'DEBUG',true);
        } catch (Exception $ex) {
            $msg = $ex->getMessage();
            Think\Log::record("payment-wechat-err: ".$msg,'DEBUG',true);
            $tel = C('DEV_TELS');
            sms("支付公众号状态异常,请检查!",$tel);
        }
        if($_COOKIE['isvip']){
            if(!empty($_COOKIE['pay_callback_url'])){
                header("Location: ".$_COOKIE['pay_callback_url']);
                exit;
            }elseif(!empty($_SERVER['HTTP_REFERER'])){
                $oid = empty($_COOKIE['orderid'])?$order_id:$_COOKIE['orderid'];
                $url = $_SERVER['HTTP_REFERER']."&isvip={$oid}";
                //header("Location: ".$url);
                exit;
            }
        }
        $data["weixin_pay_api"] = $this->WEIXIN_PAY_API;
        if(empty($this->data['orderid'])||$this->data['test']){
            $this->data['orderid'] = "v".time().rand(10000,999999);
        }
        /*if( M("payapi_log")->where(array("orderid"=>$this->data['orderid'],'status'=>1))->find() ){
            $this->error("已支付");
        }*/

        if(!isset($_GET['code']) && !isset($_GET['state']) && (empty($_GET['time']) || empty($_GET['utype']) || empty($_GET['uiver']))){
            Think\Log::record("pay_link_time_err: {$_SERVER['REQUEST_URI']}",'DEBUG',true);
        }

        $user_info = $this->Wexin_Get_Code();
        $open_id   =  $user_info['openid'];
        /*
        $pay_log = M('pay_log')->where("uid='{$open_id}' AND pay_amount>1")->find();
        if($pay_log['id']&&$this->data['money']>1){
            $url = 'http://now.qq.com/h5/index.html?roomid=6628544&_bid=&_wv=&from=';
            header("Location:".$url);
            die();
        }
        */
        $data = array(
            "pay_openid"  => $open_id,
            'openid'      => $this->openid,
        );

        $payment    = $app->payment;
        $orderInfo  = $this->OrderInfo;
        $attributes = [
            'trade_type'       => 'JSAPI', // JSAPI，NATIVE，APP...
            'body'             => $orderInfo['name'],
            'detail'           => $orderInfo['detail'],
            'out_trade_no'     => $this->data['orderid'], //订单号
            'total_fee'        => intval($orderInfo['total_fee']*100),
            'notify_url'       => $this->paymentCallbackURl, // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'openid'           => $open_id,
        ];
        $order = new Order($attributes);
        $result = $app->payment->prepare($order);
        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
            $prepayId = $result->prepay_id;
        }
        $data['js']= $app->js->config(array('onMenuShareQQ', 'onMenuShareWeibo','chooseWXPay'), false);
        $data['config'] = $payment->configForJSSDKPayment($prepayId);
        $data['order_info'] = array(
            'orderid'=>$this->data['orderid'],
            'money'  =>$orderInfo['total_fee'],
        );
        $payapi_log = array(
            'prepayid'    =>  $prepayId,
            'orderid'     =>  $this->data['orderid'],
            'pay_orderid' =>  $open_id,
            'openid'      =>  time(),
            'uid'         =>  'cps',
            'refid'       =>  $this->data['refid'],
            'ad_app_id'   =>  $this->data['ad_app_id'],
            'ad_app_uid'  =>  $this->data['ad_app_uid'],
            'gid'         =>  '1',
            'time'        =>  time(),
            'status'      =>  0,
            'money'       =>  $this->data["money"],
            'ip'          =>  get_ip(),
            'call_url'    =>  $this->data['call_url']?$this->data['call_url']:'ceshi',
            'pay_channel'    =>  1,
            'stop_time'    =>  $this->data['time'],
            'utype'    =>  empty($this->data['utype']) ? '' : $this->data['utype'],
            'uiver'    =>  empty($this->data['uiver']) ? '' : $this->data['uiver'],
            'merchant_id'    =>  $this->options['app_id'],
        );
        if( M("payapi_log")->add($payapi_log) == false){
            $this->error("支付发起错误:-1!");
            Think\Log::record("wechat js order add arr:".M("payapi_log")->getlastsql(),'DEBUG',true);

        }
        //Think\Log::record("order_arr:".M("payapi_log")->getlastsql(),'DEBUG',true);
        $register_log = [
            'uid'=>$this->data['orderid'],
            'refid' => $this->data['refid'],
            'openid' => $open_id,
            'sex' => 1,
            'cps_app_id' => '1000000001',
            'reg_type' => 1,
            'reg_time' => time(),
            'reg_ip' => get_iplong(),
        ];
        Think\Log::record("refid{$this->data['refid']}--".json_encode($register_log),'DEBUG',true);
        $this->add_register($register_log);

        $data['call_url'] = $this->data['call_url'];
        session("get",null);
        $this->assign($data);
        $this->display('index');
    }


    /**
     *  支付API接口
     */
    public function wapWx(){
        if(empty($_GET['debug']) || $_GET['debug']!='6628544'){
            exit('wapwx');
        }
        $data["weixin_pay_api"] = $this->WEIXIN_PAY_API;
        if(empty($this->data['orderid'])||$this->data['test']){
            $this->data['orderid'] = "v".time().rand(10000,999999);
        }
        /*if( M("payapi_log")->where(array("orderid"=>$this->data['orderid'],'status'=>1))->find() ){
            $this->error("已支付");
        }*/
//        $user_info = $this->Wexin_Get_Code();
        $open_id   =  '';//$user_info['openid'];
        $pay_log = M('pay_log')->where("uid='{$open_id}' AND pay_amount>1")->find();
        if($pay_log['id']&&$this->data['money']>1){
            $url = 'http://now.qq.com/h5/index.html?roomid=6628544&_bid=&_wv=&from=';
            header("Location:".$url);
            die();
        }
        $data = array(
            "pay_openid"  => $open_id,
            'openid'      => $this->openid,
        );
        import("Lib/WeiXinPay/Autoload");
        $app        = new Application($this->options);
        $payment    = $app->payment;
        $orderInfo  = $this->OrderInfo;
        $attributes = [
            'trade_type'       => 'APP', // JSAPI，NATIVE，APP...
            'body'             => $orderInfo['name'],
            'detail'           => $orderInfo['detail'],
            'out_trade_no'     => $this->data['orderid'], //订单号
            'total_fee'        => intval($orderInfo['total_fee']*100),
            'notify_url'       => $this->paymentCallbackURl, // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'openid'           => $open_id,
        ];
        $order = new Order($attributes);
        $result = $app->payment->prepare($order);
        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
            $prepayId = $result->prepay_id;
        }


        $js = json_decode($app->js->config(array('onMenuShareQQ', 'onMenuShareWeibo','chooseWXPay'), false));

//        var_dump($result);
//        var_dump($js);


//        echo $url = "appid={$js->appId}&noncestr={$js->nonceStr}&package=WAP&prepayid={$prepayId}&sign={$js->signature}&timestamp={$js->timestamp}";
        echo "<a href='weixin://wap/pay?".urlencode($url)."'>微信WAP支付</a>";
        exit;
        $data['config'] = $payment->configForJSSDKPayment($prepayId);
        $data['order_info'] = array(
            'orderid'=>$this->data['orderid'],
            'money'  =>$orderInfo['total_fee'],
        );
        $payapi_log = array(
            'prepayid'    =>  $prepayId,
            'orderid'     =>  $this->data['orderid'],
            'pay_orderid' =>  $open_id,
            'openid'      =>  time(),
            'uid'         =>  'cps',
            'refid'       =>  $this->data['refid'],
            'ad_app_id'   =>  $this->data['ad_app_id'],
            'ad_app_uid'  =>  $this->data['ad_app_uid'],
            'gid'         =>  '1',
            'time'        =>  time(),
            'status'      =>  0,
            'money'       =>  $this->data["money"],
            'ip'          =>  get_ip(),
            'call_url'    =>  'ceshi',
        );
        if( M("payapi_log")->add($payapi_log) == false){
            $this->error("支付发起错误!");
        }
//        $register_log = [
//            'refid' => $this->data['refid'],
//            'openid' => $open_id,
//            'sex' => 1,
//            'cps_app_id' => '1000000001',
//            'reg_type' => 1,
//            'reg_time' => time(),
//            'reg_ip' => get_iplong(),
//        ];
//        $this->add_register($register_log);

        $data['call_url'] = $this->data['call_url'];
        session("get",null);
        $this->assign($data);
        //$this->display('wapwx');
    }

    /**
     *  支付回调
     */
    public function callback(){
        import("Lib/WeiXinPay/Autoload");
        // file_put_contents('./pay.log',date("ymd H:i:s").PHP_EOL.file_get_contents('php://input', 'r').PHP_EOL.json_encode($_REQUEST).PHP_EOL,FILE_APPEND);
        $options = $this->options;
        $app = new Application($options);
        $response = $app->payment->handleNotify(function($notify, $successful){
            $order= M("payapi_log")->where(array("orderid"=>$notify['out_trade_no'],'status'=>0))->find();
            $_order['pay_time'] = strtotime($notify['time_end']);
            $_order['transaction_id'] = $notify['transaction_id'];
            if ($notify['result_code'] =='SUCCESS') {
                $_order['status'] = 1;
            } else {
                $_order['status'] = 2;
            }
            if( M("payapi_log")->where(array("orderid"=>$notify['out_trade_no']) )->save($_order) !== false){

                if(isset($order)&&$_order['status']==1) {
                    Think\Log::record("order: ".json_encode($order),'DEBUG',true);
                    $this->add_pay($order['id']);
                setcookie('isvip', 1, 3*24*3600);
                setcookie('orderid', $order['orderid'], 3*24*3600);
                setcookie('pay_callback_url', $order['call_url']."?type=jacall&orderid={$order['orderid']}", 3*24*3600);
                }
                /*$url = $order['call_url'];
                $url .= "?orderid=".$notify['out_trade_no']."&status=SUCCESS&key=18361130555";
                //200 成功 300已提交 400 错误
                $call_back_code = $this->httpGet($url);
                if ($call_back_code == 400) {
                     $this->httpGet($url);
                }else{
                     $call_data = array(
                         'call_status'=>1
                      );
                     M("payapi_log")->where(array("orderid"=>$notify['out_trade_no']) ) ->save($call_data);
                }*/
            }
            return true;
        });
        return $response;
    }


    /**
     * 70card 支付
     */
    public function pay70()
    {
        $order_id = $this->data['orderid'];
        if(empty($order_id)){
            $order_id = "v".time().rand(10000,999999);
        }

        /*if( M("payapi_log")->where(array("orderid"=>$order_id,'status'=>1))->find() ){
            $this->error("已支付");
        }*/
        $config_70 = C('70_CARD');
        $this->data['call_url']=$config_70['notify_url'];
        //var_dump($this->data['call_url']);die;
        $money = 48;//empty($this->data['money']) ? 48 : $this->data['money'];
        $bank_id = empty($this->data['bank_id']) ? 2005 : $this->data['bank_id'];

        $payapi_log = array(
            'prepayid'    =>  $order_id,
            'orderid'     =>  $order_id,
            'pay_orderid' =>  $order_id,
            'openid'      =>  time(),
            'uid'         =>  time(),
            'refid'       =>  $this->data['refid'],
            'ad_app_id'   =>  $this->data['ad_app_id'],
            'ad_app_uid'  =>  $this->data['ad_app_uid'],
            'gid'         =>  '1',
            'time'        =>  time(),
            'status'      =>  0,
            'money'       =>  $money,
            'ip'          =>  get_ip(),
            'call_url'    =>  $this->data['call_url'],
            'pay_channel'    =>  5,
            'stop_time'    =>  $this->data['time'],
            'utype'    =>  empty($this->data['utype']) ? '' : $this->data['utype'],
            'uiver'    =>  empty($this->data['uiver']) ? '' : $this->data['uiver'],
        );
        if( M("payapi_log")->add($payapi_log) == false){
            $this->error("支付发起错误!");
        }
        $register_log = [
            'refid' => $this->data['refid'],
            'openid' => $order_id,
            'sex' => 1,
            'cps_app_id' => '1000000001',
            'reg_type' => 5,
            'reg_time' => time(),
            'reg_ip' => get_iplong(),
        ];
        $this->add_register($register_log);
        session("get",null);
        //跳转去支付

        $ext = '';
        $sign = "userid=".$config_70['userid']."&orderid=".$order_id."&bankid=".$bank_id."&keyvalue=".$config_70['keyvalue'];
        $sign = md5($sign);
        $aurl = 'http://'.$_SERVER['HTTP_HOST'].'/PayApi/success';
        $nurl = 'http://'.$_SERVER['HTTP_HOST'].'/PayApi/callback_70';
        $url = $config_70['pay_url']."?userid=".$config_70['userid']."&orderid=".$order_id."&money=".$money."&url=".$nurl."&aurl={$aurl}&bankid=".$bank_id."&sign=".$sign."&ext=".$ext;
        Think\Log::record('70pay jump:'.$url,'DEBUG',true);
        //必须使用js或者form提交,使用location第三方会提示支付域名错误
        echo '<script type="text/javascript">setTimeout("window.location.href=\''.$url.'\'",3);</script>';
        //header('Location:'.$url);
    }

    /**
     * 70card 回调
     */
    public function callback_70()
    {
        $data = I('get.');
        $config_70 = C('70_CARD');

        $sign2 = md5("money={$data['money']}&returncode={$data['returncode']}&userid={$data['userid']}&orderid={$data['orderid']}&keyvalue={$config_70['keyvalue']}");
        if ($sign2 != $data['sign2']){
            //签名错误
            Think\Log::record('70pay jump sign err:'.json_encode($data),'DEBUG',true);
            echo 'fail';
            exit;
        }

        if($data['returncode'] != 1){
            //支付失败
            Think\Log::record('70pay jump pay err:'.json_encode($data),'DEBUG',true);
            echo 'fail';
            exit;
        }

        $order= M("payapi_log")->where(array("orderid"=>$data['orderid']))->find();
        if(empty($order['id'])){
            //订单不存在
            Think\Log::record('70pay jump order err:'.json_encode($data),'DEBUG',true);
            echo 'fail';
            exit;
        }

        if($order['status'] == 0){
            $_order['pay_time'] = time();
            $_order['status'] = 1;
            if( M("payapi_log")->where(array("id"=>$order['id']) )->save($_order) !== false){
                Think\Log::record("order: ".json_encode($order),'DEBUG',true);
                $this->add_pay($order['id']);
            }
        }

        echo 'ok';
    }

    /**
     * 支付成功
     */
    public function success()
    {
        $data = I('get.');

        $order= M("payapi_log")->where(array("orderid"=>$data['orderid']))->find();
        if(empty($order['id'])){
            $this->error("支付失败!",$order['call_url']);
        }
        if(!$order['call_url'] || $order['call_url']=='ceshi'){
            header("Location: http://www.huajiao.com/mobile");
            exit;
        }
        $this->assign('order', $order);
        $this->display('success');
    }

    /**
     *@威富通 微信wap支付
     */
    public function wechatwap()
    {
        vendor('Wechatwap.Wechatwap');
        if($_COOKIE['isvip']){
            if(!empty($_COOKIE['pay_callback_url'])){
                header("Location: ".$_COOKIE['pay_callback_url']);
                exit;
            }elseif(!empty($_SERVER['HTTP_REFERER'])){
                $oid = empty($_COOKIE['orderid'])?$order_id:$_COOKIE['orderid'];
                $url = $_SERVER['HTTP_REFERER']."&isvip={$oid}";
                header("Location: ".$url);
                exit;
            }
        }
        session("get",null);
        $order_id = $this->data['orderid'];
        if(empty($order_id)){
            $order_id = "v".time().rand(10000,999999);
        }

        $order = M("payapi_log")->where(array("orderid"=>$order_id))->find();
        /*if($order['status'] == 1){
            $this->error("已支付");
        }*/

        $money = empty($this->data['money']) ? 48 : $this->data['money'];
        if(empty($order['id'])) {
            $payapi_log = array(
                'prepayid' => $order_id,
                'orderid' => $order_id,
                'pay_orderid' => $order_id,
                'openid' => time(),
                'uid' => time(),
                'refid' => $this->data['refid'],
                'ad_app_id' => $this->data['ad_app_id'],
                'ad_app_uid' => $this->data['ad_app_uid'],
                'gid' => '1',
                'time' => time(),
                'status' => 0,
                'money' => $money,
                'ip' => get_ip(),
                'call_url' => $this->data['call_url'] ? $this->data['call_url'] : 'ceshi',
                'pay_channel' => 6,
                'stop_time' => $this->data['time'],
                'utype'    =>  empty($this->data['utype']) ? '' : $this->data['utype'],
                'uiver'    =>  empty($this->data['uiver']) ? '' : $this->data['uiver'],
            );
            if (M("payapi_log")->add($payapi_log) == false) {
                $this->error("支付发起错误!");
            }
            $register_log = [
                'uid'=>$order_id,
                'refid' => $this->data['refid'],
                'openid' => $order_id,
                'sex' => 1,
                'cps_app_id' => '1000000001',
                'reg_type' => 6,
                'reg_time' => time(),
                'reg_ip' => get_iplong(),
            ];
            $this->add_register($register_log);
        }

        //跳转去支付
        $aurl = 'http://'.$_SERVER['HTTP_HOST'].'/PayApi/success/orderid/'.$order_id;
        $nurl = 'http://'.$_SERVER['HTTP_HOST'].'/PayApi/callback_wap';
        $param = [
            'req' => [
                'out_trade_no' => $order_id,
                'body' => '客服电话:17081089402',
                'total_fee' => $money * 100,
                'mch_create_ip' => get_ip(),
            ],
            'service' => 'pay.weixin.wappay',
            'aurl' => $aurl,
            'nurl' => $nurl,
        ];

        $request = new \Request('wap');
        $res = $request->submitOrderInfo($param);
        Think\Log::record('wap jump err:'.json_encode($res),'DEBUG',true);
        if($res['status'] == 500){
            $this->error("支付发起错误!");
        }

        //echo '<script type="text/javascript">setTimeout("window.location.href=\''.$url.'\'",3);</script>';
        header('Location:'.$res['pay_info']);
    }

    /**
     *@威富通 微信公众号支付
     */
    public function wechatjs()
    {
        $wftUrl = M('setting')->where('k="wftpayurl"')->getField('v');
        /*if(!$this->chackurl($wftUrl))
        {
            header("Location: ".'http://'.$_SERVER['HTTP_HOST'].'/PayApi/link?'.$_SERVER['QUERY_STRING']);
            exit;
        }*/
        vendor('Wechatwap.Wechatwap');

        $order_id = $this->data['orderid'];
        if(empty($order_id)){
            $order_id = "v".time().rand(10000,999999);
        }

        if($_COOKIE['isvip']){
            if(!empty($_COOKIE['pay_callback_url'])){
                header("Location: ".$_COOKIE['pay_callback_url']);
                exit;
            }elseif(!empty($_SERVER['HTTP_REFERER'])){
                $oid = empty($_COOKIE['orderid'])?$order_id:$_COOKIE['orderid'];
                $url = $_SERVER['HTTP_REFERER']."&isvip={$oid}";
                header("Location: ".$url);
                exit;
            }
        }

        $order = M("payapi_log")->where(array("orderid"=>$order_id))->find();
        /*if($order['status'] == 1){
            $url = empty($_SERVER['HTTP_REFERER']) ? $order['call_url'].'?type=jacall&orderid='.$order_id : $_SERVER['HTTP_REFERER'];
            $this->error("已支付", $url);
        }*/

        //$money = empty(I('get.money', 0)) ? 48 : I('get.money', 0);
        if(empty($order['id'])) {
            $payapi_log = array(
                'prepayid' => $order_id,
                'orderid' => $order_id,
                'pay_orderid' => $order_id,
                'openid' => time(),
                'uid' => time(),
                'refid' => $this->data['refid'],
                'ad_app_id' => $this->data['ad_app_id'],
                'ad_app_uid' => $this->data['ad_app_uid'],
                'gid' => '1',
                'time' => time(),
                'status' => 0,
                'money' => $this->data['money'],
                'ip' => get_ip(),
                'call_url' => $this->data['call_url'] ? $this->data['call_url'] : 'ceshi',
                'pay_channel' => 7,
                'stop_time' => $this->data['time'],
                'utype'    =>  empty($this->data['utype']) ? '' : $this->data['utype'],
                'uiver'    =>  empty($this->data['uiver']) ? '' : $this->data['uiver'],
            );
            if (M("payapi_log")->add($payapi_log) == false) {
                $this->error("支付发起错误!");
            }
            $register_log = [
                'refid' => $this->data['refid'],
                'openid' => $order_id,
                'sex' => 1,
                'cps_app_id' => '1000000001',
                'reg_type' => 7,
                'reg_time' => time(),
                'reg_ip' => get_iplong(),
            ];
            $this->add_register($register_log);
        }

        //跳转去支付
        $aurl = 'http://'.$_SERVER['HTTP_HOST'].'/PayApi/success/orderid/'.$order_id;
        $nurl = 'http://'.$_SERVER['HTTP_HOST'].'/PayApi/callback_js';

        $wftchoose = M('setting')->where('k="wftchoose"')->getField('v');

        $wftappid = M('setting')->where('k="wftappid'.$wftchoose.'"')->getField('v');
        $wftsecret = M('setting')->where('k="wftsecret'.$wftchoose.'"')->getField('v');

        //$config = C('WECHAT_CONFIG');
        $this->WEIXIN_PAY_API = [
            'appid' => $wftappid,
            'secret' => $wftsecret,
        ];
        var_dump($this->WEIXIN_PAY_API);die;
        /*
        $config = C('WECHAT_CONFIG');
        $this->WEIXIN_PAY_API = [
            'appid' => $config['appid'],
            'secret' => $config['secret'],
        ];
        */
        if(empty(I('get.wechat', 0))){
            header('Location:http://'.$wftUrl.'/PayApi/wechatjs?'.$_SERVER['QUERY_STRING'].'&wechat=1');
            exit;
        }
        $user_info = $this->Wexin_Get_Code();

        $open_id   =  $user_info['openid'];
        $param = [
            'req' => [
                'out_trade_no' => $order_id,
                'body' => '客服电话:17081089402',
                'total_fee' => $this->data['money'] * 100,
                'mch_create_ip' => get_ip(),
                'sub_openid'=>$open_id,
            ],
            'service' => 'pay.weixin.jspay',
            'aurl' => $aurl,
            'nurl' => $nurl,
        ];

        session("get",null);
        $request = new \Request('js');
        $res = $request->submitOrderInfo($param);
        Think\Log::record('wap jump err:'.json_encode($res),'DEBUG',true);
        if($res['status'] == 500){
            //header("Location: ".'http://'.$_SERVER['HTTP_HOST'].'/PayApi/link?'.$_SERVER['QUERY_STRING']);
            //exit;
            $this->error("支付发起错误: ".$res['msg']);
        }

        $url = 'https://pay.swiftpass.cn/pay/jspay?token_id='.$res['token_id'].'&showwxtitle=1';
        //echo '<script type="text/javascript">setTimeout("window.location.href=\''.$url.'\'",3);</script>';
        header('Location:'.$url);
    }

    /**
     * 威富通 微信支付异步回调
     */
    public function callback_wap()
    {
        vendor('Wechatwap.Wechatwap');
        $request = new \Request('wap');
        $res = $request->callback();
        Think\Log::record('wap jump err:'.json_encode($res),'DEBUG',true);
        if($res['status'] != '00'){
            Think\Log::record('wap jump err:'.$res['msg'] . '; json:' .json_encode($res['data']),'DEBUG',true);
            echo 'failure';
            exit;
        }

        $data = $res['data'];
        $order= M("payapi_log")->where(array("orderid"=>$data['out_trade_no']))->find();
        if(empty($order['id'])){
            //订单不存在
            Think\Log::record('wap jump order err:' .json_encode($res['data']),'DEBUG',true);
            echo 'failure';
            exit;
        }

        if($order['status'] == 0){
            $_order['pay_time'] = strtotime($data['time_end']);
            $_order['status'] = 1;
            $_order['transaction_id'] = $data['transaction_id'];
            if( M("payapi_log")->where(array("id"=>$order['id']) )->save($_order) !== false){
                Think\Log::record("order: ".json_encode($order),'DEBUG',true);
                $this->add_pay($order['id']);
                setcookie('isvip', 1, 3*24*3600);
                setcookie('orderid', $order['orderid'], 3*24*3600);
                setcookie('pay_callback_url', $order['call_url']."?type=jacall&orderid={$order['orderid']}", 3*24*3600);
            }
        }

        echo 'success';
    }

    /**
     * 威富通 微信支付异步回调
     */
    public function callback_js()
    {
        vendor('Wechatwap.Wechatwap');
        $request = new \Request('js');
        $res = $request->callback();
        Think\Log::record('wap jump err:'.json_encode($res),'DEBUG',true);
        if($res['status'] != '00'){
            Think\Log::record('wap jump err:'.$res['msg'] . '; json:' .json_encode($res['data']),'DEBUG',true);
            echo 'failure';
            exit;
        }

        $data = $res['data'];
        $order= M("payapi_log")->where(array("orderid"=>$data['out_trade_no']))->find();
        if(empty($order['id'])){
            //订单不存在
            Think\Log::record('wap jump order err:' .json_encode($res['data']),'DEBUG',true);
            echo 'failure';
            exit;
        }

        if($order['status'] == 0){
            $_order['pay_time'] = strtotime($data['time_end']);
            $_order['status'] = 1;
            $_order['transaction_id'] = $data['transaction_id'];
            if( M("payapi_log")->where(array("id"=>$order['id']) )->save($_order) !== false){
                Think\Log::record("order: ".json_encode($order),'DEBUG',true);
                $this->add_pay($order['id']);

                setcookie('isvip', 1, 3*24*3600);
                setcookie('orderid', $order['orderid'], 3*24*3600);
                setcookie('pay_callback_url', $order['call_url']."?type=jacall&orderid={$order['orderid']}", 3*24*3600);
            }
        }

        echo 'success';
    }

    /**
     * 创建付款记录
     * @param $id integer 订单id
     * @return bool
     */
    private function add_pay($id)
    {
        $prefix = C('DB_PREFIX');
        $order= M("payapi_log")->where(array("id"=>$id))->find();
        $count = M('pay_log')->where("cps_app_id='1000000001' AND pay_serial='{$order['orderid']}'")->count();
        if($count > 0){
            return false;
        }
        $member = [];
        if($order['refid']) {
            $member = M('member')->field('user,cid,g.uid,g.group_id AS gid,t,deduct_rate')
                ->join("{$prefix}auth_group_access g ON g.uid={$prefix}member.uid", 'LEFT')
                ->where("{$prefix}member.uid={$order['refid']}")
                ->find();
        }

        $data['account'] = 1;
        $ref = 'ref1_id';
        $two_deduct = false;//是否二次扣量
        if(empty($member) || empty($member['gid']) || $member['gid'] == 1){
            $data['account'] = -1;
            $data['ref1_id'] = $order['refid'];
            $data['ext'] = $order['refid'];
        } elseif($member['gid'] == 2) {
            $data['ref1_id'] = $order['refid'];
            $data['ref1_name'] = $member['user'];
        } elseif($member['gid'] == 3) {
            $ref = 'ref2_id';
            $parent = M('member')->where('uid='.$member['cid'])->find();
            if($parent['enable_deduct'] && $member['deduct_rate'])
                $two_deduct = true;
            $data['ref1_id'] = $parent['uid'];
            $data['ref1_name'] = $parent['user'];
            $data['ref2_id'] = $order['refid'];
            $data['ref2_name'] = $member['user'];
        }

        Think\Log::record('bili:' . $data['account'] . "--{$order['ad_app_id']} -- " .date("Y-m-d H:i:s", $member['t']), 'DEBUG', true);
        //扣量
        if($data['account']!=-1 && !$order['ad_app_id'] && $member['t']+1*3600*24<=time()){
            Think\Log::record('bili-t:' . $member['t'], 'DEBUG', true);
            //在$minSec及$maxSec之间的判断为渠道在测试,不扣量
            //大于100秒的全部扣
            $minSec = 150;
            $maxSec = 3000;
            if(!empty($order['stop_time']) && $order['stop_time']>$minSec && $order['stop_time']<$maxSec){
                $data['account'] = 0;
                Think\Log::record("bili-cc stop_time({$order['id']}) set to 0:" . $order['stop_time']." min:{$minSec},max:{$maxSec}", 'DEBUG', true);
//            }
//            if($this->get_rand(array('ref' => $ref,'refid' => $order['refid']))){
//                $data['account'] = 0;
            } else {
                if($two_deduct && $this->get_rand(array('ref' => $ref,'refid' => $order['refid'],'rate'=>$member['deduct_rate']))){
                    $data['account'] = 2;
                } else {
                    $data['account'] = 1;
                }
            }

            //第一个充值不扣量
            $paycount = M('pay_log')->where("$ref={$order['refid']} AND pay_time>".strtotime(date('Y-m-d 00:00:00')))->count();
            $paycount < 1 && $data['account'] = 1;
            Think\Log::record('bili-a:' . $data['account'], 'DEBUG', true);
        }
        $type = empty($order['pay_channel']) ? 1 : $order['pay_channel'];

        $data['uid'] = $order['pay_orderid'] ? $order['pay_orderid'] : 0;
        $data['username'] = '';
        $data['cps_app_id'] = '1000000001';
        $data['pay_serial'] = $order['orderid'];
        $data['ad_app_id'] = $order['ad_app_id'];  //username
        $data['ad_app_uid'] = $order['ad_app_uid']; //other
        $data['pay_channel_serial'] = $order['transaction_id'];
        $data['pay_channel'] = $type;
        $data['pay_amount'] = $order['money'];
        $data['pay_type'] = 1;
        $data['pay_time'] = $order['pay_time'];
        $data['pay_ip'] = $order['ip'];

        $data['ref1_id'] =isset($data['ref1_id'])?$data['ref1_id']:0;
        $data['ref2_id'] =isset($data['ref2_id'])?$data['ref2_id']:0;

        Think\Log::record(json_encode($data),'DEBUG',true);
//Think\Log::record("bank_data:".json_encode($order),'DEBUG',true);
        $pay_log=M('pay_log')->add($data);

        if($order['ad_app_id']&&$pay_log!== false){

            $code=$this->postpayinfo($order,$order['refid']);
        }

        return true;
    }

    /**
     * 创建注册记录
     * @param $param array
     * @return bool
     */
    private function add_register($param)
    {
        $prefix = C('DB_PREFIX');
        $count = M('register_log')->where("openid='{$param['openid']}' AND cps_app_id='{$param['cps_app_id']}'")->count();
        if($count > 0){
            return false;
        }
        $member = [];
        if($param['refid']) {
            $member = M('member')->field('user,cid,g.uid,g.group_id AS gid')
                ->join("{$prefix}auth_group_access g ON g.uid={$prefix}member.uid", 'LEFT')
                ->where("{$prefix}member.uid={$param['refid']}")
                ->find();
        }

        if(empty($member) || empty($member['gid']) || $member['gid'] == 1){
            $data['account'] = 0;
            $data['ref1_id'] = $param['refid'];
            $data['ext'] = $param['refid'];
        } elseif($member['gid'] == 2) {
            $data['ref1_id'] = $param['refid'];
            $data['ref1_name'] = $member['user'];
        } elseif($member['gid'] == 3) {
            $parent = M('member')->where('uid='.$member['cid'])->find();
            $data['ref1_id'] = $parent['uid'];
            $data['ref1_name'] = $parent['user'];
            $data['ref2_id'] = $param['refid'];
            $data['ref2_name'] = $member['user'];

        }

        $data['account'] = 1;
        if(mt_rand(1,100)<80) $data['account'] = 0;

        $data['username'] = '';
        $data['uid'] = empty($param['uid'])?time()-1472000000:$param['uid'];
        $data['sex'] = $param['sex'];
        $data['openid'] =  $param['openid'];
        $data['cps_app_id'] = $param['cps_app_id'];
        $data['reg_type'] = $param['reg_type'] ? $param['reg_type'] : 1;
        $data['reg_time'] = $param['reg_time'] ? $param['reg_time'] : time();
        $data['reg_ip'] = $param['reg_ip'];
        $data['ref1_id'] =isset($data['ref1_id'])?$data['ref1_id']:0;
        Think\Log::record("reg--pay-data: ".json_encode($data),'DEBUG',true);

        //保证注册不比充值少
        if($data['ref2_id']){
            $today = strtotime(date("Y-m-d 0:0:0"));
            $regcount = M('register_log')->where("ref2_id='{$data['ref2_id']}' AND reg_time>{$today} and account=1")->count();
            $paycount = M('pay_log')->where("ref2_id='{$data['ref2_id']}' AND pay_time>{$today} and account=1")->count();
            Think\Log::record("reg--pay2-{$data['ref2_id']}: reg:".$regcount.' pay:'.$paycount,'DEBUG',true);

            //订单量/已付款的>5,则此条注册无效
            //随机一个注册比
            $randrate = mt_rand(1500,2500)/1000;
            Think\Log::record("reg--pay2-{$data['ref2_id']} randrate: ".$randrate,'DEBUG',true);
            $nowrate = $paycount?$regcount/$paycount:0;
            Think\Log::record("reg--pay2-{$data['ref2_id']} nowrate: ".$nowrate,'DEBUG',true);
            if($paycount && $regcount && $nowrate>$randrate){
                $data['account'] = 0;
                Think\Log::record("reg--pay2-{$data['ref2_id']} randrate account set to 0",'DEBUG',true);
            }
            //当天没有注册的不扣
            if(!$regcount) $data['account'] = 1;
            //有注册没有充值的扣掉
            if(!$paycount && $regcount>mt_rand(2,4)){
                Think\Log::record("reg--pay2-{$data['ref2_id']} pay is 0 account set to 0",'DEBUG',true);
                $data['account'] = 0;
            }

            if($paycount>$regcount){
                $dao = M();
                $c2update = $paycount-$regcount+mt_rand(1,3);
                $sql = "update qw_register_log set account=1 where account=0 and ref2_id='{$data['ref2_id']}' AND reg_time>{$today} limit {$c2update}";
                Think\Log::record("reg--pay2-{$data['ref2_id']} sql: ".$sql,'DEBUG',true);
                $dao->execute($sql);
            }
        }elseif($data['ref1_id']){
            $today = strtotime(date("Y-m-d 0:0:0"));
            $regcount = M('register_log')->where("ref1_id='{$data['ref1_id']}' AND reg_time>{$today} and account=1")->count();
            $paycount = M('pay_log')->where("ref1_id='{$data['ref1_id']}' AND pay_time>{$today} and account>=1")->count();
            Think\Log::record("reg--pay1-{$data['ref1_id']}: reg:".$regcount.' pay:'.$paycount,'DEBUG',true);

            //订单量/已付款的>5,则此条注册无效
            //随机一个注册比
            $randrate = mt_rand(1500,3500)/1000;
            Think\Log::record("reg--pay1-{$data['ref1_id']} randrate: ".$randrate,'DEBUG',true);
            $nowrate = $paycount?$regcount/$paycount:0;
            Think\Log::record("reg--pay1-{$data['ref1_id']} nowrate: ".$nowrate,'DEBUG',true);
            if($paycount && $regcount && $nowrate>$randrate){
                $data['account'] = 0;
                Think\Log::record("reg--pay1-{$data['ref1_id']} randrate account set to 0",'DEBUG',true);
            }
            //当天没有注册的不扣
            if(!$regcount) $data['account'] = 1;
            //有注册没有充值的扣掉
            if(!$paycount && $regcount>mt_rand(3,8)){
                Think\Log::record("reg--pay1-{$data['ref1_id']} pay is 0 account set to 0",'DEBUG',true);
                $data['account'] = 0;
            }

            if($paycount>$regcount){
                $dao = M();
                $c2update = $paycount-$regcount+mt_rand(1,3);
                $sql = "update qw_register_log set account=1 where account=0 and ref1_id='{$data['ref1_id']}' AND ref2_id=0 AND reg_time>{$today} limit {$c2update}";
                Think\Log::record("reg--pay1-{$data['ref1_id']} sql: ".$sql,'DEBUG',true);
                $dao->execute($sql);
            }
        }

        M('register_log')->data($data)->add();

        return true;
    }


    /*
     * 获取真阅读token
     */

    public function actiontoken($refid=""){

        $DesLogic=new \Logic\DesLogic();

        $data['username']='xdy';
        $data['password']='123456';

        $log=$DesLogic->encrypt(json_encode($data));

        $cps_ad=C('cps_ad');
        $url=$cps_ad[$refid]['callbank_url']."auth";

        $code=$this->httppost($url,$log);

        /*if(I('get.other')){
            echo $url."<br>";
            var_dump($code);
            print_r(json_decode($code,true));
        }*/

        return json_decode($code,true);
    }

    /*
     * 传输支付信息
     * */

    public  function postpayinfo($payuser="",$refid=""){
        if(empty($payuser) && I('get.orderid')){
            $orderid = I('get.orderid');
            $refid = I('get.refid');
            $order = M('pay_log')->where("pay_serial='{$orderid}'")->find();
            if($order['call_status'] != 0 && !$refid){
                echo 'fail';exit;
            }
            $data = [
                'orderid' => $orderid,
                'money' => $order['pay_amount'],
                'ref1_id' => $refid,
                'ad_app_id' => I('get.ad_app_id'),
                'ad_app_uid' => I('get.ad_app_uid')
            ];
            $payuser = array_merge($order,$data);
            M('pay_log')->where("id={$order['id']}")->data($data)->save();
        }
        $refid=$refid?$refid:450;
        //$username='xdy';
        /*$data = json_decode($this->get_php_file(APP_PATH.'Runtime/Data/'.$username . "_token.php"));
        if($data->expire_time<time()){}*/

        $data=$this->actiontoken($refid);

        //$data['expire_time']=time()+24*3600;
        //$this->set_php_file(APP_PATH.'Runtime/Data/'.$username . "_token.php", json_encode($data));

        $token=$data['data']['token'];

        $DesLogic=new \Logic\DesLogic();
//测试用代码
        /*if(I('get.other')){
            $payuser['orderid']='v146927095315559';
            $payuser['pay_time']="1469282024";
            $payuser['money']="48";
            $payuser['ad_app_uid']=I('get.other');
        }*/

        $arr['orderid']=$payuser['orderid'];
        $arr['time']=$payuser['pay_time'];
        $arr['value']=$payuser['money'];
        $arr['token']=$token;
        $arr['other']=$payuser['ad_app_uid'];

        Think\Log::record("payinfo_arr: ".json_encode($arr,JSON_UNESCAPED_UNICODE),'DEBUG',true);

        $postdata=$DesLogic->encrypt(json_encode($arr));

        $cps_ad=C('cps_ad');
        $url=$cps_ad[$refid]['callbank_url']."auth/index/paychk";
        $code=$this->httppost($url,$postdata);
        //测试用代码
        /*if(I('get.other')){
            echo json_encode($arr)."<br>";
            print_r($postdata)."<br>";
            print_r(json_decode($code,true));
        }*/

        if($code['code']!=2000){
            Think\Log::record("pay_log_err: ".json_encode($code),'DEBUG',true);
            $this->httppost($url,$postdata);
        }else{
            M('payapi_log')->where(['id'=>$payuser['id']])->save(['call_status'=>1]);
        }

        Think\Log::record("payinfo_code: ".$code,'DEBUG',true);

        return json_decode($code,true);
    }

    /*
     * 读取TOKEN
     * */
    private function get_php_file($filename)
    {
        return trim(substr(file_get_contents($filename), 15));
    }

    /*
     * 存入TOKEN
     * */
    private function set_php_file($filename, $content)
    {
        $fp = fopen($filename, "w");
        fwrite($fp, "<?php exit();?>" . $content);
        fclose($fp);
    }

    /*
     * GET传参
     * */
    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    /*
     * POST 传参
    */

    private function  httppost($url,$data){
        $ch = curl_init ();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
        $return = curl_exec ( $ch );
        curl_close ( $ch );
        Think\Log::record(" http post: url: ".$url.' data : '.$data.' ret: '.$return,'DEBUG',true);
        return $return;
    }




    /**
     *   微信静默授权获取code code在后去openid
     */
    private function Wexin_Get_Code(){
        $code   = I('get.code');
        $weixin = $this->WEIXIN_PAY_API;
        // $Appid  = $weixin['appid'];
        $Appid  = 'wx25ffdee9b14b671a';
        // var_dump($Appid);die();
        $r_url  = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        $r_url  = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$Appid}&redirect_uri={$r_url}&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect.Appid";
        if($code){
            $user =  $this->get_weixin_info($code);
            if($user['errcode'] == "40029"){
                header("Location:".$url);
            }else{
                return $user;
            }
        }else{
            header("Location:".$url);
            die();
        }
    }


    /*
     * 获取微信用户信息
    */
    private function get_weixin_info($code)
    {
        $weixin = $this->WEIXIN_PAY_API;
        $Appid  = 'wx25ffdee9b14b671a';
        $secret = 'f2efb1f18e2cc2a1e756c6ee247ea9ae';
        $get_token_url="https://api.weixin.qq.com/sns/oauth2/access_token?appid={$Appid}&secret={$secret}&code={$code}&grant_type=authorization_code";
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$get_token_url);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res,true);
    }

    /**
     * 概率计算 10万次计算 相差+-300
     * @param $data array
     * @return bool
     */
    private function get_rand($data) {
        $per = isset($data['rate']) ? $data['rate'] : M('setting')->where("k='Proportion'")->getField('v');
        if(empty($per)) return 0;
        $per_arr = [
            '0' => 100 - $per,
            '1' => $per
        ];

        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($per_arr);

        //概率数组循环
        foreach ($per_arr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($per_arr);

        $time = strtotime(date("Y-m-d"));
        $where = "{$data['ref']}={$data['refid']} and pay_time>={$time}";
        if($data['ref'] == 'ref2_id'){
            $where .= " AND account=1";
        } elseif($data['ref'] == 'ref1_id'){
            $where .= " AND account>=1";
        } else {
            $where .= " AND account=1";
        }
        $account1=M('pay_log')->where($where)->count();
        $total=M('pay_log')->where($where)->count();
        if($total) {
            Think\Log::record("bili-account:{$account1} / {$total} = " . floatval($account1 / $total) . "--{$result}", 'DEBUG', true);
        }

        return $result;
    }

    protected function account($ref,$refid){
        $time=strtotime(date('Y-m-d'));
        $account1=M('pay_log')->where("{$ref}={$refid} and pay_time>={$time} and account=1")->count();
        if($account1==0){
            return false;
        }
        $total=M('pay_log')->where("{$ref}={$refid} and pay_time>={$time}")->count();

        $per=M('setting')->where("k='Proportion'")->getField('v');
        if(empty($per)){
            $per=100;
        }
        $proportion = 'bili--' . $refid . " : " . $account1 . " / " . $total . ' = ' . round($account1/$total, 3);
        Think\Log::record($proportion,'DEBUG',true);
        if($account1/$total>floatval(1 - $per/100)){
            return true;
        }

        return false;
    }

    //检查链接
    protected function chackurl($url)
    {
        return true;
        $_url = "http://www.yundq.cn/url/wx?key=693E6C9885DC4231&url=" . $url;
        $data = $this->httpGet($_url);
        WeixinLog("task url check: {$url} : {$data}");
        if (strpos($data, '黑名单')!==false) {
            return false;
        } elseif(strpos($data, '可访问')!==false||strpos($data, '待验证')!==false||strpos($data, '剩余验证次数')!==false) {
            return true;
        }else{
            $message="第三方接口返回数据异常,请手动检测";
            $pushtime=$_SESSION['panduan'];
            if(time()-$pushtime>=3600){
                //$this->push_weihu_tel($message);
                $_SESSION['panduan']=time();
            }
            //echo  '<a style="font-size:13pt;color:red;">'.$message.':'.'<a>';
            return true;
        }
    }


    /**
     * 中瑞支付
     */
    public function pay7684()
    {
        $order_id = $this->data['orderid'];
        if(empty($order_id)){
            $order_id = "v".time().rand(10000,999999);
        }

        /*if( M("payapi_log")->where(array("orderid"=>$order_id,'status'=>1))->find() ){
            $this->error("已支付");
        }*/
        $config_Zr = C('PAY7684_CONFIG');
        //var_dump($this->data['call_url']);die;
        $money = empty($this->data['money']) ? 48 : $this->data['money'];
        $bank_id = empty($this->data['bank_id']) ? 2005 : $this->data['bank_id'];

        $payapi_log = array(
            'prepayid'    =>  $order_id,
            'orderid'     =>  $order_id,
            'pay_orderid' =>  $order_id,
            'openid'      =>  time(),
            'uid'         =>  time(),
            'refid'       =>  $this->data['refid'],
            'ad_app_id'   =>  $this->data['ad_app_id'],
            'ad_app_uid'  =>  $this->data['ad_app_uid'],
            'gid'         =>  '1',
            'time'        =>  time(),
            'status'      =>  0,
            'money'       =>  $money,
            'ip'          =>  get_ip(),
            'call_url'    =>  $this->data['call_url']?$this->data['call_url']:'ceshi',
            'pay_channel'    =>  8,
            'stop_time'    =>  $this->data['time'],
            'utype'    =>  empty($this->data['utype']) ? '' : $this->data['utype'],
            'uiver'    =>  empty($this->data['uiver']) ? '' : $this->data['uiver'],
        );
        if( M("payapi_log")->add($payapi_log) == false){
            $this->error("支付发起错误!");
        }
        $register_log = [
            'refid' => $this->data['refid'],
            'openid' => $order_id,
            'sex' => 1,
            'cps_app_id' => '1000000001',
            'reg_type' => 8,
            'reg_time' => time(),
            'reg_ip' => get_iplong(),
        ];
        $this->add_register($register_log);
        session("get",null);

        //调用支付接口
        require_once(APP_PATH."Lib/Pay7684/lib/yun_md5.function.php");

        //构造要请求的参数数组，无需改动
        $parameter = array(
                "partnerid" => trim($config_Zr['partnerid']),
                "out_trade_no"  => $order_id,
                "productname"   => '客服电话:17081089402',
                "total_fee" => $money,
                "productintro"  => '',
                "no_url"    => 'http://'.$_SERVER['HTTP_HOST'].'/PayApi/callback_pay7684',
                "re_url"    => 'http://'.$_SERVER['HTTP_HOST'].'/PayApi/callback_re_pay7684?orderid='.$order_id,
                "pay_channel"   => 'weixin_pay',
        );

        $html_text = zhrpay($parameter, "安全支付进行中...");
        echo $html_text;

    }


    /**
    * 同步回调通知
    **/
    public function callback_re_pay7684(){

        Think\Log::record('callback_re_pay7684:'.json_encode($this->data).'   '.json_encode($_REQUEST),'DEBUG',true);
        //调用支付接口
        require_once(APP_PATH."Lib/Pay7684/lib/yun_md5.function.php");
        $config_Zr = C('PAY7684_CONFIG');
        //计算得出通知验证结果
        $yunNotify = md5Verify($this->data['seller_id'],$this->data['re_out_trade_no'],$this->data['re_trade_no'],$this->data['re_total_fee'],$this->data['trade_status'],$config_Zr['key'],$this->data['re_sign']);


        if($yunNotify) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            if($this->data['trade_status']=='TRADE_SUCCESS'){

        //—————————————————————————以下是可修改的代码———————————————————————————————————
                    /*
                    加入您的入库及判断代码;
                    判断返回金额与实金额是否想同;
                    判断订单当前状态;
                    完成以上才视为支付成功
                    */

                       $order= M("payapi_log")->where(array("orderid"=>$_REQUEST['re_out_trade_no']))->find();
                        if(!$order['call_url'] || $order['call_url']=='ceshi'){
                            header("Location: http://www.huajiao.com/mobile");
                            exit;
                        }
                        if(empty($order['id'])){
                            $this->error("支付失败!",$order['call_url']);
                        }

                        $this->assign('order', $order);
                        $this->display('success');
                       //echo '已经成功返回！';


        //—————————————————————————以上是可修改的代码———————————————————————————————————
                }



        //echo 'success';

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        }
        else {
            //验证失败
            echo "验证失败";
        }
    }
    /**
     * 中瑞支付 异步回调
     */
    public function callback_pay7684()
    {
        //调用支付接口
        require_once(APP_PATH."Lib/Pay7684/lib/yun_md5.function.php");

        $config_Zr = C('PAY7684_CONFIG');

        //计算得出通知验证结果
        $yunNotify = md5Verify($_REQUEST['seller_id'],$_REQUEST['re_out_trade_no'],$_REQUEST['re_trade_no'],$_REQUEST['re_total_fee'],$_REQUEST['trade_status'],$config_Zr['key'],$_REQUEST['re_sign']);

        if($yunNotify) {//验证成功
            /////////////////////////////////////////////////////////

            if($_REQUEST['trade_status']=='TRADE_SUCCESS'){

                //—————————————————————————以下是可修改的代码———————————————————————————————————
                    /*
                    加入您的入库及判断代码;
                    判断返回金额与实金额是否想同;
                    判断订单当前状态;
                    完成以上才视为支付成功
                    */

                    $order= M("payapi_log")->where(array("orderid"=>$_REQUEST['re_out_trade_no']))->find();
                    if(empty($order['id'])){
                        //订单不存在
                        Think\Log::record('pay7684 jump order err:'.json_encode($data),'DEBUG',true);
                        echo 'fail2';
                        exit;
                    }

                    if($order['status'] == 0){
                        $_order['pay_time'] = time();
                        $_order['status'] = 1;
                        $_order['transaction_id'] = $_order['pay_channel_serial']=$_REQUEST['re_trade_no'];
                        //var_dump($_order);die;
                        if( M("payapi_log")->where(array("id"=>$order['id']) )->save($_order) !== false){
                            Think\Log::record("order: ".json_encode($order),'DEBUG',true);
                            $this->add_pay($order['id']);
                        }
                    }
        //—————————————————————————以上是可修改的代码———————————————————————————————————
                }



            echo "success";     //请不要修改或删除

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        }
        else {
            Think\Log::record('pay7684 jump pay err:'.json_encode($data),'DEBUG',true);
            //验证失败
            echo "fail";//请不要修改或删除
        }
    }



    /**
     *  微信官方二维码支付
     */
    public function wechatqr()
    {

        import("Lib/WeiXinPay/Autoload");
        $app = new Application($this->options);
        if($_COOKIE['isvip']){
            if(!empty($_COOKIE['pay_callback_url'])){
                header("Location: ".$_COOKIE['pay_callback_url']);
                exit;
            }elseif(!empty($_SERVER['HTTP_REFERER'])){
                // $oid = empty($_COOKIE['orderid'])?$order_id:$_COOKIE['orderid'];
                $oid = $order_id;
                $url = $_SERVER['HTTP_REFERER']."&isvip={$oid}";
                //header("Location: ".$url);
                exit;
            }
        }
        $data["weixin_pay_api"] = $this->WEIXIN_PAY_API;
        if(empty($this->data['orderid'])||$this->data['test']){
            $this->data['orderid'] = "v".time().rand(10000,999999);
        }
        /*if( M("payapi_log")->where(array("orderid"=>$this->data['orderid'],'status'=>1))->find() ){
            $this->error("已支付");
        }*/


        /*
        $pay_log = M('pay_log')->where("uid='{$open_id}' AND pay_amount>1")->find();
        if($pay_log['id']&&$this->data['money']>1){
            $url = 'http://now.qq.com/h5/index.html?roomid=6628544&_bid=&_wv=&from=';
            header("Location:".$url);
            die();
        }
        */


        $payment    = $app->payment;
        $orderInfo  = $this->OrderInfo;
        //$orderInfo['total_fee'] = 0.03;
        $attributes = [
            'trade_type'       => 'NATIVE', // JSAPI，NATIVE，APP...
            'body'             => $orderInfo['name'],
            'detail'           => $orderInfo['detail'],
            'out_trade_no'     => $this->data['orderid'], //订单号
            'total_fee'        => intval($orderInfo['total_fee']*100),
            'notify_url'       => 'http://'.$_SERVER['HTTP_HOST'].'/PayApi/callbackqr', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'openid'           => $open_id,
        ];
        $order = new Order($attributes);
        $result = $app->payment->prepare($order);
        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
            $prepayId = $result->prepay_id;
        }


        $filename = '/Public/images/wechatqr/'.substr(md5($url),0,3).'/'.substr($this->data['orderid'],1,4).'/'.$this->data['orderid'].'.png';

        $url = $result['code_url'];
        // 二维码数据
        // 生成的文件名
        $absFile = dirname(THINK_PATH) . $filename;

        if(!is_dir(dirname($absFile))){
            if(!mkdir(dirname($absFile),0777,true)){
                return false;
            };
        }

        $errorCorrectionLevel =intval(3) ;//容错级别
        $matrixPointSize = intval(5);//生成图片大小
        vendor('phpqrcode.phpqrcode');
        //生成二维码图片
        $object = new \QRcode();

        $ret = $object::png($url, $absFile, $errorCorrectionLevel, $matrixPointSize, 2);

        $money = empty($this->data['money']) ? 48 : $this->data['money'];
        $payapi_log = array(
            'prepayid'    =>  $prepayId,
            'orderid'     =>  $this->data['orderid'],
            'pay_orderid' =>  $open_id,
            'openid'      =>  time(),
            'uid'         =>  'cps',
            'refid'       =>  $this->data['refid'],
            'ad_app_id'   =>  $this->data['ad_app_id'],
            'ad_app_uid'  =>  $this->data['ad_app_uid'],
            'gid'         =>  '1',
            'time'        =>  time(),
            'status'      =>  0,
            'money'       =>  $money,
            'ip'          =>  get_ip(),
            'call_url'    =>  $this->data['call_url']?$this->data['call_url']:'ceshi',
            'pay_channel'    =>  1,
            'stop_time'    =>  $this->data['time'],
            'utype'    =>  empty($this->data['utype']) ? '' : $this->data['utype'],
            'uiver'    =>  empty($this->data['uiver']) ? '' : $this->data['uiver'],
            'merchant_id'    =>  $this->options['app_id'],
        );

        if( M("payapi_log")->add($payapi_log) == false){
            $this->error("支付发起错误:-1!");
            Think\Log::record("wechat js order add arr:".M("payapi_log")->getlastsql(),'DEBUG',true);

        }
        //Think\Log::record("order_arr:".M("payapi_log")->getlastsql(),'DEBUG',true);
        $register_log = [
            'uid'=>$this->data['orderid'],
            'refid' => $this->data['refid'],
            'openid' => $open_id,
            'sex' => 1,
            'cps_app_id' => '1000000001',
            'reg_type' => 1,
            'reg_time' => time(),
            'reg_ip' => get_iplong(),
        ];
        Think\Log::record("refid{$this->data['refid']}--".json_encode($register_log),'DEBUG',true);
        $this->add_register($register_log);

        $data['call_url'] = $this->data['call_url'];
        $data['qrimg'] = $filename;
        $data['order_info'] = array(
            'orderid'=>$this->data['orderid'],
            'money'  =>$money,
        );
        session("get",null);
        $this->assign($data);
        $this->display('wechatqr');
    }


     /**
     *  微信官方二维码支付回调
     */
    public function callbackqr(){
        import("Lib/WeiXinPay/Autoload");
        //file_put_contents(dirname(THINK_PATH)).'./pay.log',date("ymd H:i:s").PHP_EOL.file_get_contents('php://input', 'r').PHP_EOL.json_encode($_REQUEST).PHP_EOL,
        //FILE_APPEND);
        Think\Log::record("qr order back: ".date("ymd H:i:s").PHP_EOL.file_get_contents('php://input', 'r').PHP_EOL.json_encode($_REQUEST).PHP_EOL,'DEBUG',true);
        $options = $this->options;
        $app = new Application($options);
        $response = $app->payment->handleNotify(function($notify, $successful){
            $order= M("payapi_log")->where(array("orderid"=>$notify['out_trade_no'],'status'=>0))->find();
            $_order['pay_time'] = strtotime($notify['time_end']);
            $_order['transaction_id'] = $notify['transaction_id'];
            if ($notify['result_code'] =='SUCCESS') {
                $_order['status'] = 1;
            } else {
                $_order['status'] = 2;
            }
            if( M("payapi_log")->where(array("orderid"=>$notify['out_trade_no']) )->save($_order) !== false){

                if(isset($order)&&$_order['status']==1) {
                    Think\Log::record("order: ".json_encode($order),'DEBUG',true);
                    $this->add_pay($order['id']);
                    setcookie('isvip', 1, 3*24*3600);
                    setcookie('orderid', $order['orderid'], 3*24*3600);
                    setcookie('pay_callback_url', $order['call_url']."?type=jacall&orderid={$order['orderid']}", 3*24*3600);
                }
                /*$url = $order['call_url'];
                $url .= "?orderid=".$notify['out_trade_no']."&status=SUCCESS&key=18361130555";
                //200 成功 300已提交 400 错误
                $call_back_code = $this->httpGet($url);
                if ($call_back_code == 400) {
                     $this->httpGet($url);
                }else{
                     $call_data = array(
                         'call_status'=>1
                      );
                     M("payapi_log")->where(array("orderid"=>$notify['out_trade_no']) ) ->save($call_data);
                }*/
            }
            return true;
        });
        return $response;
    }
    /**
    * 充值完成确认
    **/
    public function CheckRecharge(){
        $ret=array('code'=>0,'msg'=>'');
        $orderid=$_GET['orderid'];
        if(empty($orderid)){
            $ret['msg']='订单号不存在';
        }else{
            $pay_ret= M("pay_log")->where("cps_app_id='1000000001' AND pay_serial='{$orderid}'")->find();
            if(empty($pay_ret)){//充值不存在
                $ret['msg']='订单号不存在';
            }else{
                 $ret['code']=1;
            }
        }
        echo json_encode($ret);
    }

    // 金海哲支付
    public function jhzPay() {
        $order_id = $this->data['orderid'];
        if(empty($order_id)){
            $order_id = "v".time().rand(10000,999999);
        }
        $money = empty($this->data['money']) ? 48 : $this->data['money'];
        $money = 0.01;
        $config = C('JHZPAY_CONFIG');

        $this->createPayapiLog($order_id, $money, 9);
        session('get', null);

        $host = 'http://' . $_SERVER['HTTP_HOST'] . '/PayApi';
        $callbackUrl = $host . '/callback_jhzpay';
        $hrefbackurl = $host . '/callback_re_jhzpay';

        $data = array();
        $data['merchantNo'] = $config['merchantNo'];
        $data['requestNo']  = $order_id;
        $data['amount']     = $money * 100;
        $data['payCode']    = '4003';
        $data['backUrl']    = $callbackUrl;
        $data['pageUrl']    = $hrefbackurl;
        $data['payDate']    = time();
        $data['remark1']    = '客服电话:17081089402';
        $data['remark2']    ='';
        $data['remark3']    = '';
        $data['agencyCode'] = '';

        $signature = $config['merchantNo'] . "|"
                .$data['requestNo'] . "|"
                .$data['amount'] . "|"
                .$data['pageUrl'] . "|"
                .$data['backUrl'] . "|"
                .$data['payDate'] . "|"
                .$data['agencyCode'] . "|"
                .$data['remark1'] . "|"
                .$data['remark2'] . "|"
                .$data['remark3'];
        $pr_key = openssl_pkey_get_private($config['private_key'], '');
        $pu_key = openssl_pkey_get_public($config['public_key']);

        $sign = '';
        openssl_sign($signature,$sign,$pr_key);
        openssl_free_key($pr_key);
        $sign = base64_encode($sign);
        $data['signature'] = $sign;

        $payUrl = 'http://pay.szjhzxxkj.com/ownPay/pay';
        $query = http_build_query($data);
        $url = $payUrl . '?' . $query;

        header('Location: ' . $url);
    }

    public function callback_jhzpay() {
        Think\Log::record('callback_jhzpay'. json_encode($_REQUEST), 'DEBUG', true);

        $ret        = $_REQUEST['ret'];
        $msg        = $_REQUEST['msg'];
        $resultSign = $_REQUEST['sign'];

        $config = C('JHZPAY_CONFIG');

        $signature  = $ret . '|' . $msg;
        $pr_key = openssl_pkey_get_private($config['private_key'], '');
        $pu_key = openssl_pkey_get_public($config['public_key']);

        $sign = '';
        if(openssl_private_decrypt(base64_decode($resultSign),$sign,$pr_key)) {
            Think\Log::record('签名验证失败:'.$sign, 'ERR', true);
            echo 'SUCCESS';
            return;
        }

        $retArray = json_decode($ret, true);
        $msgArray = json_decode($msg, true);
        if(!is_array($retArray) || $retArray['msg'] != 'SUCCESS') {
            Think\Log::record('支付失败', 'DEBUG', true);
            echo 'SUCCESS';
            return;
        }

        if($this->orderToSuccess($msgArray['no'], $msgArray['payNo'])) {
            echo 'SUCCESS';
        }

        echo 'SUCCESS';
    }

    public function callback_re_jhzpay() {
        Think\Log::record('callback_re_jhzpay'. json_encode($_REQUEST), 'DEBUG', true);

        $ret        = $_REQUEST['ret'];
        $msg        = $_REQUEST['msg'];
        $resultSign = $_REQUEST['sign'];

        $config = C('JHZPAY_CONFIG');

        $pr_key = openssl_pkey_get_private($config['private_key'], '');
        $pu_key = openssl_pkey_get_public($config['public_key']);
        if(openssl_private_decrypt(base64_decode($resultSign),$sign,$pr_key)) {
            Think\Log::record('签名验证失败:'.$sign, 'ERR', true);
            return;
        }

        $retArray = json_decode($ret, true);
        $msgArray = json_decode($msg, true);
        if(!is_array($retArray) || $retArray['msg'] != 'SUCCESS') {
            Think\Log::record('支付失败', 'DEBUG', true);
            return;
        }

        $this->toOrderSuccessPage($msgArray['no']);
    }

    /**
     * 将订单状态修改为成功状态
     * @param  intval $orderid          订单号
     * @param  string $payChannelSerial 支付平台订单号
     * @return boolean
     */
    private function orderToSuccess($orderid, $payChannelSerial) {

        Think\Log::record("order111111111111111: ".json_encode($order),'DEBUG',true);
        $order = M('payapi_log')->where(array(
            'orderid' => $orderid
        ))->find();
        if(empty($order['id'])) {
            Think\Log::record(
                'order not found by id: ' . $orderid,
                'ERR',
                true
            );
            return false;
        }

        if($order['status'] != 0) {
            return true;
        }

        $where = array('id' => $order['id']);
        $update = array(
            'pay_time' => time(),
            'status' => 1,
            'pay_channel_serial' => $payChannelSerial,
            'transaction_id'=>$payChannelSerial
        );
        $saveResult = M('payapi_log')->where($where)->save($update);
        if($saveResult === false) {
            Think\Log::record(
                'update payapi_log failure: ' . json_encode(array(
                    'where' => $where,
                    'update' => $update,
                )),
                'ERR',
                true
            );
        }else{
            Think\Log::record("order: ".json_encode($order),'DEBUG',true);
            $this->add_pay($order['id']);
        }
    }

    /**
     * 执行订单支付成功页面跳转
     * @param  string $orderId 订单号
     */
    private function toOrderSuccessPage($orderId) {
        $order = M('payapi_log')->where(array(
            'orderid' => $orderId
        ))->find();

        if(!$order['call_url'] || $order['call_url'] == 'ceshi') {
            header("Location: http://www.huajiao.com/mobile");
            return;
        }

        if(empty($order['id'])) {
            $this->error("支付失败!",$order['call_url']);
        }

        $this->assign('order', $order);
        $this->display('success');
    }

    /**
     * 创建支付接口调用日志
     * @param  string $orderId    订单号
     * @param  float $money      支付金额
     * @param  intval $payChannel 支付通道
     * @return boolean
     */
    private function createPayapiLog($orderId, $money, $payChannel) {
        $callUrl = $this->data['call_url']?$this->data['call_url']:'ceshi';
        $utype = empty($this->data['utype']) ? '' : $this->data['utype'];
        $uiver = empty($this->data['uiver']) ? '' : $this->data['uiver'];

        $payapi_log = array(
            'prepayid'    => $orderId,
            'orderid'     => $orderId,
            'pay_orderid' => $orderId,
            'openid'      => time(),
            'uid'         => time(),
            'refid'       => $this->data['refid'],
            'ad_app_id'   => $this->data['ad_app_id'],
            'ad_app_uid'  => $this->data['ad_app_uid'],
            'gid'         => '1',
            'time'        => time(),
            'status'      => 0,
            'money'       => $money,
            'ip'          => get_ip(),
            'call_url'    => $callUrl,
            'pay_channel' => $payChannel,
            'stop_time'   => $this->data['time'],
            'utype'       => $utype,
            'uiver'       => $uiver,
        );
        if(M("payapi_log")->add($payapi_log) == false){
            $this->error("支付发起错误!");
        }

        $register_log = [
            'refid'      => $this->data['refid'],
            'openid'     => $orderId,
            'sex'        => 1,
            'cps_app_id' => '1000000001',
            'reg_type'   => $payChannel,
            'reg_time'   => time(),
            'reg_ip'     => get_iplong(),
        ];
        $this->add_register($register_log);
    }
    
    
    /**
    * 全富通  WAP
    **/
    public function PayQFT(){
        
        $order_id = $this->data['orderid'];
        if(empty($order_id)){
            $order_id = "v".time().rand(10000,999999);
        }

        /*if( M("payapi_log")->where(array("orderid"=>$order_id,'status'=>1))->find() ){
            $this->error("已支付");
        }*/
        //var_dump($this->data['call_url']);die;
        $money = empty($this->data['money']) ? 1 : $this->data['money'];
        $bank_id = empty($this->data['bank_id']) ? 2005 : $this->data['bank_id'];

        $payapi_log = array(
            'prepayid'    =>  $order_id,
            'orderid'     =>  $order_id,
            'pay_orderid' =>  $order_id,
            'openid'      =>  time(),
            'uid'         =>  time(),
            'refid'       =>  $this->data['refid'],
            'ad_app_id'   =>  $this->data['ad_app_id'],
            'ad_app_uid'  =>  $this->data['ad_app_uid'],
            'gid'         =>  '1',
            'time'        =>  time(),
            'status'      =>  0,
            'money'       =>  $money,
            'ip'          =>  get_ip(),
            'call_url'    =>  $this->data['call_url']?$this->data['call_url']:'ceshi',
            'pay_channel'    =>  8,
            'stop_time'    =>  $this->data['time'],
            'utype'    =>  empty($this->data['utype']) ? '' : $this->data['utype'],
            'uiver'    =>  empty($this->data['uiver']) ? '' : $this->data['uiver'],
        );
        if( M("payapi_log")->add($payapi_log) == false){
            $this->error("支付发起错误!");
        }
        $register_log = [
            'refid' => $this->data['refid'],
            'openid' => $order_id,
            'sex' => 1,
            'cps_app_id' => '1000000001',
            'reg_type' => 8,
            'reg_time' => time(),
            'reg_ip' => get_iplong(),
        ];
        $this->add_register($register_log);
        session("get",null);
        
        
        $Req_arr=array(
            'out_trade_no'=>$order_id,
            'body'=>'测试购买商品',
            'total_fee'=>$money*100,
            'mch_create_ip'=>get_ip(),
            'method'=>'submitOrderInfo'
        );
        $config = C('PAY_QFT');
        
        require(APP_PATH."Lib/PayQFT/class/RequestHandler.class.php");
        require(APP_PATH."Lib/PayQFT/class/PayHttpClient.class.php");
        require(APP_PATH."Lib/PayQFT/class/ClientResponseHandler.class.php");
        require(APP_PATH."Lib/PayQFT/Utils.class.php");
        
        $reqHandler = new \RequestHandler();
        $pay = new \PayHttpClient();
        $resHandler = new \ClientResponseHandler();
        
        $reqHandler->setReqParams($Req_arr,array('method'));
        $reqHandler->setParameter('service','pay.weixin.wappay');//接口类型：pay.weixin.jspay
        $reqHandler->setParameter('mch_id',$config['mchId']);//必填项，商户号，由威富通分配
        $reqHandler->setParameter('version',$config['version']);
        $reqHandler->setGateUrl($config['url']);
        $reqHandler->setKey($config['key']);
        
        //通知地址，必填项
        $reqHandler->setParameter('notify_url','http://'.$_SERVER['HTTP_HOST'].'/PayApi/callback_PayQFT');//通知回调地址，目前默认是空格，商户在测试支付和上线时必须改为自己的，且保证外网能访问到
        $reqHandler->setParameter('callback_url',$payapi_log['call_url']);
        $reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//随机字符串，必填项，不长于 32 位
        $reqHandler->createSign();//创建签名
        
        $data = \Utils::toXml($reqHandler->getAllParameters());
        
        
        $pay->setReqContent($reqHandler->getGateURL(),$data);
        if($pay->call()){
            $resHandler->setContent($pay->getResContent());
            $resHandler->setKey($reqHandler->getKey());
            if($resHandler->isTenpaySign()){
                //当返回状态与业务结果都为0时才返回支付二维码，其它结果请查看接口文档
                if($resHandler->getParameter('status') == 0 && $resHandler->getParameter('result_code') == 0){
                    //echo json_encode(array('token_id'=>$resHandler->getParameter('token_id')));
                    
                    $order= M("payapi_log")->where(array("orderid"=>$order_id))->find();
                    if(!$order['call_url'] || $order['call_url']=='ceshi'){
                        header("Location: http://www.huajiao.com/mobile");
                        exit;
                    }
                    if(empty($order['id'])){
                        $this->error("支付失败!",$order['call_url']);
                    }

                    $this->assign('order', $order);
                    $this->display('success');
                    exit();
                }else{
                    //echo json_encode(array('status'=>500,'msg'=>'Error Code:'.$resHandler->getParameter('err_code').' Error Message:'.$resHandler->getParameter('err_msg')));
                    echo $resHandler->getParameter('err_msg');exit;
                    $this->error("支付失败!",$resHandler->getParameter('err_msg'));
                    exit();
                }
            }
            //echo json_encode(array('status'=>500,'msg'=>'Error Code:'.$resHandler->getParameter('status').' Error Message:'.$resHandler->getParameter('message')));
            $this->error("支付失败!",$resHandler->getParameter('message'));
        }else{
            //echo json_encode(array('status'=>500,'msg'=>'Response Code:'.$pay->getResponseCode().' Error Info:'.$pay->getErrInfo()));
            $this->error("支付失败!",$pay->getErrInfo());
        }
    }
    
    
    /**
    * 全富通异步回调地址
    **/
    function callback_PayQFT(){
        $xml = file_get_contents('php://input');
        require_once(APP_PATH."Lib/PayQFT/Utils.class.php");
        require_once(APP_PATH."Lib/PayQFT/class/ClientResponseHandler.class.php");
        $resHandler = new \ClientResponseHandler();
        $config = C('PAY_QFT');
        
        $resHandler->setContent($xml);
        //var_dump($this->resHandler->setContent($xml));
        $resHandler->setKey($config['key']);
        if($resHandler->isTenpaySign()){
            if($resHandler->getParameter('status') == 0 && $resHandler->getParameter('result_code') == 0){
                //echo $this->resHandler->getParameter('status');
                // //此处可以在添加相关处理业务，校验通知参数中的商户订单号out_trade_no和金额total_fee是否和商户业务系统的单号和金额是否一致，一致后方可更新数据库表中的记录。
                //更改订单状态
                $order= M("payapi_log")->where(array("orderid"=>$resHandler->getParameter('out_trade_no')))->find();
                if(empty($order['id'])){
                    //订单不存在
                    Think\Log::record('pay7684 jump order err:'.json_encode($data),'DEBUG',true);
                    echo 'failure';
                    exit;
                }

                if($order['status'] == 0){
                    $_order['pay_time'] = time();
                    $_order['status'] = 1;
                    $_order['transaction_id'] = $_order['pay_channel_serial']=$_REQUEST['out_transaction_id'];
                    //var_dump($_order);die;
                    if( M("payapi_log")->where(array("id"=>$order['id']) )->save($_order) !== false){
                        Think\Log::record("order: ".json_encode($order),'DEBUG',true);
                        $this->add_pay($order['id']); 
                    }
                }
                \Utils::dataRecodes('接口回调收到通知参数',$resHandler->getAllParameters());
                echo 'success';
                exit();
            }else{
                echo 'failure';
                exit();
            }
        }else{
            echo 'failure';
        }
    }
    
    /**
    * 全富通 公众号
    **/
    public function PayQFT_WeChat(){
        
        $order_id = $this->data['orderid'];
        if(empty($order_id)){
            $order_id = "v".time().rand(10000,999999);
        }

        /*if( M("payapi_log")->where(array("orderid"=>$order_id,'status'=>1))->find() ){
            $this->error("已支付");
        }*/
        //var_dump($this->data['call_url']);die;
        $money = empty($this->data['money']) ? 1 : $this->data['money'];
        $money = 0.1;
        $bank_id = empty($this->data['bank_id']) ? 2005 : $this->data['bank_id'];

        $payapi_log = array(
            'prepayid'    =>  $order_id,
            'orderid'     =>  $order_id,
            'pay_orderid' =>  $order_id,
            'openid'      =>  time(),
            'uid'         =>  time(),
            'refid'       =>  $this->data['refid'],
            'ad_app_id'   =>  $this->data['ad_app_id'],
            'ad_app_uid'  =>  $this->data['ad_app_uid'],
            'gid'         =>  '1',
            'time'        =>  time(),
            'status'      =>  0,
            'money'       =>  $money,
            'ip'          =>  get_ip(),
            'call_url'    =>  $this->data['call_url']?$this->data['call_url']:'ceshi',
            'pay_channel'    =>  7,
            'stop_time'    =>  $this->data['time'],
            'utype'    =>  empty($this->data['utype']) ? '' : $this->data['utype'],
            'uiver'    =>  empty($this->data['uiver']) ? '' : $this->data['uiver'],
        );
        if( M("payapi_log")->add($payapi_log) == false){
            echo '支付发起错误!';exit;
            $this->error("支付发起错误!");
        }
        $register_log = [
            'refid' => $this->data['refid'],
            'openid' => $order_id,
            'sex' => 1,
            'cps_app_id' => '1000000001',
            'reg_type' => 8,
            'reg_time' => time(),
            'reg_ip' => get_iplong(),
        ];
        $this->add_register($register_log);
        session("get",null);
        
        $Req_arr=array(
            'out_trade_no'=>$order_id,
            'body'=>'测试购买商品',
            'total_fee'=>$money*100,
            'mch_create_ip'=>get_ip(),
            'method'=>'submitOrderInfo'
        );
        $config = C('PAY_QFT_WeChat');
        require(APP_PATH."Lib/PayQFT/class/RequestHandler.class.php");
        require(APP_PATH."Lib/PayQFT/class/PayHttpClient.class.php");
        require(APP_PATH."Lib/PayQFT/class/ClientResponseHandler.class.php");
        require(APP_PATH."Lib/PayQFT/Utils.class.php");
        
        $reqHandler = new \RequestHandler();
        $pay = new \PayHttpClient();
        $resHandler = new \ClientResponseHandler();
        
        $reqHandler->setReqParams($Req_arr,array('method'));
        $reqHandler->setParameter('service','pay.weixin.jspay');//接口类型：pay.weixin.jspay
        $reqHandler->setParameter('mch_id',$config['mchId']);//必填项，商户号，由威富通分配
        $reqHandler->setParameter('version',$config['version']);

        import("Lib/WeiXinPay/Autoload");
        $options        = array(
            'app_id'         => 'wx25ffdee9b14b671a',
            'secret'         => 'f2efb1f18e2cc2a1e756c6ee247ea9ae',
        );
        // $app = new Application($options);
        // $userService = $app->user;
        // $xxx = $userService->get($openId);

        $user_info = $this->Wexin_Get_Code();
        $open_id   =  $user_info['openid'];
        // var_dump($user_info);die();


        $reqHandler->setParameter('sub_openid',$open_id);

        $reqHandler->setGateUrl($config['url']);
        $reqHandler->setKey($config['key']);
        
        //通知地址，必填项
        $reqHandler->setParameter('notify_url','http://'.$_SERVER['HTTP_HOST'].'/PayApi/callback_PayQFT_WeChat');//通知回调地址，目前默认是空格，商户在测试支付和上线时必须改为自己的，且保证外网能访问到
        $reqHandler->setParameter('callback_url',$payapi_log['call_url']);
        $reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//随机字符串，必填项，不长于 32 位
        $reqHandler->createSign();//创建签名
        
        $data = \Utils::toXml($reqHandler->getAllParameters());
        
        Think\Log::record('PayQFT_WeChat_1:'.json_encode($_GET).json_encode($_POST),'DEBUG',true);
        $pay->setReqContent($reqHandler->getGateURL(),$data);
        if($pay->call()){
            $resHandler->setContent($pay->getResContent());
            $resHandler->setKey($reqHandler->getKey());
            if($resHandler->isTenpaySign()){
                //当返回状态与业务结果都为0时才返回支付二维码，其它结果请查看接口文档
                if($resHandler->getParameter('status') == 0 && $resHandler->getParameter('result_code') == 0){
                    Think\Log::record('PayQFT_WeChat_2:'.json_encode($_GET).json_encode($_POST),'DEBUG',true);
                    $url = "https://pay.swiftpass.cn/pay/jspay?token_id=".$resHandler->getParameter('token_id');
                    header('Location:'.$url);die;
                    //echo json_encode(array('token_id'=>$resHandler->getParameter('token_id')));die;
                    
                    $order= M("payapi_log")->where(array("orderid"=>$order_id))->find();
                    if(!$order['call_url'] || $order['call_url']=='ceshi'){
                        header("Location: http://www.huajiao.com/mobile");
                        exit;
                    }
                    if(empty($order['id'])){
                        echo '支付失败!';exit;
                        $this->error("支付失败!",$order['call_url']);
                    }

                    $this->assign('order', $order);
                    $this->display('success');
                    exit();
                }else{
                    //echo json_encode(array('status'=>500,'msg'=>'Error Code:'.$resHandler->getParameter('err_code').' Error Message:'.$resHandler->getParameter('err_msg')));
                    echo "11".$resHandler->getParameter('err_msg');exit;
                    $this->error("支付失败!",$resHandler->getParameter('err_msg'));
                    exit();
                }
            }
            //echo json_encode(array('status'=>500,'msg'=>'Error Code:'.$resHandler->getParameter('status').' Error Message:'.$resHandler->getParameter('message')));
            echo "222" . $resHandler->getParameter('message');exit;
            $this->error("支付失败!",$resHandler->getParameter('message'));
        }else{
            echo "333".$pay->getErrInfo();exit;
            //echo json_encode(array('status'=>500,'msg'=>'Response Code:'.$pay->getResponseCode().' Error Info:'.$pay->getErrInfo()));
            $this->error("支付失败!",$pay->getErrInfo());
        }
    }
    
    function Success_PayQFT_WeChat(){

        Think\Log::record('Success_PayQFT_WeChat:'.json_encode($_GET).json_encode($_POST),'DEBUG',true);

                    $order= M("payapi_log")->where(array("orderid"=>$order_id))->find();
                    if(!$order['call_url'] || $order['call_url']=='ceshi'){
                        header("Location: http://www.huajiao.com/mobile");
                        exit;
                    }
                    if(empty($order['id'])){
                        $this->error("支付失败!",$order['call_url']);
                    }

                    $this->assign('order', $order);
                    $this->display('success');
                    exit();
    }
    /**
    * 全富通异步回调地址
    **/
    function callback_PayQFT_WeChat(){
        $xml = file_get_contents('php://input');
        require_once(APP_PATH."Lib/PayQFT/Utils.class.php");
        require_once(APP_PATH."Lib/PayQFT/class/ClientResponseHandler.class.php");
        $resHandler = new \ClientResponseHandler();
        $config = C('PAY_QFT_WeChat');
        
        $resHandler->setContent($xml);
        //var_dump($this->resHandler->setContent($xml));
        $resHandler->setKey($config['key']);
        if($resHandler->isTenpaySign()){
            if($resHandler->getParameter('status') == 0 && $resHandler->getParameter('result_code') == 0){
                //echo $this->resHandler->getParameter('status');
                // //此处可以在添加相关处理业务，校验通知参数中的商户订单号out_trade_no和金额total_fee是否和商户业务系统的单号和金额是否一致，一致后方可更新数据库表中的记录。
                //更改订单状态
                $order= M("payapi_log")->where(array("orderid"=>$resHandler->getParameter('out_trade_no')))->find();
                if(empty($order['id'])){
                    //订单不存在
                    echo 'failure';
                    Think\Log::record('callback_PayQFT_WeChat:'.$resHandler->getParameter('out_trade_no'),'DEBUG',true);
                    exit;
                }

                if($order['status'] == 0){
                    $_order['pay_time'] = time();
                    $_order['status'] = 1;
                    $_order['transaction_id'] = $_order['pay_channel_serial']=$resHandler->getParameter('out_transaction_id');
                    //var_dump($_order);die;
                    if( M("payapi_log")->where(array("id"=>$order['id']) )->save($_order) !== false){
                        Think\Log::record('callback_PayQFT_WeChat_add:'.$resHandler->getParameter('out_trade_no'),'DEBUG',true);
                        $this->add_pay($order['id']); 
                    }
                }
                \Utils::dataRecodes('接口回调收到通知参数',$resHandler->getAllParameters());
                echo 'success';
                Think\Log::record('callback_PayQFT_WeChat_success:'.$resHandler->getParameter('out_trade_no'),'DEBUG',true);
                exit();
            }else{
                Think\Log::record('callback_PayQFT_WeChat_status:','DEBUG',true);
                echo 'failure';
                exit();
            }
        }else{
            Think\Log::record('callback_PayQFT_WeChat_sign:','DEBUG',true);
            echo 'failure';
        }
    }
}
