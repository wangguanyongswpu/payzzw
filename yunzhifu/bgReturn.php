<?php
require_once 'query/integration.php';
if ($_POST) {
    if (count($_POST) > 0) { 
		$key="a4b28a5a0e49017e03009916a7c3da20";
        $l=new run();
		$y=$l->index($_POST,$key);
		if($y!==false){
			$l->logt("支付成功");
			$fp = fopen('yunzhifu.txt','w+'); 
			fwrite($fp,var_export($_POST,true)); 
			fclose($fp);
			echo "success";
		}else{
			echo "fail";
			$l->logt("支付失败");
		}
	}
}


function http_post_pay($data){	
	$url="";
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}