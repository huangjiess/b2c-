<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php'); //配置
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
_mysql_query("SET NAMES UTF8");
require('../proxy_info.php');
require('../common/jssdk.php');
$user_id = 194631;
$batchcode = -1;
$pid = -1;
$batchcode = $_GET['batchcode'];
$pid = $_GET['pid'];
$customer_id = 3243;

 
?>
<!DOCTYPE html>
<html>
<head>
    <title>退换货</title>
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
    <link type="text/css" rel="stylesheet" href="./css/css_orange.css" />  

	<script type="text/javascript" src="./assets/js/jquery.min.js"></script> 
</head>

<link rel="stylesheet" href="./css/order_css/style.css" type="text/css" media="all">
<link rel="stylesheet" href="./css/order_css/tuihuo.css" type="text/css" media="all">

<!-- 基本dialog-->
<link type="text/css" rel="stylesheet" href="./css/goods/dialog.css" />
<link type="text/css" rel="stylesheet" href="./css/self_dialog.css" />


<body data-ctrl=true>
	<!-- <header data-am-widget="header" class="am-header am-header-default">
		<div class="am-header-left am-header-nav" onclick="history.go(-1)">
			<img class="am-header-icon-custom" src="./images/center/nav_bar_back.png"/><span>返回</span>
		</div>
	    <h1 class="am-header-title" style="font-size:18px;">退换货</h1>
	</header>
    <div class="topDiv"></div> -->  <!-- 暂时隐藏头部导航栏 -->
	
	<!-- 换货请直接联系客服 -->
    <div class="white-list link-kefu">
        <div class="re_type" style="display:inline-block;height:50px;line-height:50px;margin-left:11px;">
            <input type="radio" name="re_type" id="refund" value="0" checked="checked"><label for="refund" style="margin-left:5px;">退款</label>
            <input type="radio" name="re_type" id="returngoods" value="1" style="margin-left:15px;"><label for="returngoods" style="margin-left:5px;">退货</label>
        </div>
		<span class="text_right exchange_goods">换货请直接
			<span>联系客服</span>
		</span>
		<!-- 基本数据地区 - 开始 -->
	<form id="tijiaoFrom" action="./aftersale_action.php" method="post" enctype="multipart/form-data">
		<div id="mainArea"> 
			<input type="hidden" name="aftersale_type" value="aftersale">
			<input type="hidden" name="batchcode" value="<?php echo $batchcode;?>">
			<input type="hidden" name="pid" value="<?php echo $pid;?>">
			<!-- 退货原因 -->
			<div onclick="selectTuiKuanReason();" class="white-list frame_reason">
				<div class="list-one">
					<div class="left-title" style="width:30%"><span >退货原因</span></div>
					<div style="float:right;margin-right:10px;">
						<span id="selectedReason">请选择</span>
						<input type="hidden" id="re_reason" name="re_reason" value="">
						<img class="btn_right_arrow" src="./images/order_image/btn_right.png">
					</div>
				</div>
			</div>
			<div class="line_gray10"></div>
			
			<!-- 退货原因描述 -->
			<div class="itemComment" style="width:100%;" goodsId="1">
				<div id="frame_image" class="white-list frame_reason" style="height:300px;">
					<div class="list-one" style="margin-left:10px;">退货原因描述</div>					
					<div class="frame_reason_textarea">
						<textarea name="re_describe" id="reasonContent" placeholder="请填写您遇到的问题，最多125字。 "maxlength="125"></textarea>
					</div>
					
					<!-- 图片地区 -->
					<div id="image-area" class="pic_0">
						<div id="add-image0" class="area-one">
							<img id="image0" src="./images/order_image/icon_image_add.png" class="frame_image_select">
							<input type="file" id="addFile0" accept="image/*" name="Filedata[]" class="frame_image_select" style="opacity:0">
						</div>
					</div>
					<div id="image-area" class="pic_1" style="display:none;">
						<div id="add-image1" class="area-one">
							<img id="image1" src="./images/order_image/icon_image_add.png" class="frame_image_select">
							<input type="file" id="addFile1" accept="image/*" name="Filedata[]" class="frame_image_select" style="opacity:0">
						</div>
					</div>
					<div id="image-area" class="pic_2" style="display:none;">
						<div id="add-image2" class="area-one">
							<img id="image2" src="./images/order_image/icon_image_add.png" class="frame_image_select">
							<input type="file" id="addFile2" accept="image/*" name="Filedata[]" class="frame_image_select" style="opacity:0">
						</div>
					</div>
					
					<div class="text_gray_13">最多可上传3张图片</div>
				</div>
			</div>
			<div class="line_gray10"></div>
			
			<!-- 退款金额 -->
			<div class="white-list frame_reason">
				<div class="list-one" style="padding:15px 0 0 0;font-size:15px;">
					<div class="left-title">退货数量</div>
					<div class="right"><div class="digit-pane"><div class="minus">-</div><div class="count">1</div><div class="plus">+</div></div></div>
				</div>
				<div class="list-one" style="padding:15px 0 0 0;font-size:15px;">
					<div class="left-title">退款金额</div>
					<div class="div-money"><input id="money" name="return_account" type="text" placeholder="请输入退款金额" style="border:none;"/></div>
				</div>
			</div>
			<div class="white-box">
				<p class="font-grey">仅可退款金额<span class="font-red">￥152</span></p>
			</div>
			
		</div>
	</form>
	<!-- 基本数据地区 - 终结 -->
    </div>
	
	
		
	<!-- 下面的【提交】按钮地区 -->
    <div class="white-list frame_button_area">
        <div class="list-one" >
			<div onclick="tijiao();" class="btn_bottom">提交</div>
        </div>
    </div>
	
	<!-- 弹出来的【选择退款原因】窗口 - 开始 -->
	<div id="reasonSelectArea">
		<div class="frame_list">
			<div onclick="reasonSelect(this,1);" class="item_list">质量原因</div>
			<div onclick="reasonSelect(this,2);" class="item_list">商品信息描述不好</div>
			<div onclick="reasonSelect(this,3);" class="item_list">功能/效果不好</div>
			<div onclick="reasonSelect(this,4);" class="item_list">少件/漏件</div>
			<div onclick="reasonSelect(this,5);" class="item_list">包装/商品破损</div>
			<div onclick="reasonSelect(this,6);" class="item_list">发票问题</div>
			<div onclick="reasonSelect(this,7);" class="item_list" style="border-bottom:none;">其他</div>
		</div>
	</div>
	<!-- 弹出来的【选择退款原因】窗口 - 终结 -->
      
