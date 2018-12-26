<?php
//V8.0 详情页
/*
header("Location:personal_center.php");
exit();
*/
//停止V8.0之前的代码
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../common/utility.php');
require('../common_shop/common/cookie.php');
require('./tax_function.php');			//行邮税方法

$totalInfo = isset($_COOKIE['totalInfo']) ? $_COOKIE['totalInfo'] : "";
$len = (int)explode("|", $totalInfo)[0];//购物车数量显示


//判断是否从PC商城进入
if ($_COOKIE['pcshop'] != ""){
    $pcshop = $_COOKIE['pcshop'];
    setcookie("pcshop",null);
    header('Location:'.$pcshop."/shop/index.php/Home/My/orderList");
}

$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
_mysql_query("SET NAMES UTF8");
require('../proxy_info.php');
// require('../common/jssdk.php');    //导入JS SDK配置
require('select_skin.php');
//头文件----start
require('../common/common_from.php');
//头文件----end

/*$jssdk = new JSSDK($customer_id);           //实例化
$signPackage = $jssdk->GetSignPackage();*/

//if($_GET['yundian_id']>0){
//    //查询云店店主id
//    $sql_yundian = "SELECT user_id FROM ".WSY_USER.".weixin_yundian_keeper where id = '{$_GET['yundian_id']}'";
//    $result_yundian = _mysql_query($sql_yundian) or die('sql_yundian failed:'.mysql_error());
//    while($row_yundian = mysql_fetch_object($result_yundian)){
//        $user_id_yundian 	  = $row_yundian->user_id;
//    }
//    if($user_id_yundian == $user_id){
//        $yundian_check = 1;
//        //云店店主查看平台订单
//        //查询今日订单量，今日订单总额，总订单量，总订单金额
//        $sql1 = "SELECT batchcode as num,sendstatus,paystatus,status,totalprice,paytime,paystatus,aftersale_state,aftersale_type,return_status,aftersale_state FROM weixin_commonshop_orders where isvalid = true and customer_id = '{$customer_id}' and yundian_id = '{$_GET['yundian_id']}' and yundian_self = 1 group by batchcode";
//        $result1 = _mysql_query($sql1) or die('$sql1 failed:'.mysql_error());
//        while($row1 = mysql_fetch_object($result1)){
//            $res_all[]	  = $row1;
//        }
//        if($res_all){
//            foreach($res_all as $k => $v){
//                if($v['paytime'] > $todayStart && $v['paytime'] <= time()){//今天时段
//                    $res_today[] = $v;
//                }
//                $all_money += $v['totalprice'];
//            }
//            $money = 0;
//            if($res_today){
//                foreach($res_today as $k => $v){
//                    $money += $v['totalprice'];
//                }
//            }
//            $today = array('today_num'=>count($res_today),'today_trade'=>$money);
//            $all   = array('all_num'=>conut($res_all),'all_trade'=>$all_money);
//        }
//    }
//}

$now=date("Y-m-d H:i:s");

//$new_baseurl = BaseURL."back_commonshop/";
$new_baseurl = "http://".$http_host; //新商城图片显示
if(!preg_match('/^\d+$/i', $customer_id)){			
	$customer_id = passport_decrypt($customer_id);
}

if(!empty($_GET["apptype"])){
	$apptype=$configutil->splash_new($_GET["apptype"]);
}else{
	if(!empty($_SESSION["apptype".$customer_id])){
		$apptype=$_SESSION["apptype".$customer_id];
	}
}
$currtype= 1;
if(!empty($_GET["currtype"])){
	$currtype = $_GET["currtype"];
}
$search_name = '';
if(!empty($_GET["search_name"])){
	$search_name = $_GET["search_name"];
}
$pagenum = 1;

if($return_type == 0)$as_tip = "已退货完成";
$yundian =$_GET['yundian'];
if($yundian != -1 && !empty($yundian)){
//判断传入的云店ID数据库是否存在，不存在的话就为-1平台
	$query = "select id from ".WSY_USER.".weixin_yundian_keeper where id=".$yundian." and customer_id=".$customer_id." and isvalid=true ";
	$result    = _mysql_query($query) or die("L13 sql_d failed:".mysql_error());
	while($row = mysql_fetch_object($result)){
		if(!empty($row->id)){
			$is_exist_yundian=1;
		}
	}

	if(empty($is_exist_yundian)){
		$yundian=-1;
	}
}

///*当云店ID存在时，判断进入的用户是店主，还是游客，店主的orderlist则显示所有游客在该云店下单的订单，游客的orderlist则显示该游客在该云店下的单*/
//$yundian_identify = '';
//if($yundian > -1 && $yundian != 0){
//	$yundian_identify = 'yundian_user';
//
//	$query_yundian_identify = "select id as keeper_id from ".WSY_USER.".weixin_yundian_keeper where user_id=".$user_id." and customer_id=".$customer_id." and isvalid=true and id='".$yundian."'";	//首先判断进来的用户是否为当前云店的店主身份
//	$res_yundian_identify = _mysql_query($query_yundian_identify) or die('yundian_keeper failed: ' . mysql_error());
//	while( $row_yundian_identify = mysql_fetch_object($res_yundian_identify) ){
//		if(!empty($row_yundian_identify->keeper_id)){
//			$yundian_identify = 'yundian_keeper';
//		}
//	}
//}

