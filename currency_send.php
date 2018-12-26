<?php
header("Content-type: text/html; charset=utf-8");
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]

require('../common/utility_fun.php');
require(ROOT_DIR.'wsy_pay/web/function/show_pay_way.php');

$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
//头文件----start
require('../common/common_from.php');
//头文件----end
require('select_skin.php');
//转赠者uesr_id
$to_user_id = isset($_GET["to_user_id"])?$configutil->splash_new($_GET["to_user_id"]):"-1";
if($to_user_id == -1){
    $t_id = '';
}else{
    $t_id = $to_user_id;
    $id   = -1;
    $query = "SELECT id,name,weixin_name FROM weixin_users WHERE isvalid=true AND customer_id=$customer_id and  id=".$to_user_id;
    $result= _mysql_query($query);
    while($row=mysql_fetch_object($result)){
        $id          = $row->id;
        $name        = $row->name;
        $weixin_name = $row->weixin_name;
    }
}

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
$query = "SELECT custom,rule,mini_limit FROM weixin_commonshop_currency WHERE customer_id=".$customer_id." limit 1";
$result = _mysql_query($query)or die('Query failed101: ' . mysql_error());
while($row=mysql_fetch_object($result)){
    $custom     = $row->custom;
    $rule       = $row->rule;
    $mini_limit = $row->mini_limit;
    $max_money  = $currency-$mini_limit;
    $max_money  = substr(sprintf("%.3f", $max_money),0,-1);
    $currency   = substr(sprintf("%.3f", $currency),0,-1);
    if($max_money<=0){
        $max_money = 0;
    }
}



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

//$is_pw  = 0;
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
// if($paypassword!=''){
//     $is_pw = 1;
// }else{
//     $is_pw = 0;
// }

/*-------------查询是否已经设置了支付密码-------------*/
$show_pay_way = new show_pay_way($customer_id);
$check_pay_password = $show_pay_way->check_pay_password($customer_id,$user_id);
/** 支付密码设置优化 create_by hzq **/
if($check_pay_password == '-1'){
	$_SESSION['pass_url_'.$customer_id] = $_SERVER['REQUEST_URI'];
}
/** 支付密码设置优化 end hzq **/
$qian=array(" ","　","\t","\n","\r");
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $custom;?>转赠 </title>
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
  .sharebg{opacity: 0.5}
  .guanbicha{position: absolute;top: 12px;left: 14%;}
  .guanbicha img{width: 13px;height: 13px;}
    .box label {margin-bottom: 10px;}

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
<div id='loading' class='loadingPop' style="display: none;"><img src='./images/loading.gif' style="width:40px;"/><p class=""></p></div>

<body data-ctrl=true style="background:#f8f8f8;">
	<div class="sharebg"></div><!--半透明背景-->
	<div class="am-share" id="pass_w" style="width:100%;position: fixed;top:30%;z-index: 1111;height: 160px;overflow: hidden;display:none;">
        <div class="box">
            <span class="guanbicha"><img src="./images/Remove.png"></span>
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
            <input type="tel" id="ipt" maxlength="6" autofocus="autofocus">
           <!--  <div style="width:100%;text-align: right;;"> <a onclick='xiugai_pass();'>密码管理</a></div> -->
