<?php

header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
$user_id   = -1;
$pid 	   = -1;
require('../common/common_from.php'); 
$pid       = $configutil->splash_new($_POST["pid"]);   

$query     = "select pro_card_level_id from weixin_commonshop_products where id = $pid limit 1";
$card_ob   = _mysql_query($query);
$card_le   = mysql_fetch_object($card_ob);
$level     = $card_le->pro_card_level_id;
if($level < 0)//没有等级的限制
{
	echo json_encode(array('status'=>1));
	die();
}
else
{

	$query_apply  = "select shop_card_id from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
	$result_apply = _mysql_query($query_apply) or die('Query failed: ' . mysql_error());
	$row_apply    = mysql_fetch_object($result_apply);
	$shop_card_id = $row_apply->shop_card_id;


	$sql 	= "select level_id from weixin_card_members where isvalid=true and user_id=".$user_id." and card_id=".$shop_card_id;
	$user_card_level_id = -1;
	$result = _mysql_query($sql) or die('Query failed会员等级1: ' . mysql_error());
	while ($row = mysql_fetch_object($result)) 
	{
		$user_card_level_id = $row->level_id;
	}
	$sql    = "select level from weixin_card_levels where isvalid=true and id=".$user_card_level_id;
	$user_card_level = -1;
	$result = _mysql_query($sql) or die('Query failed:会员等级2 ' . mysql_error());
	while ($row = mysql_fetch_object($result)) {
		$user_card_level = $row->level;
	}

	$sql    ="select level from weixin_card_levels where isvalid=true and id=".$level;
	$card_level = -1;
	$result = _mysql_query($sql) or die('Query failed:会员等级3 ' . mysql_error());
	while ($row = mysql_fetch_object($result)) 
	{
		$card_level = $row->level;
	}

	if($user_card_level < $card_level){
		echo 0;
	}else{
		echo 1;
	}

}

?>