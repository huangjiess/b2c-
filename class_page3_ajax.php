<?php
header("Content-type: text/html; charset=utf-8");
require('../config.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../proxy_info.php'); //解决fenxiao无法获取正常路径
require_once('../common/common_ext.php');
require('./tax_function.php');
$new_baseurl = Protocol.$http_host; //新商城图片显示

$op = i2post("op","");
$serach_type = i2post("serach_type",1);
$type_id = i2post("type_id",-1);

switch ($op) {
	case 'get_cartPro':

		//PS:每次访问此文件，未避免其他错误，必须以POST方式提交以下的参数
		//----参数区域 start
		$pid = $configutil->splash_new($_POST["pid"]);
		$pos = $configutil->splash_new($_POST["pos"]);
		$user_id = $configutil->splash_new($_POST["user_id"]);
		$rcount = $configutil->splash_new($_POST["rcount"]);
		//----参数区域 end

		$resultArr 			= array();//返回数组
		$posArr 			= array();//属性数组
		$isout				= 0;	//上架下架, 1:下架 0:上架
		$p_name				= "";	//产品名
		$need_score			= 0;	//需要的积分
		$p_storenum         = 1;	//库存
		$propertyids        = "";	//属性id
		$p_now_price        = 0;	//现价
		$default_imgurl     = "";	//封面图片
		$pos_name			= "";
		$pos_parent_name	= "";
		$limit_num			= 0;	//限购数量
		$islimit			= 0;	//是否限购：0否，1是
		$isgobuy			= 0;	//能够购买的数量
		$is_identity		= 0;	//产品是否需要身份证购买
		$tax_type			= 1;
		$query = 'SELECT 	
					name,
					isout, 
					storenum,
					now_price,
					need_score,			
					propertyids,
					islimit,
					limit_num,
					default_imgurl,
					is_identity,
					tax_type
					FROM weixin_commonshop_products where  isvalid=true and id=' . $pid;

		$result = _mysql_query($query) or die('Query failed1: ' . mysql_error());
		while ($row = mysql_fetch_object($result)) {
			$isout			= $row->isout;
			$p_name			= $row->name;
			$need_score		= $row->need_score;
			$p_storenum		= $row->storenum;
			$p_now_price	= $row->now_price;
			$propertyids	= $row->propertyids;
			$default_imgurl	= $row->default_imgurl;
			$limit_num		= $row->limit_num;
			$islimit		= $row->islimit;
			$tax_type 		= $row->tax_type;
			$is_identity 	= $row->is_identity;
		}

		/*---查询该产品是否拥有批发属性---*/
		$wholesale_id  = -1;
		$wholesale_num = 1;
		$wholesale_pos = "";
		$query = "SELECT id FROM weixin_commonshop_product_extend WHERE isvalid=true AND pid = $pid LIMIT 1";
		$result= _mysql_query($query) or die('Query failed 63: ' . mysql_error());
		while( $row = mysql_fetch_object($result) ){
			$wholesale_id = $row->id;
		}
		/*---查询该产品是否拥有批发属性---*/

		$premium = 0;
		if( $islimit == 1 ){
			//获取当天购买此产品的数量
			$isgobuy = 0;
			$rrcount = 0;
			$query = 'SELECT IFNULL(sum(rcount),0) as rcount FROM weixin_commonshop_orders where user_id='.$user_id.' and pid='.$pid.' and paystatus=1 and TO_DAYS(paytime) = TO_DAYS(NOW())';
			$result = _mysql_query($query) or die('Query failed1_2: ' . mysql_error());
			while ($row = mysql_fetch_object($result)) {
				$rrcount = $row->rcount;
			}
			$isgobuy = $limit_num - $rrcount;	//用户可选择的产品限购数量
			$limit_arr = array(
				"limit_num"	=>$limit_num,
				"day_buy_num"=>$rrcount,
				"isgobuy"=>$isgobuy
			);
			

			if( $isgobuy < 1 ){
				$resultArr["code"] = -1;
				mysql_close($link);
				$jsons=json_encode($resultArr);
				die($jsons);	
			}
		}


		if( !empty( $pos ) ){
			$query = 'SELECT 	
					need_score,
					now_price,
					storenum
					FROM weixin_commonshop_product_prices where product_id=' . $pid." and proids='".$pos."'";
			$result = _mysql_query($query) or die('Query failed2: ' . mysql_error());
			$p_storenum = 0;
			while ($row = mysql_fetch_object($result)) {
				$need_score		= $row->need_score;
				$p_storenum		= $row->storenum;
				$p_now_price	= $row->now_price;
			}
			
			/*查找属性名*/
			$propertyarr = explode("_",$pos);
			//var_dump($propertyarr);
			$pcount = count($propertyarr);
			for( $i = 0; $i < $pcount; $i++){
				if (!is_numeric($propertyarr[$i])) {
					$result = array(
							'code' => 0
							);
					die(json_encode($result));
				}
				$query = "select p1.name,p2.name as parent_name from weixin_commonshop_pros p1 LEFT JOIN weixin_commonshop_pros p2 on p1.parent_id=p2.id where p1.id=".$propertyarr[$i];
				//echo $query;
				$result = _mysql_query($query) or die('Query failed3: ' . mysql_error());
				while ($row = mysql_fetch_object($result)) {
					$pos_name			= $row->name;
					$pos_parent_name	= $row->parent_name;
				}
				$posArr[$i]["pos_name"]			= $pos_name;
				$posArr[$i]["pos_parent_name"]	= $pos_parent_name;
			}
		}
		/*行邮税*/
		if($tax_type > 1 ){
			$tax_data 	= get_tax_set($pid);
			$tax		= get_single_sum_tax($tax_data,-1,($p_now_price*$rcount),($p_now_price*$rcount),($p_now_price*$rcount),1,$pid,$customer_id,0,$rcount,1);
		}
		if($tax['code']>20000){
			$tax['result'] = '该产品总金额：'.($p_now_price*$rcount).'元，'.$tax['result'];
		}
		/*行邮税*/
		//var_dump($tax);
		$error  = mysql_error();
		if(!empty($error)){
			$resultArr["code"] = 0;
		}else{
			$resultArr["code"] 				= 1;
			$resultArr["name"] 				= $p_name;
			$resultArr["isout"] 			= $isout;
			$resultArr["storenum"] 			= $p_storenum;
			$resultArr["now_price"] 		= $p_now_price;
			$resultArr["need_score"]		= $need_score;	
			$resultArr["propertyids"] 		= $propertyids;
			$resultArr["default_imgurl"]	= $default_imgurl;
			$resultArr["is_identity"]		= $is_identity;
			$resultArr["posArr"]			= $posArr;
			$resultArr["islimit"]			= $islimit;
			$resultArr["limit_data"]		= $limit_arr;
			
			/*行邮税*/
			$tax_name = get_tax_name($tax_type);
			$resultArr["tax_name"] 	    = $tax_name;
			$resultArr["tax"] 			= $tax;
			$resultArr["tax_type"] 		= $tax_type;
			/*行邮税*/

			/**/
			$resultArr["wholesale_num"] = $wholesale_num;//最低批发数量
			$resultArr["wholesale_id"]  = $wholesale_id;	//判断是否有批发属性 -1:没 大于0，有wholesale_pos
			$resultArr["wholesale_pos"] = $wholesale_pos;
			/**/
		}
		mysql_close($link);
		$jsons=json_encode($resultArr);


		
	break;
	
	default:
		# code...
		break;
}
echo $jsons;

?>