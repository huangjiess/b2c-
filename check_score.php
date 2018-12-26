<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
$user_id 		= -1;
require('../common/common_from.php'); 
$shop_card_id = $configutil->splash_new($_POST["shop_card_id"]);

//查看粉丝商城会员卡等级开始
$sql="select id from weixin_card_members where isvalid=true and user_id=".$user_id." and card_id=".$shop_card_id;
//echo $sql;
$card_id = -1;
$result = _mysql_query($sql) or die('Query failed会员等级1: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
	$card_id = $row->id;
}
$query2 = "select remain_score from weixin_card_member_scores where isvalid=true and card_member_id=" . $card_id;
$result2 = _mysql_query($query2) or die('会员卡余额1 Query failed: ' . mysql_error());
$remain_score = 0;
while ($row2 = mysql_fetch_object($result2)) {
	$remain_score = $row2->remain_score;
}
echo $remain_score;
?>