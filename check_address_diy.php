<?php
require_once('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

$location_p = "";
$location_c = "";
$location_a = "";

if(!empty($_POST['location_p'])){
    $location_p = $configutil->splash_new($_POST['location_p']);
}
if(!empty($_POST['location_c'])){
    $location_c = $configutil->splash_new($_POST['location_c']);
}
if(!empty($_POST['location_a'])){
    $location_a = $configutil->splash_new($_POST['location_a']);
}

$parent_id = -1;
$query1 = "select id from weixin_commonshop_team_area where isvalid = true and all_areaname ='".$location_p.$location_c.$location_a."' and customer_id = ".$customer_id;
$result1 = _mysql_query($query1);
while( $row1 = mysql_fetch_object($result1) ){
	$parent_id = $row1->id;
}

$resultArr = array(
	"result" 	=> -1,
	"diy_area"  => array(),
	"msg"		=> ''
	);

if ($parent_id > 0){
	$area_id = 0;
	$areaname = '';
	$query2 = "select id,areaname from weixin_commonshop_team_area where isvalid = true and parent_id=".$parent_id." and grade = 3 and customer_id =".$customer_id;
	$result2 = _mysql_query($query2);
	while( $row2 = mysql_fetch_object($result2) ){
		$area_id = $row2->id;
		$areaname = $row2->areaname;
		array_push($resultArr["diy_area"],array(
				"id"	=> $area_id,
				"area"	=> $areaname
			));
	}
	
}else{
	$error = mysql_error();
	$terror = "操作失败:".mysql_error();
	$resultArr["msg"] = empty($error) ? 'no data' : $terror;
	mysql_close($link);
	echo json_encode($resultArr);
	die;
}


$error = mysql_error();
$terror = "操作失败:".mysql_error();
$resultArr["result"] = empty($error) ? 1 : -1;
$resultArr["msg"] = empty($error) ? 'success' : $terror;
mysql_close($link);
echo json_encode($resultArr);

?>