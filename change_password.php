<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title>修改密码</title>
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
		<link href="css/changepassword.css" type="text/css" rel="stylesheet"> 
		<style type="text/css">
		</style>
	</head>
	<body>
<div class="grayline">
	<div class="pwdleft">中国+86</div>
	<div class="pwdright">137****0142</div>
</div>
<div class="singleline">
    <div class="textleft">验证码</div>
    <div class="textmid">
		<input type="text" name="" placeholder="短信验证码">
    </div>
    <div class="textringhto">获取验证码</div>
</div>
<div class="whiteline">
	<div class="pwdleft">新密码</div>
	<div class="pwdright">
		<input type="password" name="" placeholder="填写密码" maxlength="6" onkeyup="this.value=this.value.replace(/\D/g,'')"
			   >
	</div>
</div>
<div class="whiteline">
	<div class="pwdleft">确认密码</div>
	<div class="pwdright">
		<input type="password" name="" placeholder="填写密码" maxlength="6" onkeyup="this.value=this.value.replace(/\D/g,'')">
	</div>
</div>
<div class="tbtusc"><span>确认</span></div>

	<script type="text/javascript" src="js/jquery-1.12.1.min.js"></script>
	
	</body>
</html>
