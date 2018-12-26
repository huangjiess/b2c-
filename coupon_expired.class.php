<?php 
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../customer_id_decrypt.php');
require('../common/utility.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

$data = array();
$user_id = -1;
if(!empty($_POST["user_id"])){
	$user_id = $configutil->splash_new($_POST["user_id"]);
}
$batchcode = '';
if(!empty($_POST["batchcode"])){
	$batchcode = $configutil->splash_new($_POST["batchcode"]);
}
if($user_id<0){
	$data['status'] = -1;
	$data['errmsg'] = '非法用户！';
	die(json_encode($data));
}

$price = 0;
$deadline = '';
$query = "select cu.deadline from weixin_commonshop_order_coupons oc INNER JOIN weixin_commonshop_couponusers cu on cu.id=oc.C_id where oc.batchcode='".$batchcode."' and oc.isvalid=true";

$result = _mysql_query($query) or die("query_coupon : ".mysql_error());
while($row = mysql_fetch_object($result)){
	$deadline = $row->deadline;
}
if( strtotime($deadline) < time() ){
	$sql = "update weixin_commonshop_order_prices set price=price+CouponPrice,CouponPrice=0 where batchcode='".$batchcode."'";
	$result = _mysql_query($sql) or die("sql_coupon : ".mysql_error());
	
	$price = 0;
	$query = "select price from weixin_commonshop_order_prices where batchcode='".$batchcode."'";
	$result = _mysql_query($query) or die("query_coupon : ".mysql_error());
	while($row = mysql_fetch_object($result)){
		$price = $row->price;
	}
	$data['status'] = -2;
	$data['totalprice'] = $price;
	echo json_encode($data);
	die;
}
$data['status'] = 1;
echo json_encode($data);
?>