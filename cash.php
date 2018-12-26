<?php
header("Content-type: text/html; charset=utf-8");     
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
//require('../back_init.php'); 
require('../common/utility_fun.php');

$link = mysql_connect(DB_HOST,DB_USER,DB_PWD); 
mysql_select_db(DB_NAME) or die('Could not select database');
require('select_skin.php');
//头文件----start
require('../common/common_from.php');
//头文件----end

//查询是否开启APP功能
$is_open_app=0;//是否开启APP功能 1:开启 0:关闭
$sql = "select funs.id from columns as col inner join customer_funs as funs where col.sys_name='微商App' and col.isvalid=true and funs.column_id=col.id and funs.isvalid=true and funs.customer_id=".$customer_id;
$res = _mysql_query($sql) or die('Query failed 110: ' . mysql_error());
while( $row = mysql_fetch_object($res) ){
    $is_open_app = $row->id;
}
if ($is_open_app > 0){
    require('app_tocash.php');
    $app_tocash = new app_tocash($customer_id);
    $tocash_switch = $app_tocash->app_cash_switch($user_id);
    if (!empty($tocash_switch)){
        if ($tocash_switch['in_app'] == 0){
            echo "<script>alert('只能在APP中提现');window.history.go(-1);</script>";
        }
        if($tocash_switch['login_app'] == 0){
            echo "<script>alert('您未登陆过APP，仅限登陆过APP的用户提现');window.history.go(-1);</script>";
        }
    }
}


$yundian = -1;
if(!empty($_GET['yundian'])){
  $yundian = $_GET['yundian'];
}

$cash_name = "";    //提现到
$choose_type = $configutil->splash_new($_GET["c_type"]);//提现类型
switch ($choose_type) {
    case '0':
        $cash_name = "微信零钱";
        $cahs_logo = "./images/info_image/weixin.png";
    break;

    case '1':
        $cash_name = "支付宝";
        $cahs_logo = "./images/info_image/zhifubao.png";
    break;

    case '2':
        $cash_name = "财付通";
        $cahs_logo = "./images/info_image/caifutong.png";
    break;

    case '3':
        $cash_name = "银行卡";
        $cahs_logo = "./images/info_image/card.png";
    break;

    case '4':
        $cash_name = "环迅账户";
        $cahs_logo = "./images/tixian/icon-hxzf.png";
    break;
    
    default:
         $cash_name = "未知";
         $cahs_logo = "./images/order_image/icon_comment_bad_sel.png";
        break;
}

//查询钱包提现规则------------------------------------------------start
$isOpen_callback    = 0;//是否开启零钱提现
$start_time         = 1;//每月提现开始日期
$end_time           = 30;//每月提现结束日期
$week_time          = 0;//提现可设置按每周几提现 0：周日；1-6；周一-周六
$mini_callback      = 0;//最低提现金额
$max_callback       = 0;//不能提现金额
$callback_currency  = 0;//提现返购物币比例
$callback_fee       = 0;//提现手续费比例
$full_vpscore       = 0;//提现vp值限制
$cash_coefficient   = 1;//提现系数，1：不限，2：整10，3：整100，4：整1000
$callback_fee_flxed = 0;//提现手续费固定金额
$is_fee 			= 0;//提现手续费开关，0：关，1：开
$is_currency 		= 0;//提现返送购物币开关，0：关，1：开
$fee_type 			= 0;//手续费类型，1：固定金额，2：比例

$query = "SELECT isOpen_callback,
                 start_time,
                 end_time,
                 week_time,
                 mini_callback,
                 max_callback,
                 callback_currency,
                 callback_fee,
                 full_vpscore,
                 cash_coefficient,
                 callback_fee_flxed,
                 is_fee,
                 is_currency,
                 fee_type
          FROM moneybag_rule 
          WHERE customer_id=".$customer_id." AND isvalid=true LIMIT 1";

