<?php
header("Content-type: text/html; charset=utf-8");
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
_mysql_query("SET NAMES UTF8");
require('../proxy_info.php');
require('select_skin.php');
require('../common/own_data.php');
$my_data = new my_data();

//头文件----start
require('../common/common_from.php');
//头文件----end


$is_showuplevel    	=  1;
$is_showcustomer    =  1;
$p_customer         =  '省代';
$c_customer         =  '市代';
$a_customer         =  '区代';
$is_diy_area        =  0;
$diy_customer       =  '自定义区域';
$rule       		=  '';
$consume_money      =  0;
$p_condition      	=  '';
$c_condition      	=  '';
$a_condition      	=  '';
$diy_condition      =  '';
$query = "select p_customer,c_customer,a_customer,is_showcustomer,is_showuplevel,is_diy_area,diy_customer,rule,consume_money,p_condition,c_condition,a_condition,diy_condition from weixin_commonshop_team where isvalid = true and customer_id = ".$customer_id." limit 0,1";
$result = _mysql_query($query) or die ("query failed".mysql_error());
while( $row = mysql_fetch_object($result) ){
	$is_showuplevel    = $row -> is_showuplevel;		//是否显示升级
	$is_showcustomer   = $row -> is_showcustomer;	    //是否开启区域代理自定义
	$p_customer        = $row -> p_customer;			//省代自定义名称
	$c_customer        = $row -> c_customer;			//市代自定义名称
	$a_customer        = $row -> a_customer;			//区代自定义名称
	$is_diy_area       = $row -> is_diy_area;			//开启自定义区域
	$diy_customer      = $row -> diy_customer;	        //自定义级别自定义名称
	$rule     		   = $row -> rule;	       			//规则
	$consume_money     = $row -> consume_money;	       	//无限级消费奖励最低金额
	$p_condition       = $row -> p_condition;	       	//省代升级条件
	$c_condition       = $row -> c_condition;	       	//市代升级条件
	$a_condition       = $row -> a_condition;	       	//区代升级条件
	$diy_condition     = $row -> diy_condition;	       	//自定义区域升级条件
}

if( $is_showcustomer ){	//开启区域代理自定义名称
	if( empty($p_customer) ){
		$p_customer = '省代';
	}
	if( empty($c_customer) ){
		$c_customer = '市代';
	}
	if( empty($a_customer) ){
		$a_customer = '区代';
	}
	if( empty($diy_customer) ){
		$diy_customer = '自定义区域';
	}
}else{
	$p_customer   = '省代';
	$c_customer   = '市代';
	$a_customer   = '区代';
	$diy_customer = '自定义区域';
}

$isAgent 	= -1;	//5：区代，6：市代，7：省代，8:自定义区域
$is_consume = 0;	//是否无限级消费奖励
$query2 = "select isAgent,is_consume from promoters where user_id=".$user_id." and customer_id=".$customer_id." and isvalid=true and status=1";
$result2 = _mysql_query($query2) or die('query failed2'.mysql_error());
while( $row2 = mysql_fetch_object($result2) ){
	$isAgent 	= $row2 -> isAgent;
	$is_consume = $row2 -> is_consume;
}

$t_id 		 = -1;		//区域代理申请ID
$aplay_grate = -1;		//0：区代；1：市代；2：省代 3：自定义
$status 	 = -1;		//状态：0审核，1确认
$query3 = "select id,aplay_grate,status from weixin_commonshop_team_aplay where isvalid=true and customer_id=".$customer_id." and aplay_user_id=".$user_id." limit 0,1";
$result3 = _mysql_query($query3) or die('query failed3'.mysql_error());
while( $row3 = mysql_fetch_object($result3) ){
	$t_id 		 = $row3 -> id;
	$aplay_grate = $row3 -> aplay_grate;
	$status 	 = $row3 -> status;
}

