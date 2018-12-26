<?php
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../common/common_from.php');
require_once(ROOT_DIR.'wsy_pay/web/function/show_pay_way.php');
$pay_password = new show_pay_way($customer_id);
$check_pay_password = $pay_password->check_pay_password($customer_id,$user_id);
//echo $check_pay_password;


$query = "SELECT id,account,is_bind FROM system_user_t WHERE isvalid=true AND customer_id=".$customer_id." AND user_id=".$user_id." LIMIT 1";
$result= _mysql_query($query) or die('Query failed 90: ' . mysql_error());
while( $row = mysql_fetch_object($result) ){
      //$sys_id       = $row->id;         //id
      $account      = $row->account;    //绑定的手机号
      //$sys_is_bind  = $row->is_bind;

}



$sql = "SELECT show_phone,show_weixin FROM weixin_users  WHERE isvalid=TRUE AND id=".$user_id." LIMIT 1";
$result = _mysql_query($sql) or die('Query failed 51: ' . mysql_error());
while( $row=mysql_fetch_object($result) ){
        $show_phone        = $row->show_phone;
        $show_weixin       = $row->show_weixin;
}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title>账户安全</title>
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
		<link href="css/personinfo.css" type="text/css" rel="stylesheet">
		<style type="text/css">
		</style>
	</head>
	<body>
<div class="personinfosc">
    <ul>
    	<li class="lineli">
    		<div class="textleft">手机号码</div>
    		<div class="rightbox">
    			<div class="textright"><?php if(!empty($account)){echo '<a href="telephoneRevise1.php" style="color:#888">'.$account.'</a>';}else{
                    echo '<a href="bind_phone.php">去绑定</a>';
                    }?></div>
    			<img src="images/dyright.png"/>
    		</div>
    	</li>
        <?php if(!empty($account)){ ?>
        <li class="lineli" id="dengluxiu">
    		<div class="textleft">修改登录密码</div>
    		<div class="rightbox">
    			<div class="textright">修改</div>
    			<img src="images/dyright.png"/>
    		</div>
    	</li>
        <?php } ?>
    	<li class="lineli" id="zhifuxiu">
    		<div class="textleft">支付密码管理</div>
    		<div class="rightbox">
    			<div class="textright"><?php if($check_pay_password == 1){ echo '修改';}else{echo '设置';}?></div>
    			<img src="images/dyright.png"/>
    		</div>
    	</li>
    </ul>
</div>
<div class="showornot">
    <div class="showsline">

        <div class="showsleft">是否显示手机号</div>
        <?php if ($show_phone == 1){?>
        <div class="showsbtus" action="is_show_phone">
            <span class="showsbtuone"></span>
        </div>
        <?php }else{?>
        <div class="showsbtus biaoshi" style="border-color: rgb(161, 161, 161);" action="is_show_phone">
            <span class="showsbtuone" style="left: 30px; background-color: rgb(161, 161, 161);"></span>
        </div>
        <?php }?>
    </div>
    <div class="showsline">
        <div class="showsleft">是否显示微信号</div>
        <?php if ($show_weixin == 1){?>
        <div class="showsbtus" action="is_show_weixin">
            <span class="showsbtuone"></span>
        </div>
        <?php }else{?>
        <div class="showsbtus biaoshi" style="border-color: rgb(161, 161, 161);" action="is_show_weixin">
            <span class="showsbtuone" style="left: 30px; background-color: rgb(161, 161, 161);"></span>
        </div>
        <?php }?>
    </div>
</div>

	<script type="text/javascript" src="js/jquery-1.12.1.min.js"></script>
	<script type="text/javascript">
$(function (argument) {
    $(".showsbtus").click(function () {
        var user_id = "<?php echo $user_id?>";
        var action = $(this).attr('action');
        var that = $(this);
        if($(this).hasClass("biaoshi")){
            $.post('./show_info_controller.php',{user_id:user_id,action:action,is_show:1},function(res){
                var res = eval('('+res+')');
                if( res.status == 1 ){
                    that.children("span").animate({left:'0px'}).end().children("span").css("background-color","#fe7c24").end().css("border-color","#fe7c24").removeClass("biaoshi");
                }
            });

        }else{
            $.post('./show_info_controller.php',{user_id:user_id,action:action,is_show:-1},function(res){
                var res = eval('('+res+')');
                if( res.status == 1 ){
                   that.children("span").animate({left:'30px'}).end().children("span").css("background-color","#a1a1a1").end().css("border-color","#a1a1a1").addClass("biaoshi");
                }
            });
        }
    });

    $("#zhifuxiu").click(function () {
        var check_pay_password = "<?php echo $check_pay_password;?>";
        if( check_pay_password == 1 ){
            window.location.href="change_paypassword.php";
        }else{
            window.location.href="set_paypassword.php";
        }

    });

    $("#dengluxiu").click(function () {
        window.location.href="passwordRevise.php";
    });

});
    </script>
	</body>
    <?php require('../common/share.php'); ?>
    <!--引入侧边栏 start-->
    <?php  include_once('float.php');?>
    <!--引入侧边栏 end-->
</html>
