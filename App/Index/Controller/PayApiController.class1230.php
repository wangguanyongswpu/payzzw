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
     *  支付API接口
     */
    public function index(){

        return true;
        exit;
       
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
            $parentparent = M('member')->field('user,cid,g.uid,g.group_id AS gid,t,deduct_rate')
                ->join("{$prefix}auth_group_access g ON g.uid={$prefix}member.uid", 'LEFT')
                ->where("{$prefix}member.uid={$member['cid']}")
                ->find();
            if($parentparent && intval($parentparent['gid']) !== 1){
                $two_deduct = true;
                $ref = 'ref0_id';
                $data['ref0_id'] = $parentparent['uid'];
                $data['ref0_name'] = $parentparent['user'];
            }
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
            $minSec = 75;
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
        $data['ad_app_uid'] = $order['ad_app_uid'];	//other
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
        $Appid  = $weixin['appid'];
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
        $Appid  = $weixin['appid'];
        $secret = $weixin['secret'];
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
				"out_trade_no"	=> $order_id,
				"productname"	=> '客服电话:17081089402',
				"total_fee"	=> $money,
				"productintro"	=> '',
				"no_url"	=> 'http://'.$_SERVER['HTTP_HOST'].'/PayApi/callback_pay7684',
				"re_url"	=> 'http://'.$_SERVER['HTTP_HOST'].'/PayApi/callback_re_pay7684?orderid='.$order_id,
				"pay_channel"	=> 'weixin_pay',
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



			echo "success";		//请不要修改或删除

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
		
		$this->orderid_wy();//订单号检查
			
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
		 Think\Log::record("qr order back Check: ".date("ymd H:i:s").$_GET['orderid'],true);
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

    /**
      *调用自己的支付
      *
      */
    public function Payself(){
        if($_GET['state']!='STATE'){
            $this->orderid_wy();//订单号检查
            
            $order_id = $this->data['orderid'];
            session("PayQFT_WeChat_orderid",$order_id);
            /*if( M("payapi_log")->where(array("orderid"=>$order_id,'status'=>1))->find() ){
                $this->error("已支付");
            }*/
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
                'call_url'    =>  isset($_GET['call_url'])?$_GET['call_url']:'ceshi',
                'pay_channel'    =>  11,
                'stop_time'    =>  $this->data['time'],
                'utype'    =>  empty($this->data['utype']) ? '' : $this->data['utype'],
                'uiver'    =>  empty($this->data['uiver']) ? '' : $this->data['uiver'],
                'ip'          =>  get_ip(),
            );
            
            Think\Log::record("PayQFT_WeChatPayQFT_WeChatPayQFT_WeChat: ".json_encode($_GET),'DEBUG',true);
            Think\Log::record("PayQFT_WeChatPayQFT_WeChatPayQFT_WeChat111111: ".json_encode($this->data),'DEBUG',true);
            
            $result=M("payapi_log")->add($payapi_log);
            if( $result == false){
                echo '支付发起错误!';exit;
                $this->error("支付发起错误!");
            }
            
            session("PayQFT_WeChat_orderid",$result);
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
        }
        $result=session("PayQFT_WeChat_orderid");

        $key = 'key11632913921593';
        $secret = '6fbffde4546338a739045f06359e6c24';
        $url = 'http://pay.wbpay.net/Pay/payapi';//支付地址
        $data=[
            'key'=>$key,
            'money'=>$money,//充值金额
            'orderid'=>$order_id,//订单编号  编号不能重复
            'title'=>"客服电话:17081089402",//商品名称
            'callback_url'=>'http://'.$_SERVER['HTTP_HOST'].'/PayApi/Success_PayQFT_WeChat_self',//支付完成后跳转地址
            'notify_url'=>'http://'.$_SERVER['HTTP_HOST'].'/PayApi/callback_PayQFT_WeChat_self',//异步回调 通知支付
            'time'=>time(),//订单时间
            'ip'=>get_ip(),
            ];
        ksort($data);
        $parm_arr=array();
        foreach($data as $key=>$value){
            $parm_arr[]=$key.'='.$value;
        }
        $parm_str=implode($parm_arr,'&');
        $sign=md5(md5($parm_str).$secret);
        //拼接支付地址
        $url=$url.'?'.$parm_str.'&sign='.$sign;
        // echo $url;die();
        //跳转支付
        header("Content-type: text/html; charset=utf-8");
        header("Location:".$url); 

    }

    function Success_PayQFT_WeChat_self(){
        $order= M("payapi_log")->where(array("orderid"=>$_GET['order_id']))->find();
        if(!$order['call_url'] || $order['call_url']=='ceshi'){
            header("Location: http://www.huajiao.com/mobile");
            exit;
        }

        $this->assign('order', $order);
        $this->display('success');
        exit();
    }

    //万邦支付异步回调
    function callback_PayQFT_WeChat_self(){
        $key = 'key11632913921593';
        $secret = '6fbffde4546338a739045f06359e6c24';
        $param=[
            'key'=>$_GET['key'],
            'money'=>$_GET['money'],//充值金额
            'orderid'=>$_GET['orderid'],//订单编号  编号不能重复
            'transaction_id'=>$_GET['transaction_id'],
            'out_transaction_id'=>$_GET['out_transaction_id'],//商品名称
            'time'=>$_GET['time'],//订单时间
            'status'=>$_GET['status']
            ];

        ksort($param);
        $parm_arr=array();
        foreach($param as $key=>$value){
            $parm_arr[]=$key.'='.$value;
        }
        $parm_str=implode($parm_arr,'&');
        $sign1=md5(md5($parm_str).$secret);
        Think\Log::record('callback_PayQFT_WeChat_add111111:'.json_encode($_GET),'DEBUG',true);
        Think\Log::record('callback_PayQFT_WeChat_add222222:'.$sign1,'DEBUG',true);
        if ($sign1 == $_GET['sign'] && $_GET['status']==1) {

            //更改订单状态
            $order= M("payapi_log")->where(array("orderid"=>$_GET['orderid']))->find();
            if(empty($order['id'])){
                //订单不存在
                echo 'failure';
                Think\Log::record('callback_PayQFT_WeChat:'.$_GET['orderid'],'DEBUG',true);
                exit;
            }

            if($order['status'] == 0){
                $_order['pay_time'] = time();
                $_order['status'] = 1;
                $_order['transaction_id'] = $_order['pay_channel_serial']=$_GET['out_transaction_id'];
                //var_dump($_order);die;
                if( M("payapi_log")->where(array("id"=>$order['id']) )->save($_order) !== false){
                    Think\Log::record('callback_PayQFT_WeChat_add444444:'.$_GET['orderid'],'DEBUG',true);
                    $this->add_pay($order['id']); 
                }
            }
            echo 'success';exit();
        }else{
            echo "failure";exit();
        }


    }

	/**
    * 全富通 公众号
    **/
    public function PayQFT_WeChat(){
        if($_GET['state']!='STATE'){
			$this->orderid_wy();//订单号检查
			
			$order_id = $this->data['orderid'];
			session("PayQFT_WeChat_orderid",$order_id);
			/*if( M("payapi_log")->where(array("orderid"=>$order_id,'status'=>1))->find() ){
				$this->error("已支付");
			}*/
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
				'call_url'    =>  isset($_GET['call_url'])?$_GET['call_url']:'ceshi',
				'pay_channel'    =>  10,
				'stop_time'    =>  $this->data['time'],
				'utype'    =>  empty($this->data['utype']) ? '' : $this->data['utype'],
				'uiver'    =>  empty($this->data['uiver']) ? '' : $this->data['uiver'],
				'ip'          =>  get_ip(),
			);
			
			Think\Log::record("PayQFT_WeChatPayQFT_WeChatPayQFT_WeChat: ".json_encode($_GET),'DEBUG',true);
			Think\Log::record("PayQFT_WeChatPayQFT_WeChatPayQFT_WeChat111111: ".json_encode($this->data),'DEBUG',true);
			
			$result=M("payapi_log")->add($payapi_log);
			if( $result == false){
				echo '支付发起错误!';exit;
				$this->error("支付发起错误!");
			}
			
			session("PayQFT_WeChat_orderid",$result);
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
		}
		
		import("Lib/WeiXinPay/Autoload");

		$this->WEIXIN_PAY_API = [
                    'appid' => C('PAY_QFT_WeChat')['appid'],
                    'secret' => C('PAY_QFT_WeChat')['secret'],
                    'mchid' => '',
                    'serve' => '',
					'orderid'=>$order_id
                ];
        $user_info = $this->Wexin_Get_Code();
        $open_id   =  $user_info['openid'];
		
		if($_GET['state']=='STATE' && !empty($open_id)){
			
			$result=session("PayQFT_WeChat_orderid");
			$payapi_log=M("payapi_log")->where(array("id"=>$result))->find();
			$order_id = $payapi_log['orderid'];
			
			$Req_arr=array(
				'out_trade_no'=>$order_id,
				'body'=>'客服电话:17081089402',
				'total_fee'=>$payapi_log['money']*100,
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

			
			// var_dump($user_info);die();




			$reqHandler->setParameter('sub_openid',$open_id);

			$reqHandler->setGateUrl($config['url']);
			$reqHandler->setKey($config['key']);
			
			//通知地址，必填项
			$reqHandler->setParameter('notify_url','http://'.$_SERVER['HTTP_HOST'].'/PayApi/callback_PayQFT_WeChat');//通知回调地址，目前默认是空格，商户在测试支付和上线时必须改为自己的，且保证外网能访问到
			$reqHandler->setParameter('callback_url','http://'.$_SERVER['HTTP_HOST'].'/PayApi/Success_PayQFT_WeChat?order_id='.$order_id.'&id='.$result);
			$reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//随机字符串，必填项，不长于 32 位
			$reqHandler->createSign();//创建签名
			
			$data = \Utils::toXml($reqHandler->getAllParameters());
			
			Think\Log::record('PayQFT_WeChat_1:'.json_encode($_GET).json_encode($_POST).json_encode($payapi_log),'DEBUG',true);
			$pay->setReqContent($reqHandler->getGateURL(),$data);
			if($pay->call()){
				$resHandler->setContent($pay->getResContent());
				$resHandler->setKey($reqHandler->getKey());
				if($resHandler->isTenpaySign()){
					//当返回状态与业务结果都为0时才返回支付二维码，其它结果请查看接口文档
					if($resHandler->getParameter('status') == 0 && $resHandler->getParameter('result_code') == 0){
						Think\Log::record('PayQFT_WeChat_2:'.json_encode($_GET).json_encode($_POST),'DEBUG',true);
						$url = "https://pay.swiftpass.cn/pay/jspay?token_id=".$resHandler->getParameter('token_id');
						header('Location:'.$url);

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
    }
    
    function Success_PayQFT_WeChat(){
        Think\Log::record('Success_PayQFT_WeChat:'.json_encode($_GET).json_encode($_POST),'DEBUG',true);
		$order= M("payapi_log")->where(array("orderid"=>$_GET['order_id'],'id'=>$_GET['id']))->find();
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


    /**
    * 全富通 App支付
    **/
    public function PayQFT_App(){
        if($_GET['state']!='STATE'){
            $this->orderid_wy();//订单号检查
			
			$order_id = $this->data['orderid'];
            session("PayQFT_WeChat_orderid",$order_id);
            /*if( M("payapi_log")->where(array("orderid"=>$order_id,'status'=>1))->find() ){
                $this->error("已支付");
            }*/
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
                'call_url'    =>  isset($_GET['call_url'])?$_GET['call_url']:'ceshi',
                'pay_channel'    =>  10,
                'stop_time'    =>  $this->data['time'],
                'utype'    =>  empty($this->data['utype']) ? '' : $this->data['utype'],
                'uiver'    =>  empty($this->data['uiver']) ? '' : $this->data['uiver'],
                'ip'          =>  get_ip(),
            );
            
            Think\Log::record("PayQFT_WeChatPayQFT_WeChatPayQFT_App: ".json_encode($_GET),'DEBUG',true);
            Think\Log::record("PayQFT_WeChatPayQFT_WeChatPayQFT_App11111: ".json_encode($this->data),'DEBUG',true);
            
            $result=M("payapi_log")->add($payapi_log);
            if( $result == false){
                echo '支付发起错误!';exit;
                $this->error("支付发起错误!");
            }
            
            session("PayQFT_WeChat_orderid",$result);
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
        }
        import("Lib/WeiXinPay/Autoload");

        // $this->WEIXIN_PAY_API = [
        //             'appid' => C('PAY_QFT_WeChat')['appid'],
        //             'secret' => C('PAY_QFT_WeChat')['secret'],
        //             'mchid' => '',
        //             'serve' => '',
        //             'orderid'=>$order_id
        //         ];

        // $user_info = $this->Wexin_Get_Code();
        // $open_id   =  $user_info['openid'];
        
        // if($_GET['state']=='STATE' && !empty($open_id)){
            
            $result=session("PayQFT_WeChat_orderid");
            $payapi_log=M("payapi_log")->where(array("id"=>$result))->find();
            $order_id = $payapi_log['orderid'];
            
            $Req_arr=array(
                'out_trade_no'=>$order_id,
                'body'=>'客服电话:17081089402',
                'total_fee'=>$payapi_log['money']*100,
                'mch_create_ip'=>get_ip(),
                'method'=>'submitOrderInfo'
            );
            $config = C('PAY_QFT_App');
            require(APP_PATH."Lib/PayQFT/class/RequestHandler.class.php");
            require(APP_PATH."Lib/PayQFT/class/PayHttpClient.class.php");
            require(APP_PATH."Lib/PayQFT/class/ClientResponseHandler.class.php");
            require(APP_PATH."Lib/PayQFT/Utils.class.php");
            
            $reqHandler = new \RequestHandler();
            $pay = new \PayHttpClient();
            $resHandler = new \ClientResponseHandler();
            
            $reqHandler->setReqParams($Req_arr,array('method'));
            $reqHandler->setParameter('service','unified.trade.pay');//接口类型：pay.weixin.native
            // $reqHandler->setParameter('service','pay.weixin.jspay');//接口类型：pay.weixin.jspay
            $reqHandler->setParameter('mch_id',$config['mchId']);//必填项，商户号，由威富通分配
            $reqHandler->setParameter('version',$config['version']);
            $reqHandler->setParameter('limit_credit_pay','1'); 

            
            // var_dump($user_info);die();




            // $reqHandler->setParameter('sub_openid',$open_id);

            $reqHandler->setGateUrl($config['url']);
            $reqHandler->setKey($config['key']);
            
            //通知地址，必填项
            $reqHandler->setParameter('notify_url','http://'.$_SERVER['HTTP_HOST'].'/PayApi/callback_PayQFT_App');//通知回调地址，目前默认是空格，商户在测试支付和上线时必须改为自己的，且保证外网能访问到
            $reqHandler->setParameter('callback_url','http://'.$_SERVER['HTTP_HOST'].'/PayApi/Success_PayQFT_App?order_id='.$order_id.'&id='.$result);
            $reqHandler->setParameter('nonce_str',mt_rand(time(),time()+rand()));//随机字符串，必填项，不长于 32 位
            $reqHandler->createSign();//创建签名
            
            $data = \Utils::toXml($reqHandler->getAllParameters());
            
            Think\Log::record('PayQFT_WeChat_1:'.json_encode($_GET).json_encode($_POST).json_encode($payapi_log),'DEBUG',true);
            $pay->setReqContent($reqHandler->getGateURL(),$data);
            if($pay->call()){
                $resHandler->setContent($pay->getResContent());
                $resHandler->setKey($reqHandler->getKey());
                if($resHandler->isTenpaySign()){
                    //当返回状态与业务结果都为0时才返回支付二维码，其它结果请查看接口文档
                    if($resHandler->getParameter('status') == 0 && $resHandler->getParameter('result_code') == 0){
                        Think\Log::record('PayQFT_WeChat_2:'.json_encode($_GET).json_encode($_POST),'DEBUG',true);
                        // $url = "https://pay.swiftpass.cn/pay/jspay?token_id=".$resHandler->getParameter('token_id');
                        // header('Location:'.$url);
                        echo $_GET['jsonpCallback'].'('.json_encode(array('token_id'=>$resHandler->getParameter('token_id'),
                                           'services'=>$resHandler->getParameter('services'))).')';die();

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
    // }
    
    function Success_PayQFT_App(){
        Think\Log::record('Success_PayQFT_WeChat5555:'.json_encode($_GET),'DEBUG',true);

        $order= M("payapi_log")->where(array("orderid"=>$_GET['order_id'],'id'=>$_GET['id']))->find();
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
    * 全富通App异步回调地址
    **/
    function callback_PayQFT_App(){
        Think\Log::record('callback_PayQFT_WeCha1111111t:','DEBUG',true);
        $xml = file_get_contents('php://input');
        require_once(APP_PATH."Lib/PayQFT/Utils.class.php");
        require_once(APP_PATH."Lib/PayQFT/class/ClientResponseHandler.class.php");
        $resHandler = new \ClientResponseHandler();
        $config = C('PAY_QFT_App');
        
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
	
	
	/**
	* 检查订单号是否存在
	**/
	function orderid_wy($is_up_order=false,$num=0){
		if(empty($this->data['orderid'])||$this->data['test'] || $is_up_order){
            $this->data['orderid'] = "v".time().$this->data['refid'].rand(10000,999999);
        }
		$order_ret=M("payapi_log")->where(array('orderid'=>$this->data['orderid']))->find();
		if(!empty($order_ret)){//当订单号存在时 验证其单号是否为当前人员的单号
			if($num>10){
				Think\Log::record('orderid_wy_err:'.json_encode($this->data),'DEBUG',true);
				$this->error("订单号失败!",'');
				exit;
			}
			$this->orderid_wy(true,$num++);
			return;
		}
	}
}
