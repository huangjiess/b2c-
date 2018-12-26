<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
//头文件----start
require('../common/common_from.php');
//头文件----end
require('select_skin.php');

$type 		= -1;	//类型，1：QQ，2：微信，3:电话
$contact_id = -1;	//数据ID
if(!empty($_GET['type'])){
	$type = $configutil->splash_new($_GET['type']);
}
if(!empty($_GET['contact_id'])){
	$contact_id = $configutil->splash_new($_GET['contact_id']);
}

$op		= '';	//添加/编辑
$op_str = '';
if($contact_id < 0){
	$op 	= 'add';
	$op_str = '添加';
}else{
	$op 	= 'edit';
	$op_str = '编辑';
}

$type_str = '';	//类型
if($type == 1){
	$type_str = 'QQ号';
}else if($type == 2){
	$type_str = '微信号';
}else{
	$type_str = '电话号码';
}

$name 				 = '';	//姓名/昵称
$contact_information = '';	//联系方式
$query_contact = "select name,contact_information from weixin_commonshop_user_contacts where isvalid=true and customer_id=".$customer_id." and id=".$contact_id." and user_id=".$user_id;
$result_contact = _mysql_query($query_contact) or die('Query_contact failed:'.mysql_error());
while($row_contact = mysql_fetch_object($result_contact)){
	$name 				 = $row_contact->name;
	$contact_information = $row_contact->contact_information;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $op_str;?>联系人</title>
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

    <link rel="stylesheet" type="text/css" href="css/add_contact.css">
	<link type="text/css" rel="stylesheet" href="./css/order_css/global.css" />
	<link type="text/css" rel="stylesheet" href="./css/css_<?php echo $skin ?>.css" /> 
	<script type="text/javascript" src="./js/jquery-2.1.3.min.js"></script>
    <script type="text/javascript">
        var ft = document.getElementsByTagName("html")[0];//获取到html标签                               
        ft.style.fontSize = window.innerWidth/32 +"px";//window.innerWidth获取窗口文档显示区的宽度，按屏幕宽度320px来设置html字体大小为10px
        window.onresize = function(){//屏幕尺寸改变触发                
            ft.style.fontSize =window.innerWidth/32 +"px";
        }
    </script>
</head>
<body>
<?php
if($type == 1){
?>
<div class="typeone">
	<img src="images/qq.png" alt="">
	<p><?php echo $op_str;?>QQ的联系方式</p>
</div>
<?php
}else if($type == 2){
?>
<div class="typetwo">
	<img src="images/weixin.png" alt="">
	<p><?php echo $op_str;?>微信的联系方式</p>
</div>
<?php
}else{
?>
<div class="typethree">
	<img src="images/dianhua.png" alt="">
	<p><?php echo $op_str;?>电话的联系方式</p>
</div>
<?php
}
?>
<div class="inputdiv">
	<p>姓名/昵称：</p>
	<input type="text" placeholder="姓名不能多于4个字" name="name" value="<?php echo $name;?>">
	<p><?php echo $type_str;?>：</p>
	<input type="text" placeholder="" name="contact_information" value="<?php echo $contact_information;?>">
</div>
<footer id="footer" class="footer">
    <div class="save">
    	<img src="images/saveimg.png" alt="">
    	<p>保存</p>
    </div>
    <div class="xian"></div>
    <div class="close">
    	<img src="images/closeimg.png" alt="">
    	<p>关闭</p>
    </div>
</footer> 
<script src="./js/global.js"></script>
<script type="text/javascript" src="../common/js/common.js"></script>
<script type="text/javascript">
var customer_id_en = '<?php echo $customer_id_en;?>';
var user_id = '<?php echo $user_id;?>';
var contact_id = '<?php echo $contact_id;?>';
var type = '<?php echo $type;?>';
var op = '<?php echo $op;?>';
var check = /^\s+$/g;	//检测是否为空
var check_num = /^[0-9]*$/;	//检测是否数字
var check_phone = /^(((13[0-9]{1})|(14[0-9]{1})|(15[0-9]{1})|(17[0-9]{1})|(18[0-9]{1}))+\d{8})$/;	//检测电话号码
var footer_box = $('#footer');
$(window).resize(function(){
	footer_box.toggle();
});
$('.save').click(function(){
	var name = $("input[name='name']").val();
	var contact_information = $("input[name='contact_information']").val();
	var name_len = name.length;
	if(name == '' || name == undefined || check.test(name)){
		showAlertMsg ("提示：","请输入联系人姓名！","知道了");
		return;
	}
	if(name_len>4){
		showAlertMsg ("提示：","姓名不能多于4个字！","知道了");
		return;
	}
	if(contact_information == '' || contact_information == undefined || check.test(contact_information)){
		if(type == 1){
			showAlertMsg ("提示：","请输入QQ号！","知道了");
		}else if(type == 2){
			showAlertMsg ("提示：","请输入微信号！","知道了");
		}else if(type == 3){
			showAlertMsg ("提示：","请输入电话号码！","知道了");
		}
		return;
	}
	if(type == 1){
		if(!check_num.test(contact_information)){
			showAlertMsg ("提示：","请输入正确的QQ号！","知道了");
			return;
		}
	}
	if(type == 3){
		if(!check_phone.test(contact_information)){	//检查电话号码
			showAlertMsg ("提示：","请输入正确的电话号码！","知道了");
			return;
		}
	}
	$.ajax({
		url: 'save_contact.php?customer_id='+customer_id_en,
		type: 'post',
		dataType: 'json',
		data: {
			op:op,
			type:type,
			user_id:user_id,
			contact_id:contact_id,
			name:name,
			contact_information:contact_information
		},
		success:function(msg){
			showAlertMsg ("提示：",msg.msg,"知道了",function(){
				window.location.href = "headguide.php?customer_id="+customer_id_en;
			});
		}
	})
});

$('.close').click(function(){
	window.location.href = "headguide.php?customer_id="+customer_id_en;
});
</script>
</body>
<?php require('../common/share.php'); ?>
<?php  include_once('float.php');?>
</html>