//var_dump($yundian_identify);
//查询是否开启订单售后维权----start
	$is_orderActivist = -1;//订单售后维权开关 0、关闭 1、开启
	$is_receipt		  =  0;//
	$sql_order = "select is_orderActivist,is_receipt from weixin_commonshops_extend where isvalid=true and customer_id=".$customer_id;
	$result_order = _mysql_query($sql_order) or die('sql_score failed:'.mysql_error());
	while($row_order = mysql_fetch_object($result_order)){
		$is_receipt 	  = $row_order->is_receipt;
		$is_orderActivist = $row_order->is_orderActivist;
	}
	

	/* 查询不同状态的订单数量 ---create by hzq */
	$wait_pay_count = 0;
	$wait_send_count = 0;
	$wait_accept_count = 0;
	$wait_evaluate_count = 0;
	$after_sale_count = 0;

	if($search_name == ''){     //订单产品查询无须查数量  ---by whl---  18/3/5
		//待付款
		$wait_pay_sql = " select count(batchcode) as datacount from weixin_commonshop_orders where isvalid = true and customer_id=" . $customer_id . "  and is_collageActivities!=2 and status = 0  and paystatus = 0 and is_pay_on_delivery != 1";
//		if($yundian > 0){
//			if($yundian_identify == 'yundian_keeper'){				//云店店主的列表显示
//				$wait_pay_sql .= " and yundian_id=".$yundian." and yundian_self=0 ";
//			}else if($yundian_identify == 'yundian_user'){			//云店游客的列表显示
//				$wait_pay_sql .= " and yundian_id=".$yundian." and user_id='" . $user_id."' ";
//			}
//		}else{
			$wait_pay_sql .= " and user_id='" . $user_id."' ";
//		}
		$wait_pay_result = _mysql_query($wait_pay_sql) or die("Query sql_count failed : ".mysql_error());
		if($row_wait_pay = mysql_fetch_object($wait_pay_result)){
			$wait_pay_count = $row_wait_pay->datacount;
		}

		//待发货
		$wait_send_sql = " select count(batchcode) as datacount from weixin_commonshop_orders where isvalid = true and customer_id=" . $customer_id . "  and is_collageActivities!=2  and (paystatus=1 or is_pay_on_delivery = 1) and status = 0 and sendstatus = 0 ";
//		if($yundian > 0){
//			if($yundian_identify == 'yundian_keeper'){				//云店店主的列表显示
//				$wait_send_sql .= " and yundian_id=".$yundian." and yundian_self=0 ";
//			}else if($yundian_identify == 'yundian_user'){			//云店游客的列表显示
//				$wait_send_sql .= " and yundian_id=".$yundian." and user_id='" . $user_id."' ";
//			}
//		}else{
			$wait_send_sql .= " and user_id='" . $user_id."' ";
//		}
		$wait_send_result = _mysql_query($wait_send_sql) or die("Query sql_count failed : ".mysql_error());
		if($row_wait_send = mysql_fetch_object($wait_send_result)){
			$wait_send_count = $row_wait_send->datacount;
		}

		//待收货
		$wait_accept_sql = " select count(batchcode) as datacount from weixin_commonshop_orders where isvalid = true and customer_id=" . $customer_id . "  and is_collageActivities!=2  and (paystatus=1 or is_pay_on_delivery = 1) and status = 0 and sendstatus = 1";
//		if($yundian > 0){
//			if($yundian_identify == 'yundian_keeper'){				//云店店主的列表显示
//				$wait_accept_sql .= " and yundian_id=".$yundian." and yundian_self=0 ";
//			}else if($yundian_identify == 'yundian_user'){			//云店游客的列表显示
//				$wait_accept_sql .= " and yundian_id=".$yundian." and user_id='" . $user_id."' ";
//			}
//		}else{
			$wait_accept_sql .= " and user_id='" . $user_id."' ";
//		}
		$wait_accept_result = _mysql_query($wait_accept_sql) or die("Query sql_count failed : ".mysql_error());
		if($row_wait_accept = mysql_fetch_object($wait_accept_result)){
			$wait_accept_count = $row_wait_accept->datacount;
		}

		//待评价
		$wait_evaluate_sql = " select count(batchcode) as datacount from weixin_commonshop_orders where isvalid = true and customer_id=" . $customer_id . "  and is_collageActivities!=2 and (status = 0 or status = 1)  and sendstatus = 2 and is_discuss = 0 ";
//		if($yundian > 0){
//			if($yundian_identify == 'yundian_keeper'){				//云店店主的列表显示
//				$wait_evaluate_sql .= " and yundian_id=".$yundian." and yundian_self=0 ";
//			}else if($yundian_identify == 'yundian_user'){			//云店游客的列表显示
//				$wait_evaluate_sql .= " and yundian_id=".$yundian." and user_id='" . $user_id."' ";
//			}
//		}else{
			$wait_evaluate_sql .= " and user_id='" . $user_id."' ";
//		}
		$wait_evaluate_result = _mysql_query($wait_evaluate_sql) or die("Query sql_count failed : ".mysql_error());
		if($row_wait_evaluate = mysql_fetch_object($wait_evaluate_result)){
			$wait_evaluate_count = $row_wait_evaluate->datacount;
		}
		
		//售后中
		$after_sale_sql = " select count(batchcode) as datacount from weixin_commonshop_orders where isvalid = true and customer_id=" . $customer_id . "  and is_collageActivities!=2 and ((sendstatus > 2 AND sendstatus != 6 AND sendstatus != 4) || (aftersale_type > 0 AND sendstatus != 6 AND sendstatus != 4)) and status = 0";
//		if($yundian > 0){
//			if($yundian_identify == 'yundian_keeper'){				//云店店主的列表显示
//				$after_sale_sql .= " and yundian_id=".$yundian." and yundian_self=0 ";
//			}else if($yundian_identify == 'yundian_user'){			//云店游客的列表显示
//				$after_sale_sql .= " and yundian_id=".$yundian." and user_id='" . $user_id."' ";
//			}
//		}else{
			$after_sale_sql .= " and user_id='" . $user_id."' ";
//		}
		$after_sale_result = _mysql_query($after_sale_sql) or die("Query sql_count failed : ".mysql_error());
		if($row_after_sale = mysql_fetch_object($after_sale_result)){
			$after_sale_count = $row_after_sale->datacount;
		}

		//已完成
		$finnish_order_sql = " select count(batchcode) as datacount from weixin_commonshop_orders where isvalid = true and customer_id=" . $customer_id . "  and (status = 0 or status = 1) and sendstatus = 2 ";
//		if($yundian > 0){
//			if($yundian_identify == 'yundian_keeper'){				//云店店主的列表显示
//				$finnish_order_sql .= " and yundian_id=".$yundian." and yundian_self=0 ";
//			}else if($yundian_identify == 'yundian_user'){			//云店游客的列表显示
//				$finnish_order_sql .= " and yundian_id=".$yundian." and user_id='" . $user_id."' ";
//			}
//		}else{
			$finnish_order_sql .= " and user_id='" . $user_id."' ";
//		}
		$finnish_order_result = _mysql_query($finnish_order_sql) or die("Query sql_count failed : ".mysql_error());
		if($row_finnish_order = mysql_fetch_object($finnish_order_result)){
			$finnish_order_count = $row_finnish_order->datacount;
		}
	}
	/* 查询不同状态的订单数量 end */
	
	