</body>		
<script type="text/javascript">
	var wh=$(window).height();

	var imageCount = 0;
	var tuikuanReason = -1;//退款原因
	
	//点击【换货请直接联系客服】
	$(".exchange_goods").click(function(){
		alert("换货请直接联系客服");
	});
	//设置内容高度
	$(".link-kefu").height(wh-65);
	//选择一个【退款原因】，从【退款原因】列表
	function reasonSelect(obj,kind){
		tuikuanReason = kind;
		$("#selectedReason").html($(obj).html());
		$("#re_reason").val($(obj).html());
		$("#reasonSelectArea").hide();
	}

	//点击上面的【请选择-退款原因】
	function selectTuiKuanReason(){
		$("#reasonSelectArea").show();
	}
	
	//点击【提交】
	function tijiao(){
		if(tuikuanReason == -1){
			alert("请选择退款原因");
			return;
		}
	
		var reasonContent = $("#reasonContent").val();
		if(reasonContent == ""){
			alert("请输入退货原因描述");
			return;
		}
		
		var inputMoney = $("#money").val();
		if(isNaN(inputMoney) || inputMoney==""){
			alert("请正确输入退款金额！");
			return;
		}
		
		var inputMoneyFloat = parseFloat($("#money").val());
		alert("【提交】--"+tuikuanReason+"--"+reasonContent+"--"+inputMoneyFloat);
		
		$("#tijiaoFrom").submit();
	}
	
	//上传图片
	document.getElementById('addFile0').addEventListener('change', fileSelect_banner, false);
    function fileSelect_banner(evt) {                                                                                    
        if (window.File && window.FileReader && window.FileList && window.Blob) {
            var files = evt.target.files;
            var file;
            file = files[0];
            if (!file.type.match('image.*')) {
                return;
            }      
            reader = new FileReader();    
            reader.onload = (function (tFile) {
                return function (evt) {
                    dataURL = evt.target.result;
                    
					if(imageCount < 3){
						imageCount++;
						if(imageCount < 3){
							// var html = $("#image-area").html();
							// var content = '<div id="add-image' + imageCount + '" class="area-one" style="position:relative;width:90px;height:90px;display:inline-block;left:'+ 10*(imageCount+1) +'px;top:10px;">';
							// content += '	<img id="image' + imageCount + '" onclick="" src="./images/order_image/icon_image_add.png" class="frame_image_select">';
							// content += '	<input type="file" id="addFile' + imageCount + '" accept="image/*"  name="Filedata" class="frame_image_select" style="opacity:0;">';
							// content += '</div>';
							// $("#image-area").html(html+content);
							document.getElementById('addFile' + imageCount).addEventListener('change', fileSelect_banner, false);
						}
						
						$("#image"+(imageCount-1)).attr("src",dataURL);
						$("#addFile"+(imageCount-1)).hide();							
						$(".pic_" + imageCount).show();
					}
                };
            }(file));
            reader.readAsDataURL(file);
            sendFile = file;
        } else {
            alert('该浏览器不支持文件管理。');
        }
    }
 		//点击minus
	$(".minus").click(function(){
		value = $(this).parent().find(".count").html();
		value = value*1;
		if(value > 0) value--;
		$(this).parent().find(".count").html(value);
		
		price = $("#one-price").attr("price");
		t_price = price*value;
		$("#small-price").html("￥"+t_price);
		$("#total-price").html("￥"+t_price);
	});
	
	//点击plus
	$(".plus").click(function(){
		value = $(this).parent().find(".count").html();
		value = value*1;
		value++;
		$(this).parent().find(".count").html(value);
		
		price = $("#one-price").attr("price");
		t_price = price*value;
		$("#small-price").html("￥"+t_price);
		$("#total-price").html("￥"+t_price);
	});
</script>   
    <script type="text/javascript" src="./assets/js/amazeui.js"></script>
    <script type="text/javascript" src="./js/global.js"></script>
    <script type="text/javascript" src="./js/loading.js"></script>
    <script src="./js/jquery.ellipsis.js"></script>
    <script src="./js/jquery.ellipsis.unobtrusive.js"></script>
</body>
</html>