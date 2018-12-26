<?php
header("Content-type: text/html; charset=utf-8");     
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]

require('../common/utility_fun.php');

$link = mysql_connect(DB_HOST,DB_USER,DB_PWD); 
mysql_select_db(DB_NAME) or die('Could not select database');
//头文件----start
require('../common/common_from.php');
//头文件----end
require('select_skin.php');

$custom = "购物币";
$rule   = '';
$mini_limit = 0;
$currency = 0;
$sql = "SELECT currency from weixin_commonshop_user_currency where isvalid=true AND user_id=".$user_id." AND customer_id=$customer_id and isvalid=true limit 0,1";
$res = _mysql_query($sql);
while($row=mysql_fetch_object($res)){
    $currency = $row->currency;
    break;
}
//自己的可赠送余额
$query = "SELECT custom,rule FROM weixin_commonshop_currency WHERE customer_id=".$customer_id." limit 1";
$result = _mysql_query($query)or die('Query failed101: ' . mysql_error());
while($row=mysql_fetch_object($result)){
    $custom     = $row->custom;
    $rule       = $row->rule;
}
$max_money = $currency;


//检查受益人是否有购物币钱包
if($to_user_id != -1){
    $query = "SELECT id FROM weixin_commonshop_user_currency where isvalid=true and user_id=".$to_user_id." limit 1";
    $result= _mysql_query($query);
    $count = mysql_num_rows($result);
    if($count==0){
        $ins_sql = "INSERT INTO weixin_commonshop_user_currency(isvalid,customer_id,user_id,currency,createtime) VALUES(true,".$customer_id.",".$to_user_id.",0,now())";
        _mysql_query($ins_sql);
    }
}

$is_pw  = 0;
$sys_id = -1;
$sql = "SELECT id from system_user_t where user_id=".$user_id." AND customer_id=$customer_id and isvalid=true limit 0,1";
$res = _mysql_query($sql);
while($row=mysql_fetch_object($res)){
    $sys_id         = $row->id;
    break;
}

$paypassword = '';
$query = "SELECT paypassword FROM user_paypassword WHERE isvalid=true AND user_id = $user_id LIMIT 1";
$result= _mysql_query($query) or die('Query failed 37: ' . mysql_error());
while( $row = mysql_fetch_object($result) ){
    $paypassword = $row->paypassword;
}
if($paypassword!=''){
    $is_pw = 1;
}else{
    $is_pw = 0;
}


?>
<!DOCTYPE html>
<html>
<head>
    <title>提现至洛克云平台 </title>
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
    
    
    
    <link rel="stylesheet" id="wp-pagenavi-css" href="./css/list_css/pagenavi-css.css" type="text/css" media="all">
	<link rel="stylesheet" id="twentytwelve-style-css" href="./css/list_css/style.css" type="text/css" media="all">
    <link rel="stylesheet" id="twentytwelve-style-css" href="./css/goods/dialog.css" type="text/css" media="all">
	<link type="text/css" rel="stylesheet" href="./css/list_css/r_style.css" />
    <link type="text/css" rel="stylesheet" href="./css/password.css" />
    <link type="text/css" rel="stylesheet" href="./css/goods/dialog.css" />
    <link type="text/css" rel="stylesheet" href="./css/self_dialog.css" />
    
