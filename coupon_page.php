<?php
header("Content-type: text/html; charset=utf-8");
require('../config.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

require('../../weixinpl/common/utility_shop.php');
// var_dump($_POST);
//接收一系列参数---start
$user_id = -1;
if(!empty($_POST["user_id"])){
	$user_id = $configutil->splash_new($_POST["user_id"]);
}
$customer_id = -1;
if(!empty($_POST["customer_id"])){
	$customer_id = $configutil->splash_new($_POST["customer_id"]);
}
$start = 0;
if( $_POST["start"] != "" ){
	$start = $configutil->splash_new($_POST["start"]);
}
$end = 5;
if( $_POST["end"] != "" ){
	$end = $configutil->splash_new($_POST["end"]);
}
//产品ID
$pid = '';
if( $_POST["pid"] != "" ){
	$pid = $configutil->splash_new($_POST["pid"]);
}
//优惠券类型 0、全部 1、平台 2、单品
$coupon_type = 0;
if( $_POST["coupon_type"] != "" ){
	$coupon_type = $configutil->splash_new($_POST["coupon_type"]);
}
//排序
$sort = 0;
if( $_POST["sort"] != "" ){
	$sort = $configutil->splash_new($_POST["sort"]);
}

//操作动作
$op = "";
if( $_POST["op"] != "" ){
	$op = $configutil->splash_new($_POST["op"]);
}

//判断从哪里进来，0:从个人中心进来，不可选择 1:下单页面可选择
$w = 0;
if( $_POST["w"] != "" ){
	$w = $configutil->splash_new($_POST["w"]);
}

//usercoupon表对应ID
$cid = '';
if( $_POST["cid"] != "" ){
	$cid = $configutil->splash_new($_POST["cid"]);
}
//供应商ID
$supply_id = '';
if( $_POST["supply_id"] != "" ){
	$supply_id = $configutil->splash_new($_POST["supply_id"]);
}
//是否可以使用团长免单券，1是，0否
$is_use_free_coupon = 0;
if( $_POST["is_use_free_coupon"] != "" ){
	$is_use_free_coupon = $configutil->splash_new($_POST["is_use_free_coupon"]);
}

$coupon_onoff = 0;
if( !empty($_POST['coupon_onoff']) ){					//拼团优惠券开关
	$coupon_onoff = $_POST['coupon_onoff'];
}

//接收一系列参数---end

$query_parameter = "SELECT u.id,u.Money,u.deadline,u.NeedMoney,c.connected_id,u.use_roles,u.is_receive,u.is_used,u.startline,u.class_type,c.title,c.startline as starttime,c.Days as endtime";//查询参数

$query = " FROM weixin_commonshop_couponusers u left join weixin_commonshop_coupons c on u.coupon_id=c.id WHERE u.user_id=".$user_id." AND u.customer_id=".$customer_id." AND u.isvalid=true AND u.type=1 ";//查询条件

if($w == 1){//从订单进来,只查询可用优惠券
	$query .= " AND u.is_used=0 AND u.is_receive=true AND u.deadline >='".date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")."'  AND u.is_receive=true";
}

if(!empty($pid)){//从订单页面进来接收到单品id
	if($op=='use'){
		$arr = explode(',',$pid);
		$str = '';
		foreach ($arr as $k => $v) {
			$str .= " c.connected_id LIKE '%".$v."%' or ";
		}
		$str = substr($str,0,strlen($str)-3);

		$query .= " and (c.user_scene=0 or (c.user_scene=1 and (".$str.")))";
	}
	//查询当前已点击的优惠券
	$sql = "select couponusers_id from weixin_commonshop_coupon_using where isvalid=true and customer_id=".$customer_id." and user_id=".$user_id;
	$result = _mysql_query($sql) or die('selectUsing Query failed: ' . mysql_error());
	$usingStr = '';
	while ($row = mysql_fetch_object($result)) {
		$usingStr .= $row->couponusers_id.",";
	}
	$usingStr = substr($usingStr,0,strlen($usingStr)-1);
	if(!empty($usingStr)){
		$query .= " and u.id not in(".$usingStr.")";
	}
}

switch($op){
	case 'use'://可用优惠券
		$query .= " AND u.is_used=0 AND u.deadline >='".date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")."'  AND u.startline <='".date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")."' and u.is_receive=true";
		if( $is_use_free_coupon ){
			 if(!$coupon_onoff){
		        $query .= " AND u.class_type=3 ";
		    }	
		} else {
			$query .= " AND u.class_type!=3";
		}
	break;
	case 'not_use'://可用优惠券，但未到使用时间
	    $query .= " AND u.is_used=0 AND u.startline >'".date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")."' and u.is_receive=true";
	    if( $is_use_free_coupon ){
			 if(!$coupon_onoff){
		        $query .= " AND u.class_type=3 ";
		    }	
		} else {
			$query .= " AND u.class_type!=3";
		}
	break;
	case 'due'://不可用优惠券
		$query .= " AND (u.is_used=1 OR u.deadline <'".date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")."' OR u.startline >'".date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")."')";
	break;
	case 'fail'://失效优惠券
		$query .= " AND u.deadline <'".date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")."'  AND u.is_receive=true";
	break;
	case 'already'://已用优惠券
		$query .= " AND u.is_used=1  AND u.is_receive=true";
	break;
	case 'can'://可用优惠券
		$query .= " AND u.is_used=0 AND u.is_receive=true AND u.deadline >='".date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")."'  AND u.is_receive=true";
	break;
	case 'take'://可领取优惠券
		$query .= " AND u.deadline >='".date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")."'  AND u.startline <='".date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")."' and u.is_receive=false  ";
	break;
	case 'record':
		if($cid>0){
			//删除对应的记录
			$sql = "update weixin_commonshop_coupon_using set isvalid=false where customer_id=".$customer_id." and user_id=".$user_id." and supply_id=".$supply_id;
			_mysql_query($sql) or die('updateUsing Query failed: ' . mysql_error());
			//添加对应的记录
			$query = "insert into weixin_commonshop_coupon_using (customer_id,isvalid,createtime,couponusers_id,user_id,supply_id) value(".$customer_id.",true,now(),".$cid.",".$user_id.",".$supply_id.")";
			_mysql_query($query) or die('insertUsing Query failed: ' . mysql_error());
		}else{
			//删除对应的记录
			$sql = "update weixin_commonshop_coupon_using set isvalid=false where customer_id=".$customer_id." and user_id=".$user_id." and supply_id=".$supply_id;
			_mysql_query($sql) or die('updateUsing Query failed: ' . mysql_error());
		}
		echo '1001';
		die;
	break;
	case 'receive':
		//查找个人用户当天所领取优惠券数量
		$query_count = "SELECT COUNT(1) AS C_Count FROM weixin_commonshop_couponusers WHERE user_id=".$user_id." AND customer_id=".$customer_id." AND isvalid=true AND coupon_id=".$cid." AND createtime LIKE '".date("Y")."-".date("m")."-".date("d")."%'";
		$C_Count = 0;
		$result_count = _mysql_query($query_count) or die('w9787 Query failed: ' . mysql_error());
		while ($row_count = mysql_fetch_object($result_count)) {
			$C_Count=  $row_count->C_Count;
		}

		//查找优惠券已发放数量
		$query_count = "SELECT COUNT(1) AS H_Count FROM weixin_commonshop_couponusers WHERE  customer_id=".$customer_id." AND isvalid=true AND coupon_id=".$cid." ";
		$H_Count = 0;
		$result_count = _mysql_query($query_count) or die('w97871 Query failed: ' . mysql_error());
		while ($row_count = mysql_fetch_object($result_count)) {
			$H_Count=  $row_count->H_Count;
		}
		//发放给主播数量
		$query_anchor = "SELECT SUM(surplus) AS sum_surplus from mb_account_coupon where isvalid=true and c_id=".$cid;
		$sum_surplus = 0;
		$result_count = _mysql_query($query_anchor) or die('w97873 Query failed: ' . mysql_error());
		while ($row_count = mysql_fetch_object($result_count)) {
			$sum_surplus =  $row_count->sum_surplus;
		}
		$H_Count = $H_Count + $sum_surplus;

		//查找用户共拿此优惠券数量
		$query_count = "SELECT COUNT(1) AS U_Count FROM weixin_commonshop_couponusers WHERE user_id=".$user_id." AND customer_id=".$customer_id." AND isvalid=true AND coupon_id=".$cid." ";
		$U_Count = 0;
		$result_count = _mysql_query($query_count) or die('w97872 Query failed: ' . mysql_error());
		while ($row_count = mysql_fetch_object($result_count)) {
			$U_Count=  $row_count->U_Count;
		}

		//更改领取状态
		$ccoupon_id   = 0;		//ID
		$CouponMoney  = 0;		//获取随机金额
		$NeedMoney	  = 0;		//满多少可使用
		$MinMoney	  = 0;		//随机领取最低金额
		$MaxMoney	  = 0;		//随机领取最高金额
		$DaysType     = 0;	    //截止使用类型
		$class_type   = 0;	    //截止使用类型
		$MoneyType    = 0;	    //领取金额类型，0:随机金额,1:固定金额
		$CanGetNum    = 0;	    //每人每天可领取数量
		$personNum    = 0;	    //每人可领取数量
		$couponNum    = 0;	    //领取数量
		$Days		  = "";		//截止使用天数
		$startline    = "";		//使用开始时间
		$get_roles	  = 0;	    //推广员以上身份才可领取
		$use_roles	  = 0;	    //推广员以上身份才可使用
		$sql_u = "select id,MinMoney,MaxMoney,NeedMoney,Days,DaysType,get_roles,use_roles,MoneyType,startline,class_type,couponNum,personNum,CanGetNum,getStartTime,getEndTime,storenum from weixin_commonshop_coupons where is_open=1 and isvalid=true and customer_id=".$customer_id." and id=".$cid."";
		$code = "d".$user_id.strtotime(date('Y-m-d H:i:s'));
		$result = _mysql_query($sql_u) or die('sql_u failed: ' . mysql_error());
		while ($row = mysql_fetch_object($result)) {
			$ccoupon_id	  = $row->id;
			$MinMoney	  = $row->MinMoney;
			$MaxMoney	  = $row->MaxMoney;
			$NeedMoney	  = $row->NeedMoney;
			$Days		  = $row->Days;
			$DaysType	  = $row->DaysType;
			$MoneyType	  = $row->MoneyType;
			$startline	  = $row->startline;
			$class_type	  = $row->class_type;
			$couponNum	  = $row->couponNum;
			$personNum	  = $row->personNum;
			$CanGetNum	  = $row->CanGetNum;
			$get_roles	  = $row->get_roles;
			$use_roles    = $row->use_roles;
			$getStartTime = $row->getStartTime;
            $getEndTime	  = $row->getEndTime;
            $storenum	  = $row->storenum;
		}

		$get_roles = explode('_',$get_roles);

		$Coupon_ex 	 = new shopMessage_Utlity();
        $roles_a  = $Coupon_ex->check_user_roles($get_roles,$customer_id,$user_id);

        $get_roles = implode('_',$get_roles);

		if( $roles_a['code']==1 ){
		    $data = '{ "code":[4004],"CouponMoney":[0]}';//判断领取身份
			echo $data;
			die;
		}

		if( $storenum <= 0 && $storenum!=-1 ){//领取数量达上限
			$data = '{ "code":[4001],"CouponMoney":[0]}';
			echo $data;
			die;
		}
		if($personNum<=$U_Count && $personNum!=-1){//每人可领取数量达上限
			$data = '{ "code":[4002],"CouponMoney":[0]}';
			echo $data;
			die;
		}
		if($CanGetNum<=$C_Count && $CanGetNum!=-1){//每人每天可领取数量达上限
			$data = '{ "code":[4003],"CouponMoney":[0]}';
			echo $data;
			die;
		}
		$deadline = "1970-01-01 00:00";
		
		if($DaysType==1){
			$deadline = $Days;
			$end_day  = strtotime($Days);
		}else{
			$deadline  = date('Y-m-d H:i:s',strtotime("+".$Days." day"));
			$startline = date("Y-m-d H:i:s",time());
			$end_day   = strtotime("+".$Days." day");
		}

		

		if($MoneyType==0){
			$CouponMoney = $MaxMoney;//固定金额
		}else{
			$CouponMoney = rand($MinMoney,$MaxMoney);//获取随机金额
		}

		$source = 5;//领券中心发放的优惠券
		$customer_red_id = 0;

		if(2 == $class_type) {//首次领取发放的优惠券
			$source = 3;
		}

		//判断商城详情优惠券进来
		$pid = -1;
		if(!empty($_POST['t'])){
			$source = 4;
			$pid 	= $configutil->splash_new($_POST["pid"]);

			$getStart_day = strtotime($getStartTime);//领取日期
	        $getEnd_day = strtotime($getEndTime);

	        $now_day = time();

	        if($end_day<$now_day && $getStart_day<$now_day && $now_day<$getEnd_day){		//已经过期的优惠卷则不能领取
				$data = '{ "code":[4005],"CouponMoney":[0]}';
				echo $data;
				die;
	        }

	        if($pid < 0) {
	        	$data = '{ "code":[4006],"CouponMoney":[0]}';
				echo $data;
				die;
	        } 
		}

		if(!empty($_POST['customer_red_id'])){//组发放的优惠券
			$customer_red_id = $configutil->splash_new($_POST["customer_red_id"]);
			$source = 2;
		}

		$query_u="insert into weixin_commonshop_couponusers(code,user_id,customer_id,Money,deadline,NeedMoney,type,is_used,isvalid,createtime,class_type,coupon_id,use_roles,coupon_use_inentity,is_receive,startline,source,pid) values ('".$code."',".$user_id.",".$customer_id.",".$CouponMoney.",'".$deadline."',".$NeedMoney.",1,0,true,now(),".$class_type.",".$ccoupon_id.",'".$use_roles."','1_-1',1,'".$startline."',".$source.",".$pid.")";
		$res_u = _mysql_query($query_u) or die('领取 Query failed: ' . mysql_error());

		// 减库存
		$after_storenum = $storenum - 1;
		if( $res_u && ( $storenum != -1 || $after_storenum >= 0 ) ){
		    $after_storenum = (int)$after_storenum;
		    $sql = "UPDATE weixin_commonshop_coupons set storenum='{$after_storenum}' where id='{$ccoupon_id}' ";
		    _mysql_query($sql);
		}

        if(3 == $class_type){
            //团长免单优惠券的信息推送
            $shopmessage = new shopMessage_Utlity();

            $query="select weixin_fromuser from weixin_users  where isvalid=true and id=".$user_id;
            $result = _mysql_query($query) or die('send_template_message4 Query failed: ' . mysql_error());
            while ($row = mysql_fetch_object($result)) {
                $fromuser	= $row->weixin_fromuser;
                break;
            }
            $msg_content = "你已成功领取一张团长免单优惠券，可选择商品免费开团，成团后可收货\n";
            $shopmessage->SendMessage($msg_content,$fromuser,$customer_id);
        }


		if($customer_red_id){ //組發放才有的ID
			$insert_sql = "insert into red_status_t (customer_id,isvalid,status,createtime,customer_red_id) value(".$customer_id.",true,1,now(),".$customer_red_id.")";
			_mysql_query($insert_sql) or die('insert_sql1 Query failed: ' . mysql_error());
		}
		$data = '{ "code":[1000],"CouponMoney":['.$CouponMoney.'],"class_type":['.$class_type.']}';
		echo $data;
		die;
	break;
	default:
	#code...
	break;
}

