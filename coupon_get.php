<?php
header("Content-type: text/html; charset=utf-8");
require('../config.php');
require('../common/utility_shop.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

$op = '';
if(!empty($_GET["op"])){
	$op = $configutil->splash_new($_GET["op"]);
}
$user_id = -1;
if(!empty($_POST["user_id"])){
	$user_id = $configutil->splash_new($_POST["user_id"]);
}
$customer_id = -1;
if(!empty($_POST["customer_id"])){
	$customer_id = $configutil->splash_new($_POST["customer_id"]);
	$customer_id = passport_decrypt($customer_id);
}
$coupon_id = -1;
if(!empty($_POST["coupon_id"])){
	$coupon_id = $configutil->splash_new($_POST["coupon_id"]);
}
$pid = -1;
if(!empty($_POST["pid"])){
	$pid = $configutil->splash_new($_POST["pid"]);
}
if($op == 'user_get'){
	$Coupon_msg	 = new Utility_Coupon();
	$data 		 = $Coupon_msg->get_user_coupon($coupon_id,$customer_id,$user_id,$pid);
	echo $data;
}else{
	//送首次优惠券
	$Coupon_msg	 = new Utility_Coupon();
	$data 		 = $Coupon_msg->create_coupon($customer_id,$user_id,-1,2);
	echo $data;
}

mysql_close($link);

?>