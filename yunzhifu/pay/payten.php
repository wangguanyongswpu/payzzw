<?php
require_once '../query/integration.php';
if($_POST){
	//print_r($_POST);
	$key="a4b28a5a0e49017e03009916a7c3da20";//商户KEY
	$url="http://pay.yunfux.com/index.php/pay/payy/index";
	$lsi=new Integration();
	$lsi->init($key,$url);
	$lsi->Send($_POST);
}
?>