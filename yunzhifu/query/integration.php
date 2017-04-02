<?php
class Integration{
	public $keyy;
	public $order;
	public $url;
	public $cardlistt;

	public function init($config=null,$url=null){
		$this->keyy=$config;
		$this->url=$url;
	}

	public function Send($parm){
		$this->order=$parm;
		$sign=$this->sign_ver();
		$parm['sign']=$sign;
		$ec=$this->blsend($parm,"post","正在跳转到支付网站...",$this->url);
		echo $ec;
	}

	public function Send_card($parm){
		$this->order=$parm;
		$str=$this->card_ver();
		$urr=$this->url."?".$str;
		$str = file_get_contents($urr);
		$sttr=$this->xmlToArray($str);
		$this->cardlistt=$sttr['cardlist']['card'];
		return $sttr;
	}

	public function Send_sms($parm){
		$this->order=$parm;
		$str=$this->card_ver();
		$urr=$this->url."?".$str;
		$str = file_get_contents($urr);
		$sttr=$this->xmlToArray($str);
		return $sttr;
	}

	/*解密卡密*/
	public function Decode(){
		$list=$this->cardlistt;
		$card=array();
		$e=array_key_exists('0',$list);
		if($e){
			$num=count($list);
			for($i=0;$i<$num;$i++){
				$card[$list[$i]['cardno']]=$this->encrypt($list[$i]['cardpass'], 'DECODE', $this->keyy);
			}
		}else{
			$card[$list['cardno']]=$this->encrypt($list['cardpass'], 'DECODE', $this->keyy);
		}

		return $card;
	}

	/*生成字串符*/
	public function createLinkstring($para) {
		$arg  = "";
		while (list ($key, $val) = each ($para)){
			$arg.=$key."=".$val."&";
			}
			$arg = substr($arg,0,count($arg)-2);
			if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
			return $arg;
	}

    /*去掉字符空值*/
	public function paraFilter($para){
		$para_filter = array();
		while (list ($key, $val) = each ($para)){
			if($key == "sign"  || $val == "")continue;
			else
				$para_filter[$key] = $para[$key];
			}
			return $para_filter;
	}

   /*数组排序*/
   public function argSort($para){
	   ksort($para);
	   reset($para);
	   return $para;
	   }

   
    /*签名*/
	public  function sign_ver(){
		$parm=$this->paraFilter($this->order);
		$parm_sort=$this->argSort($parm);
		$parm_str=$this->createLinkstring($parm_sort);
		$ok=md5($parm_str.$this->keyy);
		return $ok;
	}
	/*卡类请求字符串生成*/
    public  function card_ver(){
		$parm=$this->paraFilter($this->order);
		$parm_sort=$this->argSort($parm);
		$parm_str=$this->createLinkstring($parm_sort);
		$ok=md5($parm_str.$this->keyy);
		$parm_str.="&sign=".$ok;
		return $parm_str;
	}

	/*自动提交表单*/
	public function blsend($para, $method, $button_name,$url) {
			$sHtml = "<form id='blpaysubmit' name='blpaysubmit' action='".$url."' method='".$method."'>";
			while (list ($key, $val) = each ($para)) {
				$sHtml.= "<input type='text' name='".$key."' value='".$val."'/>";
			}
			$sHtml = $sHtml."<input type='submit' style='border:none;width:200px;height:35px;line-height:35px;background:none;font-size:18px;' value='".$button_name."'></form>";
			//$sHtml = $sHtml."<script>document.forms['blpaysubmit'].submit();</script>";
			return $sHtml;
	}
	/*加密解密 ENCODE 加密   DECODE 解密*/
   public function encrypt($string, $operation = 'ENCODE', $key = '', $expiry = 0){
			if($operation == 'DECODE') {
				$string =  str_replace('_', '/', $string);
			}
			$key_length = 4;
			$key = md5($key ? $key : 10);
			$fixedkey = md5($key);
			$egiskeys = md5(substr($fixedkey, 16, 16));
			$runtokey = $key_length ? ($operation == 'ENCODE' ? substr(md5(microtime(true)), -$key_length) : substr($string, 0, $key_length)) : '';
			$keys = md5(substr($runtokey, 0, 16) . substr($fixedkey, 0, 16) . substr($runtokey, 16) . substr($fixedkey, 16));
			$string = $operation == 'ENCODE' ? sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$egiskeys), 0, 16) . $string : base64_decode(substr($string, $key_length));

			$i = 0; $result = '';
			$string_length = strlen($string);
			for ($i = 0; $i < $string_length; $i++){
				$result .= chr(ord($string{$i}) ^ ord($keys{$i % 32}));
			}
			if($operation == 'ENCODE') {
				$retstrs =  str_replace('=', '', base64_encode($result));
				$retstrs =  str_replace('/', '_', $retstrs);
				return $runtokey.$retstrs;
			} else {	
				if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$egiskeys), 0, 16)) {
					return substr($result, 26);
				} else {
					return '';
				}
			}
		}
    /*XML转数组*/
	public function xmlToArray($url){
		header("Content-Type: text/html; charset=utf-8");
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($url, 'SimpleXMLElement', LIBXML_NOCDATA)), true);        
        return $values;
    }

	public function logt($word='') {
		$fp = fopen("log.txt","a");
		flock($fp, LOCK_EX) ;
		fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
		flock($fp, LOCK_UN);
		fclose($fp);
    }

	public function stripslashes_array(&$array) {
		while(list($key,$var) = each($array)) {
			if ($key != 'argc' && $key != 'argv' && (strtoupper($key) != $key || ''.intval($key) == "$key")) {
				if (is_string($var)) {
					$array[$key] = stripslashes($var);
					}
					if (is_array($var)) {
						$array[$key] = stripslashes_array($var);
						}
						}
						}
				return $array;
	}



}

class run extends Integration{

	public function index($parm,$key){
		$this->str_nul();
		$parm=$this->stripslashes_array($parm);
		$is=$this->sign_verr($parm,$key);
		$this->logt($is);
		$ok=$this->verifypost($parm);
		$this->logt($ok);
		if($is && $ok=="success"){
			return $parm;
		}else{
			return false;
		}

	}

	/*签名*/
	public  function sign_verr($order,$key){
		$parm=$this->paraFilter($order);
		$parm_sort=$this->argSort($parm);
		$parm_str=$this->createLinkstring($parm_sort);
		$ok=md5($parm_str.$key);
		if($ok==$order['sign']){
			return true;
		}else{
			return false;
		}
	}
	public function verifypost($data){	
	 $url="http://pay.yunfux.com/index.php/index/dupp/viar";
     $ch = curl_init($url);
     curl_setopt($ch, CURLOPT_URL, $url);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
     curl_setopt($ch, CURLOPT_POST, 1);
     curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
     $output = curl_exec($ch);
     curl_close($ch);
	 return $output;
	 }
     /*域名验证*/
	 public  function str_nul(){
	  if(!isset($_SERVER["HTTP_REFERER"])){return false;exit;}
	   $url=$_SERVER["HTTP_REFERER"];
	   $str = str_replace("http://","",$url); //去掉http://
       $strdomain = explode("/",$str); // 以“/”分开成数组
       $domain = $strdomain[0];
	   $this->logt($domain);
	   if($domain=="pay.yunfux.com"){
		   return true;
	   }else{
		   return false;
	   }
   }

}


?>