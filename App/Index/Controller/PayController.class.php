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

class PayController extends BaseController
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
	protected $pay_title;

    public function _initialize()
    {
     	//$_GET['money']=0.01;
		$this->data = I('get.');
      	if(isset($this->data['ud'])){
        	$this->data['uid']=$this->data['ud'];
        }
		$this->data['app_id']='1000000001';
        //Think\Log::record('get params: '.json_encode($this->data),'DEBUG',true);
        $get = session("get");
		//Think\Log::record('session params: '.json_encode($get),'DEBUG',true);
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
		//Think\Log::record('money1:' .$this->data['money'],'DEBUG',true);
        if(empty($this->data['money']) && !$get){
            $this->data['money']=39;
        }
        //Think\Log::record('money11:' .$this->data['money'],'DEBUG',true);
		$this->data['money']=empty(trim($this->data['money']))?39:$this->data['money'];
                //$this->data['money']=0.01;
        //Think\Log::record('money2:' .$this->data['money'],'DEBUG',true);
        //Think\Log::record('uiver:' .$this->data['uiver'],'DEBUG',true);

        //Think\Log::record('money3:' .$this->data['money'],'DEBUG',true);
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
			$this->data['money']=0.01;
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
      
		$this->pay_title='客服微信:lnkj';
        $this->OrderInfo      = array(
            'name'          => $this->pay_title,
            'detail'        => $this->pay_title,
            'serial'        =>  time().rand(1000,9999), //订单号
            'total_fee'     =>  $this->data['money'],                  //价格
        );
        $this->openid         = $this->data['openid'];
        $this->rurl           = $this->data['rurl'];
        $this->uid            = 1;
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
	* 检查订单号是否存在
	**/
	function orderid_wy($is_up_order=false,$num=0){
        if(!empty($this->data['orderid']) && isset($_GET['state']) && $_GET['state']=='STATE'){
			return;
		}
      	$this->data['orderid']=trim($this->data['orderid']);
		if(empty($this->data['orderid'])||$this->data['test'] || $is_up_order){
            $this->data['orderid'] = "ln".time().'_'.$this->data['uid'].rand(10000,999999);
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
            //$minSec = 88;
$minSec = 67;
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
      	$data['pay_time_ymd'] = date("Y-m-d",$data['pay_time']);
      	$data['pay_time_h'] = date("H",$data['pay_time']);
      	$data['pay_time_ymdh'] = date("YmdH",$data['pay_time']);
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
        $data['ref2_id'] = isset($data['ref2_id'])?$data['ref2_id']:0;
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
            $refid = I('get.uid');
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
    private function Wexin_Get_Code($is_wft=false){
        $code   = I('get.code');
        $weixin = $this->WEIXIN_PAY_API;
        $Appid  = $weixin['appid'];
        $r_url  = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
      $cc=explode('&',$_SERVER['REQUEST_URI']);
		foreach($cc as $key=>$value){
			if(strpos($value,'money=')!==false){
				$cc[$key]='money='.$this->data['money'];
			}else if(strpos($value,'orderid=')!==false){
				$cc[$key]='orderid='.$this->data['orderid'];
			}
		}
		$_SERVER['REQUEST_URI']=implode('&',$cc);
        $r_url  = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$url_openid='https://open.weixin.qq.com/connect/oauth2/authorize';
		if($is_wft){
			$url_openid='http://pay.51fuxintong.com/mywxcode.html';
		}
        $url = $url_openid."?appid={$Appid}&redirect_uri={$r_url}&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect.Appid";
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
	* 添加订单
	**/
	private function add_order_fun($is_order,$pay_channel,$prepayId=0,$open_id=0) {
		if($is_order){
			$this->orderid_wy();//订单号检查
		}
        $order_id = $this->data['orderid'];
		$prepayId=empty($prepayId)?$order_id:$prepayId;
		$open_id=empty($open_id)?$order_id:$open_id;
		
      	$orderInfo  = $this->OrderInfo;
        $money = $orderInfo['total_fee'];
        $bank_id = empty($this->data['bank_id']) ? 2005 : $this->data['bank_id'];
        $payapi_log = array(
            'prepayid'    =>  $prepayId,
            'orderid'     =>  $order_id,
            'pay_orderid' =>  $open_id,
            'openid'      =>  time(),
            'uid'         =>  time(),
            'refid'       =>  $this->data['uid'],
            'ad_app_id'   =>  $this->data['ad_app_id']?$this->data['ad_app_id']:'22222',
            'ad_app_uid'  =>  $this->data['ad_app_uid']?$this->data['ad_app_uid']:'22222',
            'gid'         =>  '1',
            'time'        =>  time(),
            'status'      =>  0,
            'money'       =>  $money,
            'ip'          =>  get_ip(),
            'call_url'    =>  $this->data['call_url']?$this->data['call_url']:'ceshi',
            'pay_channel'    =>  $pay_channel,
            'stop_time'    =>  $this->data['time'],
            'utype'    =>  empty($this->data['utype']) ? '' : $this->data['utype'],
            'uiver'    =>  empty($this->data['uiver']) ? '' : $this->data['uiver'],
        );
      	if(empty($order_id)){
        	$this->error("支付发起错误,请重新支付!");
        }
        if( M("payapi_log")->add($payapi_log) == false){
            $this->error("支付发起错误!");
        }
        $register_log = [
            'refid' => $this->data['uid'],
            'openid' => $order_id,
            'sex' => 1,
            'cps_app_id' => '1000000001',
            'reg_type' => $pay_channel,
            'reg_time' => time(),
            'reg_ip' => get_iplong(),
        ];
        $this->add_register($register_log);
		session("get",null);
	}

	
	/**********************************公众号 start**************************************/
	/**
     *  公众号支付API接口
     */
    public function link()
    {
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
        if($_GET['type']=='jscall' && !empty($this->data['orderid'])){//充值成功回调
			$order_ret= M("payapi_log")->where(array("orderid"=>$this->data['orderid']))->find();
			if($order_ret['status']==1){//充值成功
				header("Location: ".$order_ret['call_url']."?type=jscall&orderid={$this->data['orderid']}");
				exit;
			}
		}
        $data["weixin_pay_api"] = $this->WEIXIN_PAY_API;
		if(!isset($_GET['state'])){
			$this->add_order_fun(true,1);//添加订单
			$se_data=['orderid'=>$this->data['orderid'],'money'=>$this->data['money']];
			session("get",$se_data);
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
            'notify_url'       => 'http://'.$_SERVER['HTTP_HOST'].'/Pay/callback', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
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
		
		if(isset($_GET['state']) && !empty($open_id)){
			M("payapi_log")->where("orderid='{$this->data['orderid']}'")->data(array('prepayid'=>$prepayId,'openid'=>$open_id))->save();
        }
        $data['call_url'] = $this->data['call_url'];
        
        $this->assign($data);
        $this->display('index');
    }
	
	/**
     *  公众号支付回调
     */
    public function callback(){
		session("get",null);
        import("Lib/WeiXinPay/Autoload");
         file_put_contents('./pay.log',date("ymd H:i:s").PHP_EOL.file_get_contents('php://input', 'r').PHP_EOL.json_encode($_REQUEST).PHP_EOL,FILE_APPEND);
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
	/**********************************公众号 end**************************************/
	/**********************************中瑞 start**************************************/
	/**
     * 中瑞支付
     */
    public function pay7684()
    {
		$this->add_order_fun(true,8);//添加订单
		
		$config_Zr = C('PAY7684_CONFIG');
		//调用支付接口
		require_once(APP_PATH."Lib/Pay7684/lib/yun_md5.function.php");

		//构造要请求的参数数组，无需改动
		$parameter = array(
				"partnerid" => trim($config_Zr['partnerid']),
				"out_trade_no"	=> $order_id,
				"productname"	=> $this->pay_title,
				"total_fee"	=> $money,
				"productintro"	=> '',
				"no_url"	=> 'http://'.$_SERVER['HTTP_HOST'].'/Pay/callback_pay7684',
				"re_url"	=> 'http://'.$_SERVER['HTTP_HOST'].'/Pay/callback_re_pay7684?orderid='.$order_id,
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
	/**********************************中瑞 end**************************************/
	/**********************************二维码 start**************************************/

    /**
     *  微信官方二维码支付
     */
    public function scancode()
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
		$this->data['money']=0.01;
        $payment    = $app->payment;
        $orderInfo  = $this->OrderInfo;
        //$orderInfo['total_fee'] = 0.03;
        $attributes = [
            'trade_type'       => 'NATIVE', // JSAPI，NATIVE，APP...
            'body'             => $orderInfo['name'],
            'detail'           => $orderInfo['detail'],
            'out_trade_no'     => $this->data['orderid'], //订单号
            'total_fee'        => intval($this->data['money']*100),
            'notify_url'       => 'http://'.$_SERVER['HTTP_HOST'].'/Pay/callback_scancode', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'openid'           => $open_id,
        ];
        $order = new Order($attributes);
        $result = $app->payment->prepare($order);
        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
            $prepayId = $result->prepay_id;
        }


        $filename = '/Public/img/scancode/'.substr(md5($url),0,3).'/'.substr($this->data['orderid'],1,4).'/'.$this->data['orderid'].'.png';

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

        
		$this->add_order_fun(false,2,$prepayId);//添加订单
		
        $data['call_url'] = $this->data['call_url'];
        $data['qrimg'] = $filename;
        $data['order_info'] = array(
            'orderid'=>$this->data['orderid'],
            'money'  =>$this->data['money'],
          	'uiver'  =>empty($this->data['uiver']) ? '' : $this->data['uiver'],
        );
        session("get",null);
        $this->assign($data);
        $this->display('wechatqr');
    }


     /**
     *  微信官方二维码支付回调
     */
    public function callback_scancode(){
        //import("Lib/WeiXinPay/Autoload");
        //Think\Log::record("qr order back: ".date("ymd H:i:s").PHP_EOL.file_get_contents('php://input', 'r').PHP_EOL.json_encode($_REQUEST).PHP_EOL,'DEBUG',true);
        //$options = $this->options;
		
		Think\Log::record("qr order back: ".date("ymd H:i:s").PHP_EOL.file_get_contents('php://input', 'r').PHP_EOL.json_encode($_REQUEST).PHP_EOL,'DEBUG',true);
		
		$xml = file_get_contents('php://input');
        require_once(APP_PATH."Lib/PayQFT/class/ClientResponseHandler.class.php");
        $resHandler = new \ClientResponseHandler();
        $resHandler->setContent($xml);
		$order= M("payapi_log")->where(array("orderid"=>$resHandler->getParameter('out_trade_no'),'status'=>0))->find();
		$_order['pay_time'] = strtotime($resHandler->getParameter('time_end'));
		$_order['transaction_id'] = $resHandler->getParameter('transaction_id');
		if ($resHandler->getParameter('return_code') =='SUCCESS') {
			$_order['status'] = 1;
		} else {
			$_order['status'] = 2;
		}
		if( M("payapi_log")->where(array("orderid"=>$resHandler->getParameter('out_trade_no')) )->save($_order) !== false){
			if(isset($order)&&$_order['status']==1) {
				Think\Log::record("order: ".json_encode($order),'DEBUG',true);
				$this->add_pay($order['id']);
				setcookie('isvip', 1, 3*24*3600);
				setcookie('orderid', $order['orderid'], 3*24*3600);
				setcookie('pay_callback_url', $order['call_url']."?type=jacall&orderid={$order['orderid']}", 3*24*3600);
			}
		}
		return true;
    }
	/**
	* 充值完成确认
	**/
	public function CheckRecharge(){
		 Think\Log::record("qr order back Check: ".date("ymd H:i:s").$_GET['orderid'],true);
		$ret=array('code'=>0,'msg'=>'');
		$orderid=$_GET['orderid'];
		if(empty($orderid)){
			$ret['msg']='订单号为空';
		}else{
			$pay_ret= M("pay_log")->where("pay_serial='{$orderid}'")->find();
			if(empty($pay_ret)){//充值不存在
				$ret['msg']='订单号不存在';
			}else{
			     $ret['code']=1;
            }
        }
		echo json_encode($ret);
	}
	/**********************************二维码 end**************************************/

	/**********************************金海哲 start**************************************/
	
	/**
     * 金海哲支付
     */
    public function payjhz(){
        $this->add_order_fun(true,4);//添加订单

        $config_jhz = C('JHZ_PAY');

        //支付系统网关地址
        $pay_url = $config_jhz['pay_url'];

        // 请求数据赋值
        $data = "";
        $data['merchantNo'] = $config_jhz['merchantNo']; //商户号
        $data['requestNo'] =  time(); //支付流水-商户生成的订单号，不可重复
        $data['amount'] = $money*100;//金额（分）
        $data['payCode'] = '4003';//业务代码
        $data['backUrl'] = 'http://'.$_SERVER['HTTP_HOST'].'/Pay/callback_jhz?orderid='.$order_id;   //页面返回URL（异步通知地址）
        $data['pageUrl'] = 'http://'.$_SERVER['HTTP_HOST'].'/Pay/success_jhz';   //服务器返回URL（支付完成跳转地址）
        $data['payDate'] = time();   //支付时间，必须为时间戳
        $data['agencyCode'] = 0;//分支机构号
        $data['remark1'] = '订单支付'; 
        $data['remark2'] ='';
        $data['remark3'] = '';

        $signature=$data['merchantNo']."|".$data['requestNo']."|".$data['amount']."|".$data['pageUrl']."|".$data['backUrl']."|".$data['payDate']."|".$data['agencyCode']."|".$data['remark1']."|".$data['remark2']."|".$data['remark3'];
        $pr_key ='';
        if(openssl_pkey_get_private($config_jhz['private_key'])){
            $pr_key = openssl_pkey_get_private($config_jhz['private_key']);
        }else{
            echo '获取private key失败！';
            echo '<br>';
        }
        $pu_key = '';
        if(openssl_pkey_get_public($config_jhz['public_key'])){
            $pu_key = openssl_pkey_get_public($config_jhz['public_key']);
        }else{
            echo '获取public key失败！';
            echo '<br>';
        }
        $sign = '';
        //openssl_sign(加密前的字符串,加密后的字符串,密钥:私钥);
        openssl_sign($signature,$sign,$pr_key);
        openssl_free_key($pr_key);
        $sign = base64_encode($sign);

        /****************组装签名 end*************/

        $data['signature'] = $sign;


        /*echo "<pre>";
        print_r($data);
        echo "</pre>";*/

        $sHtml = "<form id='youbaopaysubmit' name='youbaopaysubmit' action='".$pay_url."' method='post'>";
        while (list ($key, $val) = each ($data)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }
        $sHtml.= "</form>";
        $sHtml.= "<script>document.forms['youbaopaysubmit'].submit();</script>";
        echo $sHtml;


    }


    public function callback_jhz(){
        
        $status = json_decode($_REQUEST['ret'], true);
        $param = json_decode($_REQUEST['msg'], true);

        if ($status['msg']=='SUCCESS' && $status['code'] == '1000') {
            $data = "";
            $data['merchantNo'] = $config_jhz['merchantNo']; //商户号
            $data['requestNo'] =  $param['no']; //支付流水-商户生成的订单号，不可重复
            $data['amount'] = $param['money'];//金额（分）
            $data['payCode'] = '4003';//业务代码
            $data['backUrl'] = 'http://'.$_SERVER['HTTP_HOST'].'/Pay/callback_jhz';   //页面返回URL（异步通知地址）
            $data['pageUrl'] = 'http://'.$_SERVER['HTTP_HOST'].'/Pay/success_jhz';   //服务器返回URL（支付完成跳转地址）
            $data['payDate'] = $param['no'];   //支付时间，必须为时间戳
            $data['agencyCode'] = 0;//分支机构号
            $data['remark1'] = $param['remarks']; 
            $data['remark2'] ='';
            $data['remark3'] = '';
            $signature=$data['merchantNo']."|".$data['requestNo']."|".$data['amount']."|".$data['pageUrl']."|".$data['backUrl']."|".$data['payDate']."|".$data['agencyCode']."|".$data['remark1']."|".$data['remark2']."|".$data['remark3'];
            Think\Log::record('nocallback_jhz1111 jump pay err:'.$signature,'DEBUG',true);
            if ($_REQUEST['sign'] == $signature) {
                $order= M("payapi_log")->where(array("orderid"=>$_REQUEST['orderid']))->find();
                if(empty($order['id'])){
                    //订单不存在
                    Think\Log::record('payjhz jump order err:'.json_encode($data),'DEBUG',true);
                    echo 'fail2';
                    exit;
                }

                if($order['status'] == 0){
                    $_order['pay_time'] = time();
                    $_order['status'] = 1;
                    $_order['transaction_id'] = $_order['pay_channel_serial']=$param['payNo'];
                    Think\Log::record('nocallback_jhz222 jump pay err:'.$signature,'DEBUG',true);
                    //var_dump($_order);die;
                    if( M("payapi_log")->where(array("id"=>$order['id']) )->save($_order) !== false){
                        Think\Log::record("payjhz order: ".json_encode($order),'DEBUG',true);
                        $this->add_pay($order['id']);
                    }
                }
            }
        }else{
            echo 'failure';
        }
        


    }

    public function success_jhz(){
        Think\Log::record('callback_jhz jump pay err:'.json_encode($_REQUEST),'DEBUG',true);
        $order= M("payapi_log")->where(array("orderid"=>$_REQUEST['msg']['payNo']))->find();
        if(!$order['call_url'] || $order['call_url']=='ceshi'){
            header("Location: http://www.huajiao.com/mobile");
            exit;
        }
        if(empty($order['id'])){
            $this->error("支付失败!",$order['call_url']);
        }

        $this->assign('order', $order);
        $this->display('success');
    }
	/**********************************金海哲 end**************************************/
  
  
	/**********************************快付通 start**************************************/
	
	/**
     * 快付通支付
     */
    public function paykft(){
        $this->add_order_fun(true,4);//添加订单

        $config_pram = C('KFT_PAY');

        //支付系统网关地址
        $pay_url = $config_pram ['pay_url'];
		$money = empty($this->data['money']) ? 25 : $this->data['money'];
        // 请求数据赋值
        $data = [];
		
        $data ['p0_Cmd'] = 'Buy'; //业务类型
        $data ['p1_MerId'] =  $config_pram ['MerId']; //商户编号
        $data ['p2_Order'] = $this->data['orderid'];//订单号
        $data ['p3_Amt'] = $money;//支付金额
        $data ['p4_Cur'] = 'CNY';   //交易币种
        $data ['p5_Pid'] = $this->pay_title;   //商品名称
        $data ['p6_Pcat'] = $this->pay_title;   //商品种类
        $data ['p7_Pdesc'] = $this->pay_title;//商品描述
        $data ['p8_Url'] = 'http://'.$_SERVER['HTTP_HOST'].'/Pay/success_kft'; //通知商户地址
        $data ['p9_SAF'] =0;//保留字段
        $data ['pa_MP'] = 0;//保留字段
		$data ['pd_FrpId'] = 'nyyh';//支付渠道
        $data ['pr_NeedResponse'] = 1;   //保留字段
		
		//组合待加密字符串
		$hmacstr='';
		foreach($data as $value){
			$hmacstr.=$value;
		}
		$hmacstr.=$config_pram ['key'];
		
        $data ['Sjt_UserName'] = 'b'; //支付类型
        $data ['hmac'] =md5($hmacstr);//MD5签名字段
		//var_dump($data);exit;
		//发送文本到前端页面  提交支付
		$sHtml = "<form name='Form1' id='Form1' action='".$pay_url."' method='post'>";
        foreach($data as $key=>$value){
            $sHtml .= "<input type='hidden' name='".$key."' value='".$value."'/>";
        }
        $sHtml .= "</form>";
        $sHtml .= "<script>document.forms['Form1'].submit();</script>";
        echo $sHtml;
		
    }


    public function callback_kft(){
        
		$config_pram = C('KFT_PAY');
		
		$Sjt_MerchantID = $_REQUEST["Sjt_MerchantID"];
		$Sjt_Username = $_REQUEST["Sjt_Username"];
		$Sjt_TransID = $_REQUEST["Sjt_TransID"];
		$Sjt_Return = $_REQUEST["Sjt_Return"];
		$Sjt_Error = $_REQUEST["Sjt_Error"];
		$Sjt_factMoney = $_REQUEST["Sjt_factMoney"];
		$Sjt_SuccTime = $_REQUEST["Sjt_SuccTime"];
		$Sjt_BType = $_REQUEST["Sjt_BType"];
		$Sjt_Sign = $_REQUEST["Sjt_Sign"];
		
		$hmac=md5($Sjt_MerchantID.$Sjt_Username.$Sjt_TransID.$Sjt_Return.$Sjt_Error
		.$Sjt_factMoney.$Sjt_SuccTime.$Sjt_BType&$config_pram ['key']);
		
		if($Sjt_Sign==$hmac){
			if ($Sjt_BType == 1){
				echo '充值成功！<br>订单号：'.$Sjt_TransID.'<br>充值时间：'.$Sjt_SuccTime;
			}elseif ($Sjt_BType == 2){
				$order= M("payapi_log")->where(array("orderid"=>$Sjt_TransID))->find();
                if(empty($order['id'])){
                    //订单不存在
                    Think\Log::record('paykft jump order err:'.json_encode($_REQUEST),'DEBUG',true);
                    echo '订单不存在！';
                    exit;
                }else if($order['status'] == 0){
                    $_order['pay_time'] = time();
                    $_order['status'] = 1;
                    $_order['transaction_id'] = $_order['pay_channel_serial']=$Sjt_TransID;
                    Think\Log::record('nocallback_kft jump pay err:'.json_encode($_order),'DEBUG',true);
                    //var_dump($_order);die;
                    if( M("payapi_log")->where(array("id"=>$order['id']) )->save($_order) !== false){
                        Think\Log::record("paykft order: ".json_encode($order),'DEBUG',true);
                        $this->add_pay($order['id']);
                    }
                }
				echo 'ok';
			}
		}else{
			echo '数据验证失败！';
		}
    }

    public function success_kft(){
        var_dump($_REQUEST);exit;
        /*Think\Log::record('callback_jhz jump pay err:'.json_encode($_REQUEST),'DEBUG',true);
        $order= M("payapi_log")->where(array("orderid"=>$_REQUEST['msg']['payNo']))->find();
        if(!$order['call_url'] || $order['call_url']=='ceshi'){
            header("Location: http://www.huajiao.com/mobile");
            exit;
        }
        if(empty($order['id'])){
            $this->error("支付失败!",$order['call_url']);
        }

        $this->assign('order', $order);
        $this->display('success');*/
    }
	/**********************************快付通 end**************************************/
}
