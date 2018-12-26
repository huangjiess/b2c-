<?php
/**
 * 各行业共用的支付页面
 * User: zhaojing
 * Date: 17/2/22
 * Time: 下午7:23
 */
header("Content-type: text/html; charset=utf-8");
session_cache_limiter( "private, must-revalidate" );
include_once('../config.php');
include_once('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
if(empty($customer_id)){
    $customer_id = passport_decrypt($_REQUEST['customer_id']);
}

include_once('../public_method/order_operation.php');
include_once(LocalBaseURL."common/common_ext.php");   //公共操作
include_once('../common/utility_shop.php');
$utlity = new shopMessage_Utlity();

require_once('../function_model/collageActivities.php');
$collageActivities = new collageActivities($customer_id);

$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('order_Form Could not select database');

require('../common/common_from.php');
require('select_skin.php');

$http_host =  $_SERVER['HTTP_HOST'];
    $json_post_data = i2post('post_data',"");//支付数据(以json格式post传入)
$post_data = json_decode($json_post_data,true);//转换成数组
//echo $json_post_data;
// echo '<pre>';
// var_dump($post_data);
//     echo '</pre>';
$is_check_password = $_SESSION['is_check_password_'.$user_id];
unset($_SESSION['is_check_password_'.$user_id]);

$industry_type = $post_data['industry_type'];
if(!empty($post_data['is_merge'])){
    $is_merge = $post_data['is_merge'];//判断是否为合并支付
}else{
    $is_merge = 0;
}
if(!empty($post_data['city_type'])){
    $city_type = $post_data['city_type'];//城市商圈行业类型
}else{
    $city_type = -1;
}
// echo $city_type."===";
// echo "===".$city_type;

$batchcode_str="";//订单集合: 格式:batchcode1_batchcode2
foreach ($post_data['batchcode_arr'] as $k=>$v){
    $batchcode_str.=$v['batchcode']."_";
}
$batchcode_str = rtrim($batchcode_str, "_");
$price = round($post_data['price'],2);//支付金额
// print_r($price);die();
$user_id = $post_data['user_id'];
//$pay_batchcode = $post_data['pay_batchcode'];//支付订单号

$paybatchcode_arr=explode("_",$batchcode_str);

/*
$order_ope = new order_operation();
$paybatchcode = $order_ope ->get_order_paybatchcode($industry_type,$user_id);

//$paybatchcode = set_paybatchdoe($paybatchcode_arr, $industry_type);
if(empty($paybatchcode)){ //大礼包订单
    $batchcode_str=implode(",", $batchcode_arr);
    $paybatchcode = $batchcode_str;
}
*/
$query = "SELECT
				ccot.group_id,ccot.activitie_id,ccot.rcount,ccot.is_head,ccopmt.pid
				FROM collage_crew_order_t AS ccot
				LEFT JOIN collage_crew_order_pro_mes_t AS ccopmt ON ccot.batchcode=ccopmt.batchcode
				WHERE ccot.batchcode='".$post_data['batchcode_arr'][0]['batchcode']."' AND ccot.isvalid=true AND ccot.customer_id=".$customer_id;
$result = _mysql_query($query) or die('Query failed:'.mysql_error());
$is_collage_order = 0;	//拼团订单标识，1是，0否
$is_bbcollage = 0;	//是否抱抱团，1是，0否；抱抱团屏蔽零钱支付与会员卡支付
$collage_info = [];
while( $row = mysql_fetch_assoc($result) ){
	$is_collage_order = 1;
	$collage_info = $row;
	if( $collage_info['is_head'] == 1 ){	//如果是团长，则不需要团id
		$collage_info['group_id'] = -1;
	}

    $condition = array(
        'customer_id' => $customer_id,
        'isvalid' => true,
        'id' => $collage_info['activitie_id']
    );
    $field = " type ";
    $activity_info = $collageActivities -> getActivitiesMes($condition,$field)['data'][0];
    //判断活动类型是否抱抱团
    if( $activity_info['type'] == 5 ){
		$is_bbcollage = 1;
	}
}

//判断从什么端口进入
$page_port="";
switch ($from_type) {
    case 1:
        $page_port = 'wx';//微信端
        break;
    case 2:
        $page_port = 'app';
        break;
    case 0:
        $page_port = 'h5';
        break;
}
//echo "fromtype : ".$from_type." user_agent ".$_SERVER["HTTP_USER_AGENT"];
/* 微信端下单，fromuser为空则不能支付 start */
if( $page_port == 'wx' && $fromuser == '' ){
    echo "	<script>
				alert('未知错误！没有获取到个人信息！');
				location.href='order_cart.php?customer_id=".$customer_id_en."';
			</script>";
    return ;
}
/* 若订单缺少参数，不允许支付 start */
if($batchcode_str =="" || $industry_type==""){
    echo "	<script>
				alert('缺少必要参数，订单不允许支付！');
				location.href='order_cart.php?customer_id=".$customer_id_en."';
			</script>";
    return ;
}

$ordering_receive_mode="";
$query_ordering = "SELECT receive_mode FROM ".WSY_DH.".orderingretail_setting WHERE isvalid=true AND customer_id=".$customer_id;
$result = _mysql_query($query_ordering) or die('Query ordering setting failed:'.mysql_error());
while( $row = mysql_fetch_assoc($result) ){
	$ordering_receive_mode=$row['receive_mode'];
	break;
}

//货款管理开关
$ordering_isopen_account="";
$query_isopen_account = "SELECT isopen_account FROM ".WSY_DH.".orderingretail_account_setting WHERE customer_id=".$customer_id;
$result_isopen_account = _mysql_query($query_isopen_account) or die('Query ordering setting failed:'.mysql_error());
while( $row = mysql_fetch_assoc($result_isopen_account) ){
    $ordering_isopen_account=$row['isopen_account'];
    break;
}

//echo $ordering_receive_mode;
?>

<!DOCTYPE html>
<html>
<head>
    <title>选择支付方式</title>
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
    <script type="text/javascript" src="./assets/js/jquery.min.js"></script>
    <script type="text/javascript" src="./assets/js/amazeui.js"></script>
    <script type="text/javascript" src="./js/global.js"></script>
    <script src="./js/jquery.ellipsis.js"></script>
    <script src="./js/jquery.ellipsis.unobtrusive.js"></script>
    <script type="text/javascript" src="./js/jquery.zclip.min.js"></script>
    <script type="text/javascript" src="./js/loading.js"></script>
    <script type="text/javascript" src="../common/js/jquery.md5.js"></script>
    <link href="./css/mobiscroll.custom-2.6.2.min.css" rel="stylesheet" type="text/css">
    <script src="./js/mobiscroll.custom-2.6.2.min.js" type="text/javascript"></script>
    <link type="text/css" rel="stylesheet" href="./assets/css/amazeui.min.css" />
    <link type="text/css" rel="stylesheet" href="./css/order_css/global.css" />
    <link type="text/css" rel="stylesheet" href="./css/css_<?php echo $skin ?>.css" />
    <link type="text/css" rel="stylesheet" href="./css/password.css" />
</head>
<style>
    .pay_desc{
        position: fixed;
        left: 0;
        right: 0;
        top: 30%;
        text-align: center;
        width: 280px;
        border-radius: 5px;
        margin: auto;
        background-color: #fff;
        color: #000;
        z-index: 9999;
    }
    .popup-memu {width:100%;background-color:white;border-top:1px solid #DEDBD5;border-bottom:1px solid #eee;}
    .popup-menu-title {font-size:16px;text-align:center;padding:8px 0 0 0;height: 40px;}
    .sub {font-size:1.5rem;color:#707070;}
    .line {background-color:#eee;margin-left:10px;height:1px;margin-right:10px;}
    .new_popup-menu-row {width:100%;text-align:left;background-color:#fff;padding:3px 15px;height:48px;overflow: hidden;}
    .new_popup-menu-row img {display:inline-block;width:30px;vertical-align:middle;height: 30px;}
    .newdiv {font-size:15px;margin-left:15px;display:inline-block;vertical-align:middle;color:#1c1f20;width:75%;}
    .newdiv p {margin:0;font-size: 1.4rem;vertical-align: middle;white-space: nowrap;overflow: hidden;}
    .newdiv .newzhifup{color:#888;font-size: 1.25rem;}
    .alert_content p{margin:0;}
    .btnrightzfdiv{display: inline-block;vertical-align: middle;float: right;height: 46px;line-height: 40px;}
    .new_popup-menu-row .btnrightzf{width: 10px;height: 14px;}
    .lzfimg{line-height: 40px;display: inline-block;}
    .popup-menu-title p{overflow: hidden;}
    .suppspanl{float: left;
    padding-left: 15px;
    font-size: 1.3rem;
    color: #707070;line-height: 30px;}
    .suppspanr{float: right;
    padding-right: 15px;
    font-size: 1.5rem;
    color: red;line-height: 30px;}
    .suppspanr .suppspansma{font-size: 1.2rem;}
    .guanbicha{position: absolute;top: 12px;left: 14%;}
           .guanbicha img{width: 13px;height: 13px;}
            .box label {height: 43px;}
    .pay_desc_btn{margin: 20px 12px;}
</style>

<body>
<div id="gyuji" style="position:fixed;top:0;z-index:1000;left:0;background:#000000;opacity:0.5;width:100%;height:100%;display:none;"></div>
<div class="am-share" id="pass_w" style="width:100%;position: fixed;top:30%;z-index: 1111;height: 160px;overflow: hidden;display:none;">
    <div class="box">
        <span class="guanbicha"><img src="./images/Remove.png"></span>
        <h1>输入支付密码</h1>
        <label for="ipt">
            <ul id="p_pwd" pay_type="card">
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
            </ul>
        </label>
        <input type="tel" id="ipt" maxlength="6">
        <!--     <div style="width:100%;text-align: right;padding:10px;"> <a onclick='xiugai_pass();'>密码管理</a></div>
            <a class="commtBtn" onclick="commitBtn();" style="display:none;">确认</a> -->
    </div>
</div>
<div class="am-dimmer-new am-active" data-am-dimmer="" style="display: none;"></div>

<!--代付信息-->
<div class="pay_desc" style="display:none;">
    <span style="font-size:16px;display:block;margin:10px 0 8px 0;">对你的好友说：</span>
    <textarea class="payother_desc" rows="6" cols="32" placeholder="蛋蛋的忧伤，钱不够了，你能不能帮我先垫付下" style="width:90%;margin:auto;"></textarea>
    <div class="pay_desc_btn">确定</div>
</div>
<div class="shadow" style="display:none;"></div>
<!--代付信息-->

<div class="list-one popup-menu-title" id="needpay">
    <!-- <span class="sub">选择支付方式</span> -->
    <p><span class="suppspanl">订单需付金额</span><span class="suppspanr"><?php echo $price; ?><small class="suppspansma"><?php echo OOF_T ?></small></span></p>
</div>
<?php ?>
<div class=" popup-memu" id="new_zhifuPannel" style="display: block;"></div>
</body>


<script type="text/javascript">
    var protocol_http_host = "<?php echo $protocol_http_host; ?>";
    var order_batchcode="<?php echo $batchcode_str; ?>";
    var customer_id_en="<?php echo $customer_id_en; ?>";
    var pay_user_id="<?php echo $user_id; ?>";
    var price="<?php echo $price; ?>";
    var check_pay_password = -1;//检查是否有支付密码(-1:未设置，1:已设置)
    var industry_type = "<?php echo $industry_type; ?>";
    console.log(industry_type);
    var city_type = "<?php echo $city_type; ?>";

    var pay_type = "";
    //var pay_order_batchcode="<?php echo $pay_batchcode; ?>";;
    var is_collage_order 		= '<?php echo $is_collage_order;?>';	//是否拼团订单
    var collage_pid    			= '<?php echo $collage_info['pid'];?>';	//拼团产品id
    var collage_rcount    		= '<?php echo $collage_info['rcount'];?>';	//拼团产品数量
    var collage_group_id   		= '<?php echo $collage_info['group_id'];?>';	//拼团团id
    var collage_activitie_id 	= '<?php echo $collage_info['activitie_id'];?>';	//拼团活动id
    var is_bbcollage 		    = '<?php echo $is_bbcollage;?>';	//是否抱抱团
    var http_host="<?php echo $_SERVER['HTTP_HOST']; ?>";
    var page_port = "<?php echo $page_port; ?>";
    var is_merge = "<?php echo $is_merge; ?>"; //是否是合并支付
    var is_check_password = "<?php echo $is_check_password ?>"; //是否已经输入过支付密码
    var paybatchcode = "<?php echo $paybatchcode?>";
    var cash_o2o_type = "<?php echo $post_data['order_type']; ?>";//收银O2O类型 1：优惠买单  2:代金券  3:套餐
    //订货系统参数
    var proxy_id = "<?php echo $post_data['proxy_id']; ?>";       //代理商id
    var supplier_id = "<?php echo $post_data['supplier_id']; ?>"; //上级供货商id
    var account = "<?php echo $post_data['account']; ?>"; //代理商货款余额
    var is_virtual = "<?php echo $post_data['is_virtual']; ?>"; //是否开启虚拟发货
    var opt = "<?php echo $post_data['opt']; ?>";  //订货系统操作
	var ordering_receive_mode="<?php echo $ordering_receive_mode; ?>"; //订货系统收款模式：proxy订货商收款，platform平台收款
    var ordering_isopen_account="<?php echo $ordering_isopen_account; ?>"; //订货系统货款管理开关
    //优惠抵扣订单
  // print_r($price);die();
    if(price == 0){
        $utlity->send_sns_msg($customer_id,$batchcode_str,1,$supply_id=-1);
        history_replaceState(industry_type);
        $('.list-one').css('display','none');//隐藏选择支付方式
        var content="";
        content+='<div class="line"></div>';
        content+='<div><p>正在支付....</p></div>';
        $("#new_zhifuPannel").html(content);
        $.ajax({
            url: "/weixinpl/mshop/internal_payment.php",
            dataType: 'json',
            type: 'post',
            data:{'customer_id_en':customer_id_en,'industry_type':industry_type,"order_batchcode":order_batchcode,"pay_price":price,"pay_type":"deductible","user_id":pay_user_id,"is_merge":is_merge,"opt":opt},
            success:function(result){
                if(result.status==1){
                    if (industry_type == "shop") {
                        if (is_collage_order != undefined && is_collage_order == 1) {
                            var url = protocol_http_host+"/weixinpl/mshop/collage_success.php?customer_id=" + customer_id_en + "&pay_batchcode=" + result.batchcode;       //拼团订单
                        } else {

                            var url = protocol_http_host+"/weixinpl/mshop/orderlist_detail.php?customer_id=" + customer_id_en + "&pay_batchcode=" + result.batchcode;       //跳转到订单详情页
                        }
                    }else if(industry_type == "cashier_o2o"){
                        var url = protocol_http_host+"/weixinpl/back_nowpaySystem/cashplatform/my_order.php?s_top="+cash_o2o_type+"&s_nav=1&customer_id=" + customer_id_en;
                    }else if(industry_type=="cityarea"){
                        switch(city_type){
                            case "1":  //外卖
                            case "2":  //订餐
                                var url = protocol_http_host+"/weixinpl/mshop/cityarea/orderlist_caterer_package.php?customer_id="+customer_id_en+"&currtype=1&cityarea_type="+city_type;
                                break;
                            case "20":  //自提
                                var url = protocol_http_host+"/weixinpl/city_area/shop/order_detail.php?batchcode="+result.batchcode+"&customer_id="+customer_id_en;
                                break;
                            case "22"://配送
                                var url = protocol_http_host+"/weixinpl/city_area/shop/order_detail.php?batchcode="+result.batchcode+"&customer_id="+customer_id_en;
                                break;
                            case "23"://社区
                                var url = protocol_http_host+"/weixinpl/city_area/shop/order_detail.php?batchcode="+result.batchcode+"&customer_id="+customer_id_en;
                                break;
                            case "30"://套餐
                                var url = protocol_http_host+"/weixinpl/mshop/cityarea/orderlist_ktv_package.php?customer_id="+customer_id_en+"&currtype=1";
                                break;
                            case "60"://全日房
                            case "62"://钟点房
                                var url = protocol_http_host+"/weixinpl/mshop/cityarea/orderlist_hotel_package.php?customer_id="+customer_id_en+"&currtype=1";
                                break;
                            case "3"://到店付
                            case "31":
                            case "61":
                            case "21"://到店付
                                var url = protocol_http_host+"/weixinpl/mshop/cityarea/orderlist_pay_detail.php?batchcode="+result.batchcode+"&customer_id="+customer_id_en;
                                break;
                        }
                    }else if(industry_type=="gift_packs"){ //2017/10/16 824需求：礼包购买支持购物币抵扣
                        var url = protocol_http_host+"/weixinpl/mshop/order_packages_list.php?customer_id=" + customer_id_en+'&batchcode='+result.batchcode;
                    }
                        //最后跳转到支付
                        document.location = url;

                }else{
                    alert_warning(result.msg);
                    return;
                }


            }
        });
    }else{
        $.ajax({
            url: "/weixinpl/mshop/ajax_show_payway.php",
            dataType: 'json',
            type: 'post',
            data:{'customer_id':customer_id_en,'industry_type':industry_type,"page_port":page_port,"pay_user_id":pay_user_id},
            success:function(result){
                var content="";
                if(result.errcode==0) {
                    if (result.datalist.length == 0 && industry_type!="orderingretail") {  //加条件判断,行业类型不为订货系统时，无支付方式数据才做下面的提示 luojie
                        $("#needpay").hide();
                        $("#new_zhifuPannel").css("border-bottom","none");
                        content += '<img class="pic" style="width: 80%;display: block;margin: 60px auto 0 auto;" src="./images/pic.png"><p style="text-align: center;font-size: 15px;color: #888" class="text">商家未开启任何支付方式</p>';
                    } else {
                        //在所有支付方式前先加上货款支付【需显示货款余额】、虚拟库存抵扣；虚拟库存抵扣只有对平台的代理才有 luojie
                        if(industry_type =="orderingretail"){
                            //订货系统货款支付
                            if( opt !='recharge'){
                                //判断虚拟库存支付库存是否足够
                                $.ajax({
                                    url: "/weixinpl/mshop/ajax_show_payway.php",
                                    async: false,
                                    dataType: 'json',
                                    type: 'post',
                                    data:{'customer_id':customer_id_en,'industry_type':industry_type,"proxy_id":proxy_id,"order_batchcode":order_batchcode,"op":"sel_store"},
                                    success:function(result){
                                        var is_allow_virtual = result.is_allow_virtual;
                                        content += '<div class="line"></div>';
                                        if( is_virtual==0 && is_allow_virtual==1){
                                            content += '<div class = "new_popup-menu-row" data-value="virtual" onclick="new_popup(this);">';
                                            content += '<div class="lzfimg"><img src="/addons/view/ordering_retail/common/images_orange/zhifu.png"></div>';
                                        }else{
                                            content += '<div class = "new_popup-menu-row" data-value="virtual">';
                                            content += '<div class="lzfimg"><img src="/addons/view/ordering_retail/common/images_orange/zhifu_hui.png"></div>';
                                        }
                                        content += '<div class="newdiv"><p class="newfont">虚拟库存支付</p>';
                                        content += '<p class="newzhifup">虚拟库存支付</p></div>';
                                        content += '<div class="btnrightzfdiv"><img class="btnrightzf" src="/weixinpl/mshop/images/btn_right.png"></div>';
                                        content += '</div>';
                                    }
                                });
                            }
                            if( opt != 'recharge' && ordering_receive_mode != 'platform' && ordering_isopen_account == 1){
	                            content += '<div class="line"></div>';
	                            content += '<div class = "new_popup-menu-row" data-value="account" onclick="new_popup(this);">';
	                            content += '<div class="lzfimg"><img src="/addons/view/ordering_retail/common/images_orange/yue.png"></div>';
	                            content += '<div class="newdiv"><p class="newfont">货款支付 (余额：<?php if(OOF_P != 2) echo OOF_S ?>'+account+'<?php if(OOF_P == 2) echo OOF_S ?>) </p>';
	                            content += '<p class="newzhifup">货款支付</p></div>';
	                            content += '<div class="btnrightzfdiv"><img class="btnrightzf" src="/weixinpl/mshop/images/btn_right.png"></div>';
	                            content += '</div>';
                            }

                            for (i = 0; i < result.datalist.length; i++) {
                                $pay_type = result.datalist[i].pay_type;
                                $balance = 0;

                                if ( supplier_id != -1 && $pay_type!="moneybag" && $pay_type!="nopay" &&ordering_receive_mode == 'proxy' ) {	//供货商是平台或收货方是平台的才有第三方支付方式
                                    continue;
                                }
                                $show_balance = false;
                                if($pay_type == "moneybag"){
                                    $show_balance = true;
                                    $balance = getUserBalance($pay_type);
                                }
                                content += '<div class="line"></div>';
                                content += '<div class = "new_popup-menu-row" data-value="' + $pay_type + '" onclick="new_popup(this);">';
                                content += '<div class="lzfimg"><img src="//' + http_host + result.datalist[i].icon + '"></div>';
                                content += '<div class="newdiv"><p class="newfont">' + result.datalist[i].pay_name + ($show_balance == true  ? " (余额：<?php if(OOF_P != 2) echo OOF_S ?>"+$balance+"<?php if(OOF_P == 2) echo OOF_S ?>)" : "") + '</p>';
                                content += '<p class="newzhifup">' + result.datalist[i].description + '</p></div>';
                                content += '<div class="btnrightzfdiv"><img class="btnrightzf" src="/weixinpl/mshop/images/btn_right.png"></div>';
                                content += '</div>';
                            }
                        }else{
                            for (i = 0; i < result.datalist.length; i++) {
                                $pay_type = result.datalist[i].pay_type;
                                $balance = 0;

                                if ( is_collage_order == 1 && $pay_type == 'nopay' ) {	//拼团订单不需要提单不支付
                                    continue;
                                }

                                if ( is_bbcollage == 1 && ( $pay_type == 'card' /* || $pay_type == "moneybag" */) ) {	//抱抱团屏蔽零钱与会员卡支付
                                    continue;
                                }

                                $show_balance = false;
                                if($pay_type == 'card' || $pay_type == "moneybag"){
                                    $show_balance = true;
                                    $balance = getUserBalance($pay_type);
                                    $balance = save_twopoint($balance);
                                }
                                content += '<div class="line"></div>';
                                if($pay_type=="IPSpay" && price<2){
                                    content += '<div class = "new_popup-menu-row" data-value="' + $pay_type + '">';
                                }else{
                                    content += '<div class = "new_popup-menu-row" data-value="' + $pay_type + '" onclick="new_popup(this);">';
                                }
                                content += '<div class="lzfimg"><img src="//' + http_host + result.datalist[i].icon + '"></div>';
                                if($pay_type=="IPSpay" && price<2){
                                    content += '<div class="newdiv"><p class="newfont"  style="color: #bbb;">' + result.datalist[i].pay_name + ($show_balance == true  ? " (余额：<?php if(OOF_P != 2) echo OOF_S ?>"+$balance+"<?php if(OOF_P == 2) echo OOF_S ?>)" : "") +
                                        '</p>';
                                }else{
                                    content += '<div class="newdiv"><p class="newfont">' + result.datalist[i].pay_name + ($show_balance == true  ? " (余额：<?php if(OOF_P != 2) echo OOF_S ?>"+$balance+"<?php if(OOF_P == 2) echo OOF_S ?>)" : "") +
                                        '</p>';
                                }
                                content += '<p class="newzhifup">' + result.datalist[i].description + '</p></div>';
                                content += '<div class="btnrightzfdiv"><img class="btnrightzf" src="/weixinpl/mshop/images/btn_right.png"></div>';
                                content += '</div>';

                            }
                        }
                        //content += '</div>';
                    }
                }
                $("#new_zhifuPannel").html(content);
                $(".line:first").css("background-color","#fff");
            }
        })
    }


    function getUserBalance($pay_type){
        $balance = 0;
        $.ajax({
            url: "/weixinpl/mshop/ajax_show_payway.php",
            dataType: 'json',
            type: 'post',
            async: false,
            data: {
                'customer_id': customer_id_en,
                'industry_type': industry_type,
                'pay_type': $pay_type,
                "pay_user_id": pay_user_id,
                "op": "get_balance",
                "city_type":city_type
            },
            success: function (result) {
                //console.log("pay_type "+ $pay_type + "result : "+result)
               $balance = result.balance;
                $balance =  Math.floor($balance * 100) / 100;
            }
        });
        return $balance;
    }




    function new_popup(obj){
        var pay_type = $(obj).data("value");
        history_replaceState(industry_type);
        toPayPage(pay_type);
    }

    function goto_nopay(){
        $("#gyuji").hide();
        $(".confirm").fadeOut();
        //var pay_type = 'nopay';
        //toInternalPay(pay_type);
    }

    /*内部支付*/
    function toInternalPay(pay_type){
        $.ajax({
            url: "/weixinpl/mshop/internal_payment.php",
            dataType: 'json',
            type: 'post',
            data:{'customer_id_en':customer_id_en,'industry_type':industry_type,"order_batchcode":order_batchcode,"pay_price":price,"pay_type":pay_type,"user_id":pay_user_id,"is_merge":is_merge,"opt":opt,supplier_id:supplier_id,ordering_receive_mode:ordering_receive_mode},
            success:function(result){
                console.log(result.status+"==="+result.msg);
                //alert("result.status: " + result.status+" result.status: " + result.msg);
                //商城支付完成跳转
                var msg="";
                if(result.status==1){
                    msg=result.msg;
                }else{
                    //订货系统返回信息
                    if(result.status == 12001){
                        msg = result.msg;
                    }else if(result.status == 12002){
                        msg = result.msg;
                    }
                    if( result.status == 10012){
                        msg="会员卡余额不足,请尽快充值";
                    }else {
                        if (result.status == 10011) {
                            msg="钱包余额不足,请尽快充值";
                        } else {
                            msg=result.msg;
                        }
                    }
                }
                alert_warning(msg);
                setTimeout(function(){
                    if (industry_type == "shop") {
                        if (is_collage_order != undefined && is_collage_order == 1 && pay_type != 'nopay' && result.status == 1) {
                            var url = protocol_http_host+"/weixinpl/mshop/collage_success.php?customer_id=" + customer_id_en + "&pay_batchcode=" + result.batchcode;       //拼团订单
                        } else {
                            if (is_merge == 1 && result.status != 1) {
                                var url = protocol_http_host+"/weixinpl/mshop/orderlist.php?customer_id=" + customer_id_en;
                            } else {
                                var url = protocol_http_host+"/weixinpl/mshop/orderlist_detail.php?customer_id=" + customer_id_en + "&pay_batchcode=" + result.batchcode;       //跳转到订单详情页
                            }
                        }
                    } else if (industry_type == "gift_packs") {
                        var url = protocol_http_host+"/weixinpl/mshop/order_packages_list.php?customer_id=" + customer_id_en+'&batchcode='+result.batchcode;
                    }else if (industry_type == "f2c") {
                        var url = protocol_http_host+"/addons/index.php/f2c/index/personal_center?customer_id=" + customer_id_en;
                    }else if(industry_type == "cashier_o2o"){
                        var url = protocol_http_host+"/weixinpl/back_nowpaySystem/cashplatform/my_order.php?s_top="+cash_o2o_type+"&s_nav=1&customer_id=" + customer_id_en;
                    }else if(industry_type == "cityarea"){
                        switch(city_type){
                            case "1":  //外卖
                            case "2":  //订餐
                                var url = protocol_http_host+"/weixinpl/mshop/cityarea/orderlist_caterer_package.php?customer_id="+customer_id_en+"&currtype=1&cityarea_type="+city_type;
                                break;
                            case "20":  //自提
                                var url = protocol_http_host+"/weixinpl/city_area/shop/order_detail.php?batchcode="+result.batchcode+"&customer_id="+customer_id_en;
                                break;
                            case "22"://配送
                                var url = protocol_http_host+"/weixinpl/city_area/shop/order_detail.php?batchcode="+result.batchcode+"&customer_id="+customer_id_en;
                                break;
                            case "23"://社区
                                var url = protocol_http_host+"/weixinpl/city_area/shop/order_detail.php?batchcode="+result.batchcode+"&customer_id="+customer_id_en;
                                break;
                            case "30"://套餐
                                var url = protocol_http_host+"/weixinpl/mshop/cityarea/orderlist_ktv_package.php?customer_id="+customer_id_en+"&currtype=1";
                                break;
                            case "60"://全日房
                            case "62"://钟点房
                                var url = protocol_http_host+"/weixinpl/mshop/cityarea/orderlist_hotel_package.php?customer_id="+customer_id_en+"&currtype=1";
                                break;
                            case "3"://到店付
                            case "31":
                            case "61":
                            case "21"://到店付
                                var url = protocol_http_host+"/weixinpl/mshop/cityarea/orderlist_pay_detail.php?batchcode="+result.batchcode+"&customer_id="+customer_id_en;
                                break;
                        }
                    }else if(industry_type == "orderingretail"){
                    	if( opt == 'recharge' ){
                    		 var url =   protocol_http_host+"/addons/index.php/ordering_retail/Account/recharge_apply?type=proxy&customer_id="+customer_id_en;
                    	}else{
                            if(result.status == 10011 || result.status ==10012 || result.status == 12001 || result.status == 12002){
                                if(opt == 'stock'){
                                    var url =   protocol_http_host+"/addons/index.php/ordering_retail/Purchasing/purchase_order_list?customer_id="+customer_id_en;
                                }else{
                                    var url =   protocol_http_host+"/addons/index.php/ordering_retail/Purchasing/purchase_sale_order_list?customer_id="+customer_id_en;
                                }
                            }else{
                                var url =   protocol_http_host+"/addons/index.php/ordering_retail/Purchasing/purchase_proxy_paysuccess?customer_id="+customer_id_en+"&batchcode="+result.batchcode+"&supplier_id="+supplier_id;
                            }
                        }


                    }
                    //最后跳转到支付
                    document.location = url;
                },1500);
            }
        });
    }

    /*根据选择的支付方式进行对应的页面跳转*/
    function toPayPage(pay_type){
        /* 如果是拼团订单，支付前先检测活动有效性 start */
        if( is_collage_order == 1 ){
            var is_go_on = true;
            $.ajax({
                url: '/weixinpl/mshop/check_collage.php?customer_id='+customer_id_en,
                dataType: 'json',
                type: 'post',
                async: false,
                data: {
                    user_id : pay_user_id,
                    pid : collage_pid,
                    num : collage_rcount,
                    activitie_id : collage_activitie_id,
                    group_id : collage_group_id,
					comefrom : 2,
					batchcode : '<?php echo $post_data['batchcode_arr'][0]['batchcode'];?>'
                },
                success: function(res){
                    if( res.code > 0 ){
                        alert_warning(res.msg);
                        alert(res.msg);
                        is_go_on = false;
                    }
                }
            });
            if( !is_go_on ){
                //跳转到人气拼团页面
                window.location.href = protocol_http_host+'/weixinpl/mshop/collageActivities/product_list_view.php?customer_id='+customer_id_en+'&op=popularity';
                return;
            }
        }
        /* 如果是拼团订单，支付前先检测活动有效性 end */

		/*如果是兑换积分订单，支付前校验是否足够的积分兑换 start*/
		// if( is_integal == 1 ){

  //           $.ajax({
  //               url: '/mshop/admin/index.php?m=activity&a=check_order_user_integral&customer_id='+customer_id_en,
  //               dataType: 'json',
  //               type: 'post',
  //               async: false,
  //               data: {
		// 			user_id : pay_user_id,
		// 			batchcode : '<?php echo $post_data['batchcode_arr'][0]['pay_batchcode'];?>'
  //               },
  //               success: function(res){
  //                   if( res.code > 0 ){
  //                       alert_warning(res.msg);
  //                       alert(res.msg);
  //                       is_go_on = false;
  //                   }
  //               }
  //           });

  //       }

		/*如果是兑换积分订单，支付前校验是否足够的积分兑换 end*/

        if(pay_type == 'anotherpay'){
            var pay_code = get_paycode(pay_user_id,industry_type);
            if(pay_code == null){
                pay_code = order_batchcode;
            }
            anotherpay(pay_code,order_batchcode,industry_type); //下单页找人代付
        }else{
            //新支付方法，传的是batchcode集合
            if( pay_type != undefined && pay_type != '' ){
                pay_type = pay_type;
            }
            /*
             * 当pay_type =='moneybag' , 发送ajax[calc_moneybagpay_poundage.php]获取手续费。
             * */
            if( pay_type =='moneybag' || pay_type =='card' || pay_type=='account' ){
                $.ajax({
                    url: protocol_http_host+"/weixinpl/mshop/pay_password_controller.php?customer_id="+customer_id_en,
                    dataType: 'json',
                    type: 'post',
                    async:false,
                    data:{action:'check',user_id:pay_user_id},
                    success:function(result){
                        //var result = eval("("+result+")");
                        if( result.status == 1 && pay_type =='moneybag' ){
                            var sum_all_money = price;
                            $.get('/weixinpl/mshop/calc_moneybagpay_poundage.php',{sum_all_money:sum_all_money,industry_type:industry_type,customer_id:customer_id_en},function(res){
                                var res = eval('('+res+')');
                                if( res.isopen_poundage == 1 ){
                                    var all_pay = (parseFloat(res.pay_price) +parseFloat(res.poundage)).toFixed(2);
                                    $("#gyuji").show();
                                    var html = '<p style="text-align:center;font-wight:normal;">订单金额：<?php if(OOF_P != 2) echo OOF_S ?>&nbsp;'+res.pay_price+'&nbsp;<?php if(OOF_P == 2) echo OOF_S ?></p>';
                                    html += '<p style="text-align:center;">手续费：<?php if(OOF_P != 2) echo OOF_S ?>&nbsp;'+res.poundage+'&nbsp;<?php if(OOF_P == 2) echo OOF_S ?></p>';
                                    html += '<p style="text-align:center;color:#333333">合计：<font color=#FF8530><?php if(OOF_P != 2) echo OOF_S ?>&nbsp;'+all_pay+'&nbsp;<?php if(OOF_P == 2) echo OOF_S ?></font></p>';
                                    newShowConfirmMsg('零钱支付确认',html,'取消','确定',function(){
                                    if (is_check_password == 1){
                                        toInternalPay(pay_type);
                                        loading(100,1);
                                    }else{
                                        //loading(100,1); //加载中出现调用
                                        $("#gyuji").show();
                                        $("#pass_w").show();//支付层
                                        document.getElementById("ipt").focus();
                                        $('#p_pwd').attr('pay_type',pay_type);//传递支付方式
                                        $("#gyuji").show();//遮罩层出现调用
                                        $('#ipt').focus();
                                        $(".dlg_content1_row1").css("text-align","center");
                                        $("html,body").css({"height":"100%","overflow":"hidden"});
                                        // closeLoading(); //关闭加载中调用
                                    }
                                    },goto_nopay);
                                }else{
                                    if (is_check_password == 1){
                                        toInternalPay(pay_type);
                                        loading(100,1);
                                    }else{
                                    $("#gyuji").show();
                                    $("#pass_w").show();//支付层
                                    document.getElementById("ipt").focus();
                                    $('#p_pwd').attr('pay_type',pay_type);//传递支付方式
                                    $("#gyuji").show();//遮罩层出现调用
                                    }
                                }
                            });
                        }else if( result.status == 1 && (pay_type =='card'|| pay_type=='account') ){
                            if (is_check_password == 1){
                                toInternalPay(pay_type);
                                loading(100,1);
                            }else{
                            $("#gyuji").show();
                            $("#pass_w").show();//支付层
                            document.getElementById("ipt").focus();
                            $('#p_pwd').attr('pay_type',pay_type);//传递支付方式
                            $("#gyuji").show();//遮罩层出现调用
                            }

                        }else{
                            showAlertMsg ("提示：","您还没有设置支付密码，请先去设置。","知道了",set_paypassword);
                        }
                    }
                });
            }else if(pay_type == "deductible" || pay_type == "nopay" || pay_type == "account" || pay_type == "virtual"){
                toInternalPay(pay_type);
                loading(100,1);
            }else{
                var url="";
                if( pay_type =='weipay' ){//微信支付
                    //var pay_code = get_paycode(pay_user_id,industry_type);

                    if("h5"==page_port || "app"==page_port){
                        url=protocol_http_host+"/weixinpl/mshop/H5Pay/jsapi.php?order_id="+order_batchcode+'&customer_id='+customer_id_en+'&industry_type='+industry_type+'&is_merge='+is_merge+'&city_type='+city_type;
                    }else{
                        url=protocol_http_host+"/weixinpl/mshop/WeChatPay/weipay_new.php?order_id="+order_batchcode+'&customer_id='+customer_id_en+'&industry_type='+industry_type+'&is_merge='+is_merge+'&city_type='+city_type;
                    }
                }else if(pay_type =='alipay'){
                    url=protocol_http_host+"/weixinpl/mshop/alipay/alipayapi_new.php?order_id="+order_batchcode+'&customer_id='+customer_id_en+'&industry_type='+industry_type+'&is_merge='+is_merge+'&city_type='+city_type;


                }else if(pay_type =='yeepay'){
                    if( "wx" == page_port ){   //微信端才调用分享接口
                        //TODO : 易宝支付
                        new_share(debug,appId,timestamp,nonceStr,signature,share_url,title,desc,imgUrl,-1,new_share_url,1);
                    }
                    var pay_code = get_paycode(pay_user_id,industry_type);
                    if(pay_code == null){
                            pay_code = order_batchcode;
                    }
                    url=protocol_http_host+"/weixinpl/yeepay/ydsytYeepay/sendRequest.php?order_id="+order_batchcode+"&customer_id="+customer_id_en+'&industry_type='+industry_type+'&pay_batchcode='+pay_code+'&is_merge='+is_merge+'&city_type='+city_type;

                }else if(pay_type =='jdpay'){
                    var pay_code = get_paycode(pay_user_id,industry_type);
                    if(pay_code == null){
                        pay_code = order_batchcode;
                    }
                    url=protocol_http_host+"/weixinpl/jdpay_new/action/ClientOrder_new.php?order_id="+order_batchcode+"&customer_id="+customer_id_en+"&industry_type="+industry_type+'&pay_batchcode='+pay_code+'&is_merge='+is_merge+'&city_type='+city_type;
                }else if(pay_type =='paypal'){
                    var pay_code = get_paycode(pay_user_id,industry_type);
                    if(pay_code == null){
                        pay_code = order_batchcode;
                    }
                    url=protocol_http_host+"/weixinpl/common_shop/jiushop/paypal_new.php?order_id="+order_batchcode+"&customer_id="+customer_id_en+'&industry_type='+industry_type+'&pay_batchcode='+pay_code+'&is_merge='+is_merge+'&city_type='+city_type;
                }else if(pay_type == 'vlifepay'){
                    url=protocol_http_host+"/weixinpl/Vlifepay/vlife_login.php?order_id="+order_batchcode+"&customer_id="+customer_id_en+'&industry_type='+industry_type+'&is_merge='+is_merge+'&city_type='+city_type;
                }else if(pay_type == 'xingyebankpay'){
                    url=protocol_http_host+"/weixinpl/mshop/WeChatPay/xypay.php?order_id="+order_batchcode+"&customer_id="+customer_id_en+'&industry_type='+industry_type+'&is_merge='+is_merge+'&city_type='+city_type;
                }else if(pay_type == "IPSpay"){  //环迅快捷支付
                    url=protocol_http_host+"/weixinpl/IPSpay/hxpay.php?order_id="+order_batchcode+"&industry_type="+industry_type+"&from_type=wx&is_merge="+is_merge+'&city_type='+city_type;
                }else if(pay_type == "IPSweipay"){  //环迅微信支付
                    // url="https://thumbpay.e-years.com/psfp-webscan/onlinePay.do";
                    url=protocol_http_host+"/weixinpl/IPSpay/IPSweipay.php?order_id="+order_batchcode+"&industry_type="+industry_type+'&is_merge='+is_merge+'&city_type='+city_type;
                }else if(pay_type =='wftpay'){
                    if( "wx" == page_port ){
                        url=protocol_http_host+"/weixinpl/wftpay_wx/weixinpay.php?order_id="+order_batchcode+"&industry_type="+industry_type+'&is_merge='+is_merge+'&city_type='+city_type;
                    }else{
                        url=protocol_http_host+"/weixinpl/wftpay_alipay/alipay.php?order_id="+order_batchcode+"&industry_type="+industry_type+'&is_merge='+is_merge+'&city_type='+city_type;
                    }

                }else if(pay_type == 'healthpay'){
                    url=protocol_http_host+"/weixinpl/healthpay/login.php?order_id="+order_batchcode+"&customer_id="+customer_id_en+'&industry_type='+industry_type+'&is_merge='+is_merge+'&city_type='+city_type;
                }
                else{
                    var url = protocol_http_host+"/weixinpl/mshop/orderlist_detail.php?customer_id="+customer_id_en+"&pay_batchcode="+batchcode;       //跳转到订单详情页
                }
//                var pages = localStorage.page_href;
//                if(pages == null){
//                    pages = new Array();
//                }else{
//                    pages = JSON.parse(pages);
//                }
//                pages.push("订单号 ："+order_batchcode+"<br/> url : "+url);
//                localStorage.page_href =  JSON.stringify(pages);
                if (industry_type == "cashier_o2o"){
                    url += "&cash_o2o_type=" + cash_o2o_type;
                }
                if(industry_type == "orderingretail"){
                    url += "&opt=" +opt;
                }
                document.location = url;
            }
        }
    }


    function new_togglePan_down(){
        $(".am-dimmer-new").hide();
        $("#new_zhifuPannel").fadeOut();
    }

    /**
     * 同步获取支付密码
     * */
    function get_paycode(pay_user_id,industry_type){
        var pay_code = "";
        $.ajax({
            url: "/weixinpl/mshop/ajax_show_payway.php",
            dataType: 'json',
            type: 'post',
            async: false,
            data: {
                'customer_id': customer_id_en,
                'industry_type': industry_type,
                "pay_user_id": pay_user_id,
                "op": "get_paycode"
            },
            success: function (result) {
                if(result.status == 1){
                    pay_code = result.pay_batchcode;
                }
            }
        });
        return pay_code;
    }

    /* ------------- end 支付方式相关操作 -------------------*/


    /* ----- begin 支付密码相关操作 -------- */
    function set_paypassword(){
        window.location.href = protocol_http_host+'/weixinpl/mshop/set_paypassword.php?customer_id='+customer_id_en;
    }
    function forget_pay_password(){
        $("#gyuji").hide();//隐藏遮罩层
		window.location.href = protocol_http_host+'/weixinpl/mshop/forget_paypassword.php?customer_id='+customer_id_en;
    }

    function show_pass_w(){
        $("#gyuji").show();
        $("#pass_w").show();//支付层
        document.getElementById("ipt").focus();
        $("html,body").css({"height":"100%","overflow":"scroll"});

    }
        $("#gyuji,.guanbicha").click(function() {
            $('#ipt').val("");
            $('li').text("");
            $("#pass_w").hide();
            $("#gyuji").hide();
        });
    //输入支付密码
    var error_time = 0;//输入密码错误次数
    $('#ipt').on('keyup', function (e){

        var pay_price = price;
        var pay_type = $('#p_pwd').attr('pay_type');
        var num_len = $('#ipt').val().length;
        //alert(num_len);
        if( num_len == 6 ){
            var pay_password = $('#ipt').val();
            //MD5加密传输
            pay_password = $.md5(pay_password);

            $('#ipt').val("");
            $('#p_pwd').children().text("");
            $("#pass_w").hide();//隐藏支付层
            $("#gyuji").hide();
            $('#ipt').blur();
            $.post("/weixinpl/mshop/pay_password_controller.php?customer_id="+customer_id_en,{action:'correct',user_id:pay_user_id,paypassword:pay_password},function(res){
                var res = eval("("+res+")");
                if( res.status == 1 ){
                    //alert(pay_type);
                    toInternalPay(pay_type);
                    loading(100,1);
                }else{
                    error_time++;
                    if( error_time >= 3 ){
                        showConfirmMsg02('提示','支付密码输入错误次数过多，你可以点击忘记密码，进行找回密码','忘记密码','重新输入',forget_pay_password,show_pass_w);
                    }else{
                        showConfirmMsg02('提示','输入密码错误','忘记密码','重新输入',forget_pay_password,show_pass_w);
                    }
                    $("#gyuji").show();//遮罩层出现调用
                }
            });
        }
    });
    /* ----- end  支付密码相关操作 -------- */

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

    //商城找人代付方法
    function anotherpay(pay_batchcode,order_id,industry_type){
        var is_payother = "<?php echo $post_data['is_payother']?>";
        var is_payother_msg = "<?php echo $post_data['is_payother_msg']?>";
        //不支持找人代付
        if(is_payother == 0){
            alert_warning(is_payother_msg);
            return false;
        }else{
            //---找人代付 开始---//
            $('.pay_desc').show();
            $('#gyuji').show();
            $('.pay_desc_btn').click(function(){        //点击找人代付确认事件

                $('.is_payother').val(1);
                $('.pay_desc').hide();
                $('.shadow').hide();
                var payother_desc = $('.payother_desc').val();
                if(payother_desc==""){
                    payother_desc = "蛋蛋的忧伤，钱不够了，你能不能帮我先垫付下";
                }
                var url = protocol_http_host+"/weixinpl/mshop/payother_new.php?pay_batchcode="+pay_batchcode+"&customer_id="+customer_id_en+"&payother_desc="+payother_desc+"&order_id="+order_id+"&industry_type="+industry_type;
               // alert(url);
                document.location = url;
            });
            $('#gyuji').click(function(){
                $('.pay_desc').hide();
                $('#gyuji').hide();
            });
        }

    }

    //支付完成后跳转页面
    function history_replaceState(industry_type) {
        if (industry_type == "shop") {
			if( is_collage_order == 1 ) {
				history.replaceState({}, '', protocol_http_host+'/weixinpl/mshop/collageActivities/my_collages_record_list_view.php?customer_id' + customer_id_en);		//修改历史记录，支付后返回跳转我的拼团记录
			} else {
				history.replaceState({}, '', protocol_http_host+'/weixinpl/mshop/orderlist.php?customer_id' + customer_id_en + '&currtype=1');       //商城:修改历史记录，支付后返回个人中心
			}

        } else if (industry_type == "gift_packs") {
            history.replaceState({}, '', protocol_http_host+'/weixinpl/mshop/order_packages_list.php?customer_id' + customer_id_en);       //大礼包:修改历史记录，支付后返回大礼包列表
        }else if (industry_type == "f2c") {
            history.replaceState({}, '', protocol_http_host+'/addons/index.php/f2c/index/personal_center?customer_id=' + customer_id_en);       //f2c:修改历史记录，支付后返回个人中心
        }else if(industry_type == "cashier_o2o"){
            history.replaceState({}, '', protocol_http_host+"/weixinpl/back_nowpaySystem/cashplatform/my_order.php?s_top="+cash_o2o_type+"&s_nav=1&customer_id=" + customer_id_en);
        }else if(industry_type == "orderingretail"){
            if(opt=="stock"){
                history.replaceState({}, '', protocol_http_host+"/addons/index.php/ordering_retail/Purchasing/purchase_order_list?customer_id=" + customer_id_en);
            }else if(opt=="toSuperior"){
                history.replaceState({}, '', protocol_http_host+"/addons/index.php/ordering_retail/Purchasing/purchase_sale_order_list?customer_id=" + customer_id_en);
            }
        }
    }
    //截取多余小数位，只保留两个
    function save_twopoint(value){
        var temp=value.toString().split(".");
        //alert(a.toString().length);
        if(temp.length>1)
        {
            a=temp[1].toString().length;
            if(a>2)
            {
                temp[1]=temp[1].substr(0,2);
            }
            value =temp[0]+'.'+temp[1];
        }
        return value;
    }


</script>
<?php require('../common/share.php');?>
</html>