<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');//
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../common/utility.php');
require_once('select_skin.php');
//头文件----start
require('../common/common_from.php');
//头文件----end
require_once('../common/app_api/login_api_class.php');

$is_web_reg = 0;
$is_chat_bind_apph5 = 0;
$is_chat_bind_usedphone = 0;
$query = "select is_web_reg,is_chat_bind_apph5,is_chat_bind_usedphone from weixin_commonshops where isvalid=true and customer_id=".$customer_id." ";
$result=_mysql_query($query)or die('Query failed'.mysql_error());
while($row=mysql_fetch_object($result)){
	$is_web_reg = $row->is_web_reg;
	$is_chat_bind_apph5 = $row->is_chat_bind_apph5;//是否开启微信端绑定APP与H5账号：0否 1是
	$is_chat_bind_usedphone = $row->is_chat_bind_usedphone;//是否开启微信端绑定已绑过微信的手机号码：0否 1是
}

////如果已经绑定，跳转到修改用户页面
if($_SESSION["is_bind_".$customer_id] == 1)
{

  echo "<script>location.href='telephoneRevise1.php';</script>";
  return;
}

//商家未开启网页注册则返回登录界面
if($is_web_reg == 0 && $from_type == 0 ){
	echo "<script>location.href='login.php?customer_id=".$customer_id_en."';</script>";
	return;
}

$is_b = -1;
if(!empty($_GET['is_b'])){
	$is_b  = $_GET['is_b'];
}

$titlestr = '用户注册';
if($from_type ==0 ){			//网页
	$op = 'reg_ie';				//网页注册
	
}elseif($from_type ==1){		//微信
	$titlestr = '绑定手机';	
	$op = 'bind';	
}
$href_url = 'login.php?customer_id='.$customer_id_en;	
if(!empty($_SESSION["nurl_".$customer_id])){
	$href_url = $_SESSION["nurl_".$customer_id];			//跳转的链接
}

if(!empty($_GET["bind_num"]) || $_GET["bind_num"] == 'bind_phone'){
    $href_url = "/weixinpl/mshop/personal_center.php?customer_id=".$customer_id;            //跳转的链接
}

//判断是否开启短信图形验证码验证
$login_api_class = new login_api_class();
$sms_check = $login_api_class->sms_check($customer_id);
//require_once($_SERVER['DOCUMENT_ROOT'].'/weixinpl/function_model/sms/sms.class.php');
//$sms_class  = new sms($customer_id);
//$sms_config = $sms_class->get_config();
//$sms_check = $sms_config['data']['sms_check'];

?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $titlestr ;?><?php  //echo 'user_id='.$user_id; ;?></title>
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
    <link rel="stylesheet" type="text/css" href="./css/newShop.css"/>
    <link type="text/css" rel="stylesheet" href="./css/global.css" />
    <link type="text/css" rel="stylesheet" href="./assets/css/amazeui.min.css" />
    <link type="text/css" rel="stylesheet" href="./css/order_css/global.css" />
    <link type="text/css" rel="stylesheet" href="./css/css_<?php echo $skin ?>.css" />
    <script type="text/javascript">
        var sms_check = <?=$sms_check?>;
    </script>
