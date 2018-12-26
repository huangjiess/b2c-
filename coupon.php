<?php
header("Content-type: text/html; charset=utf-8"); 
session_cache_limiter( "private, must-revalidate" ); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../common/utility.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('order_Form Could not select database');

//头文件----start
require('../common/common_from.php');
//头文件----end

$type = 'my';
if(!empty($_GET["type"])){
	$type = $configutil->splash_new($_GET["type"]);
}

$n_p = 0;		//不包含运费的订单金额
if(!empty($_POST["n_p"])){
	$n_p = $configutil->splash_new($_POST["n_p"]);
}
$cp_id = 0;		//首页优惠券跳转链接
if(!empty($_GET["cp_id"])){
	$cp_id = $configutil->splash_new($_GET["cp_id"]);
	//首页优惠券跳转链接
	$code  = "";
	$contentStr = "";
	require('../common/utility_shop.php');
	$utility_coupon= new Utility_Coupon();
	$code = $utility_coupon->create_coupon($customer_id,$user_id,-1,1,$cp_id);
	if( "timeout" == $code["check"] ){
		$contentStr = "您要领取优惠券已过期";
	}elseif( "fail_1" == $code["check"] ){
		$contentStr = "您今天领取优惠券次数达到上限";
	}elseif( "fail_2" == $code["check"] ){
		$contentStr = "您领取优惠券发放数量达到上限";
	}elseif( "fail_3" == $code["check"] ){
		$contentStr = "您共领取优惠券次数达到上限";
	}elseif( "close" == $code["check"] ){
		$contentStr = "商家尚未设置优惠券,敬请期待!";
	}elseif( "no_promoter_Identity" == $code["check"] ){
		$contentStr = "您尚未成为推广员，无法领取优惠券!";
	}
	if(!empty($contentStr)){
		echo "<script>alert('".$contentStr."');</script>";
	}
}
$href_url = 'order_form.php?customer_id='.$customer_id_en;
$w = 1;		//判断从哪里进来，0:从个人中心进来，不可选择 1:下单页面可选择
$pid    = '';
$pprice = '';
if(empty($_GET["w"])){
	$w = 0;	
	$href_url = 'my_moneybag.php?customer_id='.$customer_id_en;
}else{
	$supply_id = -1;
	if(!empty($_POST['supply_id'])){
		$supply_id = $_POST['supply_id'];   		//品牌供应商ID或者平台ID
	}
	$ii = 0;
	if(!empty($_POST['ii'])){
		$ii = $_POST['ii'];							//定位第几个订单
	}	
	if(!empty($_POST['pid'])){
		$pid = $_POST['pid'];							//选择的商品组
	}
	if(!empty($_POST['pprice'])){
		$pprice = $_POST['pprice'];							//选择的商品组
	}
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>优惠券</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
        <meta content="telephone=no" name="format-detection">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black">
	    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link rel="stylesheet" type="text/css" href="css/coupon.css">
        <script type="text/javascript" src="js/jquery-2.1.3.min.js"></script>
		<link type="text/css" rel="stylesheet" href="./css/order_css/global.css" />  
		<link rel="stylesheet" href="css/css_orange.css" />
		<style> 
			/**加载效果旋转**/  
			@-webkit-keyframes rotate {0% {-webkit-transform: rotate(0deg);}50% {-webkit-transform: rotate(180deg);}100% {-webkit-transform: rotate(360deg);}}  
			@keyframes rotate {0% {transform: rotate(0deg);}50% {transform: rotate(180deg);}100% {transform: rotate(360deg);}}  
			  
			.loadmore {display:block;line-height: 50px;text-align:center;color:#ccc;font-size:14px;}  
			.loadmore span{height:20px;width: 20px;border-radius: 100%;display:inline-block;margin:10px;border:2px solid #f60;border-bottom-color: transparent;vertical-align: middle;-webkit-animation: rotate 0.75s 0 linear infinite;animation: rotate 0.75s 0 linear infinite;}  
			.loadover{/*position:relative;margin:0 12px;padding:24px 0;height:20px;line-height:20px;color:#909090;text-align: center;*/}  
			.loadover{margin-top: 20px;text-align: center;margin-bottom: 35px;}
			.loadover span{position:relative;display:inline-block;padding:0 6px;height:20px;z-index:2}  
			.loadover:after {content:''position: absolute;left: 0;top: 50%;width: 100%;height: 1px;background: #DCDCDC;z-index: 1;}  
			.clear{clear:both;}

 //ld 点击效果
        .button{ 
        	-webkit-transition-duration: 0.4s; /* Safari */
        	transition-duration: 0.4s;
        }

        .buttonclick:hover{
        	box-shadow:  0 0 5px 0 rgba(0,0,0,0.24);
        }


.area-line {
    height: 25px;
    width: 1px;
    float: left;
    margin-top: 10px;
    padding-top: 20px;
    border-left: 1px solid #eee;
}
.red{color:#f05850}
		</style>
   
	</head>
	<body>
       <div class="coupon_top">
			<div class="coupon_back">
				<img src="images/coupom_img/Back.png"/><span>返回</span>
			</div>
			<p>我的优惠券</p>			
		</div>
		<div class="fix" style="display: none;">
			<div class="box">
				<img src="images/coupom_img/ticket.png">
				<p>没有可领优惠券哦！敬请期待吧！</p>			
			</div>
		</div>			
		<div class="tab border_b">
			<div class="son active"><p>可用 (8)</p></div>
			<div class="son"><p>失效 (6)</p></div>
			<div class="son"><p>已用 (13)</p></div>			
		</div>
				<div class="option border_b">
			<div class="son">
				<p class="p_act"><span>全部类型 (8)</span><img class="img01" src="images/coupom_img/xiala_org.png"></p>
				<ul class="slide_box">
					<li class="act">全部类型(8)</li>
					<li>平台专用(8)</li>
					<li>商品专用(8)</li>
				</ul>
			</div>
			<div class="son"><p><span>领取时间</span><img class="img02" src="images/coupom_img/sx01.png"></p></div>
			<div class="son"><p><span>领取金额</span><img class="img02" src="images/coupom_img/sx01.png"></p></div>			
		</div>
		<!--引入侧边栏 start-->
		<?php  include_once('float.php');?>
		<!--引入侧边栏 end-->
<script type="text/javascript">

//全局参数部分
var ajax_data = {
	
	customer_id	:	'<?php echo $customer_id;?>',
	user_id	 	:	'<?php echo $user_id;?>',
	pid	 		:	'<?php echo $pid;?>',
	pprice	 	:	'<?php echo $pprice;?>',
	
	start 		:	0,		//读取数据开始位置	
	end   		:	8,		//数据加载数量	
	finished	:	0, 		//防止未加载完再次执行
	sover		:	0  		//数据是否已经加载完
	
}
var type = '<?php echo $type; ?>'; 
var loadtype  = "use";//加载类别
var Pro_price = new Array();//存单品对应的价格
$(function(){	
	
	
	Get_coupon();	//首次获取优惠券
	Proprice();    //处理单品对应总价钱
	//滑动加载数据（显示的数据高度必须大于窗口高度才会触发）
	$(window).scroll(function() {	
		
		var scrollTop = $(window).scrollTop(); 			//滑动距离 
		var scrollHeight = $(document).height();  		//内容的高度
		var windowHeight = $(window).height();			//窗口高度
			
		if (scrollTop + windowHeight >= scrollHeight) {		//当滑动距离+内容的高度 > 窗口的高度 = 则加载数据
			
			loadmore();  								//加载数据的函数
					
		} 
	});
	 
	

	
});


/***************函数部分**************/
//首次获取优惠券
function Get_coupon(){
	$.ajax({
		type: 'POST',  
		url: 'coupon_get.php', 
		data:{
			customer_id: '<?php echo $customer_id;?>',
			user_id: '<?php echo $user_id;?>'
		},
		dataType: 'json',
		success: function(result){
		
			if( result["check"] == "ok" ){
				if(result["resu_num"]>0){
					$('.not-coupon').hide();
					showAlertMsg("提示",'领取成功首次优惠券',"知道了");	//弹出警告							
				}
			}
			
		}
	});
	loadmore(); 	//加载首次获取优惠券
}
	
 
 
//加载完  
function loadover(sover){ 

	if(sover==1)  
	{     
		<?php 
		if($w ==1 ){?>							
			var overtext="不选择任何优惠";  
			
			var txt='<div class="loadover loadover_add" onclick="Change(-1)";><span class="sp">'+overtext+'</span></div>' ;
			
			$("body").append(txt); 	
			
		<?php }else{?>
			var overtext="Duang～到底了";  
			// if($(".loadover").length>0)  
			// {  	
				// $(".loadover span").eq(0).html(overtext);  
			// }  
			// else  
			// {  	
				// var txt='<div class="loadover"><span>'+overtext+'</span></div>'  
				// $("body").append(txt);  
			// } 
			var txt='<div class="loadover"><span>'+overtext+'</span></div>'  
			$("body").append(txt);  
		<?php }?>						
	}  
} 
 
 
//加载更多 
function loadmore(){  

	if(ajax_data.finished==0 && ajax_data.sover==0)  
	{  
		
			var txt='<div class="loadmore"><span class="loading"></span>加载中..</div>'  ;
			$("body").append(txt);  
			  
			//防止未加载完再次执行  
			ajax_data.finished=1;  
	 

			$.ajax({  
				type: 'POST',  
				url: 'coupon_page.php', 
				data:{
						start 		:	ajax_data.start,		//读取数据开始位置	
						end   		:	ajax_data.end,		//数据加载数量
						customer_id	:	ajax_data.customer_id,
						user_id	 	:	ajax_data.user_id,
						pid	 		:	ajax_data.pid,//选取的商品组
						pprice	 	:	ajax_data.pprice,//选取的商品组及对应的价格
						supply_id	:	supply_id,
						w			:	<?php echo $w; ?>,
						op	 		:	loadtype,
				
				},
				dataType: 'json',  
				success: function(data){			
					ajax_callback(data);
						
				},  
				error: function(xhr, type){  
					alert('Ajax error!');  
				}  
			}); 
		
	}  
}  
 
 function ajax_callback(data){
	 var result = '' ; 
	 
	if(loadtype=='use'){
		
		for(var i = 0 ; i < data.coupon.length; i++){ 
		
			 result+='<div class="box"><?php if($w){?> <div class="cirdiv"><input type="checkbox"  id="check_'+i+'" onclick="checkfor(this);" value="'+data.coupon[i].keyid+','+data.coupon[i].is_inentity+'" /><label for="check_'+i+'"></label></div><p>满'+data.coupon[i].NeedMoney+'减'+data.coupon[i].Money+'</p><?php } ?><div class="bottomdiv '+data.coupon[i].redone+'"><div class="tag orange '+data.coupon[i].redtwo+' ">'+data.coupon[i].c_content+'</div><div class="leftdiv '+data.coupon[i].redone+'"><div class="topp">有效期至<span>'+data.coupon[i].deadline+'</span></div><div class="botdiv"><div class="botleft"><span class="orangefive">'+data.coupon[i].Money+'</span>元</div><div class="botright"><div class="ptwo">备注：<span>'+data.coupon[i].p_content+'</span></div></div></div></div><div class="rightdiv '+data.coupon[i].redtwo+' '+data.coupon[i].orangetwo+'"><div class="lineone">满<span class="cont" id="NeedMoney_'+data.coupon[i].keyid+'" data-connected_id="'+data.coupon[i].connected_id+'" data-needmoney="'+data.coupon[i].NeedMoney+'"  data-money="'+data.coupon[i].Money+'">'+data.coupon[i].NeedMoney+'</span>元</div><div class="lineone">即可使用</div><div class="linetwo">(不含邮费)</div>>';
			 if(data.coupon[i].is_receive==0){				 				
				if(data.coupon[i].redone=="redone"){
					 result+='<div class="linethree red" onclick="receiveCoupon(this,'+data.coupon[i].keyid+')">立即领取<span></span></div>';
				 }else{
					 result+='<div class="linethree "  onclick="receiveCoupon(this,'+data.coupon[i].keyid+')">立即领取<span></span></div>';
				 }
			 }
			 result+='</span></div></div></div></div>';
			 
		} 
	}else{
		for(var i = 0 ; i < data.coupon.length; i++){ 
			
			 result+= '<div class="box"><div class="bottomdiv grayone"><div class="tag graythree">平台专用券</div><div class="leftdiv grayone"><div class="topp">有效期至<span>'+data.coupon[i].deadline+'</span></div><div class="botdiv"><div class="botleft"><span>'+data.coupon[i].Money+'</span>元</div><div class="botright"><div class="ptwo">备注：<span>'+data.coupon[i].p_content+'</span></div></div></div></div><div class="rightdiv graytwo"><div class="lineone">满<span>'+data.coupon[i].NeedMoney+'</span>元</div><div class="lineone">即可使用</div><div class="linetwo">(不含邮费)</div></div></div></div>'
			
		} 
	}
	
	ajax_data.start += data.coupon.length;	 //赋值下一次读取数据开始位置	
	
	$('#use>span>span').text(data.ycount[0].ycount);
	$('#due>span>span').text(data.dcount[0].dcount);
	
	// 为了测试，延迟1秒加载  	
	setTimeout(function(){
		
		$(".loadmore").remove(); 
		
		$('.content').append(result);  		//加载数据到body	
		
		ajax_data.finished=0;  				//允许下一次查询开始	
		
		//最后一页
		if( ajax_data.end > data.coupon.length ){  		//每次异步加载的数据量大于加载出来的数据，说明数据已经加载完，下次无需加载

			ajax_data.sover=1;  			
			
		}

		//判断是否加载完数据，显示加载完毕标签
		if($('.box').length == 0){
			
			$('.not-coupon').show();		//显示加载完毕标签 
			
		}else{

			loadover(ajax_data.sover); 		//显示加载完毕标签 
		}
		
	},1000); 

 }
 
 /***************函数部分**************/
</script> 

<script>

var supply_id = '<?php echo $supply_id ;?>';
var ii = '<?php echo $ii; ?>';					//定位第几个订单的位置 1~~+	
var n_p = "<?php echo $n_p;?>";//订单金额(不包括运费)
var user_id = "<?php echo $user_id;?>";
// var coupon_object = localStorage.getItem('coupon_'+user_id); 	//读取localStorage的数据
// var coupon_object_arr = JSON.parse(coupon_object);				//json转数组
//console.log(coupon_object_arr);					//定位第几个订单的位置 1~~+	
var coupon_object = localStorage.getItem('coupon_'+user_id); 	//读取localStorage的数据
var coupon_object_arr = new Array();
coupon_object_arr = JSON.parse(coupon_object);			//json转数组

var envent_object = localStorage.getItem('envent_'+user_id); 	//读取localStorage的数据
var envent_object_arr = JSON.parse(envent_object);				//json转数组
//console.log(envent_object_arr);
//确认订单选择优惠券
function Change(){
	var obj = '';
	$("input[type='checkbox']").each(function(){
		if($(this).prop('checked')){
			obj = $(this).val();
		}
	});
	var arr = obj.split(',');
	var id = arr[0];
	var is_inentity = arr[1];
	
	if( is_inentity==0 ){
		showAlertMsg("提示",'该优惠券只可推广员使用',"知道了");	//弹出警告
		return;
	}
	//存储该优惠券记录
	write_couponRecord(id);

	var NeedMoney;
	var money ;
	var connected_id = $("#NeedMoney_"+id).data("connected_id");
	if(id>0){				
		NeedMoney = $("#NeedMoney_"+id).data("needmoney");
		money = $("#NeedMoney_"+id).data("money");					
		if(connected_id>0){
		//判断是否为单品优惠券
			if(Pro_price[connected_id]<NeedMoney){
				showAlertMsg("提示",'金额未达到,不可选',"知道了");	//弹出警告
				return;
			}
		}else{
			if(n_p<NeedMoney){
				showAlertMsg("提示",'金额未达到,不可选',"知道了");	//弹出警告
				return;
			}
		}
	}	
	
	/*保存到本地存储*/
	
	// var arr = new Array(id,NeedMoney,money,user_id);			
	// var rtn_array_json = JSON.stringify(arr);				//转JSON					
	// localStorage.setItem('coupon_'+user_id,rtn_array_json);	//存入localStorage

	var rtn_array_temp = new Array(id,NeedMoney,money,supply_id,user_id); 
	//保存到localStorage,方便下次读取
		if(coupon_object_arr==null){			//创建
			var _A = new Array();					
			_A[ii] = rtn_array_temp ;					
			var rtn_array_json = JSON.stringify(_A);				//转JSON					
			localStorage.setItem('coupon_'+user_id,rtn_array_json);	//存入localStorage
		
		}else{					 			//修改自己的内容		
			coupon_object_arr[ii] = rtn_array_temp ; 			
			var rtn_array_json = JSON.stringify(coupon_object_arr);	//转JSON				
			localStorage.setItem('coupon_'+user_id,rtn_array_json);	//存入localStorage
			//console.log(localStorage.getItem('coupon_'+user_id));
		}
	envent_object_arr.event1 = 'coupon';
	//console.log(envent_object_arr);
	var event_arr_json = JSON.stringify(envent_object_arr);			//转JSON	
	//console.log(event_arr_json);
	localStorage.setItem('envent_'+user_id,event_arr_json);			//初始化被动事件存入localStorage
	
	
	/*保存到本地存储*/
	
	history.replaceState({},'','order_form.php?customer_id<?php echo $customer_id_en ;?>');	
		
	/* 将GET方法改为POST ----start---*/
	var strurl = "order_form.php?customer_id=<?php echo $customer_id_en;?>";
	
	var objform = document.createElement('form');
	document.body.appendChild(objform);
	
	/*var rtn_coupon_array = new Array();	
	rtn_coupon_array.push(NeedMoney,money);
	
	//选择的优惠券ID		
	var obj_p = document.createElement("input");
	obj_p.type = "hidden";
	objform.appendChild(obj_p);
	obj_p.value = id;
	obj_p.name = 'select_coupon_id';
	
	//优惠券信息
	var obj_p = document.createElement("input");
	obj_p.type = "hidden";
	objform.appendChild(obj_p);
	obj_p.value = rtn_coupon_array;
	obj_p.name = 'rtn_coupon_array'*/;
	
	objform.action = strurl;
	objform.method = "POST"
	objform.submit();
	/* 将GET方法改为POST ----end---*/
			
	
}

function viewRecord(type){
	//初始化数据
	ajax_data.start = 0;
	ajax_data.sover=0;
	loadtype = type;
	$('.not-coupon').hide();
	$('.loadover').remove();
	$('.not-used').remove();
	$('.use').removeClass('selected');
	$('.due').removeClass('selected');
	
	if(loadtype=="use"){
		$('.use').addClass('selected');
	}else if(loadtype=="due"){
		$('.due').addClass('selected');	
	}
	$('.box').remove();
	loadmore();
}
function Proprice(){
	var pidAtt = ajax_data.pid.split(",");
	var ppriceAtt = ajax_data.pprice.split(",");
	var n=0;
	for(n=0;n<pidAtt.length;n++){
		Pro_price[pidAtt[n]] = ppriceAtt[n];
	}
}
//记录当前优惠券使用中
function write_couponRecord(cid){	
	$.ajax({  
			type: 'POST',  
			url: 'coupon_page.php', 
			data:{
					customer_id	:	ajax_data.customer_id,
					user_id	 	:	ajax_data.user_id,
					cid	 		:	cid,//选取的优惠券
					supply_id   :   supply_id,
					op          :   'record'
			},
			dataType: 'json',  
			success: function(data){								
					
			},  
			error: function(data){  
				alert('error!');
				return;
			}  
		}); 
}

function checkfor(o){//选取优惠券
	if($(o).prop("checked")==false){
		$(o).prop('checked',false);
	}else{
		$("input[type='checkbox']").prop('checked',false);
		$(o).prop('checked',true);
	}	
}

function receiveCoupon(obj,cid){
		$.ajax({  
			type: 'POST',  
			url: 'coupon_page.php', 
			data:{
					customer_id	:	ajax_data.customer_id,
					user_id	 	:	ajax_data.user_id,
					cid	 		:	cid,//选取的优惠券
					op          :   'receive'
			},
			dataType: 'json',  
			success: function(data){								
				if(data==1000){					
					$(obj).parent('.rightdiv').addClass('orangetwo');
					$(obj).remove();
				}
			},  
			error: function(data){  
				alert('error!');
				return;
			}  
		}); 
}
</script>
<!--我的优惠券-->
<script>
	$(function(){
		$(".tab .son").click(function(){
			$(this).addClass("active").siblings().removeClass("active")
		})			
		/*类型 时间 金额*/
		$(".option .son").click(function(){
			var index=$(this).index();
			if(index==0){
				$(".slide_box").toggle()
				var img01src=$(".img01").attr("src");
				var img01src=img01src.replace("_gray","_org")
				$(".img01").attr("src",img01src);
				recoveryurl02($(this))
			}else{
				recoveryurl02($(this))
				/*恢复第一个盒子为未激活状态*/
				var img01src=$(".img01").attr("src");
				var img01src=img01src.replace("_org","_gray")
				$(".img01").attr("src",img01src);
				$(".slide_box").hide();
				/*改变升序跟降序*/						
				var img02src=$(this).find(".img02").attr("src");
				var arr02=img02src.split("img/");
				if(img02src.match("sx01")){
					img02src=img02src.replace(arr02[1],"sx02.png");	
					$(this).find(".img02").attr("src",img02src)
				}else if(img02src.match("sx02")){
					img02src=img02src.replace(arr02[1],"sx03.png");	
					$(this).find(".img02").attr("src",img02src)							
				}else{
					img02src=img02src.replace(arr02[1],"sx02.png");	
					$(this).find(".img02").attr("src",img02src)						
				}									
			}	
			$(this).children("p").addClass("p_act");
			$(this).siblings().children("p").removeClass("p_act");						
		})					
		/*恢复img02图片为没启动升序降序的状态*/
		function recoveryurl02(obj){
			obj.siblings().find(".img02").each(function(){
				var imgsrc=$(this).attr("src");
				var arr=imgsrc.split("img/");
				imgsrc=imgsrc.replace(arr[1],"sx01.png");
				$(this).attr("src",imgsrc)
			})						
		}
		/*头部优惠券状态切换*/
		$(".slide_box li").click(function(){
			var html=$(this).html();
			$(this).parents(".son").find("span").text(html);
			$(this).css("color","#fd7c24").siblings().css("color","#888")
		})
	})			
</script>
<script type="text/javascript" src="./js/global.js"></script>
 </body>
	<?php mysql_close($link);?>
</html>