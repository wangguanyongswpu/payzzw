<?php
/**
*
* 版权所有：恰维网络<qwadmin.qiawei.com>
* 作    者：寒川<hanchuan@qiawei.com>
* 日    期：2015-09-17
* 版    本：1.0.0
* 功能说明：模块公共文件。
*
**/

/*
 * 函数：网站配置获取函数
 * @param  string $k      可选，配置名称
 * @return array          用户数据
*/
function setting($k=''){
	if($k==''){
        $setting =M('setting')->field('k,v')->select();
		foreach($setting as $k=>$v){
			$config[$v['k']] = $v['v'];
		}
		return $config;
	}else{
		$model = M('setting');
		$result=$model->where("k='{$k}'")->find(); 
		return $result['v'];
	}
}



function get_ip() {
        $clientip = '';
        if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $clientip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $clientip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $clientip = getenv('REMOTE_ADDR');
        } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $clientip = $_SERVER['REMOTE_ADDR'];
        }

        preg_match("/[\d\.]{7,15}/", $clientip, $clientipmatches);
        $clientip = $clientipmatches[0] ? $clientipmatches[0] : 'unknown';
        return $clientip;
    }



//ip2long函数负数BUG
function iplong($ip){ 
return bindec(decbin(ip2long($ip)));
}

/**
 *  获取当前ip的long
 */
function get_iplong(){
   $ip = get_ip();
   return iplong($ip);
}