</head>
<style>
*{
    font-size:14px;
}
  .curPhoneTitle{width:100%;height:40px;line-height:40px;color:#888;padding-left:10px;}   
  .phoneEdit{width:100%;height:50px;line-height:50px;background-color:white;padding-left:10px;border-bottom:1px solid #f8f8f8;}
  .area{width:20%;float:left;}
  .phoneEdit .phoneTxt{width:46%;float:left;}
  .sendBtn{width:30%;float:left;text-align:right;}
  .sendBtn span{background-color:black;color:white;height:45px;line-height:45px; padding: 5px 8px;}
  .checkCode{height:50px;line-height:50px;background-color:white;padding-left:10px;border-bottom:#f8f8f8;border:none;overflow:hidden;display: -webkit-box;display: -webkit-flex;display: flex;}
  .btn{width:80%;margin:20px auto;text-align:center;margin-bottom:7px;}
  .btn span{width:100%;height:45px;line-height:45px; padding:10px;letter-spacing:3px;}
  #check_code,#password,#parent_phone,#pay_password,#ret_password{color:#888;width:100%;border:none;}
  #phone_num{color:#888;width:100%;border:none;font-size: 15px;}
  .checkCode div{float:left;}
  .area span{color:black;font-size: 15px;}
  #send_msg{padding: 5px 8px;}
  input::-webkit-outer-spin-button,
  input::-webkit-inner-spin-button{
	-webkit-appearance: none !important;
	margin: 0; 
}
p{
color: #c9cacb;
font-size: 14px;
text-align: right;
margin-top: -13px;
margin-right: 50px;
	}
#send_msg{
    color:#fe7c24;
    background:#FFFFFF;
    border-left:#e5e5e5;
    border:none;
}
.checkBox01{
    width:46%;
}
.trhaven{
    color:#494949;
    font-size:14px;
    text-align:center;
}
.trhaven span{
    color:#fd7d24;
}
.sendBtn,.area{
    -webkit-box-flex: none;
    -webkit-flex: none;
    flex: none;
}
.flex-auto{
    -webkit-box-flex: 1;
    -webkit-flex: 1;
    flex: 1;
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
		<?php /* if($account!=''){ ?>
        <div class="curPhoneTitle" id="cur_phone"><span>当前的手机号码是 <?php echo $account ; ?></span></div>
		<?php }*/?>
        <div class="phoneEdit" style="margin-top:10px;">
        	<div class="area"><span>中国+86</span></div>
        	<div class="phoneTxt flex-auto"><input type="number" id="phone_num" placeholder="请输入你的手机号码" value=""></div>
        	
        </div>
        <div class="checkCode test_number" style="margin-top:10px;">
        	<div class="area"><span>验证码</span></div>
        	<div class="checkBox01 flex-auto"><input type="number" placeholder="请输入验证码" id="check_code" value=""></div>
            <div class="sendBtn" style="" onclick="/*send_checkcode();*/"><button id="send_msg" style="">获取验证码</button></div>
            <input type="hidden" name="sms_check" id="sms_check" value="<?=$sms_check;?>" />
        </div>
        <!-- <div class="checkCode test_number">
            <div class="area" ><span>图形验证码</span></div>
            <div class="checkBox01 flex-auto" ><input type="text" placeholder="请输入图形验证码" id="graph_code" value=""></div>
            <div class="sendBtn" style="" onclick=""><img onclick="code_refresh(this)" src="/mp/lib/verify_code/verify.php"></div>
        </div> -->
		<div class="checkCode" style="margin-top:10px;">
        	<div class="area"><span>新密码</span></div>
        	<div style="width:80%; " class="flex-auto"><input type="password" placeholder="输入新密码" id="password" value=""></div>
        </div>
		<div class="checkCode" style="margin-top:1px;">
        	<div class="area"><span>确认密码</span></div>
        	<div style="width:80%;" class="flex-auto"><input type="password" placeholder="再次输入密码" id="ret_password" value=""></div>	
        </div>
		<?php if($is_b==-1){?>	
	<!-- 	<div class="checkCode" >
        	
        	<div style="width:80%;"><input type="text" placeholder="请输入支付密码（请输入数字）" id="pay_password" value=""></div>
        </div> -->
		
		<?php   if($from_type!=1){		//微信端没有推荐号码?>	
		<div class="checkCode" style="margin-top:1px;">
        	<div class="area"><span>推荐人手机号</span></div>
        	<div style="width:80%;" class="flex-auto"><input type="number" placeholder="请输入推荐人手机号（选填）" id="parent_phone" value=""></div>
        </div>
		<?php }?>
		<?php }?>
		
        <div class="btn" onclick="comfirm();"><span>确认</span></div>
        <?php if($is_chat_bind_apph5==1 || $is_chat_bind_usedphone==1){ ?>
	    <div class="trhaven">已有账号，去<span><a href="numberBind.php?relation=1&customer_id=<?php echo $customer_id_en; ?>">登陆</a></span></div>
        <?php }?>
	</div>	
  
    <script type="text/javascript" src="./assets/js/jquery.min.js"></script>    
    <script type="text/javascript" src="./assets/js/amazeui.js"></script>
    <script type="text/javascript" src="./js/global.js"></script>
    <script type="text/javascript" src="./js/loading.js"></script>
    <script src="./js/jquery.ellipsis.js"></script>
    <script src="./js/jquery.ellipsis.unobtrusive.js"></script>
    <script type="text/javascript" src="../common/js/common.js"></script>
	<script type="text/javascript">
		var customer_id = '<?php echo $customer_id;?>';
		var user_id     = '<?php echo $user_id;?>';
		var from_type   = '<?php echo $from_type;?>';
		var is_bind     = '<?php echo $is_bind;?>';
		var debug 		= false ;						//调试方式开关	
		var op 			= '<?php echo $op;?>';	
		var href_url 	= '<?php echo $href_url;?>';	//跳转url	
		var is_b 		= '<?php echo $is_b;?>';	
		//alert("href_url=== "+href_url);
		var winWidth = $(window).width();
		var winheight = $(window).height();
		var jcrop_api; 
		var zoom = 1;
		
		$(function() {
			$("#mainDiv").show();
			$(document.body).css("background:","#f8f8f8");
			
		});
	</script>
	<script type="text/javascript" src="./js/goods/visitor_cart.js"></script>
    <script src="./js/bind_phone.js"></script>
</body>
<?php require('../common/share.php'); ?>
</html>