$is_team	  	= 0;	//是否开启团队奖励
$is_shareholder	= 0;	//是否开启股东奖励
$query5 = "select is_team,is_shareholder from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
$result5 = _mysql_query($query5) or die('Query failed5' . mysql_error());
while ($row5 = mysql_fetch_object($result5)) {
	$is_team 	  	= $row5 -> is_team;
	$is_shareholder = $row5 -> is_shareholder;
}
$upgrade_mode = 1;	//显示模式，1：显示比当前等级高的身份，2：显示下一级身份
$query_extend = "SELECT upgrade_mode FROM weixin_commonshops_extend WHERE customer_id=".$customer_id." AND isvalid=true";
$result_extend = _mysql_query($query_extend) or die('Query_extend failed:'.mysql_error());
while( $row_extend = mysql_fetch_object($result_extend) ) {
	$upgrade_mode = $row_extend -> upgrade_mode;
}

//是否申请过代理商
$query8 = "select id from weixin_commonshop_applyagents where isvalid=true and status=0 and user_id=".$user_id;
$result8 = _mysql_query($query8) or die('query failed8'.mysql_error());
$ag_id = -1;
while( $row8 = mysql_fetch_object($result8) ){
	$ag_id = $row8 -> id;
}
//是否申请过供应商
$query9 = "select id from weixin_commonshop_applysupplys where isvalid=true and status=0 and user_id=".$user_id;
$result9 = _mysql_query($query9) or die('query failed9'.mysql_error());
$as_id = -1;
while( $row9 = mysql_fetch_object($result9) ){
	$as_id = $row9 -> id;
}

if( $ag_id>0 || $as_id>0 || $isAgent==1 || $isAgent==3 ){
	$is_showuplevel = 0;
}

$is_update_consume = 0;			//是否升级无限级消费
$consume_name	   = '团队奖';	//无限级名称
if( $is_team == 1 && $is_shareholder == 0 ){
	$is_update_consume = 1;
	$d_name			   = '';	//一级股东自定义名称
	$query_consume = "select d_name from weixin_commonshop_shareholder where customer_id=".$customer_id." and isvalid=true";
	$result_consume = _mysql_query($query_consume) or die('Query_comsume failed:'.mysql_error());
	while( $row_consume = mysql_fetch_object($result_consume) ){
		$d_name = $row_consume -> d_name;
	}
	if( !empty($d_name) ){
		$consume_name = $d_name;
	}
}
$query_money = "select total_money from my_total_money where user_id=".$user_id." and isvalid=true limit 1";
$total_pay_money = 0;	//累积消费
$result_money = _mysql_query($query_money) or die('Query_money failed:' . mysql_error());
while( $row_money = mysql_fetch_object($result_money) ) {
	$total_pay_money = $row_money -> total_money;		//个人消费金额
}
$total_pay_money = round($total_pay_money,2);

/* 获取升级条件 start */
$query_condition = 'SELECT up_type,identity,algebra,num FROM weixin_commonshop_team_shareholder_condition WHERE customer_id='.$customer_id." AND isvalid=true AND class=1";

