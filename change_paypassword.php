<?php
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD); 
mysql_select_db(DB_NAME) or die('Could not select database');
require('../common/common_from.php');
require('select_skin.php');
//查用户是否绑定，有基本信息等
$sys_id       = -1;         //id
$account      = '';    		//绑定的手机号
$wechat_id    = ''; 		//weixinid
$wechat_code  = '';			//微信二维码
$occupation   = ''; 		//职业
$sys_is_bind   = ''; 			//是否绑定微信
//$is_bind      =  0;    		//是否绑定
$query = "SELECT id,account,is_bind FROM system_user_t WHERE isvalid=true AND customer_id=".$customer_id." AND user_id=".$user_id." LIMIT 1";
$result= _mysql_query($query) or die('Query failed 90: ' . mysql_error());
while( $row = mysql_fetch_object($result) ){
      $sys_id       = $row->id;         //id
      $account      = $row->account;    //绑定的手机号
      $sys_is_bind  = $row->is_bind;
      
}
?>
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
		<link type="text/css" rel="stylesheet" href="./css/order_css/global.css" /> 
		<link type="text/css" rel="stylesheet" href="./css/css_<?php echo $skin ?>.css" />

        <script type="text/javascript" src="./assets/js/jquery.min.js"></script>
        <script type="text/javascript" src="../common/js/jquery.md5.js"></script>
		<script type="text/javascript" src="./js/global.js"></script> 
		<style type="text/css">
		</style>
	</head>
	<body>
<div class="whiteline margintops">
	<div class="pwdleft">原密码</div>
	<div class="pwdright">
		<input type="password" id="oldpwd" name="" placeholder="填写密码" maxlength="6"   onkeydown="return checkNum()">
	</div>
</div>
<div class="whiteline">
	<div class="pwdleft">新密码</div>
	<div class="pwdright">
		<input type="password" id="pwd" name="" placeholder="填写密码" maxlength="6" onkeydown="return checkNum()" >
	</div>
</div>
<div class="whiteline">
	<div class="pwdleft">确认密码</div>
	<div class="pwdright">
		<input type="password" id="repwd" name="" placeholder="填写密码" maxlength="6"  onkeydown="return checkNum()">
	</div>
</div>
<div class="tbtusc" id="conmitbtu"><span>确认</span></div>
<div class="forgetpwd"><a href="forget_paypassword.php">忘记密码</a></div>
<script type="text/javascript">
function checkNum(){
	//alert(event.keyCode);
    if( (event.keyCode>95 && event.keyCode<106) || event.keyCode == 8 || (event.keyCode>47 && event.keyCode<58) ){
        return true;
    }else{
    	return false;
    }
}
$(function () {
	var customer_id = <?php echo $customer_id?>;
	var user_id = <?php echo $user_id?>;
	$("#conmitbtu").click(function () {
		var oldpwd=$("#oldpwd").val();
		var pwd=$("#pwd").val();
		var repwd=$("#repwd").val();
		var rgExp = /^\d{6}\b/;
		if(oldpwd == ""){
			alert("原密码不能为空！");
			return false;
		}else if(pwd == ""){
			alert("密码不能为空！");
			return false;
		}else if(repwd == "" || pwd != repwd){
			alert("请确认两次密码是否一致！");
			return false;
		}else if( !pwd.match(rgExp) ){
			alert("请输入6位数字的密码！");
			return false;
		}
        pwd = $.md5(pwd);
        oldpwd = $.md5(oldpwd);
		$.post('./pay_password_controller.php',{action:'change',user_id:user_id,customer_id:customer_id,paypassword:pwd,old_password:oldpwd},function(res){
			var res = eval("("+res+")");
			if( res.status == 1 ){
				showAlertMsg('提示：',res.msg,'确定',function(){window.location.href="account_security.php?customer_id=<?php echo $customer_id_en;?>";});
			}else if(res.status == -1){
				showAlertMsg('提示：',res.msg,'确定',function(){window.location="./bind_phone.php";});
			}
			else{
				showAlertMsg('提示：',res.msg,'确定');
			}


		});
		
	});
	
});
</script>	

	</body>
	<!--引入侧边栏 start-->
	<?php  include_once('float.php');?>
	<!--引入侧边栏 end-->
</html>