$query = _mysql_query($query) or die('Query failed 32: ' . mysql_error());
while( $row = mysql_fetch_object($query) ){
    $isOpen_callback    = $row->isOpen_callback;
    $start_time         = $row->start_time;
    $end_time           = $row->end_time;
    $week_time          = $row->week_time;
    $mini_callback      = $row->mini_callback;
    $max_callback       = $row->max_callback;
    $callback_currency  = $row->callback_currency;
    $callback_fee       = $row->callback_fee;
    $full_vpscore       = $row->full_vpscore;
    $cash_coefficient   = $row->cash_coefficient;
    $callback_fee_flxed = $row->callback_fee_flxed;
    $is_fee   			= $row->is_fee;
    $is_currency   		= $row->is_currency;
    $fee_type   		= $row->fee_type;
}   
$cash_coefficient_val = -1;	//系数值
switch($cash_coefficient){
	case 1:
		$cash_coefficient_val = -1;
	break;
	case 2:
		$cash_coefficient_val = 10;
	break;
	case 3:
		$cash_coefficient_val = 100;
	break;
	case 4:
		$cash_coefficient_val = 1000;
	break;
}

//查询钱包提现规则------------------------------------------------end

//查询个人钱包------------------------------------------------start
$balance = 0;
$query = "SELECT balance FROM moneybag_t WHERE isvalid=true AND customer_id=".$customer_id." AND user_id=".$user_id." LIMIT 1";
$result= _mysql_query($query) or die('Query failed 32: ' . mysql_error());
while( $row = mysql_fetch_object($result) ){
    $balance = $row->balance;
    $balance = cut_num($balance,2);
}
$weixin_name = "";  //微信名
$account     = "";  //绑定手机号
$query = "SELECT weixin_name FROM weixin_users WHERE isvalid=true AND id=".$user_id." LIMIT 1";
$result= _mysql_query($query) or die('Query failed 32: ' . mysql_error());
while( $row = mysql_fetch_object($result) ){
    $weixin_name = $row->weixin_name; 
}


$query = "SELECT account FROM system_user_t WHERE isvalid=true AND user_id=$user_id LIMIT 1";
$result= _mysql_query($query) or die('Query failed 104: ' . mysql_error());
while( $row = mysql_fetch_object($result) ){
    $account = $row->account;
    $account     = substr($account, 0, 3).'****'.substr($account, 7);//隐藏中间号码
}

//查询个人钱包------------------------------------------------start

//判断当前用户提现条件
//print_r(getdate(time()));die;
$allow_cash = 0;        //允许提现的金额 初始化
$my_vpscore = 0;        //最低提现个人vp值
$mtime = array();
$mtime = getdate(time());
$todays= $mtime["mday"];//当前月天数 1~31
$wday  = $mtime["wday"];//今天是当星期的星期几 0~6
$allow_cash = cut_num($balance-$max_callback,2);

if($allow_cash<=0){
    $allow_cash=0;
}


$sql = "SELECT my_vpscore FROM weixin_user_vp WHERE isvalid=true AND customer_id=".$customer_id." AND user_id=".$user_id." LIMIT 1";
$res = _mysql_query($sql) or die('Query failed 32: ' . mysql_error());
while( $row = mysql_fetch_object($res) ){
    $my_vpscore = $row->my_vpscore;
}
$TodayBeing = date( 'Y-m-d H:i:s',strtotime( date( 'Y-m-d' ) ) );//今天开始时间
$TodayEnd = date( 'Y-m-d H:i:s',strtotime( date('Y-m-d',strtotime('+1 day') ) ) );//今天结束时间
// echo $TodayBeing;
// echo $TodayEnd;
// 判断今天是否提现过
$cash_num = 0;
$query = "SELECT count(id) as num FROM weixin_cash_being_log WHERE isvalid = true AND createtime>='$TodayBeing' AND createtime<='$TodayEnd' AND user_id = $user_id";
$result= _mysql_query($query) or die('Query failed 133: ' . mysql_error());
while( $row = mysql_fetch_object($result)){
    $cash_num = $row->num;
}


