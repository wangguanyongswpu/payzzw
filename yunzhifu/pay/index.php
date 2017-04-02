<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<link rel="stylesheet" type="text/css" href="../css/style.css">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>网银支付接口</title>
</head>
<body>
<br>
<?php
function order($str=''){
	return $str.time().substr(microtime(),2,6).rand(0,9);
}

?>
<form name="createOrder" action="payten.php" method="POST">
	<table>
		<tr>
			<td>
				<font color=red>*</font>商户号
			</td>

			<td>
                   <input type="text" name="partner" value="10053" maxlength="15"> &nbsp;
            </td>
		</tr>
		<tr>
			<td>
				<font color=red>*</font>订单号
			</td>

			<td>
                    <input type="text" name="out_trade_no" value="<?php echo order("H");?>" maxlength="20"> &nbsp;
            </td>
		</tr>
		<tr>
			<td>
				<font color=red>*</font>金额
			</td>

			<td>
                    <input type="text" name="total_fee" value="0.01" maxlength="20"> &nbsp;(可以是小数)
            </td>
		</tr>
		<tr>
			<td>
				<font color=red>*</font>支付类型
			</td>

			<td>
                    <input type="text" name="payment_type" value="weixin" maxlength="8"> &nbsp;
            </td>
		</tr>
		<tr>
			<td>
				<font color=red>*</font>支付小类
			</td>

			<td>
                    <input type="text" name="payment_obj" value="NATIVE" maxlength="10"> &nbsp;
            </td>
		</tr>
		<tr>
			<td>
				<font color=red>*</font>IP
			</td>

			<td>
                    <input type="text" name="exter_invoke_ip" value="<?php echo $_SERVER["REMOTE_ADDR"];?>" maxlength="30"> 
            </td>
		</tr>
		<tr>
			<td>
				<font color=red>*</font>自定义参数
			</td>

			<td>
                    <input type="text" name="custom" value="测试" maxlength="8"> &nbsp;(自定义，原样返回)
            </td>
		</tr>
		<tr>
			<td>
				<font color=red>*</font>异步通知地址
			</td>

			<td>
                    <input type="text" name="notify_url" value="http://www.qkgbm.com/yunzhifu/bgReturn.php" maxlength="100"> &nbsp;
            </td>
		</tr>
		
		<tr>
			<td>
				<font color=red>*</font>同步通知地址
			</td>

			<td>
                    <input type="text" name="return_url" value="http://www.qkgbm.com/yunzhifu/pgReturn.php" maxlength="100"> &nbsp;
            </td>
		</tr>
		
	
	</table>
	<input type='button' value='提交订单' onClick='document.createOrder.submit()'>
</form>
</body>
</html>