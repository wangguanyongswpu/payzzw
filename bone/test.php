<?php
include_once 'zhongrui.php';

$configure = [
    'out_trade_no' => date('YmdHis', time()),
    'productname' => '客服电话17026312584',
    'productIntro' => 'buygoodstest',
    'total_fee' => 0.01,
    'no_url' => 'http://www.qkgbm.com/bone/nodify.php',
    're_url' => 'http://www.qkgbm.com/bone/nodify.php',
];

$payment = new zhongrui($configure);

$st = $payment->pay();
var_dump($st);