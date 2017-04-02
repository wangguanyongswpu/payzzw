<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<title>支付跳转中...</title>
</head>
<body>
<!-------------
onLoad="document.form_api.submit();"
------------------->

<?php

require_once("yun.config.php");
require_once("lib/yun_md5.function.php");


//- - - - - - - -- - - - - - - - - - 请求参数- - - - - - - - - - -  - - - - - - - - -
		//商户订单号
        $out_trade_no = $_POST['WIDout_trade_no'];//商户网站订单系统中唯一订单号，必填

        //订单名称
        $productname = $_POST['WIDsubject'];//必填

        //付款金额
        $total_fee = $_POST['WIDtotal_fee'];//必填

        //订单描述
        $productintro =  'prodintro';    // $_POST['WIDbody'];
		
        //支付途径
        $pay_channel = $_POST['pay_channel'];
		
		//姓名
		$buyer_name =  'buyername';    // $_POST['buyer_name'];
		
        //性别
        $buyer_sex =  'man';    // $_POST['buyer_sex'];
		
        //年龄
        $buyer_age =  '20';    // $_POST['buyer_age'];
		
        //身份证号
        $buyer_idcard =  '1234567899874561';    // $_POST['buyer_idcard'];
		
        //邮编
        $buyer_postcode = '518000';    //  $_POST['buyer_postcode'];
		
        //微信
        $buyer_wechat =  '123456';    // $_POST['buyer_wechat'];
		
        //QQ
        $buyer_qq =  '123456';    // $_POST['buyer_qq'];
		
        //EMAIL
        $buyer_email = '123456@qq.com';    //  $_POST['buyer_email'];
		
        //电话
        $buyer_tel = '13800000000';    //  $_POST['buyer_tel'];
		
        //地址
        $buyer_address = 'address';    // $_POST['buyer_address'];
		
        //留言
        $buyer_liuyan =  'no';    //  $_POST['buyer_liuyan'];
		
        //推广员ID
        $tuiguang_id = '1';    //  $_POST['tuiguang_id'];
		
        //佣金比例，例：0.5 ，则给推广员 订单金额50%的提成。0<=佣金比例<=1 
        $product_tuiguang_rate = '0';    // $_POST['product_tuiguang_rate'];
		
		

//- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

//构造要请求的参数数组，无需改动
$parameter = array(
		"partnerid" => trim($partnerid),
		"out_trade_no"	=> $out_trade_no,
		"productname"	=> $productname,
		"total_fee"	=> $total_fee,
		"productintro"	=> $productintro,
		"no_url"	=> trim($no_url),
		"re_url"	=> trim($re_url),
		"pay_channel"	=> $pay_channel
);


/*
$parameter2 = array(		
		"buyer_name"	=> $buyer_name,
		"buyer_sex"	=> $buyer_sex,
		"buyer_age"	=> $buyer_age,
		"buyer_idcard"	=> $buyer_idcard,
		"buyer_postcode"	=> $buyer_postcode,
		
		"buyer_wechat"	=> $buyer_wechat,
		"buyer_qq"	=> $buyer_qq,
		"buyer_email"	=> $buyer_email,
		"buyer_tel"	=> $buyer_tel,
		"buyer_address"	=> $buyer_address,
		"buyer_liuyan"	=> $buyer_liuyan,
		
		"tuiguang_id"	=> $tuiguang_id,
		"product_tuiguang_rate"	=> $product_tuiguang_rate
);
*/

//生成数字签名

$sign = zhrpay($parameter, "支付进行中...");



?>
<form name='form_api' action='http://pay.7684.org/api/api_pay.php' accept-charset='utf-8' method='POST'>   

	<input type='hidden' name='input_charset' value='UTF-8'/>
	<input type='hidden' name='sign' value='<?php echo  $sign; ?>'/>
	
	
	<input type='hidden' name='OrderType' value='API'/>
	<input type='hidden' name='ProductIntro' value='<?php echo  $productintro; ?>'/>
	<input type='hidden' name='Api_Out_OrderNo' value='<?php echo  $out_trade_no; ?>'/>
	<input type='hidden' name='UserId' value='<?php  echo  $partnerid; ?>'/>
	<input type='hidden' name='ProductName' value='<?php echo  $productname; ?>'/>
	<input type='hidden' name='OrderAmount' value='<?php echo  $total_fee; ?>'/>
	<input type='hidden' name='pay_channel' value='<?php echo  $pay_channel; ?>'/>
	<input type='hidden' name='Api_No_Url' value='<?php  echo  $no_url; ?>'/>
	<input type='hidden' name='Api_Re_Url' value='<?php echo  $re_url; ?>'/>
	<input type='hidden' name='BuyerName' value='<?php echo  $buyer_name; ?>'/>
	<input type='hidden' name='BuyerSex' value='<?php echo  $buyer_sex; ?>'/>
	<input type='hidden' name='BuyerAge' value='<?php echo  $buyer_age; ?>'/>
	<input type='hidden' name='buyer_idcard' value='<?php echo  $buyer_idcard; ?>'/>
	<input type='hidden' name='buyer_postcode' value='<?php echo  $buyer_postcode; ?>'/>
	<input type='hidden' name='buyer_wechat' value='<?php echo  $buyer_wechat; ?>'/>
	<input type='hidden' name='Buyer_qq' value='<?php echo  $buyer_qq; ?>'/>
	<input type='hidden' name='BuyerEmail' value='<?php echo  $buyer_email; ?>'/>
	<input type='hidden' name='BuyerTel' value='<?php echo  $buyer_tel; ?>'/>
	<input type='hidden' name='BuyerAddress' value='<?php echo  $buyer_address; ?>'/>
	<input type='hidden' name='buyer_liuyan' value='<?php echo  $buyer_liuyan; ?>'/>
	<input type='hidden' name='tuiguang_id' value='<?php echo  $tuiguang_id; ?>'/>
	<input type='hidden' name='product_tuiguang_rate' value='<?php echo  $product_tuiguang_rate; ?>'/>
</form>

              <div align="center">支付中，请稍侯... ...	<input type="submit" name="sub" onclick="document.form_api.submit();"></div>
</body>
</html>