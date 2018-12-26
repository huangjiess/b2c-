<?php
header("Content-type: text/html; charset=utf-8"); 
session_cache_limiter( "private, must-revalidate" ); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

//限购活动
require_once('../../mshop/web/model/restricted_purchase.php');

$user_id      = $configutil->splash_new($_POST["user_id"]);			//购买者id
$product_id   = $configutil->splash_new($_POST["product_id"]);		//产品id
$rcount       = $configutil->splash_new($_POST["rcount"]);			//购买数量
$activitie_id = $configutil->splash_new($_POST["act_id"]);			//活动id


$res["errcode"] = 0;
$res["errmsg"]  = "success";
$res["is_allow_restricted"] = 1;		//是否允许购买 0：不允许  1：可以
//检查限购活动有效性
$restricted_purchase   = new model_restricted_purchase();

$restricted_data 	= [
					'user_id' => $user_id,
					'buy_num' => $rcount,
					'product_id' => $product_id,
					'restricted_id' => $activitie_id,
					'customer_id' => $customer_id,
					];
$restricted_result = $restricted_purchase->checkUserRestrictedPurchase($restricted_data);	//验证活动有效性
if( $restricted_result['errcode'] == 0 ){
	
}else if($restricted_result['errcode'] ==101){
	
}else{
	$json["errcode"]    = $restricted_result['errcode'];
	$json["errmsg"] 	= $restricted_result['errmsg'];
	$json["is_allow_restricted"] = 0;
	$res			= json_encode($json);
	die($res);
}

echo json_encode($res);
?>