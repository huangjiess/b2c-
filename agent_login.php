<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]

$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
_mysql_query("SET NAMES UTF8");
require('../proxy_info.php');
require('select_skin.php');
/*require('../common/jssdk.php');
$jssdk = new JSSDK($customer_id);
$signPackage = $jssdk->GetSignPackage();*/
//头文件----start
require('../common/common_from.php');
//头文件----end

$name 		 = "";
$phone 		 = "";
$companyname = "";

if($user_id>0){
	$query=" select id ,name,phone,companyname from weixin_users where id=".$user_id." and isvalid=true";
	$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
	while ($row = mysql_fetch_object($result)) {
		$user_id 	 = $row->id;
		$name 		 = $row->name;
		$phone 		 = $row->phone;
		$companyname = $row->companyname;
		break;
	}
}else{
    $query=" select id ,name,phone,companyname from weixin_users where customer_id=".$customer_id." and isvalid=true and  weixin_fromuser='".$fromuser."'";
	
	$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
	
	while ($row = mysql_fetch_object($result)) {
		$user_id 	 = $row->id;
		$name 		 = $row->name;
		$phone 		 = $row->phone;
		$companyname = $row->companyname;
		break;
	}
}

if($user_id<0){
   $query = "insert into weixin_users(weixin_fromuser,isvalid,createtime,customer_id) values('".$fromuser."',true,now(),".$customer_id.")";
   _mysql_query($query) or die('Query failed: '. mysql_error());
   $user_id = mysql_insert_id();
}

$query="select id,user_id,isAgent,status from promoters where isvalid=true and user_id=".$user_id;
$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
$promoter_id = -1;
$isAgent	 = -1;
$status		 = -1;
$p_user_id	 = -1;
while ($row = mysql_fetch_object($result)) {
    $promoter_id = $row->id;
    $p_user_id   = $row->user_id;
	$isAgent 	 = $row->isAgent;	//判断 0为推广员 1为代理商
	$status 	 = $row->status;	//判断是否为推广员
	break;
}

$is_supply = -1;
$query="select id from weixin_commonshop_applysupplys where isvalid=true and status=0 and user_id=".$user_id;
$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
   $is_supply = $row->id;	//判断是否已经提交过申请;
   break;
}
$is_apply  	 	= -1;
$ap_status 	 	= 0;
$agent_name	 	= '';
$apply_price 	= '';
$agent_discount = '';
$query="select id,status,agent_name,agent_price,agent_discount from weixin_commonshop_applyagents where isvalid=true and user_id=".$p_user_id;
$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
	$is_apply    	= $row->id;				//判断是否已经提交过申请;
	$ap_status   	= $row->status;			//判断申请状态
	$agent_name 	= $row->agent_name;		///代理商名称
	$apply_price 	= $row->agent_price;	///代理商价格
	$agent_discount = $row->agent_discount;	///代理商折扣
	break;
}

$self_str = "1_".$agent_name."_".sprintf("%.2f", $apply_price)."_".$agent_discount;

$query="select name,exp_name from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
$shop_name = "商城";
$exp_name  = "推广员";
while ($row = mysql_fetch_object($result)) {
    $shop_name = $row->name;
    $exp_name  = $row->exp_name;
	break;
}

$query = "select id,agent_price,agent_detail,is_showdiscount from weixin_commonshop_agents where isvalid=true and customer_id=".$customer_id;
$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
$agent_price	 = ""; 	
$agent_detail	 = "";
$is_showdiscount = 0;
while ($row = mysql_fetch_object($result)) {
    $agent_price	 = $row->agent_price;		//代理商价格
	$agent_detail	 = $row->agent_detail;		//代理说明
	$is_showdiscount = $row->is_showdiscount;	//是否显示代理商折扣
}
$pricearr = explode(",",$agent_price);
$len =  count($pricearr);
$diy_num = $len;