/*
$from = "";
if(!empty($_GET["from"])){
    $from = $configutil->splash_new($_GET["from"]);//finance金融保险
}

 if(!empty($_SESSION["myfromuser_".$customer_id])){
	$fromuser = $_SESSION["myfromuser_".$customer_id];
	$_SESSION["fromuser_".$customer_id]=$fromuser;
	$query = "SELECT id,parent_id from weixin_users where isvalid=true and  customer_id=".$customer_id." and weixin_fromuser='".$fromuser."' limit 0,1";
	$result = _mysql_query($query) or die('Query failed1: ' . mysql_error());
	while ($row = mysql_fetch_object($result)) {
	  $parent_id = $row->parent_id;
	  $user_id = $row->id;
	  break;
	}
	$_SESSION["user_id_".$customer_id] = $user_id;
	$_SESSION["parent_id_".$customer_id] = $parent_id;
}
if($user_id<0){
   if(!empty($fromuser)){
			
		 $query = "select id,parent_id from weixin_users where isvalid=true and customer_id=".$customer_id." and weixin_fromuser='".$fromuser."'  limit 0,1";
		 $result = _mysql_query($query) or die('Query failed2: ' . mysql_error());
		 $user_id = -1;
		 $parent_id = -1;
		 while ($row = mysql_fetch_object($result)) {
			$user_id = $row->id;
			$parent_id = $row->parent_id;
		 }
		 if($user_id<0){
			 $sql="insert into weixin_users(weixin_fromuser,isvalid,customer_id) values('".$fromuser."',true,".$customer_id.")";
			 _mysql_query($sql);
			 $user_id =mysql_insert_id();
		 }
		 $_SESSION["user_id_".$customer_id] = $user_id;
		 $_SESSION["parent_id_".$customer_id] = $parent_id;
	 }
}




//define("InviteUrl","//".CLIENT_HOST."/weixinpl/commonshop/show_commonshop.php?customer_id=");
if(empty($_GET["islist"])){
    //是否需要判断 要跳转     此句有错
	$query="select member_template_type from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
	$result = _mysql_query($query) or die('Query failed3:' . mysql_error());
	$member_template_type=1;
	while ($row = mysql_fetch_object($result)) {
	   $member_template_type = $row->member_template_type;
	   break;
	}
	if($member_template_type==2){
	   //另外一个 个人中心模板
	   echo "<script>document.location='../common_shop/jiushop/order_list_new.php?user_id=".passport_encrypt((string)$user_id)."&member_template_type=2&from=".$from."';</script>";
	   mysql_close($link);
	   return;
	}
	if($member_template_type==3){
	   //单品 个人中心模板
	   echo "<script>document.location='../common_shop/jiushop/order_list_new_new.php?user_id=".passport_encrypt((string)$user_id)."&member_template_type=3&from=".$from."';</script>";
	   mysql_close($link);
	   return;
	}
	if($member_template_type==4){
	   header("Location: ../common_shop/jiushop/order_list_new.php?user_id=".passport_encrypt((string)$user_id)."&member_template_type=4&from=".$from);
	   mysql_close($link);
	   return;
	}
	if($member_template_type==5){
	   header("Location: ../common_shop/jiushop/order_list_new.php?user_id=".passport_encrypt((string)$user_id)."&member_template_type=5&from=".$from);
	   mysql_close($link);
	   return;
	}
	if($member_template_type==6){
	   header("Location: ../common_shop/jiushop/order_list_new_new.php?user_id=".passport_encrypt((string)$user_id)."&member_template_type=6&from=".$from);
	   mysql_close($link);
	   return;
	}
	if($member_template_type==7){
	   header("Location: ../common_shop/jiushop/order_list_new_new.php?user_id=".passport_encrypt((string)$user_id)."&member_template_type=7&from=".$from);
	   mysql_close($link);
	   return;
	}
}else{ 
	//城市商圈，渠道开关
	$is_cityarea=0;
	$is_cityarea_count=0;
	$query="select count(1) as is_cityarea_count from customer_funs cf inner join columns c where c.isvalid=true and cf.isvalid=true and cf.customer_id=".$customer_id." and (c.sys_name='商圈-美食' or c.sys_name='商圈-外卖' or c.sys_name='商圈-金融保险' or c.sys_name='商圈-酒店' or c.sys_name='商圈-ktv') and c.id=cf.column_id";
	$result = _mysql_query($query) or die('W_is_supplier Query failed: ' . mysql_error());  
	while ($row = mysql_fetch_object($result)) {
		$is_cityarea_count = $row->is_cityarea_count;
		break;
	}
	if($is_cityarea_count>0){
		$is_cityarea=1;
	}
	$isshop = 0;   
	if(!empty($_GET["isshop"])){
	   $isshop = $_GET["isshop"];
	}
	if($is_cityarea==0){
		$isshop = 1;   //若商圈功能没开通，直接进入商城订单页
	}
	if($is_cityarea==1 && $isshop==0){	
		header("Location: ../common_shop/jiushop/type_list.php?customer_id=".$customer_id_en."&user_id=".passport_encrypt((string)$user_id));
		mysql_close($link);
		return;
	}
}

$weixin_name="";
$query="select name,weixin_name,weixin_headimgurl from weixin_users where isvalid=true and id=".$user_id;
$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
$name = "";
$headimgurl="";
while ($row = mysql_fetch_object($result)) {
   $name = $row->name;
   $weixin_name = $row->weixin_name;
   $headimgurl= $row->weixin_headimgurl;
}
if(empty($headimgurl)){
   //重新获取头像
    $headimgurl = "../common_shop/common/images/user_log.png";
    $query = 'SELECT id,appid,appsecret,access_token FROM weixin_menus where isvalid=true and customer_id='.$customer_id;
	$result = _mysql_query($query) or die('Query failed: ' . mysql_error());  
	$access_token="";
	while ($row = mysql_fetch_object($result)) {
		$keyid =  $row->id ;
		$appid =  $row->appid ;
		$appsecret = $row->appsecret;
		$access_token = $row->access_token;
		break;
	}
	if(!empty($appid)){
	    //认证的服务号
		$url="https://api.weixin.qq.com/cgi-bin/user/info";
		$data = array('access_token'=>$access_token,'openid'=>$fromuser); 
		

		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1); 
		// 这一句是最主要的
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); 
		$html = curl_exec($ch);  
		
		curl_close($ch) ;

		$obj=json_decode($html);
		
		 if(!empty($obj->errmsg) or  empty($obj->nickname)){
			 $errmsg =$obj->errmsg ;
			//echo $errorcode;
			if($errmsg=="access_token expired" or  empty($obj->nickname)){
			 //高级接口超时，重新绑定
			//echo "<script>win_alert('发生未知错误！请联系商家');</script>";
				$data = array('grant_type'=>'client_credential','appid'=>$appid,'secret'=>$appsecret);  
				$url = "https://api.weixin.qq.com/cgi-bin/token";

				$ch = curl_init(); 
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POST, 1); 
				// 这一句是最主要的
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); 
				$html = curl_exec($ch);  
				
				$obj=json_decode($html);
				
				$access_token = "";
				curl_close($ch) ;
				if(!empty($obj->access_token)){
				   $access_token = $obj->access_token;
				   
				   $query4="update weixin_menus set appid='".$appid."',appsecret='".$appsecret."', access_token = '".$access_token."' where customer_id=".$customer_id;
				   _mysql_query($query4);
				   
					$url="https://api.weixin.qq.com/cgi-bin/user/info";
				   $data = array('access_token'=>$access_token,'openid'=>$fromuser); 


					$ch = curl_init(); 
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_POST, 1); 
					// 这一句是最主要的
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); 
					$html = curl_exec($ch);  
					$obj=json_decode($html);
					
					if(empty($obj->nickname)){
					    $data = array('grant_type'=>'client_credential','appid'=>$appid,'secret'=>$appsecret);  
						$url = "https://api.weixin.qq.com/cgi-bin/token";

						$ch = curl_init(); 
						curl_setopt($ch, CURLOPT_URL, $url);
						curl_setopt($ch, CURLOPT_POST, 1); 
						// 这一句是最主要的
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
						curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); 
						$html = curl_exec($ch);  
						//echo $html;
						$obj=json_decode($html);
						
						$access_token = "";
						curl_close($ch) ;
						if(!empty($obj->access_token)){
						   $access_token = $obj->access_token;
						   
						   $query4="update weixin_menus set appid='".$appid."',appsecret='".$appsecret."', access_token = '".$access_token."' where customer_id=".$customer_id;
						   _mysql_query($query4);
						   
							$url="https://api.weixin.qq.com/cgi-bin/user/info";
						   $data = array('access_token'=>$access_token,'openid'=>$fromuser); 


							$ch = curl_init(); 
							curl_setopt($ch, CURLOPT_URL, $url);
							curl_setopt($ch, CURLOPT_POST, 1); 
							// 这一句是最主要的
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
							curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); 
							$html = curl_exec($ch); 
                            
							$obj=json_decode($html);
						}else{
						   echo "<script>win_alert2('发生未知错误！请联系商家');</script>";
						   return;
						}
					}
					
					$weixin_name =  $obj->nickname;
					$sex = $obj->sex;
					$headimgurl= $obj->headimgurl;
					$subscribe_time = $obj->subscribe_time;
					$query4 = "update weixin_users set weixin_headimgurl='".$headimgurl."',weixin_name='".$weixin_name."',sex=".$sex." where id=".$user_id;
					//echo $query;	
					_mysql_query($query4);
				}else{
				   echo "<script>win_alert2('发生未知错误！请联系商家');</script>";
				   return;
				}
			 }
		  }else{
				$weixin_name =  $obj->nickname;
				
				$sex = $obj->sex;
				$headimgurl= $obj->headimgurl;
				$subscribe_time = $obj->subscribe_time;
				$query4 = "update weixin_users set weixin_headimgurl='".$headimgurl."',weixin_name='".$weixin_name."',sex=".$sex." where id=".$user_id;
				_mysql_query($query4);
		 }

	}
}

if(empty($weixin_name)){
    $weixin_name = $name;
}


$issell = 0;
if(!empty($_SESSION["issell"])){

   $issell = $_SESSION["issell"];
}



$is_alipay=false;
$is_weipay=false;
$is_tenpay=false;
$is_allinpay=false;
$isdelivery=false;
$iscard=false;
$isshop =false;
$query2 = 'SELECT id,is_alipay,is_tenpay,is_weipay,is_allinpay,isdelivery,iscard,isshop,is_paypal FROM customers where isvalid=true and id='.$customer_id;

$defaultpay = "去付款";
$result2 = _mysql_query($query2) or die('Query failed: ' . mysql_error());
while ($row2 = mysql_fetch_object($result2)) {
    $is_alipay=$row2->is_alipay;
	$is_tenpay=$row2->is_tenpay;
	$is_weipay = $row2->is_weipay;
	$is_allinpay = $row2->is_allinpay;
	$iscard = $row2->iscard;
	$isdelivery = $row2->isdelivery;
	$isshop = $row2->isshop;
	$is_paypal=$row2->is_paypal;
	break;
}
$card_remain=0;
$card_member_id=-1;
$sendstatus = 0;
$is_pic = 0;
$shop_card_id=-1;
$template_type_bg;
 $query="select shop_card_id,exp_name,template_head_bg from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
 $result = _mysql_query($query) or die('Query failed: ' . mysql_error());  
 while ($row = mysql_fetch_object($result)) {
     $shop_card_id = $row->shop_card_id;
	 $template_head_bg=$row->template_head_bg;
	$template_type_bg=$template_head_bg?1:0;	
	 break;
 }
 
 $member_template_type=1;
 $nopostage_money = 0;
$query="select member_template_type,nopostage_money,is_pic from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
$result = _mysql_query($query) or die('Query failed: ' . mysql_error());   
while ($row = mysql_fetch_object($result)) {
	$is_pic=$row->is_pic;
	$member_template_type = $row->member_template_type;
	$nopostage_money = $row->nopostage_money;
}





$is_shopdistr=0;
$is_my_commission = 0;
$query = "select issell,is_my_commission from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
	$issell = $row->issell;
    $is_my_commission = $row->is_my_commission;	
}

//是否开启购物币
$sql_cur_sql = "SELECT c.isOpen,u.currency,c.custom FROM weixin_commonshop_currency c LEFT JOIN weixin_commonshop_user_currency u ON c.customer_id=u.customer_id WHERE c.customer_id=".$customer_id." and u.user_id=".$user_id." limit 1";
$sql_cur_res = _mysql_query($sql_cur_sql);
while($row=mysql_fetch_object($sql_cur_res)){
	$isOpen_currency = $row->isOpen;
	$currency 		 = $row->currency;
	$custom 		 = $row->custom;
}

*/

