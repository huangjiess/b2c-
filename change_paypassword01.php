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
		<script type="text/javascript" src="./js/global.js"></script>
		<style type="text/css">
		</style>
	</head>
	<body>
<div class="grayline">
	<div class="pwdleft">中国+86</div>
	<div class="pwdright"><?php echo $account;?></div>
</div>
<div class="singleline">
    <div class="textleft">验证码</div>
    <div class="textmid">
		<input type="text" name="" id="code" placeholder="短信验证码">
    </div>
    <input type="button" class="textringhto" value="获取验证码" style="text-align: center;">
    <!-- <div class="textringhto">获取验证码</div> -->
</div>
<div class="whiteline">
	<div class="pwdleft">新密码</div>
	<div class="pwdright">
		<input type="password" name="" id="new_password" placeholder="填写密码">
	</div>
</div>
<div class="whiteline">
	<div class="pwdleft">确认密码</div>
	<div class="pwdright">
		<input type="password" name="" id="confirm_password" placeholder="填写密码">
	</div>
</div>
<div class="tbtusc" onclick="confirm()"><span>确认</span></div>

	<script type="text/javascript" src="js/jquery-1.12.1.min.js"></script>
	
	</body>
</html>
<script type="text/javascript">
	var count_time = 60;
	var account = "<?php echo $account;?>";
	var customer_id = <?php echo $customer_id?>;
	var user_id = <?php echo $user_id?>;
	$('.textringhto').click(function(){
		$.ajax({
			url:'send_phone_msg.php?action=send_msg',
			data:{
				
				"phone"			:account,
				"customer_id"	:customer_id,
				//"from_type"		:from_type,
				"user_id"		:user_id
				},
			dataType:'json',
			type:'post',
			async: false,  
			success:function(res){
				showAlertMsg('提示：','验证码已发至您手机','确定');
				$('.textringhto').attr('disabled',true);
				$('.textringhto').css('color','#999');
				var count_back = setInterval(hold,1000);
				function hold(){
					count_time--;
					$('.textringhto').val('请稍候('+count_time+'s)');
					if( count_time == 0 ){
						clearInterval(count_back);
						$('.textringhto').val('获取验证码');
						$('.textringhto').attr('disabled',false);
						$('.textringhto').css('color','#fe7c24');
						count_time = 60;
					}

				}
				
			  
			}	
		});
	});
	function confirm(){
		var account = "<?php echo $account;?>";
		var new_password = $('#new_password').val();
		var confirm_password = $('#confirm_password').val();
		var code = $('#code').val();
		var rgExp = /^\d{6}\b/;
		if( code == "" ){
			alert("请输入验证码");
			return false;
		}
	    if( new_password == "" ){
			alert("请输入新密码");
			return false;
		}
		if( !new_password.match(rgExp) ){
			alert("请输入6位数字的密码！");
			return false;
		}
		if( confirm_password == "" ){
			alert("请输入确认密码");
			return false;
		}
	    if( new_password != confirm_password ){
			alert("两次密码不一致！");
			return false;
		}
		$.post('./pay_password_controller.php',{action:'update',user_id:user_id,customer_id:customer_id,paypassword:new_password,code:code,account:account},function(res){
			var res = eval("("+res+")");
			if( res.status == 1 ){
				showAlertMsg('提示：',res.msg,'确定',function(){window.location.reload()});
			}else{
				showAlertMsg('提示：',res.msg,'确定');
			}


		});

	}



</script>