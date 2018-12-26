<?php
header("Content-type: text/html; charset=utf-8"); //svn
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../proxy_info.php');
require('../common/common_from.php');

$card_id = -1;	
if( !empty($_GET["card_id"]) ){
	$card_id = $configutil->splash_new($_GET["card_id"]);
}
$card_id   = passport_decrypt($card_id);
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title>明细</title>
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
		<link href="css/red_score/style.css" type="text/css" rel="stylesheet"> 
		<link rel="stylesheet" type="text/css" href="css/w_asset.css"/>
	</head>
	<body style="background: #f6f6f6;">
	
	
	<!-- body尾部放js-->
	<script type="text/javascript" src="js/jquery-1.12.1.min.js"></script>
	<script type="text/javascript">
	var customer_id 	= '<?php echo $customer_id;?>';
	var customer_id_en 	= '<?php echo $customer_id_en;?>';
	var user_id 		= '<?php echo $user_id;?>';
	var card_id 		= '<?php echo $card_id;?>';	//会员卡ID
	
	$(function(){
		searchDate(card_id);
	})
	
	function searchDate(type){
		$.ajax({
			url:'get_red_score_log.php?customer_id'+customer_id_en,
			dataType: 'json',
			type: "post",
			data:{
			  'user_id'	: user_id,
			  'type'	: 1,
			  'card_id'	: card_id
			},
			success:function(res){
				var content = "";
				
				var year = new Array();
				for( y in res ){
					year.push(y);	//获取所有的年份
				}
				year.sort().reverse();	//先升序，后倒序
				  
				var year_index = 0;	//年份下标
				for( y in res ){ 
					y = year[year_index];	//从重新排序的数组中获取年份
					year_index ++;
					var month = new Array();
					//返回的数据解密后，10月份后的月份排序会乱，需要重新排序
					for(i in res[y]){
						month.push(i);	//获取所有的月份
					}
					month.sort().reverse();	//先升序，后倒序
					
					var month_index = 0;	//月份下标
					for( id in res[y] ){
						id = month[month_index];	//从重新排序的数组中获取月份
						month_index ++;
						// console.log(id);
						var _len = res[y][id].length;
						if( year_index > 1 ){
							content += '<div class="w-month">'+y+'年'+id+'月</div>';
						} else {
							content += '<div class="w-month">'+id+'月</div>';
						}
						
						content += '</div>';
						content += '<div class="w-detail">';
						for( var i=0; i<_len; i++ ){
							content += '<div class="all">';
							content += '<div class="mingxi mm" v-for="detail in details">';
							content += '<div class="fri">';
							if( res[y][id][i].type == 1 ){
								content += '收益';
							}else if( res[y][id][i].type == 0 ){
								content += '支出';
							}
							content += '<p>'+res[y][id][i].createtime+'</p>';
							content += '</div>';
							content += '<div class="sec sec2">';
							if( res[y][id][i].type == 1 ){
								content += '+'+res[y][id][i].score+'';
							}else if( res[y][id][i].type == 0 ){
								content += '-'+res[y][id][i].score+'';
							}
							content += '</div>';
							content += '</div>';
							content += '</div>';
						}
						
						content += '</div>';
					 
					}
				}
				$('body').append(content);
			}
		});
	}
	var hides = true;
	</script>
	</body>
</html>
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
<!-- 页联系js -->
<!--引入侧边栏 start-->
<?php  include_once('float.php');?>
<!--引入侧边栏 end-->