//快递查询方式
$query = "select is_kuaidi from weixin_commonshops where isvalid=true and customer_id=".$customer_id; 
$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
$is_kuaidi = 0; //快递查询方式：0免费查询，1付费查询 默认0
while ($row = mysql_fetch_object($result)) {
	$is_kuaidi = $row->is_kuaidi;
}

?>
<!DOCTYPE html>
<html>
<head>
	<title><?php if($yundian_identify == 'yundian_keeper'){ ?>平台的订单<?php }else{ ?>我的订单<?php } ?></title>
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


	<link type="text/css" rel="stylesheet" href="./css/css_<?php echo $skin; ?>.css" />    
	

	<!-- 基本dialog-->
	<link type="text/css" rel="stylesheet" href="./css/goods/dialog.css" />
	<link type="text/css" rel="stylesheet" href="./css/self_dialog.css" />
<link type="text/css" rel="stylesheet" href="./css/order_css/style.css" media="all">
<link type="text/css" rel="stylesheet" href="./css/order_css/dingdan.css" />

	<style>
		.tis{
			width: 100%;
			text-align: center;
			color:#999;
			font-size: 18px;
			margin-top: 20px;
			margin-bottom: 10px;
		}
.cell_icon {
    position: absolute;
    left: 13px;
    top:18px;
    background-image: url(./collageActivities/img/cell_icon.png);
    display: block;
    width: 40px;
    height: 20px!important;
    background-repeat: no-repeat;
    background-size: 40px 20px;
    font-size:.8rem!important;
    color: #fff!important;
    line-height: 18px!important;
    text-align: center;
    overflow: hidden;box-sizing:border-box;padding:0 2px;
}
	.tips-list{font-size:0;margin:1px 0 0 0;}
	.tips-list .bg-red{background-color:red;}
	.tips-list .tips{display:inline-block;width:14px;height:14px;font-size:10px;line-height:14px;text-align:center;color:#fff;margin:0 3px 0 0;}
    .search-box{width:100%;background:#FFF;padding:5px 10px;box-sizing:border-box;}
    .search-box02{display:flex;}
    .search-content{width:100%;height:28px;line-height:28px;background:#e9e9e9;border-radius:4px;display:flex;align-items:center;padding:0 17px;justify-content:center;}
    .search-content02{justify-content:left;width:80% !important;}
    .search-content02 input{width:100%;}
    .search-img{width:13px;height:13px;margin-right:5px;}
    .search-box input{background:#e9e9e9;height:25px;line-height:25px;border:none;margin:0;padding:0;}
    .search-cancel{width:20%;font-size:14px;color:#1c1f20;text-align:center;background:#FFF;border:none;display:none;}
    .null-page{display:none;}
    .null-content{display:flex;justify-content:center;align-items:center;width:100%;flex-direction:column;height:100%;width:100%;}
    .null-page img{width:70%;}
    .null-page p{font-size:13px;color:#a1a1a1;margin-top:18px;}
    #middle-tab.tabbar{top:0;background-color:#eee;}
	</style>
	
</head>


<body id="mainBody" data-ctrl=true>
	<div id="mainDiv">
	    <!--
		<header data-am-widget="header" class="am-header am-header-default">
		    <div class="am-header-left am-header-nav" onclick="history.back()">
			    <img class="am-header-icon-custom icon_back" src="./images/center/nav_bar_back.png"/><span>返回</span>
		    </div>
	        <h1 class="am-header-title topTitle">订单管理</h1>
	    </header>
	    <div class="topDiv"></div><!-- 暂时隐藏头部导航栏 -->
	    
	    <!-- 上面的Tabbar开始 -->
        
	    <div id="middle-tab" class="tabbar" >
            <div class="search-box">
          <div class="search-content">
            <img class="search-img" src="./images/order_image/search-btn.png" width="20" height="20">
            <input class="search-input" placeholder="输入产品关键字搜索" value="" />
          </div>
          <button class="search-cancel">取消</button>
        </div>
         <div <?php if($search_name != '')echo 'style="display:none;"';?> style="background:#FFF;" >
	    	<div id="kindAll" class="area-one" data-type="1">
	    		<img src="./images/order_image/icon_dingdan_quanbu.png" width="20" height="20">
	    		<div>全部</div>
                
	    	</div>
	    	
	    	<div id="kindDaiFuKuan" class="area-one" data-type="2">
	    		<img src="./images/order_image/icon_daifukuan.png" width="20" height="20">
	    		<div>待付款</div>
                <?php if($wait_pay_count>0){ if($wait_pay_count>=100){ ?><span class="numTips">99+</span><?php }else{ ?><span class="numTips"><?php echo $wait_pay_count; ?></span><?php } }?>
	    	</div>
	    	
	    	<div id="kindDaiFaHuo" class="area-one" data-type="3">
	    		<img src="./images/order_image/icon_daifahuo.png" width="20" height="20">
	    		<div>待发货</div>
                <?php if($wait_send_count>0){ if($wait_send_count>=100){ ?><span class="numTips">99+</span><?php }else{ ?><span class="numTips"><?php echo $wait_send_count; ?></span><?php } }?>
	    	</div>
	    	
	    	<div id="kindDaiShouHuo" class="area-one" data-type="4">
	    		<img src="./images/order_image/icon_daishouhuo.png" width="20" height="20">
	    		<div>待收货</div>
                <?php if($wait_accept_count>0){ if($wait_accept_count>=100){ ?><span class="numTips">99+</span><?php }else{ ?><span class="numTips"><?php echo $wait_accept_count; ?></span><?php } }?>
	    	</div>
	    	
	    	<?php if($yundian > 0){ ?>

	    	<div id="kindDaiPingJia" class="area-one" data-type="9">
	    	<img src="./images/order_image/icon_daipingjia.png" width="20" height="20">
	    	<div>已完成</div>
			<?php if($finnish_order_count>0){ if($finnish_order_count>=100){ ?><span class="numTips">99+</span><?php }else{ ?><span class="numTips"><?php echo $finnish_order_count; ?></span><?php } }?>
	    	</div>

	    	<?php }else{ ?>

	    	<div id="kindDaiPingJia" class="area-one" data-type="5">
	    		<img src="./images/order_image/icon_daipingjia.png" width="20" height="20">
	    		<div>待评价</div>
				<?php if($wait_evaluate_count>0){ if($wait_evaluate_count>=100){ ?><span class="numTips">99+</span><?php }else{ ?><span class="numTips"><?php echo $wait_evaluate_count; ?></span><?php } }?>
	    	</div>

	    	<?php } ?>
            
	    	<!-- 已完成暂时不用 -->
            <!--
			<div id="kindYiWanCheng" class="area-one" data-type="6">
                <img src="./images/order_image/icon_daipingjia.png" width="20" height="20">
                <div>已完成</div>
            </div>
        -->
        <div id="kindShouHouZhong" class="area-one" data-type="7">
        	<img src="./images/order_image/icon_shouhouzhong.png" width="20" height="20">
        	<div>售后中</div>
			<?php if($after_sale_count>0){ if($after_sale_count>=100){ ?><span class="numTips">99+</span><?php }else{ ?><span class="numTips"><?php echo $after_sale_count; ?></span><?php } }?>
        </div>
        </div>

    </div>
    <!-- 上面的Tabbar终结 -->
    <!--占位-->
    <div style="height:100px;width:100%; <?php if($search_name != '')echo "display:none;";?>"></div>
    <!-- 基本数据地区 开始 -->            
    <div id="productContainerDiv">
    	<div class="entry-content">
    		<ul id="pinterestList">
    			<?php
               // if($currtype !=7) { //非售后
    			$sql_count = " select count(wco.batchcode) as datacount from weixin_commonshop_orders wco ";
    			$sql_cond = " where wco.isvalid = true and wco.customer_id=" . $customer_id . "  and wco.is_collageActivities!=2 ";

//    			if($yundian > 0){
//    				if($yundian_identify == 'yundian_keeper'){				//云店店主的列表显示
//						$sql_cond .= " and wco.yundian_id=".$yundian." and wco.yundian_self=0  ";
//					}else if($yundian_identify == 'yundian_user'){			//云店游客的列表显示
//						$sql_cond .= " and wco.yundian_id=".$yundian." and wco.user_id='" . $user_id."' ";
//					}
//    			}else{
    				$sql_cond .= " and wco.user_id='" . $user_id."' ";
//    			}

    			switch ($currtype) {  
    					case 1:	// 所有订单
    					break;
                        case 2: //待付款

                        $sql_cond = $sql_cond . " and wco.status = 0  and wco.paystatus = 0 and wco.is_pay_on_delivery != 1";
                        break;
                        case 3: // 待发货
                        $sql_cond = $sql_cond . " and (wco.paystatus=1 or wco.is_pay_on_delivery = 1) and wco.status = 0 and wco.sendstatus = 0 ";
                        break;
                        case 4: //待收货
                        $sql_cond = $sql_cond . " and (wco.paystatus=1 or wco.is_pay_on_delivery = 1) and wco.status = 0 and wco.sendstatus = 1";
                        break;
                        case 5: //待评价
                        $sql_cond = $sql_cond . " and (wco.status = 0 or wco.status = 1)  and wco.sendstatus = 2 and wco.is_discuss = 0 ";
                        break;
                        case 7: //售后中
                        $sql_cond = $sql_cond . " and ((wco.sendstatus > 2 AND wco.sendstatus != 6 AND wco.sendstatus != 4) || (wco.aftersale_type > 0 AND wco.sendstatus != 6 AND wco.sendstatus != 4)) and wco.status = 0 ";
                        break;
                        case 9: //已完成
                        $sql_cond = $sql_cond . " and (wco.status = 0 or wco.status = 1)  and wco.sendstatus = 2 ";
                        break;

                }
                if($search_name != ''){   //查询产品
                    $sql_count .= ' inner join weixin_commonshop_products wcp on wco.pid=wcp.id ';
                    $sql_cond  .= ' and wcp.name LIKE "%'.$search_name.'%"';
                }
                //$sql_batchcode .= $sql_cond;
                $sql_count .= $sql_cond;
                $sql_cond   = ''; 
                
               
              /*  }else{
                    $sql_count = "select count(batchcode) as datacount from weixin_commonshop_order_aftersale";
                }*/
                $datacounts = 0;
					// echo $sql_count;
                $result_count = _mysql_query($sql_count) or die("Query sql_count failed : ".mysql_error());
                if($row_count = mysql_fetch_object($result_count)){
                	$datacounts = $row_count->datacount;
                	
                }
                
                if($datacounts == 0 && $search_name == ''){
                    ?>
                	<p class="tis" id="nomany">---暂无更多记录---</p>
                <?php
	            }else{
	            	require('orderlist_prods.php');
	            }


            ?>


        </ul>
    </div>
</div>
<!-- 基本数据地区 终结 -->
<!--空白页面 开始-->
    <div class="null-page" <?php if($datacounts == 0 && $search_name != '') echo 'style="display:block"'; ?>>
      <div class="null-content">
       <image src="./images/order_image/null.png" />
       <p>没有搜索到该产品~</p>
      </div>
    </div>
<!--空白页面 结束-->

</div>

</body>
<script type="text/javascript" src="./assets/js/jquery.min.js"></script>
<script type="text/javascript" src="./assets/js/amazeui.js"></script>
<script type="text/javascript">
    var downFlag = false; // 是否加载全部
    var pageNum = 1, pageSize = 5,isMore = true; // 总笔数
    var dataCounts = <?php echo $datacounts;?>;
    var maxPage = Math.ceil(dataCounts/pageSize);

    var winWidth 	= $(window).width();
    var winheight 	= $(window).height();
    var ctype 		= '<?php echo $currtype;?>';
    var search_name = '<?php echo $search_name;?>';
    var is_receipt 	= '<?php echo $is_receipt;?>';
    var customer_id_en = '<?php echo $customer_id_en;?>';
    var user_id 	= <?php echo $user_id;?>;
    var yundian 	= '<?php echo $yundian; ?>';
    var yundian_id 	= '<?php echo $yundian; ?>';
    user_id_en 		= '<?php echo passport_encrypt($user_id);?>';
    $('.search-input').val(search_name);
    console.log(yundian);
</script>

<script type="text/javascript" src="./js/global.js"></script>
<script type="text/javascript" src="./js/loading.js"></script>
<script src="./js/jquery.ellipsis.js"></script>
<script src="./js/jquery.ellipsis.unobtrusive.js"></script>
<script type="text/javascript">
	// 返回上一页操作  订单列表返回个人中心
    $(function() {

        if(document.referrer.indexOf("weixinpl/common_shop/jiushop/index.php") === -1){//CRM18153，首页过来的点击返回不跳转去个人中心
            if(yundian > 0){
                var url = '/weixinpl/mshop/personal_center.php?customer_id='+customer_id_en+'&yundian='+yundian;
            }else{
                var url = '/weixinpl/mshop/personal_center.php?customer_id='+customer_id_en+'&currtype=1';}

                    
            if (window.history && window.history.pushState) {
                window.addEventListener('load', function() {   //CRM16969，防止苹果手机自动触发返回；无法重现，不知道效果 
                    //setTimeout(function() {       
                       $(window).on('popstate', function () {
                            window.location.href = url;
                            /*		　　window.history.pushState('forward', null, '#');
                             　　window.history.forward('#');*/  //2018-3-13 HJW屏蔽 苹果手机无法返回
                        });   
                   // }, 0);   
                 })
            }
            // window.location.href = url;
            //CRM17421，屏蔽下面两行
            if(!isIE()){
                //非IE浏览器不执行会导致 不能返回个人中心
                window.history.pushState('forward', null, '#'); //在IE中必须得有这两行
                window.history.forward('#');
            }
        }

    })
    function isIE() { //ie?
     if (!!window.ActiveXObject || "ActiveXObject" in window)
            { return true; }
     else
            { return false; }
 	}

	$(function() {
		switch(parseInt(ctype)){
			case 1:

			$(".area-one[data-type='"+ctype+"']").find('img').attr('src','./<?php echo $images_skin?>/order_image/icon_dingdan_quanbu_sel-orange.png');
			break;
			case 2:

			$(".area-one[data-type='"+ctype+"']").find('img').attr('src','./<?php echo $images_skin?>/order_image/icon_daifukuan_sel-orange.png');
			break;
			case 3:

			$(".area-one[data-type='"+ctype+"']").find('img').attr('src','./<?php echo $images_skin?>/order_image/icon_daifahuo_sel-orange.png');
			break;
			case 4:

			$(".area-one[data-type='"+ctype+"']").find('img').attr('src','./<?php echo $images_skin?>/order_image/icon_daishouhuo_sel-orange.png');
			break;
			case 5:

			$(".area-one[data-type='"+ctype+"']").find('img').attr('src','./<?php echo $images_skin?>/order_image/icon_daipingjia_sel-orange.png');
			break;
			case 7:

			$(".area-one[data-type='"+ctype+"']").find('img').attr('src','./<?php echo $images_skin?>/order_image/icon_shouhouzhong_sel-orange.png');
			break;
			case 9:

			$(".area-one[data-type='"+ctype+"']").find('img').attr('src','./<?php echo $images_skin?>/order_image/icon_daipingjia_sel-orange.png');
			break;
        } //这段switch用于改变图标。
        $(".area-one[data-type='"+ctype+"']").addClass("select");
        $(".area-one").click(function(){
        	window.location.href = "orderlist.php?customer_id="+customer_id_en+"&currtype="+$(this).data("type")+"&user_id="+user_id_en+"&yundian="+yundian;
        });
    });
	
	function searchData() {
		content = "";

		if (pageNum == maxPage || maxPage == 0) return;

		$.ajax({
			type: "get",
			url: "orderlist_turnpage.php",
			data: "pagenum="+(pageNum+1)+"&currtype="+ctype+"&search_name="+search_name+"&user_id="+user_id_en,
			success: function(msg){
				$("#pinterestList").append(msg);
			}
		});
		pageNum++;
	}
	
	
	window.onscroll = function (event) {  // 返回顶部
		var intY = $(window).scrollTop();
		if (pageNum == maxPage || maxPage == 0) return;
		var height = document.body.scrollHeight - 100;
		if (intY+winheight-15>height) searchData();
	};
	

    //提醒发货
    function order_remind(batchcode){
		var Obj=get('batchcode'+batchcode,1000*60*60*24);//过期时间为24小时
		if (Obj !="" && Obj !=null) {
			alertAutoClose("已提醒发货，一天内请勿重复操作！");
		}else{
			$.getJSON("orderlist_operation.php",{batchcode:batchcode,op:"remind",user_id:user_id_en,customer_id:customer_id_en},function(data){
				//console.log(data);
				set('batchcode'+batchcode,batchcode);
				alertAutoClose(data.msg);
			});
		}
    	/* $.getJSON("orderlist_operation.php",{batchcode:batchcode,op:"remind",user_id:user_id_en,customer_id:customer_id_en},function(data){
			console.log(data);
			set('batchcode',batchcode);
    			if(batchcode == localStorage.batchcode){
    				//showAlertMsg("操作提示",'已提醒发货，请勿重复操作！',"知道了");
                    alertAutoClose("已提醒发货，请勿重复操作！");
	    		}else{
	    			//showAlertMsg("操作提示",data.msg,"知道了");
                    alertAutoClose(data.msg);
	    		}
	    		localStorage.batchcode=batchcode;
    	}); */
    }
    //申请延时收货
    function order_delay(batchcode){

    	showConfirmMsg("操作提示","只能延迟一次，是否确定申请延迟收货？","申请","取消",function(){
    		$.getJSON("orderlist_operation.php",{batchcode:batchcode,op:"delay"},function(data){

    			showAlertMsg ("提示：",data.msg,"知道了",function(){
    				location.reload();
    			});
    		});
    	});
    }

    //取消订单
    function order_cancel(batchcode){
    	showConfirmMsg("操作提示","取消后不可恢复，是否确认取消订单？","取消","不取消",function(){
            //$.ajaxSettings.async = false;
            $.getJSON("orderlist_operation.php",{batchcode:batchcode,op:"cancel"},function(data){

    			showAlertMsg ("提示：",data.msg,"知道了",function(){
    				location.reload();
    			});
				
    		});
    	});
    }
    //确认订单
    function order_confirm(batchcode,totalprice,is_receipt){
		if ( is_receipt == 1 ) {
			var confirmTip = '警：确认完成后，订单将进行结算，订单不再受理退货，退款，如若确定商品无误，请点击确定，否则取消。';
		} else {
			var confirmTip = '警：如若确定商品无误，请点击确定，否则取消。';
		}
    	showConfirmMsg("操作提示", confirmTip, "确定", "取消",function(){
    		$.getJSON("orderlist_operation.php",{batchcode:batchcode,totalprice:totalprice,op:"confirm"},function(data){
    			setTimeout(function(){
    				showAlertMsg("操作提示",data.msg,"知道了");
    				if(is_receipt==1 && data.arr.yundian_self == 0){
    					confirmOrder(batchcode,totalprice);
    				}else if(is_receipt==1 && data.arr.yundian_id != -1 && data.arr.yundian_self == 1 && data.arr.clearing_onoff == 1){	//云店收货自动结算
    					confirmOrder(batchcode,totalprice);
    				}
    				$(".sharebg-active,.share_btn, .cancel, .close_button").click(function(){
    					location.reload();	
    				})            
    			},300);           	
    			

    		});
    	});
    }

    //确认完成订单
    function confirmOrder(batchcode,totalprice){
    	$.ajax({
    		url:"../back_newshops/Order/order/order.class.php",
    		dataType:"json",
    		type:"post",
    		data:{'batchcode':batchcode,'totalprice':totalprice,'op':"confirm","is_receipt":1}
    	});
    }
    //点击【查看物流】
    function check_express(expressNum,expressname,batchcode){
        kuaidi = <?php echo $is_kuaidi;?>;
        if (kuaidi == 1) {
            window.location.href = "/weixinpl/back_newshops/Distribution/settings/kuaidi_ck.php?is_web=1&customer_id="+customer_id_en+"&batchcode="+batchcode+"&postid="+expressNum+"&type="+expressname;
        } else if (kuaidi == 2) {
            window.location.href = "/weixinpl/back_newshops/Distribution/settings/kuaidi100.php?is_web=1&customer_id="+customer_id_en+"&batchcode="+batchcode+"&postid="+expressNum;
        } else {
            window.location.href = "http://m.kuaidi100.com/index_all.html?type="+expressname+"&postid="+expressNum+"#result";
        }
        // window.location.href = " https://m.kuaidi100.com/result.jsp?type="+expressname+"&nu="+expressNum;
    }
 //链接到评价页

 
 function toEvaluation(batchcode){
 	window.location.href = "orderlist_evaluation.php?batchcode="+batchcode+"&customer_id="+customer_id_en;
 }

    /*
    function toAftersale(batchcode,pid,prvalues){
        location.href='orderlist_aftersale.php?batchcode='+batchcode+"&pid="+pid+"&prvalues="+prvalues+"&customer_id=<?php echo $customer_id_en;?>";
    }
    */
    function toAftersale(batchcode){
    	location.href='orderlist_aftersale.php?batchcode='+batchcode+"&customer_id=<?php echo $customer_id_en;?>";
    }
    //付款
    function order_pay(batchcode,totalprice){
    	debugger;
    	var iptCurrency = $(".user_currency").val();
    	var open_curr = $(".open_curr").attr('open_val');
    	if(iptCurrency != "" && open_curr == 1){
    		if(parseFloat(iptCurrency) > parseFloat(totalprice)){
    			showAlertMsg("操作提示","最多只能使用"+totalprice+"个<?php echo defined('PAY_CURRENCY_NAME')? PAY_CURRENCY_NAME: '购物币'; ?>","知道了");
    			return;
    		}
            if(parseFloat(iptCurrency) == parseFloat(totalprice)){ //全部使用购物币支付
            	showConfirmMsg("操作提示","是否确定全部使用<?php echo defined('PAY_CURRENCY_NAME')? PAY_CURRENCY_NAME: '购物币'; ?>支付？","支付","取消",function(){
            		$.ajax({
            			type: "get",
            			url: "orderlist_operation.php",
            			data: "op=pay_currency&batchcode="+batchcode+"&customer_id="+customer_id_en,
            			success: function(data){
            				showAlertMsg("操作提示",data.msg,"知道了",function(){
            					if(data.result == 1) {
            						location.href="orderlist.php?customer_id="+customer_id_en+"&currtype=3&user_id="+user_id_en;
            					}else{
            						location.href="orderlist.php?customer_id="+customer_id_en+"&currtype="+ctype+"&user_id="+user_id_en;
            					}
            				});
            			}
            		});
            	});
            	return;
            }
        }

        togglePan();
    }
    function togglePan(){
    	$(".am-dimmer").toggle();
    	$("#zhifuPannel").slideToggle();
    }
    
	//跳转到供应商页面
	function gotoShop(shopID){
		window.location.href = "my_store/my_store.php?supplier_id="+shopID+"&customer_id="+customer_id_en;
	}
	
	//跳转到首页
	function gotoIndex(){
		if(yundian > 0){
            window.location.href = "../common_shop/jiushop/index.php?customer_id="+customer_id_en+"&yundian="+yundian;
        }else{
            window.location.href = "../common_shop/jiushop/index.php?customer_id="+customer_id_en;
        }
	}
	
	//封装过期控制代码
    function set(key,value){
        var curTime = new Date().getTime();
        localStorage.setItem(key,JSON.stringify({data:value,time:curTime}));
    }
    function get(key,exp){
        var data = localStorage.getItem(key);
		if(data == null){
			return false;
		}
        var dataObj = JSON.parse(data);
        if (new Date().getTime() - dataObj.time>exp) {
            console.log('信息已过期');
            //alert("信息已过期")
        }else{
            //console.log("data="+dataObj.data);
            //console.log(JSON.parse(dataObj.data));
            var dataObjDatatoJson = JSON.parse(dataObj.data)
            return dataObjDatatoJson;
        }
    }

var fWidth = $('.area-one').width();
     var tWidth = $('.area-one img').width();
     $('.numTips').css("right",(fWidth-tWidth)/2-10+"px");
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
<?php
	//底部菜单栏
//include_once("foot.php");
/*判断是否显示底部菜单 start*/
require_once('../common/utility_setting_function.php');
$fun = "goods_order_list";
$is_publish = check_is_publish(2,$fun,$customer_id);
if($is_publish){
	require_once('./bottom_label.php');
}
/*判断是否显示底部菜单 end*/
?>
<?php  
$nav_is_publish = check_nav_is_publish($fun,$customer_id);
include_once('float.php');
?>
<script type="text/javascript" src="./js/loading.js"></script>
<script src="//res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script>
    wx.config({
        debug: false,
        appId: appId,
        timestamp: timestamp,
        nonceStr: nonceStr,
        signature: signature,
        jsApiList: [
            // 所有要调用的 API 都要加到这个列表中
            'scanQRCode'
        ]
    });
    //核销扫码
    function write_off_code(batchcode,o_shop_id,o_verification_code,totalprice,is_receipt) {
        wx.scanQRCode({
            needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
            scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
            success: function (res) {
                var result = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
                var branch_id = result;
                loading(100,1);
                do_order_inspect(o_verification_code,batchcode,user_id,branch_id,o_shop_id);
            }
        });
    }
    function do_order_inspect(code,batchcode,user_id,branch_id,o_shop_id){
        $.ajax({
            type: "get",
            url: "do_order_inspect.php",
            data: "code="+code+"&o_shop_id="+o_shop_id+"&batchcode="+batchcode+"&customer_id="+customer_id_en+"&user_id="+user_id+"&branch_id="+branch_id,
            success: function(data){
                data = JSON.parse(data);
                console.log(data);
                closeLoading(); //关闭加载中调用
                showAlertMsg ("提示：",data.msg,"知道了",function(){
                    if(data.code == 1){
                        //开启收货自动结算 by Hiking
                        if (is_receipt == 1) {
                            $.ajax({
                                url:"../back_newshops/Order/order/order.class.php",
                                dataType:"json",
                                type:"post",
                                data:{'batchcode':batchcode,'totalprice':totalprice,'op':"confirm","is_receipt":1}
                            });
                        }

                        location.href = '../common_shop/jiushop/forward.php?customer_id='+customer_id_en+'&exp_user_id='+data.exp_user_id+'&type=branch_write_off';
                    }
                });
            },
            error : function(err) {
                console.log(err);
            }
        });
    }
</script>
<script>
   $(function(){
       var nullHeight= $(window).height();
       var topHeight = $('.search-box').height();
       	$('.null-page').css('height',nullHeight-topHeight+'px');
       	$('.search-input').focus(function(){
			$('.search-content').addClass("search-content02");
			$('.search-box').addClass("search-box02");
			$('.search-cancel').show();
       	})
       	$('.search-input').blur(function(){
			if($(this).val()==''){
			$('.search-content').removeClass("search-content02");
			$('.search-box').removeClass("search-box02");
			$('.search-cancel').hide();
			}else{
			   $('.search-cancel').show();
			}
       	})
       	$('.search-cancel').on("click",function(){
       		if(search_name != ''){
       			document.location='orderlist.php?customer_id='+customer_id_en;
       		}
			$('.search-input').val('');
			$('.search-content').removeClass("search-content02");
			$('.search-box').removeClass("search-box02");
			$('.search-cancel').hide();
       	})
       	$('.search-input').keyup(function(event){
			if(event.keyCode ==13){
				var search_name = $('.search-input').val();
				search_name = search_name.replace(/(^\s*)|(\s*$)/g, "");
				search_name=search_name.replace(/\"/g,"");
				search_name=search_name.replace(/\'/g,"");
				document.location='orderlist.php?customer_id='+customer_id_en+"&search_name="+search_name+"&user_id="+user_id_en;
			}
		});
   })

   var block_chain_name = '<?php echo $block_chain_name; ?>';
    //领取微米
    function receive_micron(obj,batchcode,block_chain_reward)
    {
    	$(".sharebg").remove();
		var scroll_top = $(window).height(); //浏览器的可视区域高度
		scroll_top = (scroll_top-100)/2;
	    $("body").append('<div class="sharebg" style="opacity:0"></div>');
	    $(".sharebg").animate({"opacity":1});
	    $(".sharebg").append('<div style="width:100px;height:100%;margin: auto;margin-top:'+scroll_top+'px;"><img src="/mshop/web/view/block_chain/img/timg.gif" style="width:100px;height:100px;"></div>');
	    $(".sharebg").addClass("sharebg-active");

    	$(obj).attr('onclick',false);
    	//判定用户是否绑定
	    $.ajax({
	        url:'/mshop/web/index.php?m=block_chain&a=whether_to_bind',
	        data:{'customer_id':customer_id_en,'user_id':user_id,'batchcode':batchcode},
	        dataType:'json',
	        type:'post',
	        complete:function(){},
	        success:function(res)
	        {
	            console.log(res);
	           if (res.errcode == 1) 
                {
                    window.location.href = '/mshop/web/index.php?m=block_chain&a=binding&user_id='+user_id+'&batchcode='+batchcode;
                    $(obj).attr('onclick','receive_micron(this,"'+batchcode+'",'+block_chain_reward+')');
                }
                else if(res.errcode == 0)
                {
                    $(obj).attr('onclick','receive_micron(this,"'+batchcode+'",'+block_chain_reward+')');
                    showAlertMsgChain("提示",block_chain_reward+block_chain_name+'领取成功',"知道了");
                    return;
                }
                else
                {
                    $(obj).attr('onclick','receive_micron(this,"'+batchcode+'",'+block_chain_reward+')');
                    showAlertMsg("提示",res.errmsg,"知道了");
                    return;
                }
	        }
	    }); 
    }
    //弹窗
	function showAlertMsgChain(title,content,cancel_btn){
	    $(".sharebg").remove();
	    $("body").append('<div class="sharebg" style="opacity:0"></div>');
	    $(".sharebg").animate({"opacity":1});
	    $(".sharebg").addClass("sharebg-active");
	    $("body").append('<div class="am-share alert" style="top:100%"></div>');
	    $(".alert").animate({"top":0})
	    $(".alert").addClass("am-modal-active");      
	    var html = "";
	    html += '<div class = "close_button">';
	    html += '<img src = "/weixinpl/mshop/images/info_image/btn_close.png"  width = "30">';
	    html += '</div>';
	    html += '<div class = "alert_content">';
	    html += '  <div class = "dlg_content1_row1" style="text-align:left;">';
	    html += '       <span  style="font-size:15px;">'+title+'</span>';
	    html += '    </div>';
	    html += '<div class = "dlg_content1_row2">';
	    html += '    <span style="font-size: 15px;">'+content+'</span>';
	    html += '</div>';
	    html += '</div>';
	    html += '<div style="width: 100%;height: 50px;background: white;border-bottom-left-radius: 7px;border-bottom-right-radius: 7px;">';
	    html += '<div class = "dlg_commit cancel1 skin-bg" style="width: 50%;float: left;border-radius: 5px;border-top-left-radius: 0px;border-bottom-right-radius: 0px;border-top-right-radius: 0px;">';
	    html += '    <span>查看</span>';
	    html += '</div>';
	    html += '<div class = "dlg_commit cancel skin-bg" style="float: right;width: 50%;border-radius: 5px;border-bottom-left-radius: 0px;border-top-right-radius: 0px;border-top-left-radius: 0px;background-color: #f3f3f3!important;">';
	    html += '    <span style="color: #1c1f20;">'+cancel_btn+'</span>';
	    html += '</div>';
	    html += '</div>';
	    $(".alert").html(html); 
	    // dialog cancel_btn按键点击事件  
	    $(".sharebg-active,.share_btn, .cancel, .close_button").click(function()
	    {
	        $(".sharebg").remove();
	        $('.alert').remove();
	        window.location.reload();
	    })
	    $('.cancel1').click(function()
	    {
	    	window.location.href = '/mshop/web/index.php?m=block_chain&a=Block_chain_integral&user_id=<?php echo passport_encrypt((string)$user_id) ?>&customer_id=<?php echo $customer_id_en; ?>';
	    })
	}
</script>
</body>
</html>