$condition 			= array();	//已选的升级条件
$show_name 			= '';		//显示身份名称
switch( $isAgent ){	//当前身份
	case 5:	//区代
		$show_name 		 = $a_customer;	//显示当前身份名称
		//获取升级条件
		$c_condition_arr = explode('_',$c_condition);
		$p_condition_arr = explode('_',$p_condition);
		for( $c = 0; $c < 6; $c++ ){
			if( 1 == $c_condition_arr[$c] ){
				$condition[3] .= ($c+1).',';
			}
			if( 1 == $p_condition_arr[$c] ){
				$condition[4] .= ($c+1).',';
			}
		}
	break;

	case 6:	//市代
		$show_name 		 = $c_customer;	//显示当前身份名称
		//获取省代的升级条件
		$p_condition_arr = explode('_',$p_condition);
		for( $p = 0; $p < 6; $p++ ){
			if( 1 == $p_condition_arr[$p] ){
				$condition[4] .= ($p+1).',';
			}
		}
	break;

	case 7:	//省代
		$show_name = $p_customer;	//显示身份名称
	break;

	case 8:	//自定义等级
		$show_name 		 = $diy_customer;	//显示当前身份名称
		//获取升级条件
		$a_condition_arr = explode('_',$a_condition);
		$c_condition_arr = explode('_',$c_condition);
		$p_condition_arr = explode('_',$p_condition);
		for( $a = 0; $a < 6; $a++ ){
			if( 1 == $a_condition_arr[$a] ){
				$condition[2] .= ($a+1).',';
			}
			if( 1 == $c_condition_arr[$a] ){
				$condition[3] .= ($a+1).',';
			}
			if( 1 == $p_condition_arr[$a] ){
				$condition[4] .= ($a+1).',';
			}
		}
	break;

	default:
		if ( 1 == $is_diy_area ) {
			//获取自定义等级的升级条件
			$p_condition_arr 	= explode('_',$p_condition);
			$c_condition_arr 	= explode('_',$c_condition);
			$a_condition_arr 	= explode('_',$a_condition);
			$diy_condition_arr 	= explode('_',$diy_condition);
			for( $d = 0; $d < 6; $d++ ){
				if( 1 == $diy_condition_arr[$d] ){
					$condition[1] .= ($d+1).',';
				}
				if( 1 == $a_condition_arr[$d] ){
					$condition[2] .= ($d+1).',';
				}
				if( 1 == $c_condition_arr[$d] ){
					$condition[3] .= ($d+1).',';
				}
				if( 1 == $p_condition_arr[$d] ){
					$condition[4] .= ($d+1).',';
				}
			}
		} else {
			//获取升级条件
			$a_condition_arr = explode('_',$a_condition);
			$c_condition_arr = explode('_',$c_condition);
			$p_condition_arr = explode('_',$p_condition);
			for( $a = 0; $a < 6; $a++ ){
				if( 1 == $a_condition_arr[$a] ){
					$condition[2] .= ($a+1).',';
				}
				if( 1 == $c_condition_arr[$a] ){
					$condition[3] .= ($a+1).',';
				}
				if( 1 == $p_condition_arr[$a] ){
					$condition[4] .= ($a+1).',';
				}
			}
		}
	break;
}
$condition_arr = array();	//储存升级条件
for ( $qc = 1 ; $qc < 5 ; $qc++ ) {
	if ( $condition[$qc] == '' ) {	//没有升级条件
		continue;
	} else {
		$condition[$qc] = substr($condition[$qc],0,-1);	//去掉最后的逗号
	}
	$query_condition_vc = $query_condition.' AND level='.$qc.' AND up_type in ('.$condition[$qc].') ORDER BY level ASC,up_type ASC';	//拼接sql语句
	// echo $query_condition_vc;
	$result_condition_vc = _mysql_query($query_condition_vc) or die('Query_condition_vc failed:'.mysql_error());
	while( $row_condition_vc = mysql_fetch_object($result_condition_vc) ){
		$up_type 	= $row_condition_vc -> up_type;	//升级条件类型
		$identity 	= $row_condition_vc -> identity;	//身份
		$algebra 	= $row_condition_vc -> algebra;	//推广下面级数
		$num 		= $row_condition_vc -> num;		//数量
		
		$condition_arr[$qc][$up_type]['identity'] = $identity;
		$condition_arr[$qc][$up_type]['algebra']  = $algebra;
		$condition_arr[$qc][$up_type]['num'] 	  = $num;
	}
			
	if( 2 == $upgrade_mode ){	//模式二只显示下一级
		break;
	}
	// var_dump($condition_arr[$qc]).'<br>';
}
// var_dump($condition_arr);
$condition_bit = array('','人','人','单','元','元','元');	//升级条件的单位
/* 获取升级条件 end */

/* 整理升级条件 start */
$data_arr = array();	//储存需要显示的数据

