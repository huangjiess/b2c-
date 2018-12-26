<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
$link = mysql_connect(DB_HOST,DB_USER, DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
_mysql_query("SET NAMES UTF8");
require('../common/jssdk.php');
$customer_id=$configutil->splash_new($_GET['customer_id']);
echo $customer_id;
$jssdk = new JSSDK($customer_id);
$signPackage = $jssdk->GetSignPackage();

$query = 'SELECT id,appid,appsecret,access_token FROM weixin_menus where isvalid=true and customer_id='.$customer_id;
	 $result = _mysql_query($query) or die('Query failed: ' . mysql_error());  
	 $access_token="";
	 $appid="";
	 $appsecret=""; 
	 while ($row = mysql_fetch_object($result)) {
		$keyid =  $row->id ;
		$appid =  $row->appid ;
		$appsecret = $row->appsecret;
		$access_token = $row->access_token;
		break;
	 }
	  if($access_token==""){
		echo "<script>win_alert('发生未知错误！请联系商家');</script>";
	 }


?>


<!DOCTYPE html>
<html>
<head>
	<title></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta content="no" name="apple-touch-fullscreen">
	<meta name="MobileOptimized" content="320"/>
	<meta name="format-detection" content="telephone=no">
	<meta name=apple-mobile-web-app-capable content=yes>
	<meta name=apple-mobile-web-app-status-bar-style content=black>
	<meta http-equiv="pragma" content="nocache">
	<meta http-equiv="X-UA-Compatible" content="IE=Edge">
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8">
	<link rel="stylesheet" type="text/css" href="css/common.css">
	<style type="text/css">
		body{background: #fff;}
		.erwei{width: 75%;margin:0 auto;position: absolute;top: 80px;left: 12.5%; z-index: 2;}
		.hei{width: 100%;height:100%;background: rgba(0,0,0,0.4);position: fixed;z-index: 1;}
		.p01{font-size: 1.35rem;color: #fff;text-align: center;margin-top: 35px;}
		.jia{position: fixed;right: 5%;bottom: 40px;z-index: 2;}
	</style>
</head>
<body>
	<div class="hei">
		
	</div>
	<div class="erwei">
		<p class="p01">正在启动扫一扫功能</p>
	</div>
	 <script src="//res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
 	<script>
  wx.config({
		 debug: false,
		appId: '<?php echo $signPackage["appId"];?>',
		timestamp: <?php echo $signPackage["timestamp"];?>,
		nonceStr: '<?php echo $signPackage["nonceStr"];?>',
		signature: '<?php echo $signPackage["signature"];?>',
		jsApiList: [
		  // 所有要调用的 API 都要加到这个列表中
		  'scanQRCode'
		]
	  });
 wx.ready(function () {
		// 在这里调用 API

	wx.scanQRCode({
    needResult: 0, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
    scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
    success: function (res) {
    var result = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
    }
   })

		
	  });
	</script>
</body>
</html>