<!--            <a class="commtBtn" onclick="commitBtn();" >确认</a>-->
        </div>
	</div>
    <div class="pass_bg"></div>
	<div class="content_top">
		<div style="width:40%;float:left;margin-left:20px;" onclick="repair_user();">
            <img src="./images/info_image/guize_black.png" alt="" style="width: 20px;height: 15px;vertical-align:middle;"/>
            <span style="vertical-align: middle;color:#6d6d6d;"><?php echo $custom;?>转赠</span>
        </div>
        <div style="width:40%;float:right;margin-right:20px;text-align: right;" onclick="viewLog();">
            <img src="./<?php echo $images_skin?>/info_image/list-orange.png" alt="" style="width: 20px;height: 15px;vertical-align:middle;"/>
            <span class="rule" style="vertical-align: middle;">转赠记录</span>
        </div>
    </div>

    <div class="infoBox" style="position: relative;">
        <div class="ele">
            <div class="left">用户&nbsp;&nbsp;  ID:</div>
            <div class="redunder"><?php echo $t_id;?></div>
        </div>
        <div class="ele">
            <div class="left" >昵&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 称:</div>
            <div class="right"><?php echo $weixin_name;?></div>
        </div>
        <div class="ele">
            <div class="left">真实姓名:</div>
            <div class="right"><?php echo $name;?></div>
        </div>
        <div style="position: absolute;width:100%;top:10px;right:10px;">
            <div style="float:right;width:60px;" onclick="repair_user();" class="button buttonclick">
                <img src="./images/info_image/xiugai.png" alt="" style="width:15px;height:14px;vertical-align:middle;"/>
                <span style="vertical-align: middle;color:#707070;">修改</span>
            </div>
        </div>
    </div>
    <div class="infoBox">
        <div class="ele" style="color:#363636;">转赠金额        </div>
        <div class="ele" style="font-size:21px;height:50px;line-height:50px;">
           <span class="ele_coinstyle" style="width: 20%;display: inline-block;font-size: 14px;vertical-align:middle;height:50px;"><?php echo OOF_S ?></span><input type='number' value="" id="price" placeholder="请输入转赠的<?php echo $custom;?>" style="width:80%;border:none;font-size:14px;float:right;height: 50px;"/>
        </div>
        <div class="line" style="margin-right: 10px;"></div>
        <div class="ele">总余额 <span class="black"><?php echo $currency;?><?php echo $custom;?></span>，可转赠余额 <span class="redunder"><?php echo $max_money;?><?php echo $custom;?></span></div>
        <div class="rule" style="text-align: right;padding: 10px;" onclick="All_to_send(<?php echo $max_money;?>);">全部转赠</div>
        <div class="line" style="margin-right: 10px;"></div>
        <div class="ele" style="line-height: 50px;height:50px;">
            <img src="./images/info_image/mail.png" alt=""/>
            <!-- <span style="vertical-align: middle;margin-left:3px;">恭喜发财  大吉大利</span> -->
            <input type="text" value="恭喜发财  大吉大利" placeholder="恭喜发财  大吉大利" style="border:none;color:;#bcbcbc;" id="describe">
        </div>
    </div>
    <div class="content_bottom">
        <div style="width:100%;float:left;padding-left:20px;" >
            <input class="ele" type="checkbox" id="rule"  style="float:left;" checked="checked">
            <span style="vertical-align: middle;margin-left:3px;float:left;color:#797979;" onclick="viewRule();">转赠规则</span>
        </div>
    </div>
    <div class="btn button buttonclick" onclick="commit();"><span>转赠</span></div>


    <script type="text/javascript" src="./assets/js/jquery.min.js"></script>
    <script type="text/javascript" src="./assets/js/amazeui.js"></script>
    <script type="text/javascript" src="./js/global.js?v=<?php echo time(); ?>"></script>
    <script type="text/javascript" src="./js/loading.js"></script>
    <script src="./js/jquery.ellipsis.js"></script>
    <script src="./js/jquery.ellipsis.unobtrusive.js"></script>
    <script src="./js/r_global_brain.js" type="text/javascript"></script>
    <script type="text/javascript" src="./js/r_jquery.mobile-1.2.0.min.js"></script>
    <script src="./js/sliding.js"></script>
</body>

<script type="text/javascript">

    if($("#price").val()!="")
        $("#price").css("background","#fff");
    else
        $("#price").css("background","rgb(240, 240, 240)");

    //jump to 转赠记录
    function viewZhuanzeng(){
            window.location.href="tixianjilu_xiangqing.html";
    }
    //jump to 输入密码
    function xiugai_pass(){
        window.location.href="change_paypassword.php?f_h=1&customer_id=<?php echo $customer_id_en;?>";
    }

    //jump to 修改转赠用户
    function repair_user(){
        window.location.href="change_currency_user.php?customer_id=<?php echo $customer_id_en;?>";
    }

    $(".sharebg-active").click(function() {
        $(this).hide();
        $(".am-share1").hide();
    });