//是否申请过区域代理
$query_team = "select id from weixin_commonshop_team_aplay where isvalid=true and status=0 and customer_id=".$customer_id." and aplay_user_id=".$user_id;
$result_team = _mysql_query($query_team) or die('query_team failed'.mysql_error());
$team_id = -1;
while($row_team = mysql_fetch_object($result_team)){
	$team_id = $row_team->id;
}
?>
<!DOCTYPE html>
<html lang="zh-CN"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<title>代理商申请</title>
		<meta charset="utf-8">
		<meta content="" name="description">
		<meta content="" name="keywords">
		<meta content="eric.wu" name="author">
		<meta content="telephone=no, address=no" name="format-detection">
		<meta content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
		<link href="../weixin_inter/agent_login/css/reset.css" rel="stylesheet">
		<link href="../weixin_inter/agent_login/css/common.css" rel="stylesheet">
		<link href="../weixin_inter/agent_login/css/register.css" rel="stylesheet">
		<link type="text/css" rel="stylesheet" href="./css/order_css/global.css" />
		<link type="text/css" rel="stylesheet" href="./css/css_<?php echo $skin ?>.css" /> 
		 <link rel="stylesheet" type="text/css" href="css/supplier.css"/>
		
		<script type="text/javascript" src="./assets/js/jquery.min.js"></script> 
		<style>
		.spanleft{
			float: left;
			width: 8%;
			font-size: 14px;
			line-height: 40px;
			height: 40px;
		}
		.user_name{
			height: 45px;
			text-indent: 17%;
			font-size: 18px;
			margin:10px 0px;
		}
		.user_name span{
			height:45px;
			line-height:45px;
		}
		.user_name input{
			display: inline-block;
			margin-top: 10px;
			height: 27px;
			width: 56%;
			background:#FFFFFF;
		}
		.user_phone{
			height: 45px;
			text-indent: 10%;
			font-size: 18px;
			margin:10px 0px;
		}
		.user_phone span{
			height:45px;
			line-height:45px;
		}
		.user_phone input{
			display: inline-block;
			margin-top: 10px;
			height: 27px;
			width: 56%;
			background:#FFFFFF;
		}
        .box_shadow{
            position: fixed;
            z-index: 100;
            width: 100%;
            height: 100%;
            top: 0;
            background: rgba(0,0,0,0.4);
            display: none;
            left:0;
        }
        .sp_classChoice{
	background: #FFFFFF;
	width: 90%;
	position: fixed;
	z-index: 1000;
	margin-left: 5%;
	top: 90px;
	border-radius: 3px;
	-webkit-border-radius: 3px;
	-moz-border-radius:3px;
	overflow: hidden;
	display: none;
    left:0;
}
.sp_class{
	color: #1c1f20;
	font-size: 14px;
	padding: 15px 0;
	text-align: center;
	border-bottom: 1px solid #e1e1e1;
	position: relative;
	cursor: pointer;
}
.sp_class img{
	width: 5%;
	position: absolute;
	right: 15px;
	top: 22px;
}
.sp_class p{
	text-align: left;
	margin-left: 10%;
}
.sp_class span{
	font-size: 15px;
	color: #fe7c24;
	margin-left: 8px;
}
.class_content img{
	display: none;
}
.class_content{
	max-height: 240px;
    overflow-y: scroll;
}

		</style>
	</head>
	<body onselectstart="return true;" ondragstart="return false;" onload="initload();">
		<div data-role="container" class="container register">
			<div class="body">
				<header data-role="header">
					<img src="../weixin_inter/agent_login/images/top1.png">
				</header>
				<form id="frmLogin" action="#"  method="post" style="margin-top:20px;display:block;">
				<section data-role="body" class="body">
					<div class="register_info" style="display: block;">
						<div class="line title">立即开启赚钱之旅！</div>
						      
						<?php if($ap_status <= 0){?>
						
						<div class="sp_all">
							<div class="sp_content">
								<div class="sp_content_child cho_one">
                                   <p class="spLeft" style="width:100%;">请选择代理级别:<span class="user_choice">
								   <?php if($is_apply > 0){
									   $counts = $agent_discount/10;
								   echo $agent_name."  ".$apply_price."元 ".round($counts,2)."折";} ?></span></p>
								   <input type="hidden" id="agent_select" value="">

					               <img src="images/sp_arrowRight.png"/>
									  
								</div>
              <div class="box_shadow">
			
		                 </div>                  
            <div class="sp_classChoice">
                    <div class="sp_class">
                        请选择代理级别
                        <img src="images/Remove.png"/ style="width: 4%;" class="close_class">
                    </div>
                    <div class="class_content">
						
						<?php 
							for($i=0;$i<$len;$i++){
								$varr = $pricearr[$i];
								 if(empty($varr)){
									 continue;
							    }
								$vlst = explode("_",$varr);
										   
								$type = $vlst[0];
								if(empty($vlst[1])){
									continue;
								}
								$name 	 = $vlst[1];
								$value 	 = $vlst[2];
								$discount = $vlst[3];
						?>
                        
                        <div class="sp_class" arr="<?php echo $pricearr[$i]; ?>" style="background: #ffede0;">
                            <p nums="<?php echo $i;?>"><?php echo $name; ?><span><?php echo $value; ?></span>元 <?php if($is_showdiscount){ ?><span><?php echo round($discount/10,2); ?></span>折<?php } ?></p>
                            <img src="images/right.png" style="display: block;" />
                        </div>
							<?php }?>
                        <!--div class="sp_class">
                            <p>二级代理 <span>500</span>元 <span>9.5</span>折</p>
                            <img src="images/right.png"/>
                        </div>
                        <div class="sp_class">
                            <p>二级代理 <span>500</span>元 <span>9.5</span>折</p>
                            <img src="images/right.png"/>
                        </div>
                        <div class="sp_class">
                            <p>二级代理 <span>500</span>元 <span>9.5</span>折</p>
                            <img src="images/right.png"/>
                        </div-->
                    </div>
			
		</div>
                                
                                
								<div class="sp_content_child ch_one ffir">
									<span>*</span>
									<p class="spLeft ">姓    名:</p>
									<input type="text" name="user_name" id="user_name" value="" placeholder="请填写名字" />
								</div>
								<div class="sp_content_child ch_one">
									<span>*</span>
									<p class="spLeft">联系电话:</p>
									<input type="text" name="user_phone" id="user_phone" value="" placeholder="请填写联系电话" />
								</div>
							</div>
						</div>
						
						<a class="btn btn_apply" id="submit" onclick="apply()">申请</a>		
						<a class="btn btn_ing" id="submit" style="display:none;">审核中...</a>		
						<?php }?>
					</div> 
					
					<ul class="desc">
						<li>						
							<!--<span class="title">代理说明：</span>-->
							<label><?php echo $agent_detail;?></label>
						</li>
					</ul>
					<input type="hidden" name="user_id" value="<?php echo $p_user_id;?>">
				</section>
				</form>
				
				<footer data-role="footer">                                    
                    <div data-role="copyright" data-copyright="copyright1" class="copyright1">
						<div class="widget_wrap">
							<ul class="tbox">
								<li>
									<p>
										<a href="javascript:;">©<?php echo $shop_name;?></a>
									</p>
								</li>
							</ul>
						</div>
					</div>
					
				</footer>
			</div>
		</div>
		<script type="text/javascript" src="./js/global.js"></script>
		<script type="text/javascript" src="../common_shop/common/js/hidetool.js"></script>
		<script type="text/javascript" src="../common/utility.js"></script>
	<script>
		var customer_id    = '<?php echo $customer_id;?>';
		var customer_id_en = '<?php echo $customer_id_en;?>';
		var is_apply  	   = '<?php echo $is_apply;?>';
		var is_supply 	   = '<?php echo $is_supply;?>';
		var status 		   = '<?php echo $status;?>';
		var ap_status 	   = '<?php echo $ap_status;?>';
		var agent_name	   = '<?php echo $agent_name;?>';
		var apply_price	   = '<?php echo $apply_price;?>';
		var agent_discount = '<?php echo $agent_discount;?>';
		var agent_price    = '<?php echo $agent_price;?>';
		var isAgent 	   = '<?php echo $isAgent;?>';
		var team_id 	   = '<?php echo $team_id;?>';
		var self_str 	   = '<?php echo $self_str;?>';
		var types          = null;
		var submitcount    = 0;
		
		function apply(){
			var user_name      = $('#user_name').val();
			var user_phone     = $('#user_phone').val();
			 if(self_str==""){
				showAlertMsg ("提示：","代理级别不能为空，请重新选择","知道了");
				return;
			 }
			if(user_name==""){
				showAlertMsg ("提示：","姓名不能为空，请重新填写","知道了");
				return;
			}
			if(user_phone==""){
				showAlertMsg("提示：","电话号码不能为空，请重新填写","知道了");
				return;
			}
			if(!chkPhoneNumber(user_phone)){
				showAlertMsg("提示：","请输入正确的电话号码","知道了");
				return;
			};
			if(agent_price==""){
				showAlertMsg ("提示：","商家未设置好,请稍后提交","知道了");
				return;
			}
			if(isAgent==1){
				showAlertMsg ("提示：","您已成为代理商,请勿申请","知道了");
				return;
			}
			if(isAgent==3){
				showAlertMsg ("提示：","您已成为合作商,请勿申请","知道了");
				return;
			}
			if(isAgent>=5){
				showAlertMsg ("提示：","您已成为区域商,请勿申请","知道了");
				return;
			}
			if(team_id>0){
				showAlertMsg ("提示：","您已经提交过区域商请","知道了");
				return;
			}
			if(is_supply>0){
				showAlertMsg ("提示：","你已经提交过合作商申请","知道了");
				return;
			}

			if(submitOnce()){ 
				if(status != 1){
					showAlertMsg ("提示：","您还不是<?php echo $exp_name;?>","知道了");
					return;
				}
				if(is_apply > 0){
					if(ap_status<=0){
								
								var agent_select = $('#agent_select').val();
								
								$.ajax({
									url: 'save_agentlogin.php?customer_id='+customer_id_en,
									data:{
										agent_select:agent_select,
										user_name:user_name,
										user_phone:user_phone
									},
									type:'post',
									dataType:'json',
									async:true,
									success:function(res){
										if(1==res.status){
											check();
											submitcount = 0;
											showConfirmMsg('您已更改,等待商家审核','返回我的特权还是继续操作？','返回我的特权','继续操作',function(){
												window.location.href = 'my_privilege.php?customer_id='+customer_id_en;
											})
										}
									},
									error:function(er){
										goBack();
									}
								});
								return false;
					}else if(ap_status==1){
						showAlertMsg ("提示：","商家已经通过了","知道了");
						return;
					}
				}else{
							var agent_select = $('#agent_select').val();
							//var agent_select = $('.sp_class').data('value');
							$.ajax({
								url: 'save_agentlogin.php?customer_id='+customer_id_en,
								data:{
									agent_select:agent_select,
									user_name:user_name,
									user_phone:user_phone
								},
								type:'post',
								dataType:'json',
								async:true,
								success:function(res){
									if(1==res.status){
										check();
										submitcount = 0;
											newShowConfirmMsg2('提交成功,等待商家审核','需要返回我的特权吗？','取消','返回我的特权',function(){
												window.location.href = 'my_privilege.php?customer_id='+customer_id_en;
											},function(){
												window.location.href = 'agent_login.php?customer_id='+customer_id_en;
											}
										);
										//window.location.href = 'agent_login.php?customer_id='+customer_id_en;
									}
								},
								error:function(er){
									goBack();
								}
							});
							return false;
				}
			}
		}
		
		function submitOnce(){
			if (submitcount == 0){
			   submitcount++;
			   return true;
			} else{
			   showAlertMsg ("提示：","正在操作，请不要重复提交！","知道了");
			   return false;
			}
		}
		
		function initload(){
		   if(ap_status==-1){
				showAlertMsg ("提示：","商家已经拒绝您的申请,请联系商家","知道了");
				return false;
			}else{
				return true;
			}
		}
		
		function check(){
			if( is_apply > 0 && ap_status <= 0){
				$('.btn_apply').hide();
				$('.btn_ing').show();
			}else{
				$('.btn_apply').show();
				$('.btn_ing').hide();
			}
		}
		$(function(){
			check();	
		})
		
		$('#agent_select').change(function(){
			check();
		});
        $(function(){
			$('.close_class').click(function(){
				$('.box_shadow').css('display','none');
				$('.sp_classChoice').css('display','none');
			})
			$('.cho_one').click(function(){
                $('.class_content .sp_class').css('background','#FFFFFF');
				$('.class_content .sp_class img').hide();
				var bb = $('.user_choice').text();
				$('.class_content .sp_class').each(function(){
					var arr = $(this).attr('arr');
				//	console.log(arr);
				//	console.log(self_str);
					if(arr==self_str){
						$(this).css('background','#ffede0');
						$(this).children('img').show();
					}
					// var aa = $(this).text();
					// if(aa==bb){
						// $(this).css('background','#ffede0');
						// $(this).next().show();
					// }
				})
				$('.box_shadow').css('display','block');
				$('.sp_classChoice').css({'display':'block','opacity':'0','top':'90px'});
				$('.sp_classChoice').animate({top: '120px', opacity: 1}, 500)
			})
			$('.class_content .sp_class').click(function(){
				$('.box_shadow').css('display','none');
				$('.sp_classChoice').css('display','none');
				$('.user_choice').html("");
			    var aa = $(this).find('p').text();
				//var nums = $(this).find('p').attr('nums');
				var arr = $(this).attr('arr');
				self_str = arr;
				$('#agent_select').val(arr);
				//$('#nums').val(nums);
				$('.user_choice').append(aa);
			})
		})
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
<?php  
require_once('../common/utility_setting_function.php');
$fun = "agent_login";
$nav_is_publish = check_nav_is_publish($fun,$customer_id);
$is_publish = check_is_publish(2,$fun,$customer_id);
include_once('float.php');
/*判断是否显示底部菜单 start*/
if($is_publish){
	require_once('./bottom_label.php');
}
/*判断是否显示底部菜单 end*/
?>
<!--引入侧边栏 end--> 	
	
</body>
</html>