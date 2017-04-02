<?php
ini_set('date.timezone','Asia/Shanghai');
error_reporting(E_ERROR);

require_once "WxPayApi.class.php";
require_once 'WxPay.Notify.php';
require_once 'log.php';
//初始化日志


class PayNotifyCallBack extends WxPayNotify
{   
	public function  __construct(){
 
       $logHandler= new CLogFileHandler(APP_PATH.'/YZLOG/Pay_logs/'.date('Y-m-d').'.log');
       $log = Log::Init($logHandler, 15);
	}

	//查询订单
	public function Queryorder($out_trade_no,$ret=null)
	{
		$input = new WxPayOrderQuery();
		$input->SetOut_trade_no($out_trade_no);
		$result = WxPayApi::orderQuery($input);
		Log::DEBUG("query chenfei:" . json_encode($result));
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{   
		  if($result['trade_state']=="SUCCESS"){
			if($ret){
				return $result;
			}
			return true;
		   }
		}
		return false;
	}
	
	//重写回调处理函数
	public function NotifyProcess($data, &$msg)
	{
		Log::DEBUG("call back chenfei:" . json_encode($data));
		$notfiyOutput = array();
		
		if(!array_key_exists("out_trade_no", $data)){
			$msg = "输入参数不正确";
			return false;
		}
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["out_trade_no"])){
			$msg = "订单查询失败";
			return false;
		}
		//支付成功
		$pay_data= array(
              "identity"=> "微信支付—成功",
              "suk_price"=>$data['total_fee'] /100, 
			);
		M("shop_order")->where(array("order_id"=>$data['out_trade_no']))->save($pay_data);
		return true;
	}
}

//$notify = new PayNotifyCallBack();
//$notify->Handle(false);
