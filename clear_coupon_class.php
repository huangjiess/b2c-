<?php
header("Content-type: text/html; charset=utf-8");
require('../config.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
$customer_id = -1;
if(!empty($_POST["customer_id"])){
	$customer_id = $configutil->splash_new($_POST["customer_id"]);
}
require('../customer_id_decrypt.php'); //�����ļ�,��ȡcustomer_id_en[���ܵ�customer_id]�Լ�customer_id[�ѽ���]

$user_id = -1;
if(!empty($_POST["user_id"])){
	$user_id = $configutil->splash_new($_POST["user_id"]);
}
//ɾ����Ӧ�ļ�¼
	$sql = "update weixin_commonshop_coupon_using set isvalid=false where customer_id=".$customer_id." and user_id=".$user_id;

	 //�����Ż�ȯ��־��¼��׷�ݸ����û��޷�ʹ������
	_file_put_contents("log_update_youhuiquan.txt", date("Y-m-d H:i:s").",sql=======".$sql."\r\n",FILE_APPEND);
	_mysql_query($sql) or die('updateUsing Query failed: ' . mysql_error());
	echo "1001";
?>