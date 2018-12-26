<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
$customer_id = -1;
if(!empty($_POST["customer_id"])){
	$customer_id = $configutil->splash_new($_POST["customer_id"]);	
}
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

//接收一系列参数---start
$user_id   = $_SESSION["user_id_".$customer_id];
$visitor_cart = '';
if(!empty($_POST["visitor_cart"])){
	$visitor_cart = $_POST["visitor_cart"];	
}
$visitor_cart = json_decode($visitor_cart,true);
//接收一系列参数---end

//与数据库数据合并---start
$cart_id = -1;
$old_cart_data = '';//原来存在数据库中的数据
$query = "select id,cart_data from weixin_commonshop_cart_status where isvalid=true and customer_id=".$customer_id." and user_id=".$user_id." limit 1";

$result = _mysql_query($query) or die('CS1 Query failed: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
	$cart_id	   = $row->id;
	$old_cart_data = $row->cart_data;
	$old_cart_data = json_decode($old_cart_data,true);
}

$new_cart_data = '';
//数据库中不存在数据，新数据直接为游客的购物车数据
if( empty( $old_cart_data ) ){
	$new_cart_data = $visitor_cart;
}else{
	$new_cart_data = $old_cart_data;
	$arr_sid = array();
	for($i=0;$i<count($old_cart_data);$i++){
		$sid = $old_cart_data[$i][0];//旧数据的供应商ID
		$arr_sid[] = $sid;
	}

	foreach($visitor_cart as $key => $val){
		$supply_id = $val[0];//游客数据的供应商ID
		$_data     = $val[1];//游客数据的购物车数据
		
		//如果旧数据已存在供应商ID
		if( in_array( $supply_id , $arr_sid ) ){
			for($i=0;$i<count($new_cart_data);$i++){
				$sid = $new_cart_data[$i][0];
				
				if( $sid == $supply_id ){//供应商ID相同时进入
					foreach($_data as $k => $v){
						$pid  = $v[0];
						$pnum = $v[1];
						$pos  = $v[2];
						
						$join = true;
						
						$json_arr = $new_cart_data[$i][1];//该供应商的产品数据
						//循环是否有相同产品
						for($j=0;$j<count($json_arr);$j++){
							$arr_pid = $json_arr[$j][0];
							if( $pid == $arr_pid ){
								$pid_num = (int)$pnum + (int)$json_arr[$j][1];
							}
						}
						for($j=0;$j<count($json_arr);$j++){
							$arr_pid = $json_arr[$j][0];
							$arr_pos = $json_arr[$j][2];
							
							if( $pid == $arr_pid && $pos == $arr_pos ){
								$arr_num = $json_arr[$j][1];
								$new_cart_data[$i][1][$j][1] = (int)$arr_num + (int)$pnum;
								$join = false;
								break;
							}
						}
						
						if($join){
							$new_cart_data[$i][1][] = $v;
						}
					}
				}
			}			
		}else{
			array_push($new_cart_data,array('0'=> $supply_id,'1'=> $_data));
		}	
	}
}
//与数据库数据合并---end

//首页数据处理---start
foreach($new_cart_data as $hk => $hv){
	$pro_data = $hv[1];
	$supply_id = $hv[0];
	
	foreach($pro_data as $pk => $pv){
		$pid   = $pv[0];
		$p_num = $pv[1];
		$pos   = $pv[2];
		
		$antiArray[$pid."_".$pos] = array('sid'=>$supply_id,'pid'=>$pid,'p_num'=>$p_num,'pos'=>$pos);
	}
}
//首页数据处理---end

//新数据
$cart_data = json_encode($new_cart_data);
$home_cart_data = json_encode($antiArray);

if( $cart_id > 0 ){
	$sql = "update weixin_commonshop_cart_status set cart_data='".$cart_data."',home_cart_data='".$home_cart_data."',createtime=now() where isvalid=true and user_id=".$user_id." and customer_id=".$customer_id."";
}else{
	$sql = "insert into weixin_commonshop_cart_status(isvalid,customer_id,user_id,cart_data,createtime,home_cart_data) values(true,".$customer_id.",".$user_id.",'".$cart_data."',now(),home_cart_data='".$home_cart_data."')";
}

_mysql_query($sql) or die('CS2 Query failed: ' . mysql_error());

$error = mysql_error();
if( empty( $error ) ){
	$data = array('code'=>10001,'op'=>'success','cart_data'=>$cart_data,'user_id'=>$user_id);
}else{
	$data = array('code'=>40001,'op'=>'error','cart_data'=>'');
}

echo json_encode($data);
?>