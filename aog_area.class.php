<?php
header("Content-type: text/html; charset=utf-8");     
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD); 
mysql_select_db(DB_NAME) or die('Could not select database');

$pid = -1;
$pros = '';
$aog_p = '';
$aog_c = '';
$aog_a = '';
$aog_d = '';

if( !empty($_POST['pid']) ){
	$pid = $configutil->splash_new($_POST["pid"]);
}

if( !empty($_POST['pros']) ){
	$pros = $configutil->splash_new($_POST["pros"]);
}

if( !empty($_POST['aog_p']) ){
	$aog_p = $configutil->splash_new($_POST["aog_p"]);
}

if( !empty($_POST['aog_c']) ){
	$aog_c = $configutil->splash_new($_POST["aog_c"]);
}

if( !empty($_POST['aog_a']) ){
	$aog_a = $configutil->splash_new($_POST["aog_a"]);
}

if( !empty($_POST['aog_d']) ){
	$aog_d = $configutil->splash_new($_POST["aog_d"]);
}

//默认是无货
$return = array(
	'is_available' => 0,
	'aog_date' => ''
);
$query = "SELECT is_available, hours_time FROM aog_products_pros_areas WHERE pid=".$pid." AND pros='".$pros."' AND province='".$aog_p."' AND city='".$aog_c."' AND area='".$aog_a."' AND diy_area='".$aog_d."' AND isvalid=true";
$result = _mysql_query($query) or die('Query failed:'.mysql_error());
while ( $row = mysql_fetch_object($result) ) {
	$aog_date = date('m月d日', strtotime('+ '.$row -> hours_time.' hours'));
	$return = array(
		'is_available' => $row -> is_available,
		'aog_date' => $aog_date
	);
}

echo json_encode($return);

?>