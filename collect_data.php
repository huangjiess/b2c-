<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php'); //配置
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
_mysql_query("SET NAMES UTF8");

$user_id 	 = -1;
$c_id 		 = -1;			//产品ID或店铺ID
$type 		 = 0;			//收藏类型：1产品，2店铺
$op 		 = '';			//操作
$data 		 = array();

$user_id 	 = $_POST['user_id'];
$c_id 	 	 = $_POST['c_id'];
$type 		 = $_POST['type'];
$op 		 = $_POST['op'];	

switch($type){
	case 1:	//产品
		switch($op){
			case 'add':	//添加收藏
				if($user_id>0){
					$rcount = 0;	//是否有记录
					$query = 'select count(1) as rcount from weixin_user_collect where customer_id='.$customer_id." and user_id=".$user_id." and collect_id=".$c_id." and collect_type=1";
					$result = _mysql_query($query) or die('query failed'.mysql_error());
					while($row = mysql_fetch_object($result)){
						$rcount = $row->rcount;
					}
					if(0 == $rcount){	//无记录则插入新纪录
						$query2 = 'insert into weixin_user_collect(user_id,collect_id,collect_type,customer_id,createtime,isvalid) values('.$user_id.','.$c_id.',1,'.$customer_id.',now(),true)';
						_mysql_query($query2) or die('query failed2'.mysql_error());
						$wuc_id = mysql_insert_id();
						if($wuc_id>0){
							$data['status'] = 1;
						}else{
							$data['status'] = -1;
						}
					}else{	//有记录则更新纪录
						$query3 = 'update weixin_user_collect set isvalid=true,createtime=now() where customer_id='.$customer_id." and user_id=".$user_id." and collect_id=".$c_id." and collect_type=1";
						$result3 = _mysql_query($query3) or die('query failed3'.mysql_error());
						if($result3){
							$data['status'] = 1;
						}else{
							$data['status'] = -1;
						}
					}
					//更新收藏量
					if($data['status'] == 1){
						$query4 = 'update weixin_commonshop_products set collect_num=collect_num+1 where customer_id='.$customer_id." and id=".$c_id;
						_mysql_query($query4) or die('query failed4'.mysql_error());
					}
				}else{
					$data['status'] = -1;
				}
				break;
				
			case 'del':	//取消收藏
				if($user_id>0){
					$query5 = 'update weixin_user_collect set isvalid=false,createtime=now() where customer_id='.$customer_id." and user_id=".$user_id." and collect_id=".$c_id." and collect_type=1";
					$result5 = _mysql_query($query5) or die('query failed5'.mysql_error());
					if($result5){
						$data['status'] = 2;
						//更新收藏量
						$query6 = 'update weixin_commonshop_products set collect_num=collect_num-1 where customer_id='.$customer_id." and id=".$c_id;
						_mysql_query($query6) or die('query failed6'.mysql_error());
					}else{
						$data['status'] = -2;
					}
				}else{
					$data['status'] = -2;
				}
				break;
		}
		break;
		
	case 2:	//店铺
		switch($op){
			case 'add':	//添加收藏
				if($user_id>0){
					$scount = 0;	//是否有记录
					$query = 'select count(1) as scount from weixin_user_collect where customer_id='.$customer_id." and user_id=".$user_id." and collect_id=".$c_id." and collect_type=2";
					$result = _mysql_query($query) or die('query failed'.mysql_error());
					while($row = mysql_fetch_object($result)){
						$scount = $row->scount;
					}
					if(0 == $scount){	//无记录则插入新纪录
						$query2 = 'insert into weixin_user_collect(user_id,collect_id,collect_type,customer_id,createtime,isvalid) values('.$user_id.','.$c_id.',2,'.$customer_id.',now(),true)';
						_mysql_query($query2) or die('query failed2'.mysql_error());
						$wuc_id = mysql_insert_id();
						if($wuc_id>0){
							$data['status'] = 3;
						}else{
							$data['status'] = -3;
						}
					}else{	//有记录则更新新纪录
						$query3 = 'update weixin_user_collect set isvalid=true,createtime=now() where customer_id='.$customer_id." and user_id=".$user_id." and collect_id=".$c_id." and collect_type=2";
						$result3 = _mysql_query($query3) or die('query3 failed'.mysql_error());
						if($result3){
							$data['status'] = 3;
						}else{
							$data['status'] = -3;
						}
					}
					if($data['status'] == 3){
						//更新收藏量
						$query4 = 'update weixin_commonshop_brand_supplys set collect_num=collect_num+1 where isvalid=true and customer_id='.$customer_id." and user_id=".$c_id;
						_mysql_query($query4) or die('query failed4'.mysql_error());
					}
				}else{
					$data['status'] = -3;
				}
				break;
				
			case 'del':
				if($user_id>0){
					$query5 = 'update weixin_user_collect set isvalid=false,createtime=now() where customer_id='.$customer_id." and user_id=".$user_id." and collect_id=".$c_id." and collect_type=2";
					$result5 = _mysql_query($query5) or die('query failed5'.mysql_error());
					if($result5){
						$data['status'] = 4;
						//更新收藏量
						$query6 = 'update weixin_commonshop_brand_supplys set collect_num=collect_num-1 where isvalid=true and customer_id='.$customer_id." and user_id=".$c_id;
						_mysql_query($query6) or die('query failed6'.mysql_error());
					}else{
						$data['status'] = -4;
					}
				}else{
					$data['status'] = -4;
				}
				break;
		}
		//获取收藏量
		$collect_num = 0;
		$query7 = 'select collect_num from weixin_commonshop_brand_supplys where isvalid=true and customer_id='.$customer_id.' and user_id='.$c_id;
		$result7 = _mysql_query($query7) or die('query failed7'.mysql_error());
		while($row7 = mysql_fetch_object($result7)){
			$collect_num = $row7->collect_num;	//店铺收藏量
		}
		$data['collect_num'] = $collect_num;

		//品牌供应商收藏总数加上虚拟粉丝数 crm 15589
		$fans_nums_sql = "select virtual_fans_flag,virtual_fans_nums from weixin_commonshop_applysupplys where isvalid=true and user_id='".$c_id."'";
		$fans_nums_res = _mysql_query($fans_nums_sql);
		while($fans_nums_row = mysql_fetch_object($fans_nums_res)){
			$virtual_fans_flag = $fans_nums_row->virtual_fans_flag;	//虚拟粉丝开关
			$virtual_fans_nums = $fans_nums_row->virtual_fans_nums;	//虚拟粉丝数量
		}
		if ($virtual_fans_flag == 1) {
			$data['collect_num'] = (float)$data['collect_num']+(float)$virtual_fans_nums;
		}
		
		break;
		
	default:
		break;
}
echo json_encode($data);
?>