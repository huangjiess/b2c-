<?php
header("Content-type: text/html; charset=utf-8");
require('../config.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
$customer_id = -1;
if(!empty($_POST["customer_id"])){
	$customer_id = $configutil->splash_new($_POST["customer_id"]);
}
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]

$user_id = -1;
if(!empty($_POST["user_id"])){
	$user_id = $configutil->splash_new($_POST["user_id"]);
}
//删除对应的记录
	$sql = "update weixin_commonshop_coupon_using set isvalid=false where customer_id=".$customer_id." and user_id=".$user_id;

	 //保存优惠券日志记录，追溯个别用户无法使用问题
	_file_put_contents("log_update_youhuiquan.txt", date("Y-m-d H:i:s").",sql=======".$sql."\r\n",FILE_APPEND);
	_mysql_query($sql) or die('updateUsing Query failed: ' . mysql_error());
	echo "1001";
?>