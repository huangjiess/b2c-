<?php
header("Content-type: text/html; charset=utf-8");
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
$user_id 		= -1;
// require('../common/common_from.php');
$pid = $configutil->splash_new($_POST["pid"]);
$now_time = time();

//判断抢购商品的时间是够合法
$sql="select issnapup,buystart_time,countdown_time from weixin_commonshop_products where isvalid=true and id=".$pid ;
$result = _mysql_query($sql) or die('Query failed抢购商品时间: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
	$issnapup       = $row->issnapup;
	$buystart_time  = $row->buystart_time;
	$countdown_time = $row->countdown_time;
	if($issnapup == 1){																//判断是否为抢购商品
		$buystart_time  = strtotime($buystart_time);		//获取抢购商品抢购开始时间的时间戳
		$countdown_time = strtotime($countdown_time);		//获取抢购商品抢购结束时间的时间戳
		if ($now_time<$buystart_time) {
			$json["status"] = 1;
			$json["msg"] = "还没到抢购开始时间";
			$jsons=json_encode($json);
			die($jsons);
		}elseif($now_time>$countdown_time){
			$json["status"] = 2;
			$json["msg"] = "已过抢购结束时间";
			$jsons=json_encode($json);
			die($jsons);
		}
	}
}
//此种情况为无错情况，非抢购商品或所购买的抢购商品在抢购时间中
$json["status"] = 3;
$json["msg"] = "";
$jsons=json_encode($json);
die($jsons);
?>