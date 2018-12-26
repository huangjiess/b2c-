<?php
header("Content-type: text/html; charset=utf-8");
session_cache_limiter( "private, must-revalidate" );
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]

//require('../back_init.php'); 
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD); 
mysql_select_db(DB_NAME) or die('Could not select database');
_mysql_query("SET NAMES UTF8");
// require('../common/jssdk.php');
require('select_skin.php');
require('../proxy_info.php');
//头文件----start
require('../common/common_from.php');
//头文件----end

$aplay_grate = -1;	//申请区代等级
$aplay_grate = $configutil->splash_new($_POST['aplay_grate']);

$agreement = '';	//代理申请协议
$query = "select agreement from weixin_commonshop_team where isvalid=true and customer_id=".$customer_id;
$result = _mysql_query($query) or die('query failed'.mysql_error());
while($row = mysql_fetch_object($result)){
	$agreement = $row->agreement;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>区域商申请</title>
    <!-- 模板 -->
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
    <script type="text/javascript" src="./assets/js/jquery.min.js"></script>    
    <link type="text/css" rel="stylesheet" href="./css/vic.css" />
    <!-- 页联系style-->
    <link type="text/css" rel="stylesheet" href="./css/goods/global.css" />
    <link type="text/css" rel="stylesheet" href="./css/goods/dialog.css" />
    <link type="text/css" rel="stylesheet" href="./css/goods/quyudailishang1-2.css" />
	<link type="text/css" rel="stylesheet" href="./css/order_css/global.css" />
    <!-- 页联系style-->
    <link type="text/css" rel="stylesheet" href="./css/css_<?php echo $skin ?>.css" />  
</head>
<style>
.dlg_content1_row2 img{
	max-width: 88% !important;
}
</style>
<body data-ctrl=true>
	<!-- header部门-->
	<!-- <header data-am-widget="header" class="am-header am-header-default header">
		<div class="am-header-left am-header-nav header-btn">
			<img class="am-header-icon-custom"  src="./images/center/nav_bar_back.png"/><span>返回</span>
		</div>
	    <h1 class="header-title" style="font-size:18px;">区域商</h1>
	    <div class="am-header-right am-header-nav">
		</div>
	</header>
	<div class="topDiv" style="height:49px;"></div> -->   <!-- 暂时屏蔽头部 -->
	<!-- header部门-->
	<!-- content -->
	<input type="hidden" id="aplay_grate" name="aplay_grate" value="<?php echo $aplay_grate;?>">
    <div class = "content" id="containerDiv">
    	<div class = "content-item">
    		<div class = "content-item-title">
    			<span>
    				<font class="item-arist">*&nbsp;</font>
    				<font>请输入您的姓名</font>
    			</span>
    		</div>
    		<div  class = "content-item-input">
		    	<input class = "item-input" id = "name" type="text" name="name" placeholder="请输入您的姓名">
		    </div>
		</div>
		<div class = "content-item">
    		<div class = "content-item-title">
    			<span>
    				<font class="item-arist">*&nbsp;</font>
    				<font>请输入您的手机号码</font>
    			</span>
    		</div>
    		<div class = "content-item-input">
		    	<input class = "item-input" id = "phone" type="text" name="phone" placeholder="请输入您的手机号码">
		    </div>
		 </div>
		 <div class = "content-item">
    		<div class = "content-item-title">
    			<span>
    				<font class="item-arist">*&nbsp;</font>
    				<font>常住地址</font>
    			</span>
    		</div>
    		<div class = "content-item-input">
		    	 <select class = "select-address" id = "location_p" name="location_p">
					<!--<option>--请选择省份--</option>-->
			      </select>
		    </div>
		    <div class = "content-item-input1">
		    	 <select class = "select-address" id = "location_c" name="location_c">
		    	 	<!--<option>--请选择市--</option>-->
			      </select>
		    </div>
		    <div class = "content-item-input1">
		    	 <select class = "select-address" id = "location_a" name="location_a">
		    	 	<!--<option>--请选择区/县--</option>-->
			      </select>
		    </div>
		 </div>
		 <div class = "content-item">
    		<div class = "content-item-title">
    			<span>
    				<font class = "item-title1">备注</font>
    			</span>
    		</div>
    		<div class = "content-item-input">
		    	<input class = "item-input" id = "remark" type="text" name="remark" placeholder="备注留言">
		    </div>
		 </div>
		 
		 <div class = "yuedu-content">
		 	<img class = "check-button" src = "./images/goods_image/20160050304.png">
		    <span class="read_agreement"> 阅读区域团队协议</span>
	     </div>
	     
	     <div class =  "submit-button">
	     	<span style = "color:white;">提交申请</span>
	     </div>
	</div>
	<!-- content -->
	<!--dialog-->
   <div class="am-share dlg">
   	  <!--dialog rect-->
   </div>
    <div id="agreement" style="display:none;"><?php echo $agreement;?></div>
   
    
</body>		
<!-- 页联系js -->
<script type="text/javascript" src="./assets/js/amazeui.js"></script>
<script type="text/javascript" src="./js/global.js"></script>
<script type="text/javascript" src="./js/loading.js"></script>
<script src="./js/jquery.ellipsis.js"></script>
<script src="./js/jquery.ellipsis.unobtrusive.js"></script>
<script src="./js/goods/global.js"></script>
<script src="./js/goods/area_agent_apply.js"></script>
<!-- 页联系js -->
<script charset="utf-8" src="../common/region_select.js"></script><!-- 地址选择js -->
<script type="text/javascript">
	/*省*/
	new PCAS('location_p','location_c','location_a' ,'','',''); 
	/*省End*/
</script>
<script type="text/javascript">
var customer_id     = '<?php echo $customer_id;?>';
var customer_id_en  = '<?php echo $customer_id_en;?>';
var check 			= false;

	// 提交申请按键点击事件
	$(".submit-button").click(
		function(){
			if(check){
				return;
			}
			if(!validateData()) return;
			if(!$(".check-button").hasClass("check-on")){
				showAlertMsg ("提示：",'请同意代理申请协议！',"知道了");
				return false;
			}
			
			var aplay_grate = $('#aplay_grate').val();
			var name = $('#name').val();
			// console.log(name);
			var phone = $('#phone').val();
			var location_p = $('#location_p').val();
			var location_c = $('#location_c').val();
			var location_a = $('#location_a').val();
			var remark = $("#remark").val();
			if(remark == undefined ) remark = "";
			check = true;
			$('.submit-button').attr('disabled',"true");
			$('.submit-button span').html('正在提交中...');
			$.ajax({
				url: 'save_area_agent_apply.php?customer_id='+customer_id_en,
				data:{
					aplay_grate:aplay_grate,
					name:name,
					phone:phone,
					location_p:location_p,
					location_c:location_c,
					location_a:location_a,
					remark:remark
				},
				type:'post',
				dataType:'json',
				async:true,
				success:function(res){
					history.replaceState({},'','my_privilege.php?customer_id'+customer_id_en);//修改历史记录，防止返回报错
					$('.submit-button span').html('提交成功');
					showAlertMsg ("提示：",res.errorMsg,"知道了");
					setTimeout(function(){
						window.location.href = 'area_agent.php?customer_id='+customer_id_en;
					},1500);
				},
				error:function(er){
					
				}
			})
		}
	);
</script>
<!--引入微信分享文件----start-->
<script>
<!--微信分享页面参数----start-->
debug=false;
share_url=''; //分享链接
title=""; //标题
desc=""; //分享内容
imgUrl="";//分享LOGO
share_type=3;//自定义类型
<!--微信分享页面参数----end-->
</script>
<?php require('../common/share.php');?>
<!--引入微信分享文件----end-->
<!--引入侧边栏 start-->
<?php  include_once('float.php');?>
<!--引入侧边栏 end-->
</body>
</html>