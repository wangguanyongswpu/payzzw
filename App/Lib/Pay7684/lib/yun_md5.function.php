<?php
function md5Verify($seller_id,$re_out_trade_no,$re_trade_no,$re_total_fee,$trade_status,$key,$re_sign) {
	$prestr = $seller_id.$re_out_trade_no.$re_trade_no.$re_total_fee.$trade_status.$key;
	$mysgin = md5($prestr);


	if($mysgin == $re_sign) {
		return true;
	}
	else {
		return false;
	}
}


global $zrapikeys;
$config_Zr = C('PAY7684_CONFIG');
$zrapikeys=$config_Zr['key'];


function zhrpay($parameter,$subm){

     $myparameter="";
	foreach ($parameter as $pars) {
   		$myparameter.=$pars;
	}
	
	$sign=md5($myparameter.'zhongruipayapi'.$GLOBALS['zrapikeys']);
	$mycodess = "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>";
	$mycodess = $mycodess."<html>";
    $mycodess = $mycodess."<head>";
	$mycodess = $mycodess."<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
	$mycodess = $mycodess."<title>支付跳转中...</title>";
	$mycodess = $mycodess."</head>";
    $mycodess = $mycodess."<body>";
	// $mycodess = $mycodess."<form name='yunsubmit' action='http://pay.7684.org/api/' accept-charset='utf-8' method='POST'>";
	
	$mycodess = $mycodess."<form name='form_api' action='http://pay.7684.org/api/api_pay.php' accept-charset='utf-8' method='POST'> "; 

	$mycodess = $mycodess."<input type='hidden' name='input_charset' value='UTF-8'/>";
	$mycodess = $mycodess."<input type='hidden' name='sign' value='".$sign."'/>";
		
		
	$mycodess = $mycodess."<input type='hidden' name='OrderType' value='API'/>";
	$mycodess = $mycodess."<input type='hidden' name='ProductIntro' value='".$parameter['productintro']."'/>";
	$mycodess = $mycodess."<input type='hidden' name='Api_Out_OrderNo' value='".$parameter['out_trade_no']."'/>";
	$mycodess = $mycodess."<input type='hidden' name='UserId' value='".$parameter['partnerid']."'/>";
	$mycodess = $mycodess."<input type='hidden' name='ProductName' value='".$parameter['productname']."'/>";
	$mycodess = $mycodess."<input type='hidden' name='OrderAmount' value='".$parameter['total_fee']."'/>";
	$mycodess = $mycodess."<input type='hidden' name='pay_channel' value='".$parameter['pay_channel']."'/>";
	$mycodess = $mycodess."<input type='hidden' name='Api_No_Url' value='".$parameter['no_url']."'/>";
	$mycodess = $mycodess."<input type='hidden' name='Api_Re_Url' value='".$parameter['re_url']."'/>";
	$mycodess = $mycodess."<input type='hidden' name='BuyerName' value=''/>";
	$mycodess = $mycodess."<input type='hidden' name='BuyerSex' value=''/>";
	$mycodess = $mycodess."<input type='hidden' name='BuyerAge' value=''/>";
	$mycodess = $mycodess."<input type='hidden' name='buyer_idcard' value=''/>";
	$mycodess = $mycodess."<input type='hidden' name='buyer_postcode' value=''/>";
	$mycodess = $mycodess."<input type='hidden' name='buyer_wechat' value=''/>";
	$mycodess = $mycodess."<input type='hidden' name='Buyer_qq' value=''/>";
	$mycodess = $mycodess."<input type='hidden' name='BuyerEmail' value=''/>";
	$mycodess = $mycodess."<input type='hidden' name='BuyerTel' value=''/>";
	$mycodess = $mycodess."<input type='hidden' name='BuyerAddress' value=''/>";
	$mycodess = $mycodess."<input type='hidden' name='buyer_liuyan' value=''/>";
	$mycodess = $mycodess."<input type='hidden' name='tuiguang_id' value=''/>";
	$mycodess = $mycodess."<input type='hidden' name='product_tuiguang_rate' value=''/>";
	$mycodess = $mycodess."</form>";

	$mycodess = $mycodess."<script>document.forms['form_api'].submit();</script>";
	$mycodess = $mycodess."<title>中瑞支付接口</title>";
	
    $mycodess = $mycodess."</body>";
	$mycodess = $mycodess."</html>";
	// var_dump($mycodess);die();
	return $mycodess;
}

?>