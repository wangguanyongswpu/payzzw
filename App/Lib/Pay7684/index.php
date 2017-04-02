<?php
/* *
 * 功能：中瑞商服即时到账交易接口接口调试入口页面
 * 版本：1.3
 * 日期：2014-09-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，进行配制
 */

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
	<title>中瑞商服即时到账交易接口接口</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
*{
	margin:0;
	padding:0;
}
ul,ol{
	list-style:none;
}
.title{
    color: #ADADAD;
    font-size: 14px;
    font-weight: bold;
    padding: 8px 16px 5px 10px;
}
.hidden{
	display:none;
}

.new-btn-login-sp{
	border:1px solid #D74C00;
	padding:1px;
	display:inline-block;
}

.new-btn-login{
    background-color: #ff8c00;
	color: #FFFFFF;
    font-weight: bold;
	border: medium none;
	width:82px;
	height:28px;
}
.new-btn-login:hover{
    background-color: #ffa300;
	width: 82px;
	color: #FFFFFF;
    font-weight: bold;
    height: 28px;
}
.bank-list{
	overflow:hidden;
	margin-top:5px;
}
.bank-list li{
	float:left;
	width:153px;
	margin-bottom:5px;
}

#main{
	width:900px;
	margin:0 auto;
	font-size:14px;
	font-family:'宋体';
}
#logo{
	background-color: transparent;
    background-image: url("images/new-btn-fixed.png");
    border: medium none;
	background-position:0 0;
	width:166px;
	height:35px;
    float:left;
}
.red-star{
	color:#f00;
	width:10px;
	display:inline-block;
}
.null-star{
	color:#fff;
}
.content{
	margin-top:5px;
}

.content dt{
	width:200px;
	display:inline-block;
	text-align:right;
	float:left;
	
}
.content dd{
	margin-left:100px;
	margin-bottom:5px;
}
#foot{
	margin-top:10px;
}
.foot-ul li {
	text-align:center;
}
.note-help {
    color: #999999;
    font-size: 12px;
    line-height: 130%;
    padding-left: 3px;
}

.cashier-nav {
    font-size: 14px;
    margin: 15px 0 10px;
    text-align: left;
    height:30px;
    border-bottom:solid 2px #CFD2D7;
}
.cashier-nav ol li {
    float: left;
}
.cashier-nav li.current {
    color: #AB4400;
    font-weight: bold;
}
.cashier-nav li.last {
    clear:right;
}
.alipay_link {
    text-align:right;
}
.alipay_link a:link{
    text-decoration:none;
    color:#8D8D8D;
}
.alipay_link a:visited{
    text-decoration:none;
    color:#8D8D8D;
}
</style>
</head>
<body text=#000000 bgColor=#ffffff leftMargin=0 topMargin=4>
	<div id="main">
		<div id="head">
            <dl class="alipay_link">
                <a target="_blank" href="http://pay.7684.org/"><span>中瑞商服首页</span></a>|
                <a target="_blank" href="http://pay.7684.org/"><span>中瑞商服</span></a>|
                <a target="_blank" href="http://pay.7684.org/"><span>帮助中心</span></a>
            </dl>
            <span class="title">中瑞商服即时到账交易接口快速通道</span>
		</div>
        <div class="cashier-nav">
            <ol>
				<li class="current">1、确认信息 →</li>
				<li>2、点击确认 →</li>
				<li class="last">3、确认完成</li>
            </ol>
        </div>
