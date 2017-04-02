<?php
class Config{
    private $cfg = array(
        //接口请求地址，固定不变，无需修改
        'url'=>'https://pay.swiftpass.cn/pay/gateway',
        //测试商户号，商户需改为自己的
        'mchId'=>'6522000110',
        //测试密钥，商户需改为自己的
        'key'=>'80ed739ffc713975735cb79cd5089f9d',
        //版本号默认2.0
        'version'=>'2.0'
    );

    public function C($cfgName){
        return $this->cfg[$cfgName];
    }
}

class JsConfig{
    private $cfg = array(
        //接口请求地址，固定不变，无需修改
        'url'=>'https://pay.swiftpass.cn/pay/gateway',
        //测试商户号，商户需改为自己的
        'mchId'=>'101590002156',
        //测试密钥，商户需改为自己的
        'key'=>'d69ce8d375467d63c33361635789364f',
        //版本号默认2.0
        'version'=>'2.0'
    );

    public function C($cfgName){
        return $this->cfg[$cfgName];
    }
}