<style>  
.sharebg1 {
    background-color: rgba(0, 0, 0, 0.6);
    bottom: 0;
    height: 100%;
    left: 0;
    opacity: 0;
    position: fixed;
    right: 0;
    top: 0;
    width: 100%;
    z-index: 1100;
    display: none;
}
   .list {margin: 10px 5px 0 3px;	overflow: hidden;}
   .area-line{height:25px;width:1px;float:left;margin-top: 10px;padding-top: 20px;border-left:1px solid #cdcdcd;}
   .topDivSel{width:100%;height:45px;top:50px;padding-top:0px;background-color:white;}
   .infoBox{width:90%;margin:10px auto;;background-color:white;border:1px solid #eee;}
   .infoBox .ele{height: 32px;width:90%;line-height: 32px;margin:0 auto;color: #a5a5a5;}
   .ele .left{width:40%;float:left;color:#727272}
   .ele .right{width:60%;float:left;color:#1c1f20;}
   .ele img{width: 20px;height: 20px;vertical-align:middle;}
   .ele .redunder{text-decoration: underline;}
   .red{color:#f4212b;}
   .black{color:#a5a5a5;}
   .line{background-color: #eee;margin-left: 10px;height: 1px;}
   .content_top{height: 45px;line-height:45px;background-color:#f8f8f8;}
   .content_bottom{height: 22px;line-height:22px;background-color:#f8f8f8;}
   .btn{width:80%;margin:20px auto;text-align:center;}
  .btn span{width:100%;height:45px;line-height:45px; padding:10px;letter-spacing:3px;}
  .hasnoPrice{background: #d1d1d1;}
  #pass_w{width:100%;position: absolute;top:30%;z-index: 1102;display: none;}
  .pass_bg{width:100%;height: 100%; opacity: 0.5;background: #ccc;}
  .sharebg1{opacity: 0.5}

//ld 点击效果
    .button{ 
    -webkit-transition-duration: 0.4s; /* Safari */
    transition-duration: 0.4s;
    }
    .buttonclick:hover{
      box-shadow:  0 0 6px 0 rgba(0,0,0,0.24);
    }
	


</style>


</head>
<!-- Loading Screen -->
<div id='loading' class='loadingPop'style="display: none;"><img src='./images/loading.gif' style="width:40px;"/><p class=""></p></div>

<body data-ctrl=true style="background:#f8f8f8;">
	<div class="sharebg1"></div><!--半透明背景-->
	<div class="am-share" id="pass_w" style="width:100%;position: absolute;top:50px;z-index: 1111;">
        <div class="box">
            <h1>输入支付密码</h1>
            <label for="ipt">
                <ul>
                    <li></li>
                    <li></li>
                    <li></li>
                    <li></li>
                    <li></li>
                    <li></li>
                </ul>
            </label>
            <input type="tel" id="ipt" maxlength="6">
            <div style="width:100%;text-align: right;;"> <a onclick='xiugai_pass();'>密码管理</a></div>
            <a class="commtBtn" onclick="commitBtn();" style="display:none;">确认</a>
        </div>
	</div>
    <div class="pass_bg"></div>
	<div class="content_top">
		<div style="width:40%;float:left;margin-left:20px;">
            <img src="./images/info_image/guize_black.png" alt="" style="width: 20px;height: 15px;vertical-align:middle;"/>
            <span style="vertical-align: middle;color:#6d6d6d;"><?php echo $custom;?>提现</span>
        </div>
        <div style="width:40%;float:right;margin-right:20px;text-align: right;" onclick="viewLog();">
            <img src="./<?php echo $images_skin?>/info_image/list-orange.png" alt="" style="width: 20px;height: 15px;vertical-align:middle;"/>
            <span class="rule" style="vertical-align: middle;">提现记录</span>
        </div>
    </div>
    

    <div class="infoBox">
        <div class="ele" style="color:#363636;">提现金额        </div>
        <div class="ele" style="font-size:21px;height:50px;font-size:0;line-height:50px;">
           <span class="ele_coinstyle" style="width:6%;font-size:30px;vertical-align:middle;height:50px;">￥</span><input type='number' value="" id="price" placeholder="请输入提现金额" style="background-color:#ffffff !important;width:88%;border:none;font-size:28px;float:right;"/>
        </div>
        <div class="line" style="margin-right: 10px;"></div>
        <div class="ele">总余额 <span class="black"><?php echo $currency;?><?php echo $custom;?></span>，可提现余额 <span class="redunder"><?php echo $max_money;?><?php echo $custom;?></span></div>
        <div class="rule" style="text-align: right;padding: 10px;" onclick="All_to_send(<?php echo $max_money;?>);">全部提现</div>
        <div class="line" style="margin-right: 10px;"></div>
        <div class="ele" style="line-height: 50px;height:50px;">
            <span style="vertical-align: middle;margin-left:3px;">ID：</span>
            <input type="text" value="" placeholder="请输入洛克云用户ID" style="border:none;color:;#bcbcbc;" id="luokeyun_id" name="luokeyun_id">
        </div>       
		<div class="line" style="margin-right: 10px;"></div>
		<div class="ele" style="line-height: 50px;height:50px;">
            <span style="vertical-align: middle;margin-left:3px;">手机号：</span>
            <input type="text" value="" placeholder="请入洛克云用户手机号" style="border:none;color:;#bcbcbc;" id="luokeyun_phone" name="luokeyun_phone">
        </div> 
    </div>
	<div class="content_bottom">
        <div style="width:100%;float:left;padding-left:20px;" >
            <input class="ele" type="checkbox" id="rule"  style="float:left;">
            <span style="vertical-align: middle;margin-left:3px;float:left;color:#797979;" >我同意提现到洛克云平台！</span>
        </div>
    </div>
    <div class="btn button buttonclick" onclick="commit();"><span>确认提现</span></div>
    
   
    <script type="text/javascript" src="./assets/js/jquery.min.js"></script>    
    <script type="text/javascript" src="./assets/js/amazeui.js"></script>
    <script type="text/javascript" src="./js/global.js"></script>
    <script type="text/javascript" src="./js/loading.js"></script>
    <script src="./js/jquery.ellipsis.js"></script>
    <script src="./js/jquery.ellipsis.unobtrusive.js"></script>  
    <script src="./js/r_global_brain.js" type="text/javascript"></script>
    <script type="text/javascript" src="./js/r_jquery.mobile-1.2.0.min.js"></script>
    <script src="./js/sliding.js"></script>
</body>		

<script type="text/javascript">
	var max_money       = <?php echo $max_money;?>;
	var is_pwd_null     = "<?php echo $is_pwd_null;?>";
	var customer_id     = "<?php echo $customer_id_en;?>";
	

    if($("#price").val()!="") 
        $("#price").css("background","#fff");
    else
        $("#price").css("background","rgb(240, 240, 240)");
    
    //jump to 输入密码
    function xiugai_pass(){
        window.location.href="modify_password.php?f_h=1&customer_id=<?php echo $customer_id_en;?>";
    }
    
    //jump to 修改转赠用户
   /*  function repair_user(){
        window.location.href="change_currency_user.php?customer_id=<?php echo $customer_id_en;?>";
    } */

    $(".sharebg-active").click(function() {
        $(this).hide();
        $(".am-share1").hide();
    }); 

$(function(){

    var is_pw = "<?php echo $is_pw;?>";
    if( is_pw == 0 ){
        function callbackfunc(){
            window.location.href="modify_password.php?customer_id=<?php echo $customer_id_en;?>";
        }
        showConfirmMsg("提示","您尚未设置支付密码，是否立即设置？","确定","取消",callbackfunc);
        return false;
    }

})

    function All_to_send(All){
        var all_money = All;
        if(All<=0){
            showAlertMsg("提示","当前可转赠余额为0","知道了");
            return false;
        }
        $("#price").val(all_money);
    }     
        
    function commit(){
		var send_money = $("#price").val();
		var luokeyun_id = $("#luokeyun_id").val();
		var luokeyun_phone = $("#luokeyun_phone").val();
        
		if(send_money<=0 || send_money==''){
			showAlertMsg("提示","请输入正确的提现金额！","确定");
            return false;
		}
		
		if(send_money<10){
			showAlertMsg("提示","每次提现金额最少10以上！","确定");
            return false;
		}
		
		if(luokeyun_id=='' || luokeyun_phone==''){
			showAlertMsg("提示","请输入洛克云用户信息！","确定");
            return false;
		}
		
        if( send_money > max_money ){
            showAlertMsg("提示","提现金额大于可提现金额，请重新输入！","确定");
            return false;
        }
		
		if($("input[type='checkbox']").is(':checked')==false){
            showAlertMsg("提示","请勾选确认提现至洛克云平台","确定");
            return false;
        }
		
		if(is_pwd_null==1){
            function callbackfunc(){
                window.location.href="modify_password.php?customer_id=<?php echo $customer_id_en;?>";
            }
            showConfirmMsg("提示","您尚未设置支付密码，请设置支付密码","确定","取消",callbackfunc);
             return false;
        }

        $(".sharebg1").show();
        var type = 'Check_pw';
        $.ajax({
            url         :   'save_currency_get_luokeyun.php',
            dataType    :   'json',
            type        :   "post",
            data        :{
                            'customer_id':customer_id,
                            'type':type
                        },
            success:function(data){
                if(data.msg==400){
                    function callbackfunc(){
                        window.location.href="modify_password.php?customer_id=<?php echo $customer_id_en;?>";
                    }
                    showConfirmMsg("提示","您尚未设置支付密码，是否立即前往设置？","确定","取消",callbackfunc);
                }else if(data.msg==40004){
                    $("#pass_w").show();
                    $(".am-share").addClass("am-modal-active");    
                    // $(".sharebg").addClass("sharebg-active");
                    $(".sharebg-active").click(function(){
                        $(".am-share").removeClass("am-modal-active");
                        $(".sharebg1").hide();
                        $("#pass_w").hide();
                        return false;
                    })
                    
                }
            }
        });     
    }

    $(".sharebg1").click(function() {
        $('#ipt').val("");
    	$("#pass_w").hide();
    	$(".sharebg1").hide();
    });
	
    function viewLog(){
        window.location.href="my_currency_luokeyun_log.php?customer_id=<?php echo $customer_id_en;?>";
    }

	function commitBtn(){
		$(".sharebg1").hide();
		$("#pass_w").hide();
        var pw_lenght   = $('input').val().length;
		var send_money 	= $("#price").val();
		var pw 			= $('input').val();
		var type 		= 'send_currency';
		var luokeyun_id = $("#luokeyun_id").val();
		var luokeyun_phone = $("#luokeyun_phone").val();
        if( pw_lenght != 6 ){
            showAlertMsg("提示","请输入六位长度的密码","确定");
            return false;
        }

        $.ajax({
            url         :   'save_currency_get_luokeyun.php',
            dataType    :   'json',
            type        :   "post",
            data        :{
                            'luokeyun_id':luokeyun_id,
                            'luokeyun_phone':luokeyun_phone,
                            'send_currency':send_money,
                            'password':pw,
                            'type':type
                        },
            success:function(data){

                if(data.msg==400){
                    function callbackfunc(){
                        window.location.href="modify_password.php?customer_id=<?php echo $customer_id_en;?>";
                    }
                    showConfirmMsg("提示","您尚未设置支付密码，是否立即前往设置？","确定","取消",callbackfunc);
                }

                if(data.msg==401){
                    var ins_id = data.ins_id;
                    // window.location.href='my_money_out.php?from=currency&id='+ins_id;
                    function callbackfunc(){
                        window.location.href="my_money_out.php?customer_id=<?php echo $customer_id_en;?>&from=currency&id="+ins_id;
                    }
                    showConfirmMsg("提示","提现成功！金额将转至对应的洛克云平台账号","确定","取消",callbackfunc);
                }else{
                    var msg = data.remark;
                    showAlertMsg("提示",msg,"确定");
                    return false;
                }
            }
        })

	}

    
    $('input').on('input', function (e){
        var numLen = 6;
        var pw = $('input').val();
        var list = $('li');
        for(var i=0; i<numLen; i++){
            if(pw[i]){
                $(list[i]).text('·');
            }else{
                $(list[i]).text('');
            }
        }
    });
    $('#ipt').on('keyup', function (e){
        var num_len = $('input').val().length;
        if(num_len == 6){
            $(".commtBtn").show();
        }else{
            $(".commtBtn").hide();
        }
    });



</script>
<!--引入侧边栏 start-->
<?php  include_once('float.php');?>
<!--引入侧边栏 end-->
</html>