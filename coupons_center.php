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

require('./coupon_function.php');
$show_coupon = new Coupon_Utility();

$statu = '';//发放优惠券是否已领取或已失效
if(!empty($_GET['statu'])){
	$statu = $configutil->splash_new($_GET["statu"]);
}

$single = 0;//发放优惠券
if(!empty($_GET['single'])){
	$single = $configutil->splash_new($_GET["single"]);
}

$m = '';//首次订阅
if(!empty($_GET['m'])){
	$m = $configutil->splash_new($_GET["m"]);
	if($m == 'g'){
		$single = 1;
	}

}

$cp_id = 0;
$customer_red_id = 0;//组发放ID
$one_coupon = array();//单张优惠券
$get_coupon_data = array();//首次优惠券
$class_type = 2;
if(!empty($_GET["cp_id"])){
	$cp_id = $configutil->splash_new($_GET["cp_id"]);
	$query = "select class_type from weixin_commonshop_coupons where isvalid=true and customer_id=".$customer_id." and id=".$cp_id;
	$result = _mysql_query($query) or die('w9787 Query failed: ' . mysql_error());
	while ($row = mysql_fetch_object($result)) {
		$class_type =  $row->class_type;
	}
	if(!empty($_GET["customer_red_id"])){
		$customer_red_id = $configutil->splash_new($_GET["customer_red_id"]);
	}
}

$get_coupon_data = $show_coupon->show_coupon($customer_id,$user_id,2,$cp_id,0,$class_type,$single );
// var_dump($get_coupon_data);
for($n=0;$n<count($get_coupon_data);$n++){
	for($j=0;$j<count($get_coupon_data[$n]['data']);$j++){
		if(!empty($statu) || ( $get_coupon_data[$n]['code']>=4004 && $get_coupon_data[$n]['code']<=4006 && $cp_id>0) ){//发放优惠券失效或已领取
			$get_coupon_data[$n]['data'][$j]['c_top'] = 'top_g';
			$get_coupon_data[$n]['data'][$j]['word_color'] = 'gray';
			$get_coupon_data[$n]['data'][$j]['is_receive'] = 1;
			if($statu=='has' || ($get_coupon_data[$n]['code']>=4004 && $get_coupon_data[$n]['code']<=4006)){
				$get_coupon_data[$n]['data'][$j]['img'] = 'images/coupom_img/has_got.png';
			}elseif($statu=='fail' ){
				$get_coupon_data[$n]['data'][$j]['img'] = 'images/coupom_img/has_lost.png';
			}
		}
	}
}


