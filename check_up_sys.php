<?php
header("Content-type: text/html; charset=utf-8");     
require('../config.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD); 
mysql_select_db(DB_NAME) or die('Could not select database');

$starttime = explode(' ',microtime());

//先在weixin_users_extends创建原本的三个字段用来转移
$query = "ALTER TABLE weixin_users_extends ADD occupation VARCHAR(56) NULL,ADD wechat_id VARCHAR(32) NULL,ADD wechat_code VARCHAR(256) NULL";
_mysql_query($query)or die('Query failed 11: ' . mysql_error());


//先进行修改操作
$query = "UPDATE system_user_t as s INNER JOIN weixin_users_extends as w SET w.occupation = s.occupation,w.wechat_id = s.wechat_id,w.wechat_code = s.wechat_code WHERE w.user_id = s.user_id";
$result = _mysql_query($query)or die('Query failed 15: ' . mysql_error());



$occupation		= '';
$wechat_id		= '';
$wechat_code	= '';
$user_id 		= -1;
$up_sql			= '';
$ins 			= 'INSERT INTO weixin_users_extends(user_id,customer_id,from_type,isvalid,is_up_openid,createtime,occupation,wechat_id,wechat_code) VALUES ';
$query 			= "SELECT occupation,wechat_id,wechat_code,user_id FROM system_user_t WHERE isvalid=true GROUP BY user_id DESC";
$result= _mysql_query($query)or die('Query failed 16: ' . mysql_error());
while( $row = mysql_fetch_object($result) ){
	$occupation 	= $row->occupation;
	$wechat_id 		= $row->wechat_id;
	$wechat_code 	= $row->wechat_code;
	$user_id		= $row->user_id;	
	$id  = -1;
	$sql = "SELECT id FROM weixin_users_extends WHERE isvalid=true AND user_id = $user_id LIMIT 1";
	$res = _mysql_query($sql)or die('Query failed 16: ' . mysql_error());
	while( $row2 = mysql_fetch_object($res) ){
		$id = $row2->id;
	}
	if( $id < 0 ){
		$ins_sql .= "($user_id,$customer_id,0,true,0,now(),'$occupation','$wechat_id','$wechat_code'),";
	}

}
if($ins_sql !='' ){
	$insert_sql = rtrim("$ins$ins_sql",",");
	//echo $insert_sql;die;
	_mysql_query($insert_sql)or die('Query failed 47: ' . mysql_error());
}

//echo $insert_sql;

//删除原来的三个字段
$query = "ALTER TABLE system_user_t DROP COLUMN occupation,DROP COLUMN wechat_id,DROP COLUMN wechat_code";
_mysql_query($query)or die('Query failed 8: ' . mysql_error());

$endtime = explode(' ',microtime());
$thistime = $endtime[0]+$endtime[1]-($starttime[0]+$starttime[1]);
$thistime = round($thistime,3);
echo '<script>alert("全部初始化完毕！执行耗时：'.$thistime.'秒。")</script>';



?>