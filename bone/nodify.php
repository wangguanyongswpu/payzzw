<?php
include_once 'zhongrui.php';

$configure = [
    'out_trade_no' => time(),
    'productname' => '客服电话 17026312584',
    'productIntro' => '购买商品',
    'total_fee' => '0.01',
    'no_url' => 'http://www.qkgbm.com/bone/nodify.php',
    're_url' => 'http://www.qkgbm.com/bone/nodify.php',
];

$payment = new zhongrui($configure);

$seller_id = $_REQUEST['seller_id'] ? : 0;
$re_out_trade_no = $_REQUEST['re_out_trade_no'] ? : 0;
$re_trade_no = $_REQUEST['re_trade_no'] ? : 0;
$re_total_fee = $_REQUEST['re_total_fee'] ? : 0;
$trade_status = $_REQUEST['trade_status'] ? : 0;
$re_sign = $_REQUEST['re_sign'] ? : 0;

$log = $payment->callback($seller_id, $re_out_trade_no, $re_trade_no, $re_total_fee, $trade_status, $re_sign);

file_put_contents('paymet.log', $log, FILE_APPEND);