?>
<!DOCTYPE html>
<html>
	<head>
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
		<link rel="stylesheet" href="css/coupon.css"  type="text/css"/>
		<link type="text/css" rel="stylesheet" href="./css/order_css/global.css" />
		<link rel="stylesheet" href="css/css_orange.css" />
		<title>领券中心</title>
	</head>
	<body style="padding-bottom: 50px;">
		</div>
		<!--<div class="coupon_top">
			<a href="javascript:history.go(-1);">
				<div class="coupon_back">
					<img src="images/coupom_img/Back.png"/><span>返回</span>
				</div>
			</a>
			<p>领券中心</p>

		</div>-->
		<div  class="content">

			<?php
				for($i=0;$i<count($get_coupon_data);$i++){
					for($j=0;$j<count($get_coupon_data[$i]['data']);$j++){
			?>
					<div class="coupon">
						<div class="cp_top <?php echo $get_coupon_data[$i]['data'][$j]['c_top'] ?>">
							<p class="p01" <?php if( $get_coupon_data[$i]['data'][$j]['MoneyType'] == 1 ){ echo 'style="font-size:1rem;"'; } ?>>
							<?php if($get_coupon_data[$i]['data'][$j]['c_class_type']==3){?><span>【免单】</span><?php }else{?><span><?php if(OOF_P != 2) echo OOF_S ?></span><?php echo $get_coupon_data[$i]['data'][$j]['Money'] ?><span><?php if(OOF_P == 2) echo OOF_S ?></span><?php }?></p>
							<p class="p02"><?php echo $get_coupon_data[$i]['data'][$j]['c_content'];?></p>
							</div>
						<div class="cp_content">
							<?php if($get_coupon_data[$i]['data'][$j]['c_class_type']==3){?>
							<p class="ct01 <?php echo $get_coupon_data[$i]['data'][$j]['word_color'] ?>">拼团团长免单券</p>
							<?php } ?>
							<p class="ct01 <?php echo $get_coupon_data[$i]['data'][$j]['word_color'] ?>">
                            <?php if($get_coupon_data[$i]['data'][$j]['c_class_type']!=3){?>
							<?php if($get_coupon_data[$i]['data'][$j]['NeedMoney']>=0){?>
								满<?php echo $get_coupon_data[$i]['data'][$j]['NeedMoney'] ?><?php echo OOF_T ?>即可使用
							<?php }else{ ?>
								不限金额使用
							<?php }} ?>
							</p>
							<p class="ct02 <?php echo $get_coupon_data[$i]['data'][$j]['word_color'] ?>">(不含邮费)</p>
							<p class="ct03"><?php echo $get_coupon_data[$i]['data'][$j]['p_content'] ?></p>
							<p class="ct04">有效期：<?php echo $get_coupon_data[$i]['data'][$i]['startline'] ?>—<?php echo $get_coupon_data[$i]['data'][$j]['deadline'] ?></p>
						</div>
						<?php if(!$get_coupon_data[$i]['data'][$j]['is_receive']){ ?>
						<div class="got_rightNow <?php echo $get_coupon_data[$i]['data'][$j]['word_color'] ?>" onclick="receiveCoupon(this,<?php echo $get_coupon_data[$i]['data'][$j]['keyid'].','.$get_coupon_data[$i]['code'] ?>,'<?php echo $get_coupon_data[$i]['msg'] ?>')">立即领取 <img src="images/coupom_img/<?php echo $get_coupon_data[$i]['data'][$j]['arrow_color'] ?>.png"/></div>
						<?php } ?>
						<?php if(!empty($get_coupon_data[$i]['data'][$j]['img'] )){ ?>
						<div class="label">
							<img src="<?php echo $get_coupon_data[$i]['data'][$j]['img'] ?>">
						</div>
						<?php } ?>
					</div>
			<?php
				}
			} ?>

		</div>
		<div style="height:50px;"></div>
		<!--领取中心没有优惠 -->
		<div class="fix" style="display:
		<?php
			$getcount = 0;
			for($v=0;$v<count($get_coupon_data);$v++){
				if(count($get_coupon_data[$v]['data'])>0){
					$getcount = 1;
					break;
				}
			}
			if($getcount>0){echo 'none';}else{echo 'block';}
		?>
		;">
			<div class="box">
				<img src="images/coupom_img/ticket.png">
				<p>没有可领优惠券哦！敬请期待吧！</p>
			</div>
		</div>
		<!--领取成功弹框 -->
		<div class="tips" style="display: none;">
			<div class="tips_box">
				<span>领取成功</span>
			</div>
		</div>
		<div class="fix02"  onclick="location.href = 'my_coupon.php?customer_id=<?php echo $customer_id_en; ?>'">
			<div class="box">
				<span>查看我的优惠券</span>
				<img src="images/coupom_img/right_btn.png">
			</div>
		</div>
		<script src="js/jquery-1.12.1.min.js" type="text/javascript"></script>
		<!--引入侧边栏 start-->
		<?php  include_once('float.php');?>
		<!--引入侧边栏 end-->
<script>
//全局参数部分
var ajax_data = {

	customer_id	:	'<?php echo $customer_id;?>',
	user_id	 	:	'<?php echo $user_id;?>',

	start 		:	8,		//读取数据开始位置
	end   		:	8,		//数据加载数量
	finished	:	0, 		//防止未加载完再次执行
	sover		:	0  		//数据是否已经加载完

}
var customer_red_id = '<?php echo $customer_red_id; ?>';
	$(function(){
		//滑动加载数据（显示的数据高度必须大于窗口高度才会触发）
		$(window).scroll(function() {

			var scrollTop = $(window).scrollTop(); 			//滑动距离
			var scrollHeight = $(document).height();  		//内容的高度
			var windowHeight = $(window).height();			//窗口高度

			if (scrollTop + windowHeight >= scrollHeight) {		//当滑动距离+内容的高度 > 窗口的高度 = 则加载数据

				loadmore();  								//加载数据的函数

			}
		});

		$(".tab .son").click(function(){
			$(this).addClass("active").siblings().removeClass("active")
		})
	})
/***************函数部分**************/

