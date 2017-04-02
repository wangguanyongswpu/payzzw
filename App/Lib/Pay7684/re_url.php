<?php
/* *
 * 功能：服务器同通知页面
 */

require_once("yun.config.php");
require_once("lib/yun_md5.function.php");
?>
<!DOCTYPE HTML>
<html>
    <head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
//计算得出通知验证结果
$yunNotify = md5Verify($_REQUEST['seller_id'],$_REQUEST['re_out_trade_no'],$_REQUEST['re_trade_no'],$_REQUEST['re_total_fee'],$_REQUEST['trade_status'],$key,$_REQUEST['re_sign']);


if($yunNotify) {//验证成功
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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
			   
			   echo '已经成功返回！';
			   

			  
//—————————————————————————以上是可修改的代码———————————————————————————————————
		}



echo 'success';

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
else {
    //验证失败
    echo "验证失败";
}
?>
        <title>中瑞商服接口</title>
	</head>
    <body>
    </body>
</html>