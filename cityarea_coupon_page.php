<?php
header("Content-type: text/html; charset=utf-8");
require_once('../config.php');
require_once ('../common/common_ext.php');
require_once ('../function_model/cityArea/o2o_coupon.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

$customer_id = i2post("customer_id");
$user_id     = i2post("user_id");
$start       = i2post("start");
$end         = i2post("end");
$use_type    = i2post("use_type");//可用、失效、已用
$orderby_type= i2post("orderby_type");//排序类型：时间、金额



$re_data['customer_id'] = $customer_id;
$re_data['user_id']     = $user_id;
$re_data['start']       = $start;
$re_data['end']         = $end;
$re_data['use_type']    = $use_type;
$re_data['orderby_type']= $orderby_type;
//echo json_encode($re_data);
//
//exit;

$cityarea_coupon = new O2oCoupon($customer_id);
$re_data         = $cityarea_coupon->get_user_cityarea_coupon($user_id,$use_type,$orderby_type,$start,$end);

echo json_encode($re_data);
mysql_close();
exit;



?>