//各项条件------------start
$vp_boole = 0;
if( $my_vpscore >= $full_vpscore ){   //---如果 自己的vp值>= 系统最低所需vp值。则符合
        $vp_boole = 1;
}
//echo $vp_boole."==";die;
$start_boole = 0;
if( $start_time <= $todays ){        //---是否满足可期限日期 则符合
        $start_boole = 1;
}
$end_boole =0;                      //---提现结束日期是否满足
if( $end_time >= $todays ){
    $end_boole = 1;
}
$week_boole = 0;
if( $week_time = $wday || $$week_time = -1 ){
    $week_boole = 1;
}

$is_allow = 0;
if($vp_boole == 1 && $start_boole == 1 && $end_boole == 1 && $week_boole == 1){
    $is_allow = 1;
}
//各项条件------------end

$sql_custom = "SELECT custom FROM weixin_commonshop_currency WHERE isvalid=true AND customer_id=".$customer_id;
$res_custom = _mysql_query($sql_custom) or die('Sql_custom failed:'.mysql_error());
$custom = '';
while ($row_custom = mysql_fetch_object($res_custom) ){
    $custom = $row_custom->custom;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>提现 </title>
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
    
    <script type="text/javascript" src="./assets/js/jquery.min.js"></script>    
    
    <link rel="stylesheet" id="wp-pagenavi-css" href="./css/list_css/pagenavi-css.css" type="text/css" media="all">
    <link rel="stylesheet" id="twentytwelve-style-css" href="./css/list_css/style.css" type="text/css" media="all">
    <link rel="stylesheet" id="twentytwelve-style-css" href="./css/goods/dialog.css" type="text/css" media="all">
    
    <link type="text/css" rel="stylesheet" href="./css/list_css/r_style.css" />
    <link type="text/css" rel="stylesheet" href="./css/password.css" />
    
<style>  
   .selected{border-bottom: 5px solid black; color:black; }
   .list {margin: 10px 5px 0 3px;   overflow: hidden;}
   .area-line{height:25px;width:1px;float:left;margin-top: 10px;padding-top: 20px;border-left:1px solid #cdcdcd;}
   .topDivSel{width:100%;height:45px;top:50px;padding-top:0px;background-color:white;}
   .infoBox{width:90%;margin:10px auto;;background-color:white;border:1px solid #ddd;}
   .infoBox .ele{/*height: 40px;*/width:90%;line-height: 40px;margin:0 auto;}
   .ele .left{width:40%;float:left;color:#727272}
   .ele .right{width:60%;float:left;}
   .ele img{width: 20px;height: 20px;vertical-align:middle;}
   .red{color:red;}
   .black{color:black}
   .line{background-color: #DEDBD5;margin-left: 10px;height: 1px;}
   .content_top{padding-top:20px;background-color:#f8f8f8;}
   .content_bottom{height: 22px;line-height:22px;background-color:#f8f8f8;}
   .btn{width:80%;margin:20px auto;text-align:center;}
   .btn span{width:100%;color:white;height:45px;line-height:45px; padding:10px;letter-spacing:3px;}
   .content_top .detail{width:100%;text-align: center;font-size:20px;height: 35px;line-height:35px;}
   .detail img{width: 20px;height: 20px;vertical-align:middle;}
   .sharebg{opacity: 1}
   .cash_fee,.cash_currency{display: inline-block;margin-left: 10px;color: #888888;font-size:12px;padding: 0 0 10px;}
   .to_account{display: inline-block;margin-left: 10px;padding: 0 0 10px;}
    .box h1{position: relative;}
   .zhifuxx{position: absolute;top: 12px;left: 14%;}
</style>
</head>
<!-- Loading Screen -->
<div id='loading' class='loadingPop' style="display: none;"><img src='./images/loading.gif' style="width:40px;"/><p class=""></p></div>

<body data-ctrl=true style="background:#f8f8f8;">
    <div class="topDiv"></div>
    <div class="sharebg"></div><!--半透明背景-->
    <div class="am-share" id="pass_w" style="width:100%;position: fixed;top:30%;z-index: 1111;height: 160px;overflow: hidden;display:none;">
        <div class="box">
            <h1><span class="zhifuxx">X</span>输入支付密码</h1>
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
            <input type="tel" id="ipt" maxlength="6" autofocus="autofocus">
<!--            <div style="width:100%;text-align: right;;"> <a onclick='xiugai_pass();'>密码管理</a></div>-->
<!--            <a class="commtBtn" onclick="commitBtn();" style="display:none;">确认</a>-->
        </div>
    </div>


    <div class="content_top">
        <div class="detail" ><span><?php echo $weixin_name;?></span></div>
        <div class="detail" ><span><?php echo $account;?></span></div>
        <div class="detail">
            <img src="<?php echo $cahs_logo;?>" alt="" style=""/>
            <span style="vertical-align: middle;font-size:14px;"><?php echo $cash_name;?></span>
        </div>
    </div>
 
    <div class="infoBox">
        <div class="ele">
			<span>提现金额</span>
			<div class="ele_coinstyle_cash" style="text-align: right;float:right;" onclick="get_all();">全部提现</div>
			<div style="clear:both;"></div>
		</div>
        <div class="ele" style="font-size:22px;height:42px;display:flex;align-items:center;">
            <!-- <span style="width:6%;color:#ff8430;font-size:30px;vertical-align:middle;height:50px;">￥</span><input type='number' value="" id="price" placeholder="请输入提现金额" style="width:100%;border:none;"/>-->
            <span class="ele_coinstyle_cash"><?php if(OOF_P != 2) echo OOF_S ?><?php if(OOF_P == 2) echo OOF_S ?></span>
            <input type='number' value="" id="price" placeholder="<?php if($cash_coefficient_val>0){?>请输入金额必须为<?php echo $cash_coefficient_val;?>的倍数<?php }else{?>请输入提现金额<?php }?>" style="background-color:#ffffff !important;border:none;font-size:18px;flex-grow:2;" onkeyup="price_keyup();" onchange="price_keyup();" />
        </div>
        <div class="line" style="margin-right: 10px;"></div>
        <div class="ele">总余额 <span class="black"><?php echo $balance;?><?php echo OOF_T ?></span>，可提现余额 <span class="red"><?php echo $allow_cash;?><?php echo OOF_T ?></span></div>
		<div style="padding: 0 0 10px;">
			<div class="cash_fee" style="display:none;">
				手续费:
				<span class="fee_num"></span><?php echo OOF_T ?>
			</div>
			<div class="cash_currency" style="display:none;">
				返<?php echo $custom;?>:
				<span class="currency_num"></span>个
			</div>
			<div class="to_account" style="display:none;">
				实际到帐:
				<span class="real_money"></span><?php echo OOF_T ?>
			</div>
		</div>
    </div>
    <div class="btn" onclick="commit();"><span>提现</span></div>
    
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
    var customer_id 			= "<?php echo $customer_id_en?>";
	var cash_coefficient_val 	= <?php echo $cash_coefficient_val;?>;	//提现系数值
	var callback_currency 		= <?php echo $callback_currency;?>;		//提现返送购物币比例
	var callback_fee 			= <?php echo $callback_fee;?>;			//提现手续费比例
	var callback_fee_flxed 		= <?php echo $callback_fee_flxed;?>;	//提现手续费固定金额
	var is_fee 					= <?php echo $is_fee;?>;				//提现手续费开关，0：关，1：开
	var is_currency 			= <?php echo $is_currency;?>;			//提现返送购物币开关，0：关，1：开
	var fee_type 				= <?php echo $fee_type;?>;				//手续费类型，1：固定金额，2：比例
    var yundian              = '<?php echo $yundian; ?>';	
    $(document).ready(function(){
        if($("#price").val()!="") 
            $("#price").css("background","#fffff");
        else
            $("#price").css("background","#fffff");
    });
    
    $(".sharebg,.zhifuxx").click(function() {
		$('#ipt').val("");
		$('li').text("");
        $("#pass_w").hide();
        $(".sharebg").hide();
        //$("box").hide();
    });
    
    //jump to 输入密码
    function xiugai_pass(){
        window.location.href="change_paypassword.php?customer_id="+customer_id;
    }

    var num     = /^([1-9][\d]{0,7}|0)(\.[\d]{1,2})?$/;
    
    
    var customer_id = "<?php echo $customer_id_en;?>";

    function commit(){
        $('#ipt').val("");
            $('li').text("");
        var sharebg = $('.sharebg');
        if(!sharebg.length){
            $("body").append('<div class="sharebg"></div>');
        }
        // var cash_num = "<?php echo $cash_num;?>";
        // if( cash_num > 0 ){
        //     showAlertMsg ("提示：","每个用户每天最多能申请提现一次","知道了");
        //     return false;
        // }

        // $(".sharebg").remove();
        // $("body").append('<div class="sharebg"></div>');
        //先验证用户是否有密码
        
        //$(".sharebg").show();
         //$(".sharebg").css({"opacity":"1"});
        var pw = $('#ipt').val();
        var type    = "<?php echo $choose_type?>";
        var to_cash = parseFloat($("#price").val()); 
        var allow_cash = parseFloat("<?php echo $allow_cash;?>");
		var real_money = $('.real_money').text();
        //alert(to_cash+"=="+"==="+allow_cash);
        var save_type = 'check';
        if( !num.test(to_cash)){
            //showAlertMsg ("提示：","请输入正确的金额","知道了");
            alertAutoClose("请输入正确的金额");
            return false;
        }
        if( allow_cash < to_cash){
            //showAlertMsg ("提示：","提现金额超限","知道了");
            alertAutoClose("提现金额超限");
            return false;
        }
		if( to_cash <= 0 ){
			//showAlertMsg ("提示：","提现金额不能为0或负数","知道了");
            alertAutoClose("提现金额不能为0或负数");
            return false;
		}
		if( parseFloat(real_money) < 1 && parseFloat(real_money) >= 0 ){
			//showAlertMsg ("提示：","当前的实际到账金额必须大于1","知道了");
            alertAutoClose("当前的实际到账金额必须大于1");
            return false;
		}
		if( cash_coefficient_val > 0 ){
			if( to_cash % cash_coefficient_val > 0 ){	//求余
				//showAlertMsg ("提示：","只能提现"+cash_coefficient_val+"的倍数的金额","知道了");
                alertAutoClose("只能提现"+cash_coefficient_val+"的倍数的金额");
				return false;
			}
		}
        // $(".sharebg").show();
        $.ajax({
            url     :   'save_tocash.php',
            dataType:   'json',
            type    :   "post",
            data    :{
                        'to_cash':to_cash,
                        'type':type,
                        'customer_id':customer_id,//加密后的customer_id
                        'save_type':save_type
            },
            success:function(data){

                if(data.msg==10000){//无密码
                    function callbackfunc(){
                        window.location.href="modify_password.php?customer_id=<?php echo $customer_id_en;?>";
                    }
                    showConfirmMsg("提示","您尚未设置支付密码，是否立即前往设置？","确定","取消",callbackfunc);

                }else if(data.msg==30066){//首轮验证通过

                    $("#pass_w").show();
                    document.getElementById("ipt").focus();
                    $(".am-share").addClass("am-modal-active");    
                    $(".sharebg-active").click(function(){	

                        $(".am-share").removeClass("am-modal-active");
                        $(".sharebg").hide();
                        $('#ipt').val("");
                        $("#pass_w").hide();
                        return false;
                    }) 
 
                }else{
                   var conten = data.remark;
                   //showAlertMsg("提示",conten,"知道了");
                   alertAutoClose(conten);
                   return false;

                }
            }
        });
        document.getElementById("ipt").focus();
      
    }
    var error_time = 0;//输入密码错误次数
    $('#ipt').on('keyup', function (e){		
         var num_len = $('#ipt').val().length;
        if(num_len == 6) {			
            var pw = $('#ipt').val();
            var to_cash = $("#price").val();
            var type = "<?php echo $choose_type?>";
            $("body").append('<div class="sharebg"></div>');
            $.ajax({
                url: 'save_tocash.php',
                dataType: 'json',
                type: "post",
                data: {
                    'to_cash': to_cash,
                    'type': type,
                    'customer_id': customer_id,//加密后的customer_id
                    'pw': pw
                },
                success: function (data) {
                    if (data.msg == 402) {
                        error_time++;
                    }

                    var content = data.remark;

                    function callbackfunc() {
                        if(yundian > -1 && yundian != 0){     //由云店跳转过来的提现页面，返回的都是云店的个人中心
                            window.location.href = "<?php echo Protocol.$_SERVER['HTTP_HOST'];?>/weixinpl/mshop/personal_center.php?yundian="+yundian;
                        }else{
                            window.location.href = "<?php echo Protocol.$_SERVER['HTTP_HOST'];?>/mshop/web/index.php?m=currency&a=index&customer_id=<?php echo $customer_id_en;?>";
                        }
                    }

                    $(".sharebg").hide();
                    $(".alert").hide();
                    $("#pass_w").hide();
                    if (data.msg == 405 || data.msg == 406 || data.msg == 407) {


                        $(".sharebg").addClass("sharebg-active");
                        showConfirmMsg("提示", "申请已提交至商家", "确定", "取消", callbackfunc);

                    } else if (data.msg == 388 || data.msg == 403 || data.msg == 398) {

                        function callbackfunc() {
                            $('#ipt').val("");
                            $('li').text("");
                            if(yundian > -1 && yundian != 0){     //由云店跳转过来的提现页面，返回的都是云店的个人中心
                                window.location.href = "<?php echo Protocol.$_SERVER['HTTP_HOST'];?>/weixinpl/mshop/personal_center.php?yundian="+yundian;
                            }else{
                                window.location.href = "<?php echo Protocol.$_SERVER['HTTP_HOST'];?>/mshop/web/index.php?m=currency&a=index&customer_id=<?php echo $customer_id_en;?>";
                            }
                        }

                        showConfirmMsg("提示", content, "确定", "取消", callbackfunc);
                    } else {
                        //alert(1);

                        $('#ipt').val("");
                        $('li').text("");
                        $(".sharebg").show();
//                    $(".commtBtn").hide();
                        if (error_time >= 3) {
                            showConfirmMsg03('提示', '支付密码输入错误次数过多，你可以点击忘记密码，进行找回密码', '忘记密码', '重新输入', forget_pay_password,show_pass_w);
                            return;
                        }
                        showConfirmMsg03('提示', '密码输入错误', '忘记密码', '重新输入', forget_pay_password,show_pass_w);

                    }
                }
            });
        }
    });


    function showConfirmMsg03(title,content,ok_btn,cancel_btn,callbackfunc,callbackfunc02) {
        //if(btn==null) btn="确认";
        $("body").append('<div class="am-share confirm"></div>');
        $(".confirm").addClass("am-modal-active");
        $("body").append('<div class="sharebg" style="opacity:0"></div>');
        $(".sharebg").animate({"opacity": 1});
        $(".sharebg").addClass("sharebg-active");
        var html = "";
        html += '<div class = "close_button">';
        html += '<img src = "/weixinpl/mshop/images/info_image/btn_close.png"  width = "30">';
        html += '</div>';
        html += '<div class = "alert_content">';
        html += '  <div class = "dlg_content1_row1" style="text-align:left;">';
        html += '       <span  style="font-size:15px;">' + title + '</span>';
        html += '    </div>';
        html += '<div class = "dlg_content1_row2">';
        html += '    <span style="font-size: 15px;">' + content + '</span>';
        html += '</div>';
        html += '</div>';
        if (ok_btn != "") {
            html += '<div class = "dlg_commit_left commit">';
            html += '    <span>' + ok_btn + '</span>';
            html += '</div>';
        }
        if (cancel_btn != "") {
            html += '<div class = "dlg_commit_right cancel">';
            html += '    <span">' + cancel_btn + '</span>';
            html += '</div>';
        }

        $(".confirm").html(html);

        // dialog cancel_btn按键点击事件
        $(".sharebg-active,.share_btn, .cancel, .close_button").click(function () {
            callbackfunc02();
            $(".sharebg-active").css('opacity',0.5);
            $(".confirm").remove();
            $('#ipt').val("");
            $('li').text("");

        });
        $(".commit").click(function(){
            callbackfunc();
            $(".am-share").removeClass("am-modal-active");
            $(".sharebg").animate({"opacity":0});
            setTimeout(function(){
                $(".sharebg-active").removeClass("sharebg-active");
                $(".sharebg").remove();
                $(".confirm").remove();
            },300);

        });
    }
    //忘记密码
    function forget_pay_password(){
        window.location.href = 'forget_paypassword.php?customer_id=<?php echo $customer_id_en;?>';
    }

    function show_pass_w(){
        $(".am-share").addClass("am-modal-active");
        $(".sharebg").show();
        $("#pass_w").show();//支付层
        document.getElementById("ipt").focus();
    }

    function get_all(){
        var all_money = <?php echo $allow_cash?>;
        $("#price").val(all_money);
		price_keyup();
    }

    function xiugai_pass(){
        window.location.href="change_paypassword.php?customer_id=<?php echo $customer_id_en;?>";
    }
    $('#ipt').on('input', function (e){
        var numLen = 6;
        var pw = $('#ipt').val();
        //alert(pw);
        var list = $('li');
        for(var i=0; i<numLen; i++){
            //alert(pw[i]);
             if(pw[i]){
                //alert(pw[i]);
                $(list[i]).text('·');
            }else{
                $(list[i]).text('');
            } 
        }
    });
//    $('#ipt').on('keyup', function (e){
//        var num_len = $('#ipt').val().length;
//        if(num_len == 6){
//            $(".commtBtn").show();
//        }else{
//            $(".commtBtn").hide();
//        }
//    });
	function price_keyup(){
		var money = $('#price').val();
		var _fee = 0;		//手续费
		var _currency = 0;	//购物币
		var _money = 0;		//实际到帐
		
		if( is_fee ){
			switch(fee_type){
				case 1:
					_fee = callback_fee_flxed;
				break;
				case 2:
					_fee = Math.round(money*callback_fee)/100;
				break;
			}
			if( _fee > 0 ){
				$('.fee_num').text(_fee);
				$('.cash_fee').show();
			} else {
				$('.cash_fee').hide();
			}
		}
		
		if( is_currency ){
			_currency = Math.round(money*callback_currency)/100;
			if( _currency > 0 ){
				$('.currency_num').text(_currency);
				$('.cash_currency').show();
			} else {
				$('.cash_currency').hide();
			}
		}
		
		_money = (money - _fee - _currency).toFixed(2);
		
		if( _money < 0 ){
			_money = 0;
		}
		
		if( _money >= 0 ){
			$('.real_money').text(_money);
			$('.to_account').show();
		} else {
			$('.to_account').hide();
		}
		
	}
</script>
<!--引入侧边栏 start-->
<?php  include_once('float.php');?>
<!--引入侧边栏 end-->
</html>