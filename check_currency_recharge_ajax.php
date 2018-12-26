<?php
header("Content-type: text/html; charset=utf-8");     
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$_SESSION["C_id"] = $customer_id;
require('../back_init.php'); 
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD); 
mysql_select_db(DB_NAME) or die('Could not select database');
require('../proxy_info.php');
require('../common/common_from.php');
_mysql_query("SET NAMES UTF8");

if(!empty($_POST["value"])){
	$key 	 = $configutil->splash_new($_POST["value"]);
}
if(!empty($_POST["account"])){
	$account 	 = $configutil->splash_new($_POST["account"]);
}
if(!empty($_POST["op"])){
	$op 	 = $configutil->splash_new($_POST["op"]);
}

$data = array();//返回数组
$data['errcode'] = -1;
$data['msg']     = "查找失败";


if(!empty($key) && !empty($account)){
	//根据卡密查找卡密相关信息
	$is_overtime = 1;  //充值卡是否过期,-1为已过期，-2为还没到充值时间
	
	$sql = "select key_t.money,list_t.status as card_status,key_t.endtime,key_t.starttime,key_t.status,key_t.recharge_id,key_t.id as key_id from currency_recharge_card_key_t as key_t inner join currency_recharge_card_list_t as list_t on list_t.id =key_t.recharge_id where key_t.`key`='".$key."' and key_t.account='".$account."' and key_t.isvalid=true and key_t.customer_id=".$customer_id;
	$result = _mysql_query($sql)or die( 'sql failed in 28: ' . mysql_error() );
	while ($row = mysql_fetch_object($result)) {
		$money   = $row->money;	
		$status  = $row->status;
		$key_id  = $row->key_id;
		$recharge_id  = $row->recharge_id;
		$starttime  = $row->starttime;
		$endtime  = $row->endtime;
		$card_status  = $row->card_status;
		break;				
	}
	$is_time = strtotime($endtime) - strtotime(date("Y-m-d H:i:s"),time());//是否过期
	$is_get_time = strtotime($starttime) - strtotime(date("Y-m-d H:i:s"),time());//是否到充值时间
		
	if($is_time<0){
		$is_overtime = -1;
	}
	if($is_get_time>0){
		$is_overtime = -2;
	}
	if(!empty($money)){
		$data['money']   = $money;
		$data['errcode'] = 0;
		$data['msg']     = "查找成功";
		$data['status']  = $status;
		$data['card_status']  = $card_status;
		$data['recharge_id']  = $recharge_id;
		$data['key_id']  = $key_id;
		$data['is_overtime']  = $is_overtime;		
	}
	if($op == "commit"){  //提交判断
		$query2 = "select count(1) as wcount from currency_recharge_card_key_t as key_t inner join currency_recharge_card_list_t as list_t on list_t.id =key_t.recharge_id where key_t.`key`='".$key."' and key_t.account='".$account."' and key_t.isvalid=true and key_t.customer_id=".$customer_id;
		
		$result2 = _mysql_query($query2) or die('Query2 failed: ' . mysql_error());
		while ($row2 = mysql_fetch_object($result2)) {
			$wcount      = $row2->wcount;
		}
		if($wcount < 1){  //输入密码错误，添加错误日志
			$card_id =-1;
			$sql_key = "select id as card_id from currency_recharge_card_key_t where isvalid=true and customer_id=".$customer_id." and account='".$account."'";
			$result_key = _mysql_query($sql_key) or die('sql_key failed: ' . mysql_error());
			while ($row_key = mysql_fetch_object($result_key)) {
				$card_id      = $row_key->card_id;
			}
			
			$sql_log = "insert into currency_recharge_card_operation_log(customer_id,card_id,account,password,user_id,operation_time,isvalid,createtime) values(".$customer_id.",".$card_id.",'".$account."','".$key."',".$user_id.",curdate(),true,now())";
			
			$result_log = _mysql_query($sql_log) or die('sql_log failed: ' . $sql_log);
			
			/*查找用户当天输入的错误次数*/
			$error_count = 0;
			$sql_error = "select count('id') as error_count from currency_recharge_card_operation_log where isvalid=true and customer_id=".$customer_id." and user_id=".$user_id." and operation_time=curdate()";
			
			$result_error = _mysql_query($sql_error) or die('sql_error failed: ' . mysql_error());
			while ($row_error = mysql_fetch_object($result_error)) {
				$error_count      = (int)$row_error->error_count;
			}
	
			$data['errcode'] = -2;
			$data['msg']     = "不存在的充值卡密!";
			$data['recharge_time']     = $error_count;
		}
	}
}

echo json_encode($data);
?>