<form name="alipayment" action="yunpay.php" method="post" target="_blank">
            <div id="body" style="clear:left">
                <dl class="content">
				
                     <dt>订单号out_trade_no：</dt>
                     <dd><span class="null-star">*</span><input size="30" name="WIDout_trade_no" value="<?php echo date('YmdHis');?>" /><span>商户系统中唯一订单号，必填</span></dd>
					
                    <dt>产品名称productname：</dt>
                    <dd><span class="null-star">*</span><input size="30" name="WIDsubject" value="苹果手机"/><span>必填</span></dd>
						
						
						
                    <dt>付款金额total_fee：</dt>
                    <dd><span class="null-star">*</span><input size="30" name="WIDtotal_fee" value="0.1"/><span>必填</span></dd>
					
					
					
					
					
                    <dt>订单描述productintro：</dt>
                    <dd><span class="null-star">*</span><input size="30" name="WIDbody" value="订单描述"/></dd>
					
					
					
					
					
					
                    <dt>购买者姓名buyer_name：</dt>
                    <dd><span class="null-star">*</span><input size="30" name="buyer_name"  value="姓名"/></dd>
					
					
					
					
                  <dt>购买者性别buyer_sex：</dt>
                  <dd><span class="null-star">*</span><input size="30" name="buyer_sex"  value="男"/></dd>
					
					
                  <dt>年龄buyer_age：</dt>
                  <dd><span class="null-star">*</span><input size="30" name="buyer_age"  value="25"/></dd>
					
					
					
					
                  <dt>身份证buyer_idcard：</dt>
                  <dd><span class="null-star">*</span><input size="30" name="buyer_idcard"  value="440251198804031234"/></dd>
					
					
					
                  <dt>邮编buyer_postcode：</dt>
                  <dd><span class="null-star">*</span><input size="30" name="buyer_postcode"  value="518000"/></dd>
					
					
					
                  <dt>微信buyer_wechat：</dt>
                  <dd><span class="null-star">*</span><input size="30" name="buyer_wechat"  value="wechat"/></dd>
					
					
					
					
                  <dt>QQ buyer_qq：</dt>
                  <dd><span class="null-star">*</span><input size="30" name="buyer_qq"  value="1013152152"/></dd>
					
					
					
					
                  <dt>邮箱buyer_email：</dt>
                  <dd><span class="null-star">*</span><input size="30" name="buyer_email"  value="1013152152@qq.com"/></dd>
					
					
					
					
                  <dt>电话buyer_tel：</dt>
                  <dd><span class="null-star">*</span><input size="30" name="buyer_tel"  value="15874445515"/></dd>
					
					
					
					
                  <dt>地址buyer_address：</dt>
                  <dd><span class="null-star">*</span><input size="30" name="buyer_address"  value="深圳市福田区华强北路"/></dd>
					
					
					
                  <dt>留言buyer_liuyan：</dt>
                  <dd><span class="null-star">*</span><input size="30" name="buyer_liuyan"  value="买家订单留言"/></dd>
					
					
					
					

					
                  <dt>推广员ID tuiguang_id：</dt>
                  <dd><span class="null-star">*</span><input size="30" name="tuiguang_id"  value="10004"/></dd>
					
					
					
					

					
                  <dt>佣金比例product_tuiguang_rate</dt>
                  <dd><span class="null-star">*</span><input size="30" name="product_tuiguang_rate"  value="0.5"/></dd>
					
					
					
					
                    <dt>付款方式：</dt>
                    <dd>
                        <span class="null-star">*</span>
                        <select name="pay_channel">
                          <option value="">请选择付款方式</option>
                          <option value="Alipay">支付宝</option>
                          <option value="weixin_pay" selected="selected">微信支付</option>
                          <option value="bank_pay">网银</option>
                        </select>
                    </dd>
					




					<dt></dt>
                    <dd>
                        <span class="new-btn-login-sp">
                            <button class="new-btn-login" type="submit" style="text-align:center;">确 认</button>
                        </span>
                    </dd>
                </dl>
    </div>
	  </form>
        <div id="foot">
			<ul class="foot-ul">
				<li><font class="note-help">如果您点击“确认”按钮，即表示您同意该次的执行操作。 </font></li>
				<li>
					中瑞商服版权所有 2008-2015 7684.org
				</li>
			</ul>
		</div>
	</div>
</body>
</html>