//筛选条件-----start
//优惠券类型
if($coupon_type){
	if($coupon_type=='shop_type'){//平台专用券
		$query .= " AND c.connected_id<0 ";
	}elseif($coupon_type=='pro_type'){//单品专用券
		$query .= " AND c.connected_id>0 ";
	}
}
$query = $query_parameter .$query;
//排序
if($sort){
	switch($sort){
		case 1://时间升序
			$query .= ' order by u.createtime asc';
		break;
		case 2://时间降序
			$query .= ' order by u.createtime desc';
		break;
		case 3://金额升序
			$query .= ' order by u.Money asc';
		break;
		case 4://金额降序
			$query .= ' order by u.Money desc';
		break;
		default:
			$query .= ' order by u.id desc';
		break;
	}
}
//筛选条件-----end

/*****************只显示未使用，未过期的优惠券*******************/

$keyid 		  = -1;	// 优惠劵id
$Money 		  = 0;	//优惠劵金额
$NeedMoney 	  = 0;	//使用优惠劵的限制金额
$startline	  = "1970-01-01";//开始日期
$deadline	  = "1970-01-01";//截止日期
$type_str     = "仅限在线支付";//类型
$connected_id = 0;//店铺优惠券ID
$data 		  = '';
$overload     = 0;	//是否继续加载 1:不加载 0:继续加载
$use_roles    = 0;
$is_receive   = 0;	//领取状态
$is_used      = 0;	//使用状态

