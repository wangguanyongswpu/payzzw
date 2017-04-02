<?php
namespace Logic;

class DesLogic {
	var $key = '1234abcd';		//长度为8位
    var $iv; //偏移量

    function __construct($key = false, $iv = 0) {
        $this->key = $key ? $key : $this->key;
        if ($iv == 0) {
            $ivArray = array(1, 2, 3, 4, 5, 6, 7, 8);
            foreach ($ivArray as $element) {
                $this->iv.=CHR($element);
            }
        } else {
            $this->iv = $iv; //mcrypt_create_iv ( mcrypt_get_block_size (MCRYPT_DES, MCRYPT_MODE_CBC), MCRYPT_DEV_RANDOM );  
        }
    }

    //加密
    function encrypt($string) {
        $size = mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_CBC);
        $string = $this->pkcs5Pad($string, $size);
        $data = mcrypt_encrypt(MCRYPT_DES, $this->key, $string, MCRYPT_MODE_CBC, $this->iv);
        $data = base64_encode($data);
        return $data;
    }

    //解密
    function decrypt($string) {

        $string = base64_decode($string);
        $result = mcrypt_decrypt(MCRYPT_DES, $this->key, $string, MCRYPT_MODE_CBC, $this->iv);
        $result = $this->pkcs5Unpad($result);

        return $result;
    }

    function hex2bin($hexData) {
        $binData = "";
        for ($i = 0; $i < strlen($hexData); $i += 2) {
            $binData .= chr(hexdec(substr($hexData, $i, 2)));
        }
        return $binData;
    }

    function pkcs5Pad($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    function pkcs5Unpad($text) {
        $pad = ord($text {strlen($text) - 1});
        if ($pad > strlen($text))
            return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
            return false;
        return substr($text, 0, - 1 * $pad);
    }
	
}