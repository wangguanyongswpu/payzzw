<?php

/* *
 * 功能：服务器异步通知页面，
 *本页面除了success和fail之外不能有任何其他的输出结果
 */

require_once("yun.config.php");
require_once("lib/yun_md5.function.php");

//计算得出通知验证结果
$yunNotify = md5Verify($_REQUEST['seller_id'],$_REQUEST['re_out_trade_no'],$_REQUEST['re_trade_no'],$_REQUEST['re_total_fee'],$_REQUEST['trade_status'],$key,$_REQUEST['re_sign']);




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


	           //商户订单号
	            $out_trade_no = $_REQUEST['re_out_trade_no'];

	           //中瑞商服交易号
	           $trade_no = $_REQUEST['re_trade_no'];

	           //交易金额
	           $total_fee=$_REQUEST['re_total_fee'];

               //服务器返回的签名
               $re_sign=$_REQUEST['re_sign'];
			   
               //服务器返回交易状态、TRADE_SUCCESS 或者TRADE_FAIL
               $trade_status=$_REQUEST['trade_status'];
			   
               //服务器返回商户号
               $seller_id=$_REQUEST['seller_id'];
			   
			   
			  
//—————————————————————————以上是可修改的代码———————————————————————————————————
		}


        
	echo "success";		//请不要修改或删除
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
else {
    //验证失败
    echo "fail";//请不要修改或删除

    //调试用，写文本函数记录程序运行情况是否正常
    //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
}
?>