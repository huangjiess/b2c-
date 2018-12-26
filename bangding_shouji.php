<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../common/utility.php');

//头文件----start
require('../common/common_from.php');
//头文件----end
require('select_skin.php');
//$user_id = 194515;
/***************查询数据库是否已绑定手机号码********************/
if($user_id>0){
$login_id = -1;
$account  = "";
$query_cl  ="select id,account from system_user_t where isvalid=true and customer_id=".$customer_id." and user_id=".$user_id." limit 0,1";
//echo $query_cl;
$result_cl = _mysql_query($query_cl) or die('w161 Query failed: ' . mysql_error());
while ($row_cl = mysql_fetch_object($result_cl)) {
   $login_id = $row_cl->id;
   $account  = $row_cl->account;
}
	if( $login_id > 0 && !empty($account)){
		$is_bind = 1;
		
	}else{
		$is_bind = 0;
	}
}	

/***************查询数据库是否已绑定手机号码end*********************/

$titlestr = '绑定手机号码';
if($from_type == 1){
	if(empty($user_id) && $is_bind == 1){
		$titlestr = '绑定微信';
	}elseif($user_id>0 && $is_bind == 1){
		$titlestr = '重新绑定手机号码';
	}
}
		
			
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $titlestr ;?> <?php echo $user_id; ?></title>
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
    
    <link type="text/css" rel="stylesheet" href="./assets/css/amazeui.min.css" />
    <link type="text/css" rel="stylesheet" href="./css/order_css/global.css" />    
    <link type="text/css" rel="stylesheet" href="./css/css_<?php echo $skin ?>.css" /> 
    
</head>
<style>
  .curPhoneTitle{width:100%;height:40px;line-height:40px;color:#888;padding-left:10px;}   
  .phoneEdit{width:100%;height:50px;line-height:50px;background-color:white;padding-left:10px;border-bottom:1px solid #f8f8f8;}
  .phoneEdit .area{width:20%;float:left;}
  .phoneEdit .phoneTxt{width:46%;float:left;}
  .sendBtn{width:30%;float:left;text-align:right;}
  .sendBtn span{background-color:black;color:white;height:45px;line-height:45px; padding: 5px 8px;}
  .checkCode{width:100%;height:50px;line-height:50px;background-color:white;padding-left:10px;border-bottom:#f8f8f8;}
  .btn{width:80%;margin:20px auto;text-align:center;}
  .btn span{width:100%;height:45px;line-height:45px; padding:10px;letter-spacing:3px;}
  #check_code,#password{color:#888;width:100%;border:none;}
  #phone_num{color:#888;width:100%;border: none;}
  .checkCode div{float:left;}
  .area span{color:black;}
  #send_msg{padding: 5px 8px; }
  input::-webkit-outer-spin-button,
  input::-webkit-inner-spin-button{
	-webkit-appearance: none !important;
	margin: 0; 
}
</style>

<body id="mainBody" data-ctrl=true style="background:#f8f8f8;">
    <div id="mainDiv" style="width: 100%;height:100%;">
	   <!--  <header data-am-widget="header" class="am-header am-header-default">
		    <div class="am-header-left am-header-nav" onclick="goBack();">
			    <img class="am-header-icon-custom" src="./images/center/nav_bar_back.png" style="vertical-align:middle;"/><span style="margin-left:5px;">返回</span>
		    </div>
	        <h1 class="am-header-title" style="font-size:18px;"><?php echo $titlestr ;?></h1>
	    </header>
        <div class="topDiv"></div> --><!-- 暂时隐藏头部导航栏 -->
		<?php  if($account!=''){ ?>
        <div class="curPhoneTitle" id="cur_phone"><span>当前的手机号码是 <?php echo $account ; ?></span></div>
		<?php }?>
        <div class="phoneEdit">
        	<div class="area"><span>中国+86</span></div>
        	<div class="phoneTxt"><input type="number" id="phone_num" placeholder="请输入你的手机号码" value=""></div>
        	<div class="sendBtn" style="" onclick="/*send_checkcode();*/"><button id="send_msg" style="">短信验证</button></div>
        </div>
        <div class="checkCode">
        	<!-- <div style="width:40%;"><span style="color:#888;">请输入验证码</span></div> -->
        	<div style="width:80%;"><input type="number" placeholder="请输入验证码" id="check_code" value=""></div>
        </div>
		<div class="checkCode">
        	<!-- <div style="width:40%;"><span style="color:#888;">密码</span></div> -->
        	<div style="width:80%;"><input type="text" placeholder="请输入密码" id="password" value=""></div>
        </div>
        <div class="btn" onclick="comfirm();"><span>确认</span></div>

    <script type="text/javascript" src="./assets/js/jquery.min.js"></script>       
    <script type="text/javascript" src="./assets/js/amazeui.js"></script>
    <script type="text/javascript" src="./js/global.js"></script>
    <script type="text/javascript" src="./js/loading.js"></script>
    <script src="./js/jquery.ellipsis.js"></script>
    <script src="./js/jquery.ellipsis.unobtrusive.js"></script>
    <script type="text/javascript" src="../common/js/common.js"></script>
</body>		

<script type="text/javascript">
	var customer_id = '<?php echo $customer_id;?>';
	var user_id     = '<?php echo $user_id;?>';

    var winWidth = $(window).width();
    var winheight = $(window).height();
    var jcrop_api; 
    var zoom = 1;
    var debug = false ;						//调试方式开关
    var op = 'getcode';
    var tableobj={
        'op':''
    };
    
	$(function() {
        $("#mainDiv").show();
        $(document.body).css("background:","#f8f8f8");
        
	});

</script>
<script type="text/javascript">	
	    /*window.wx && wx.config({
                debug: false,
                appId: '<?php echo $signPackage["appId"];?>',
                timestamp: <?php echo $signPackage["timestamp"];?>,
                nonceStr: '<?php echo $signPackage["nonceStr"];?>',
                signature: '<?php echo $signPackage["signature"];?>',
                jsApiList: [
                    "hideOptionMenu",
                    "showOptionMenu",
                    "closeWindow"
                ]
            });

            wx.ready(function () {
                wx.hideOptionMenu();
            });*/
</script>
<script src="./js/bind_phone.js"></script>


</html>