for ( $ca = 1; $ca < 5 ; $ca++ ) {
	$is_can_upgrade  = 1;		//是否可以升级，0：否，1：是
	$next_level_name = '';		//下一级身份名称
	$upgrade_level	 = 0;		//下一级身份等级
	if ( $condition_arr[$ca] == '' ){	//没有选择的升级条件为空，跳到下个循环
		continue;
	} else {
		switch( $ca ){
			case 1:
				$next_level_name = $diy_customer;
				$upgrade_level = 3;
			break;
			
			case 2:
				$next_level_name = $a_customer;
				$upgrade_level = 0;
			break;
			
			case 3:
				$next_level_name = $c_customer;
				$upgrade_level = 1;
			break;
			
			case 4:
				$next_level_name = $p_customer;
				$upgrade_level = 2;
			break;
		}
		$data_arr[$ca]['next_level_name'] 	= $next_level_name;	//下一级身份名称
		$data_arr[$ca]['upgrade_level'] 	= $upgrade_level;	//下一级身份等级
		for ( $caa = 1; $caa < 7 ; $caa++ ) {
			if( $condition_arr[$ca][$caa] != '' ){
				$percentage = 100;	//进度条显示
				$show_num 	= 0;	//显示数量
				$show_type 	= '';	//显示条件名称
				
				switch( $caa ){
					case 1:
						$promoter_count = $my_data->numberOfSubordinate($customer_id,$user_id,$condition_arr[$ca][$caa]['identity'],1);
						if ( $condition_arr[$ca][$caa]['num'] > 0 ) {
							$percentage = round(100*($promoter_count/$condition_arr[$ca][$caa]['num']),2);	//进度条显示
						}
						$show_type 	= '推广成交量';
						$show_num 	= $promoter_count;
						//是否可以升级
						if ( $promoter_count < $condition_arr[$ca][$caa]['num'] ) {
							$is_can_upgrade = 0;
						}
					break;
					
					case 2:
						//团队人数
						$team_people = $my_data->numberOfSubordinate($customer_id,$user_id,$condition_arr[$ca][$caa]['identity'],$condition_arr[$ca][$caa]['algebra']);
						if ( $condition_arr[$ca][$caa]['num'] > 0 ) {
							$percentage = round(100*($team_people/$condition_arr[$ca][$caa]['num']),2);	//进度条显示
						}
						$show_type 	= '总成交量';
						$show_num 	= $team_people;
						//是否可以升级
						if ( $team_people < $condition_arr[$ca][$caa]['num'] ) {
							$is_can_upgrade = 0;
						}
					break;
					
					case 3:
						//团队订单数
						$team_order = $my_data->CountTeamOrder($user_id,$condition_arr[$ca][$caa]['algebra'],$condition_arr[$ca][$caa]['identity']);
						if ( $condition_arr[$ca][$caa]['num'] > 0 ) {
							$percentage = round(100*($team_order/$condition_arr[$ca][$caa]['num']),2);	//进度条显示
						}
						$show_type 	= '总订单量';
						$show_num 	= $team_order;
						//是否可以升级
						if ( $team_order < $condition_arr[$ca][$caa]['num'] ) {
							$is_can_upgrade = 0;
						}
					break;
					
					case 4:
						//团队销售额
						$team_money = $my_data->CountTeamManaged($user_id,$condition_arr[$ca][$caa]['algebra'],$condition_arr[$ca][$caa]['identity']);
						if ( $condition_arr[$ca][$caa]['num'] > 0 ) {
							$percentage = round(100*($team_money/$condition_arr[$ca][$caa]['num']),2);	//进度条显示
						}
						$show_type 	= '总销售额';
						$show_num 	= $team_money;
						//是否可以升级
						if ( $team_money < $condition_arr[$ca][$caa]['num'] ) {
							$is_can_upgrade = 0;
						}
					break;
					
					case 5:
						//个人累积消费
						if ( $condition_arr[$ca][$caa]['num'] > 0 ) {
							$percentage = round(100*($total_pay_money/$condition_arr[$ca][$caa]['num']),2);	//进度条显示
						}
						$show_type 	= '个人累积消费';
						$show_num 	= $total_pay_money;
						//是否可以升级
						if ( $total_pay_money < $condition_arr[$ca][$caa]['num'] ) {
							$is_can_upgrade = 0;
						}
					break;
					
					case 6:
						//团队直推销售额
						$team_direct_money = $my_data->CountTeamManaged($user_id,1,1);
						if ( $condition_arr[$ca][$caa]['num'] > 0 ) {
							$percentage = round(100*($team_direct_money/$condition_arr[$ca][$caa]['num']),2);	//进度条显示
						}
						$show_type 	= '推广销售额';
						$show_num 	= $team_direct_money;
						//是否可以升级
						if ( $team_direct_money < $condition_arr[$ca][$caa]['num'] ) {
							$is_can_upgrade = 0;
						}
					break;
					
					default:
					break;
				}
				$data_arr[$ca]['is_can_upgrade'] 				= $is_can_upgrade;
				$data_arr[$ca]['condition'][$caa]['percentage'] = $percentage;
				$data_arr[$ca]['condition'][$caa]['show_type'] 	= $show_type;
				$data_arr[$ca]['condition'][$caa]['show_num'] 	= $show_num;
				$data_arr[$ca]['condition'][$caa]['need_num'] 	= $condition_arr[$ca][$caa]['num'];
			}
		}
	}
	if ( 2 == $upgrade_mode ){	//模式二只显示下一级
		break;
	}
}
/* 整理升级条件 end */
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title></title>
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
		<link type="text/css" rel="stylesheet" href="./css/goods/global.css" />
		<link type="text/css" rel="stylesheet" href="./css/order_css/global.css" />
		<?php if ( 1 == $upgrade_mode ) {?>
		<link type="text/css" rel="stylesheet" href="./css/goods/quyudailishang1-1.css" />
		<link href="./css/goods/style.css" rel="stylesheet" type="text/css">
		<?php } else { ?>
		<link href="./css/goods/common.css" type="text/css" rel="stylesheet">
		<link href="./css/goods/style.css" rel="stylesheet" type="text/css">
		<?php }?>
		<link type="text/css" rel="stylesheet" href="./css/css_<?php echo $skin ?>.css" /> 
	</head>
	<style>
	/*--dialog----*/
	.dlg{width:100%;top:0px; overflow:auto;padding:0 10px;}
	.close_button{width:100%; height:50px;text-align:right; padding-right:40px;}
	.close_button img{width:30px; height:50px;}
	.dlg_content{width:100%;  background:white;border-radius:3px;padding:10px;}
	.dlg_content_row1{width:100%; text-align:center; }
	.dlg_content_row1 span{font-size:14px; line-height:40px;}
	.dlg_content_row2{width:100%;}
	.dlg_content_row2 span{color:grey;line-height:25px;font-size:12px;}
	/*--dialog----*/
	</style>
	<body>
		<?php
			$show_header_type = 2;	//头部显示
			require('my_privilege_header.php');
		?>
		<div class="cont">
		<?php
			if ( $upgrade_mode == 1 ) {
		?>
			<div class="leftLine" style="height:333px;z-index:-111;"></div>
		<?php
			}
		?>
			<p class="cl_888 p01" style="margin:0 5%;"><span onclick="showAreaAgentMsg()">区域规则</span></p>
		<?php
			if ( ( $isAgent == 7 && $is_update_consume == 0 ) || ( $isAgent == 7 && $is_update_consume && $is_consume > 0) ) {
		?>
			<div class="killer">
				<img src="images/info_image/person_icon.png">
				<p class="p01" style="text-align:center;">恭喜你!</p>
				<p class="p03">您现在已经是举世无双的高手了，</p>
				<p class="p03">赶紧去享受一下您的权益吧！</p>
			</div>	
		<?php
			} else {
				if ( $upgrade_mode == 2 ) {
					if( $is_update_consume && $is_consume == 0 ) {	//无限级升级
						$percentage = 100;
						if( $consume_money > 0 ){
							$percentage = round(100*($total_pay_money/$consume_money),2);	//进度条显示
						}
		?>
			<div class="container" style="margin-bottom: 20px;">
				<div class="er"  style="max-width:133px;width:40%;min-width:113px">
					<div class="table">
						<img class="person_img" src="images/info_image/person01.png">
						<p class="cl_333 p03"><?php echo $consume_name;?></p>
					</div>
				</div>
				<div class="msg_box">
					<div class="prograss_bar">
						<div class="Percentage" style="width:<?php echo $percentage;?>%;max-width:100%;min-width:0;"></div>
					</div>
					<div class="table msg" style="font-size:11px;">
						<p class="cl_o p04">个人累积消费</p>
						<p class="cl_o p05"><?php echo $total_pay_money;?>/<?php echo $consume_money;?>元</p>
					</div>
				</div>
				<?php if( $is_consume ){?>
				<div class="buy_btn o_bg" style=" background:grey;border:none;">
					已升级
				</div>
				<?php }else if( $total_pay_money >= $consume_money ){?>
				<div class="buy_btn o_bg update_consume" data-grate="4" >
					升级
				</div>
				<?php }else{?>
				<div class="buy_btn o_bg" style=" background:grey;border:none;">
					升级
				</div>
				<?php }?>
			</div>
			<?php
				}
			?>
			<?php
				for ( $i = 1 ; $i < 5 ; $i++ ){	//输出身份和升级条件
					if ( $data_arr[$i] != '' ){
			?>
			<div class="container">
				<p class="p02 cl_888">下一级</p>
				<div class="er"  style="max-width:133px;width:40%;min-width:113px">
					<div class="table">
						<img class="person_img" src="images/extends_image/tgy03_w.png">
						<p class="cl_333 p03"><?php echo $data_arr[$i]['next_level_name'];?></p>
					</div>
				</div>
			<?php
				for ( $j = 1; $j < 7 ; $j++ ){	//输出升级条件
					if ( $data_arr[$i]['condition'][$j] != '' ){
			?>
				<div class="msg_box">
					<div class="prograss_bar">
						<div class="Percentage" style="width:<?php echo $data_arr[$i]['condition'][$j]['percentage'];?>%;max-width:100%;min-width:0;"></div>
					</div>
					<div class="table msg" style="font-size:11px;">
						<p class="cl_o p04"><?php echo $data_arr[$i]['condition'][$j]['show_type'];?></p>
						<p class="cl_o p05"><?php echo $data_arr[$i]['condition'][$j]['show_num'];?>/<?php echo $data_arr[$i]['condition'][$j]['need_num'].$condition_bit[$j];?></p>
					</div>
				</div>
			<?php
					}
				}
			?>
			<?php
				//申请按钮判断
				if ( 1 == $is_team && 1 == $data_arr[$i]['is_can_upgrade'] && 1 == $is_showuplevel ) {
					if( $data_arr[$i]['upgrade_level'] == $aplay_grate && 0 == $status ) {
			?>
				<div class="buy_btn o_bg">
					审核中
				</div>
			<?php
					} else {
			?>
				<div class="buy_btn o_bg apply_btn" data-grate="<?php echo $data_arr[$i]['upgrade_level'];?>">
					申请
				</div>
			<?php
					}
				} else {
			?>
				<div class="buy_btn o_bg" style="background-color:grey;border:none;">
					申请
				</div>
			<?php
				}
			?>
			</div>
		<?php
					}
				}
			} else {
		?>
		<?php if( $is_update_consume ){	//无限级升级?>
		<div class="content-wrapper">
			<div class="leftLine" style="height: 372px;"></div>
			<div class="content-main">	
				<div class="m-chatting-body">		
					<div class="m-chatting-content">			
						<div class="content-row1">				
							<img src="./images/goods_image/20160050404.png" width="17" height="17">				
							<span><?php echo $consume_name;?></span>			
						</div>			
						<div class="content-row2">
							<div class="content-row2-item">					
								<div class="m-progressbar-body">						
									<div class="m-progressbar-content" style="width:<?php if(0==$consume_money){echo '100';}else{echo round(100*($total_pay_money/$consume_money),2);}?>%;max-width:100%;min-width:0;"></div>					
								</div>					
								<div class="m-progressbar-remark">						
									<span>个人累积消费</span>						
									<div class="m-progressbar-remark-right">							
										<span><font><?php echo $total_pay_money;?></font>/<font><?php echo $consume_money;?></font>元</span>						
									</div>					
								</div>				
							</div>
							<?php if( $is_consume ){?>
							<div class="shengji_button" style=" background:grey;">
								<span>已升级</span>
							</div>
							<?php }else if( $total_pay_money >= $consume_money ){?>
							<div class="shengji_button update_consume" data-grate="4" >
								<span>升级</span>
							</div>
							<?php }else{?>
							<div class="shengji_button" style=" background:grey;">
								<span>升级</span>
							</div>
							<?php }?>
						</div>		
					</div>	
				</div>
			</div>
		</div>
		<?php 	}
				$check_div_show = 0;
				for ( $i = 1 ; $i < 5 ; $i++ ){	//输出身份和升级条件
					if ( $data_arr[$i] != '' ){
			?>
		<div class="content-wrapper" style="overflow:hidden;">
			<div class="leftLine" style="height: 600px;"></div>
			<div class="content-main">
			<?php
				if ( $check_div_show || ( $check_div_show == 0 && $is_update_consume ) ) {
			?>
				<div class="content-main-title"></div>	
			<?php
				}
			?>
				<div class="m-chatting-body">		
					<div class="m-chatting-content">			
						<div class="content-row1">				
							<img src="./images/goods_image/20160050303.png" width="14" height="17">
							<span><?php echo $data_arr[$i]['next_level_name'];?></span>			
						</div>	
						<div class="content-row2">	
						<?php
							for ( $j = 1; $j < 7 ; $j++ ){	//输出升级条件
								if ( $data_arr[$i]['condition'][$j] != '' ){
						?>
							<div class="content-row2-item">					
								<div class="m-progressbar-body">						
									<div class="m-progressbar-content" style="width:<?php echo $data_arr[$i]['condition'][$j]['percentage'];?>%;max-width:100%;min-width:0;"></div>					
								</div>					
								<div class="m-progressbar-remark">						
									<span><?php echo $data_arr[$i]['condition'][$j]['show_type'];?></span>						
									<div class="m-progressbar-remark-right">							
										<span><font><?php echo $data_arr[$i]['condition'][$j]['show_num'];?></font>/<font><?php echo $data_arr[$i]['condition'][$j]['need_num'];?></font><?php echo $condition_bit[$j];?></span>						
									</div>					
								</div>				
							</div>
						<?php 	}
							}
							//申请按钮判断
							if ( 1 == $is_team && 1 == $data_arr[$i]['is_can_upgrade'] && 1 == $is_showuplevel ) {
								if( $data_arr[$i]['upgrade_level'] == $aplay_grate && 0 == $status ) {
						?>
							<div class="shengji_button">
								<span>审核中</span>
							</div>
						<?php
								} else {
						?>
							<div class="shengji_button apply_btn" data-grate="<?php echo $data_arr[$i]['upgrade_level'];?>" >
								<span>申请</span>
							</div>
						<?php
								}
							} else {
						?>
							<div class="shengji_button" style=" background:grey;">
								<span>申请</span>
							</div>
						<?php
							}
						?>
						</div>		
					</div>	
				</div>
			</div>
		</div>
		<?php
					$check_div_show = 1;
					}
				}
				}
			}
		?>
		</div>
		<div class="am-share shangpin-dialog dlg" style="z-index:11111;box-sizing:border-box;">
		  <!--dialog rect-->
		</div>
		<div id="rule" style="display:none;"><?php echo $rule;?></div>
	</body>
<script type="text/javascript" src="./assets/js/jquery.min.js"></script>
<script type="text/javascript" src="./js/loading.js"></script>
<script src="./js/jquery.ellipsis.js"></script>
<script src="./js/jquery.ellipsis.unobtrusive.js"></script>
<script src="./js/goods/global.js"></script>
<script src="./js/global.js"></script>
<script src="./js/goods/area_agent.js"></script>
<script>
var customer_id    = '<?php echo $customer_id;?>';
var customer_id_en = '<?php echo $customer_id_en;?>';
var post_data   = new Array();
var post_object  = new Array();
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
<!-- 页联系js -->
<!--引入侧边栏 start-->
<?php  
require_once('../common/utility_setting_function.php');
$fun = "area_agent";
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
</html>