// $(function(){

//     var is_pw = "<?php echo $is_pw;?>";
//     if( is_pw == 0 ){
//         function callbackfunc(){
//             window.location.href="modify_password.php?customer_id=<?php echo $customer_id_en;?>";
//         }
//         showConfirmMsg("提示","您尚未设置支付密码，是否立即设置？","确定","取消",callbackfunc);
//         return false;
//     }

// })

    //Jump to 转赠规则
    function viewRule(){
        var title = "转赠规则";
        var content = "<?php echo str_replace($qian, '', $rule); ?>";
        showDialogMsg(title,content);
    }

    function All_to_send(All){
        var all_money = All;
        if(All<=0){
            alertAutoClose("当前可转赠余额为0");
            // showAlertMsg("提示","当前可转赠余额为0","知道了");
            return false;
        }
        $("#price").val(all_money);
    }
        var id              = "<?php echo $id?>";
        var from_user_id    = "<?php echo $user_id;?>";
        var to_user_id      = "<?php echo $to_user_id?>";
        var send_money      = $("#price").val();
        var max_money       = <?php echo $max_money;?>;
        var is_pwd_null     = "<?php echo $is_pwd_null;?>";
        var describe        = $("#describe").val();
        var customer_id     = "<?php echo $customer_id_en;?>";
        var toUser = "<?php echo $id?>";

    function commit(){
        if( toUser < 0 ){
            function callbackfunc(){
                window.location.href="change_currency_user.php?customer_id=<?php echo $customer_id_en;?>";
            }
            showConfirmMsg("提示","输入的用户不存在，请重新输入","确定","取消",callbackfunc);
           return false;
        }
        // if( describe == '' ){
        //     describe = "恭喜发财  大吉大利";
        // }
        if($("input[type='checkbox']").is(':checked')==false){
            alertAutoClose("请阅读转赠规则并勾选");
            // showAlertMsg("提示","请阅读转赠规则并勾选","确定");
            return false;
        }
        function set_paypassword(){
            window.location.href = 'set_paypassword.php?customer_id=<?php echo $customer_id_en;?>';
        }
        var check_pay_password = <?php echo $check_pay_password?>;//是否设置了支付密码
        if( check_pay_password == -1 ){
            showAlertMsg ("提示：","您还没有设置支付密码，请先去设置。","知道了",set_paypassword);
            return;
        }
        // if(is_pwd_null==1){
        //     function callbackfunc(){
        //         window.location.href="modify_password.php?customer_id=<?php echo $customer_id_en;?>";
        //     }
        //     showConfirmMsg("提示","您尚未设置支付密码，请设置支付密码","确定","取消",callbackfunc);
        //      return false;
        // }
        if( id == -1 ){
            function callbackfunc(){
                window.location.href="change_currency_user.php?customer_id=<?php echo $customer_id_en;?>";
            }
            showConfirmMsg("提示","当前转赠账号有误，请重新输入！","确定","取消",callbackfunc);
            return false;
        }
        if( send_money > max_money ){
            alertAutoClose("转赠金额大于可转赠金额，请重新输入！");
            // showAlertMsg("提示","转赠金额大于可转赠金额，请重新输入！","确定");
            return false;
        }
        if( from_user_id == to_user_id ){
            alertAutoClose("不能给自己转赠！");
            // showAlertMsg("提示","不能给自己转赠！","确定");
            return false;

        }
        if( to_user_id == '' || to_user_id < 0 ){
            alertAutoClose("请输入您想转赠到的用户！");
            return false;

        }
        $(".sharebg").show();

        var type = 'Check_pw';
        $.ajax({
            url         :   'save_currency_send.php',
            dataType    :   'json',
            type        :   "post",
            data        :{
                            'customer_id':customer_id,
                            'type':type
                        },
            success:function(data){
                document.getElementById("ipt").focus();
                if(data.msg==400){
                    function set_paypassword(){
                        window.location.href = 'set_paypassword.php?customer_id=<?php echo $customer_id_en;?>';
                    }
                    showConfirmMsg("提示","您尚未设置支付密码，是否立即前往设置？","确定","取消",set_paypassword);
                }else if(data.msg==40004){
                    $("#pass_w").show();
                    document.getElementById("ipt").focus();
                    $(".am-share").addClass("am-modal-active");
                    // $(".sharebg").addClass("sharebg-active");
                    $(".sharebg-active").click(function(){
                        $(".am-share").removeClass("am-modal-active");
                        $(".sharebg").hide();
                        $("#pass_w").hide();
                        return false;
                    })

                }
            }
        });
        document.getElementById("ipt").focus();
    }

    $(".sharebg,.guanbicha").click(function() {
        $('#ipt').val("");
        $('li').text("");
        $("#pass_w").hide();
        $(".sharebg").hide();
        //$("box").hide();
    });

    function viewLog(){
        window.location.href="my_currency_turn.php?customer_id=<?php echo $customer_id_en;?>";
    }
    var error_time = 0;//输入密码错误次数
    $('#ipt').on('keyup', function (e) {
        var pw_lenght = $(this).val().length;
        if (pw_lenght == 6) {
            $(".sharebg").hide();
            $("#pass_w").hide();

            var send_money = $("#price").val();
            var pw = $('#ipt').val();
            var type = 'send_currency';
            var describe = $("#describe").val();
            if( describe == '' ){
                describe = "恭喜发财  大吉大利";
            }
//            if (pw_lenght != 6) {
//                showAlertMsg("提示", "请输入六位长度的密码", "确定");
//                return false;
//            }

            $.ajax({
                url: 'save_currency_send.php',
                dataType: 'json',
                async: false,
                type: "post",
                data: {
                    'from_user_id': from_user_id,
                    'to_user_id': to_user_id,
                    'send_currency': send_money,
                    'password': pw,
                    'describe': describe,
                    'type': type
                },
                success: function (data) {

                    if (data.msg == 400) {
                        function set_paypassword() {
                            window.location.href = 'set_paypassword.php?customer_id=<?php echo $customer_id_en;?>';
                        }

                        showConfirmMsg("提示", "您尚未设置支付密码，是否立即前往设置？", "确定", "取消", set_paypassword);
                    }

                    if (data.msg == 401) {
                        var ins_id = data.ins_id;
                        // window.location.href='my_money_out.php?from=currency&id='+ins_id;
                        function callbackfunc() {
                            $('#ipt').val("");
                            $('li').text("");
                            window.location.href = "my_money_out.php?customer_id=<?php echo $customer_id_en;?>&from=currency&id=" + ins_id;
                        }

                        showConfirmMsg("提示", "转赠成功！对方将收到您的转赠", "确定", "取消", callbackfunc);
                    } else {
                        error_time++;
                        $('#ipt').val("");
                        $('li').text("");
                        var msg = data.remark;
                        if (error_time >= 3) {
                            showConfirmMsg03('提示', '支付密码输入错误次数过多，你可以点击忘记密码，进行找回密码', '忘记密码', '重新输入', forget_pay_password , show_pass_w);
                            return;
                        }
                        if(data.msg == 40001){
                            showConfirmMsg03('提示', '密码输入错误', '忘记密码', '重新输入', forget_pay_password , show_pass_w);
                        }else{
                            showAlertMsg("提示",msg,"确定");
                        }
                        $('#ipt').val("");

                        return false;
                    }
                }
            })
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
    $('#ipt').on('input', function (e){
        var numLen = 6;
        var pw = $(this).val();
        var list = $('li');
        for(var i=0; i<numLen; i++){
            if(pw[i]){
                $(list[i]).text('·');
            }else{
                $(list[i]).text('');
            }
        }
    });
//    $('#ipt').on('keyup', function (e){
//        var num_len = $('input').val().length;
//        if(num_len == 6){
//            $(".commtBtn").show();
//        }else{
//            $(".commtBtn").hide();
//        }
//    });



</script>
<!--引入侧边栏 start-->
<?php  include_once('float.php');?>
<!--引入侧边栏 end-->
</html>