<?php
/**
 * 
 * 微信支付API异常类
 * @author widyhu
 *
 */
class WxPayException extends Exception {
    
    public function  __construct($data)
    {
           print_r($data);
    }

	public function errorMessage()
	{
		return $this->getMessage();
	}
}
