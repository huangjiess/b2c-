<?php
	header("Content-type: text/html; charset=utf-8"); //svn
	require('../config.php');
	require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
	$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
	mysql_select_db(DB_NAME) or die('Could not select database');
	require('../proxy_info.php');

	_mysql_query("SET NAMES UTF8");

	$read_time = 5;
	$notice = "";
	$app_url = "";
	$query = "select read_time,notice,app_url from weixin_app_guide where customer_id=".$customer_id." and isvalid=true";
	$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
	while ($row = mysql_fetch_object($result)) { 
		$read_time = $row->read_time;
		$notice    = $row->notice;
		$app_url   = $row->app_url;
	}
	$pos = strpos($app_url,"//");
	$pos2 = strpos($app_url,"https://");
    if($pos === 0 || $pos === 0){
    }else{
        $app_url = "http://".$app_url;
    }
	
	$notice = htmlspecialchars_decode($notice);
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title>app登录操作手册</title>
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
		<link href="css/style.css" type="text/css" rel="stylesheet"> 
		<style type="text/css">
		body{background-color:#f3f3f3}
		.manualbox{margin: 20px 15px;background-color: #ffffff;padding: 10px;overflow-y: scroll;}
		.manualtil{color: #1c1f20;font-size: 1.38rem;padding: 0 0 5px 0;}
		.manualcon{font-size: 1.3rem;color: #888888;padding: 0 8px;text-indent: 2em;}
		.btn{display: table;margin: 10px auto 15px auto;padding: 10px 20px;background-color: #ff8430;color:#ffffff;font-size: 1.3rem;}
		</style>

	</head>
	<body>
	<div class="manualbox">
		<p class="manualtil">描述和声明：</p>
		<p class="manualcon"><?php echo $notice; ?></p>
	</div>
	<div class="btn">
		已阅读（<span id="timess" class="disabled"></span>）
	</div>
	<!-- body尾部放js-->
	<script type="text/javascript" src="js/jquery.js"></script>
	<script type="text/javascript">
	var winheight=$(window).height() * 0.8;
	$(".manualbox").css("max-height",winheight);
	$(function () {
		changeTime(<?php echo $read_time; ?>);
	});
	//秒数递减的函数
	function changeTime(times){
	    var myVar=setInterval(function(){
	        $("#timess").text(times);
	        times--;
	        if(times<0){
	            clearInterval(myVar);
	            $("#timess").removeClass("disabled");
	            $(".btn").html("已阅读");
	            return;
	        }
	    },1000);            
	}
	$(".btn").click(function () {
		if ($("#timess").hasClass("disabled")){
			return false;
		}else{
			location.href = "<?php echo $app_url; ?>";
		}
	})
	</script>
	</body>
</html>