//加载更多
function loadmore(){

	if(ajax_data.finished==0 && ajax_data.sover==0)
	{


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
						op          :   'take'


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
		 for(var i = 0 ; i < data.coupon.length; i++){
			result+='<div class="coupon"><div class="cp_top '+data.coupon[i].c_top+'">';
			console.log(data.coupon[i].MoneyType);
			if( data.coupon[i].MoneyType ){
				result += '<p class="p01" style="font-size:0.12rem">';
			}else{
				result += '<p class="p01">';
			}
			result += '<span><?php if(OOF_P != 2) echo OOF_S ?></span>'+data.coupon[i].Money+'<span><?php if(OOF_P == 2) echo OOF_S ?></span>'+'</p><p class="p02">'+data.coupon[i].c_content+'</p></div><div class="cp_content">';
			if( data.coupon[i].c_class_type == 3 ){
				result+='<p class="ct01 '+data.coupon[i].word_color+'">拼团团长免单券</p>';
			}
			result+='<p class="ct01 '+data.coupon[i].word_color+'">';
				if(data.coupon[i].NeedMoney<0){
					result+='不限金额即可使用';
				}else{
					result+='满'+data.coupon[i].NeedMoney+'<?php echo OOF_T ?>即可使用';
				}
				result+='</p><p class="ct02 '+data.coupon[i].word_color+'">(不含邮费)</p><p class="ct03">'+data.coupon[i].p_content+'</p><p class="ct04">有效期：'+data.coupon[i].startline+'—'+data.coupon[i].deadline+'</p></div><div class="got_rightNow '+data.coupon[i].word_color+'" onclick="receiveCoupon(this,'+data.coupon[i].keyid+')">立即领取 <img src="images/coupom_img/'+data.coupon[i].arrow_color+'.png"/></div></div>';
		}


	ajax_data.start += data.coupon.length;	 //赋值下一次读取数据开始位置

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

		}
	},1000);

 }

function receiveCoupon(obj,cid,code,msg){//领取优惠券，obj:优惠券模板，cid:优惠券ID，code:返回码 1001正常领取，大于4000为错误，msg:错误消息
		if( 4000 < code ){
			showAlertMsgNoclose("提示",msg,"知道了");	//弹出警告
			return;
		}
		$.ajax({
			type: 'POST',
			url: 'coupon_page.php',
			data:{
					customer_id	     :	ajax_data.customer_id,
					user_id	 		 :	ajax_data.user_id,
					cid	 			 :	cid,//选取的优惠券
					customer_red_id	 :	customer_red_id,//选取的优惠券
					op         		 :   'receive'
			},
			dataType: 'json',
			success: function(data){
				if(data.code==1000){
					$(obj).parent('.coupon').append('<div class="label"><img src="images/coupom_img/has_got.png"/></div>');
                    if(data.class_type==3){
                        $(obj).parent('.coupon').children('.cp_top').children('.p01').html('<span>【免单】</span>');
                    }else{
                        $(obj).parent('.coupon').children('.cp_top').children('.p01').html('<span><?php if(OOF_P != 2) echo OOF_S ?></span>'+data.CouponMoney+'<span><?php if(OOF_P == 2) echo OOF_S ?></span>');
                    }
					$(obj).parent('.coupon').children('.cp_top').addClass('top_g');
					$(obj).parent('.coupon').children('.cp_content').children('.ct01').addClass('gray');
					$(obj).parent('.coupon').children('.cp_content').children('.ct02').addClass('gray');
					$(obj).remove();
					$('.tips').css('display','block');
					setTimeout(function(){$('.tips').css('display','none')},1000);
				}else{
					if(data.code==4001){
						showAlertMsg("提示","当前优惠券领取数量已达上限","知道了");	//弹出警告
						return;
					}
					else if(data.code==4002){
						showAlertMsg("提示","你当前可领取数量已达上限","知道了");	//弹出警告
						return;
					}
					else if(data.code==4003){
						showAlertMsg("提示","你当前每天可领取数量已达上限","知道了");	//弹出警告
						return;
					}
					else if(data.code==4004){
						showAlertMsg("提示","未达到领取身份","知道了");	//弹出警告
						return;
					}
				}
			},
			error: function(data){
				alert('error!');
				return;
			}
		});
}
 /***************函数部分**************/
</script>
<?php require('../common/share.php'); ?>
<script type="text/javascript" src="./js/global.js"></script>
</body>
</html>
