<?php
require_once 'query/integration.php';
if ($_GET) {
    if (count($_GET) > 0) {
		$key="a4b28a5a0e49017e03009916a7c3da20";
        $l=new run();
		$y=$l->index($_GET,$key);
		if($y!==false){
			echo "success";
		}else{
			echo "fail";
		}
	}
}