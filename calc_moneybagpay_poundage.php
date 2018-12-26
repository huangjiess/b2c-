<?php
/**
 * 用于处理计算订单的零钱支付手续费
* User: zhaojing
* Date: 17/3/1
* Time: 下午3:10
*/
//TODO:处理计算手续费的ajax请求  lsw
/* 接收GET参数 pay_batchcode (支付订单号) , customer_id , industry_type(行业类型，默认“shop”)
 * 调用方法 internal_payment_handle.php / get_mb_poundage_by_pbc
* 请求返回json数据 [isopen_poundage,poundage_percentage,pay_price,poundage]
*/
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require_once('../common/common_ext.php');
require_once('../function_model/moneybag.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('order_Form Could not select database');
$moneybag = new MoneyBag();
$industry_type = i2get('industry_type','shop');
$sum_all_money = i2get('sum_all_money',0);
$data = $moneybag->calc_pay_poundage($customer_id,$sum_all_money);
echo json_encode($data);
//echo 5555;