$query .= "  limit ".$start.",".$end."";

//查询是否开启优惠券 2017/11/12修改 根据报障10837
$query_is_coupon = "select is_coupon from weixin_commonshops where isvalid=true and customer_id=".$customer_id." limit 0,1";
$is_coupon=0; //是否开启优惠券
$result_is_coupon = _mysql_query($query_is_coupon) or die('W3667 Query failed: ' . mysql_error());
while ($row_is_coupon = mysql_fetch_object($result_is_coupon)) {
	$is_coupon = $row_is_coupon->is_coupon;
	break; 
}
if($is_coupon==1){//优惠券开关开启才返回优惠券  2017/11/12补充 根据报障10837
	$result = _mysql_query($query) or die('W333 Query failed: ' . mysql_error());
	while ($row = mysql_fetch_object($result)) {
		$keyid 	      =  $row->id;
		$deadline     =  $row->deadline;
		$startline    =  $row->startline;
		$is_used	  =  $row->is_used;
		$connected_id =  $row->connected_id;
		$title        =  $row->title;
		$p_content    = '仅限购买商城内的商品使用';
		$c_content    = '平台专用券';
		$c_top        = 'top_r';
		$word_color   = 'red';
		$arrow_color  = 'arrow_red';
		$starttime    =  $row->starttime;
		$endtime      =  $row->endtime;

		$usestr       =  $is_used==1?'has_used':'has_lost';

		if(strtotime($starttime) > time()){   //未到优惠券使用时间,不可用状态
			$usestr = 'not_used';
		}else if($is_used ==1){
			$usestr = 'has_used';
		}else{
			$usestr = 'has_lost';
		}

		if($connected_id>0){//查询单品名字
			$p_name = '';
			$query_p = 'select name from weixin_commonshop_products where isvalid=true and id in ('.$connected_id.')';
			$result_p = _mysql_query($query_p) or die('W221 Query failed: ' . mysql_error());
			while ($row_p = mysql_fetch_object($result_p)) {
				$p_name .= $row_p->name . ',';
			}
			$p_name = substr($p_name,0,strlen($p_name)-1);
			$p_content = $p_name.'专用';
			$c_content = '商品专用券';
			$c_top     = 'top_y';
			$word_color = 'yellow';
			$arrow_color = 'arrow_yellow';
		}
		$Money 	      = round($row->Money,2);
		$NeedMoney    = round($row->NeedMoney,2);
		$overload     = 1 ;
		$use_roles    =  $row->use_roles;
		$is_receive   =  $row->is_receive;
		$class_type   =  $row->class_type;

	    $use_roles = explode('_',$use_roles);

		$Coupon_exe 	= new shopMessage_Utlity();
	    $roles_b  	 	= $Coupon_exe->check_user_roles($use_roles,$customer_id,$user_id);
	    $is_inentity = 1;
		if( $roles_b['code']==1 ){
		    $is_inentity = 0;
		}

		$data .='{
					"keyid": "'.$keyid.'",
					"deadline": "'.$deadline.'",
					"startline": "'.$startline.'",
					"Money": "'.$Money.'",
					"NeedMoney": "'.$NeedMoney.'",
					"is_inentity": "'.$is_inentity.'",
					"connected_id": "'.$connected_id.'",
					"p_content": "'.$p_content.'",
					"c_content": "'.$c_content.'",
					"usestr": "'.$usestr.'",
					"c_top": "'.$c_top.'",
					"word_color": "'.$word_color.'",
					"arrow_color": "'.$arrow_color.'",
					"is_receive": "'.$is_receive.'",
					"c_class_type": "'.$class_type.'",
					"type_str": "'.$type_str.'",
					"title":"'.$title.'"
				},';

	}
	$data=rtrim($data,',');
}


$data = '{ "coupon":['.$data.'],"overload":[{"status": '.$overload.'}]}';
echo $data;
mysql_close($link);

?>