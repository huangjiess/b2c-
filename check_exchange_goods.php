<?php
/*开发须知：
1.产品价格必须查库（包含活动产品）
2.对产品数量做小数，负数处理
*/
header("Content-type: text/html; charset=utf-8");//ini_set('display_errors','on');
require_once('../config.php');
require('../common/common_ext.php');
// require('../common/utility_fun.php');
$customer_id 	= $_POST['customer_id'];
require_once('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST, DB_USER, DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
// require('../proxy_info.php');
// require_once('../common/utility_shop.php');
// require_once('../common/utility_shop_pay.php');
// require_once('order_newform_function.php');

$user_id_en=$_POST['user_id'];
if($user_id_en){
    $user_id 		= passport_decrypt($user_id_en);
}else{
    $user_id		= $_SESSION["user_id_".$customer_id];
}


$json["status"] = 200;
$json["msg"] 	=   "验证成功";
$json_data 		=i2post('json_data','');
$json_data 		= json_decode($json_data,true);	//json转数组

$is_select_card		=i2post('is_select_card',0);	//会员卡使用开关
$card_member_id		= -1;
$select_card_id=i2post('select_card_id',0);
if($select_card_id){
    $card_member_id		= $select_card_id;	//会员卡id
    $card_member_id		= passport_decrypt((string)$card_member_id);	//解密
    $card_discount = get_card_discount($card_member_id);
    $json['card_discount'] = $card_discount;
}

$res_arr = get_exc_goods_act($json_data,$user_id,$customer_id); //[0] 包含的门槛数组; [1] 换购产品价格总和  [2] 活动id数组 [3] key为活动标识，value为对应参与活动购买的产品数量

check_act_append_rule($res_arr[2] ,$customer_id);
check_status_time($res_arr[2] ,$customer_id);
check_every_max_num($res_arr[3],$res_arr[2],$customer_id);
$json['threshold_arr'] =  $res_arr[0];
$json['ex_price_count'] = $res_arr[1];

$jsons			= json_encode($json);
die($jsons);




/**
 * [获取换购产品的id和对应活动的id,返回门槛和价格和换购产品的价格总额]
 * @param  [type：array] $json_data          [description:订单产品数组]
 * @param  [type] $user_id                   [description:用户ID]
 * @param  [type] $customer_id               [description:商家ID]
 * @param  [type：array] $threshold_arr      [description:包含所有换购活动的门槛]
 * @param  [type] $ex_price_count            [description:所有换购产品价格总和]
 * @param  [type: array] $act_id_arr         [description:包含所有换购活动id数组]
 * @param  [type: array] $every_ex_max_num   [description:订单中参与换购活动对应的产品数量]
 */
function get_exc_goods_act($json_data, $user_id, $customer_id){
    $shopLen = count($json_data);
    for ($i = 0; $i < $shopLen; $i++) {
        $shopId = $json_data[$i][0];		//店铺id
        $proNum = $json_data[$i][1];        //产品数组
        if($shopId == -1){
            $act_id_arr = Array();
            $threshold_arr = Array();
            $limit_buy_num = Array();
            $every_ex_max_num = Array();
            $ex_price_count = 0.00;
            for ($j = 0; $j < count($proNum); $j++) {

                $is_exchange = $proNum[$j][11];       //判断是否为换购产品  0：不是；1： 是
                //如果是换购活动的产品，则将活动的id存起来
                if($is_exchange == 1){
                    $proId = $proNum[$j][0];                   //产品id
                    $rcount = $proNum[$j][2];                  //数量
                    $exchange_act_id = $proNum[$j][10];       //对应换购产品的活动id
                    $act_id_arr[] = $exchange_act_id;
                    if($every_ex_max_num["act_".$exchange_act_id]){
                        $every_ex_max_num["act_".$exchange_act_id] += $rcount;
                    }else{
                        $every_ex_max_num["act_".$exchange_act_id] = $rcount;
                    }

                    $threshold_arr[] = get_act_threshold($exchange_act_id,$customer_id);
                    $ex_price_count += get_ex_goods_price($exchange_act_id,$proId,$rcount);

                    //查询该活动每人换购的数量的限制
                    $limit_arr = get_ex_limit($exchange_act_id,$proId);
                    $num_per_person = $limit_arr[0];    //次数
                    $num_per_time = $limit_arr[1];      //数量

                    //再查询出该用户已经购买了多少次
                    $havebuy_limit_times = check_limit_goods_num($user_id,$exchange_act_id,$proId);
                    if($havebuy_limit_times >= $num_per_person){
                        $p_name = select_pro_name($proId);
                        $json["status"] = 400;
                        $json["msg"] 	=   "[".$p_name."]换购产品已达到限购次数，请换购其他产品";
                        $jsons			= json_encode($json);
                        die($jsons);
                    }else{
                        if(!$limit_buy_num[$exchange_act_id.'_'.$proId] && $rcount<= $num_per_time){
                            $limit_buy_num[$exchange_act_id.'_'.$proId] = $rcount;
                        }else{
                            if($limit_buy_num[$exchange_act_id.'_'.$proId] + $rcount < $num_per_time){
                                $limit_buy_num[$exchange_act_id.'_'.$proId] = $rcount;
                            }else{
                                $p_name = select_pro_name($proId);
                                $json["status"] = 400;
                                $json["msg"] 	=   "[".$p_name."]换购产品超过限购次数，请换购其他产品";
                                $jsons			= json_encode($json);
                                die($jsons);
                            }
                        }

                    }
                }
            }
        }
    }
    $res[0] = $threshold_arr;//门槛数组
    $res[1] = $ex_price_count;//换购产品价格总和
    $res[2] = $act_id_arr; //活动id数组
    $res[3] = $every_ex_max_num; //key为活动标识，value为对应参与活动购买的产品数量
    return $res;
}


/**
* [检测该笔订单中包含不同活动的满赠产品是都达到的每个换购活动每笔订单满赠产品的最大数额限制]
* @param  [type: array] $every_ex_max_num    [description:订单中参与换购活动对应的产品数量]
 * @param [type：array] $act_id_arr          [description:活动数组]
 * @param [type] $customer_id                [description:商家编号]
 */
function check_every_max_num($every_ex_max_num,$act_id_arr,$customer_id){
    $act_id_arr = array_unique($act_id_arr);
    for($i = 0; $i<count($act_id_arr); $i++){
        $query = "select exchange_num from weixin_commonshop_exchange where  isvalid=1 and  id='".$act_id_arr[$i]."' and customer_id=".$customer_id;
        $query_res  = _mysql_query($query) or die('check_every_max_num Query failed: ' . mysql_error());
        while ($row  = mysql_fetch_object($query_res))
        {
            $exchange_num = $row->exchange_num;  //每笔订单可换赠产品总量
        }
        $act_id = $act_id_arr[$i];
        if($every_ex_max_num["act_".$act_id]>$exchange_num && $exchange_num != -1){
            $json["status"] = 400;
            $json["msg"] 	=   "换购产品数量超过限制，请重新选购！";
            $jsons			= json_encode($json);
            die($jsons);
        }
    }
}


/**
 * [检测该活动某产品每人限购的次数和每次允许购买的数量]
 * @param  [type] $exchange_act_id          [description:换购活动ID]
 * @param  [type] $proId                    [description:换购产品ID]
 * @param  [type] $limit_arr[0]             [description:该活动该产品每人可购买次数]
 * @param  [type] $limit_arr[1]              [description:该活动该产品每次可购买数量]
 */
function get_ex_limit($exchange_act_id,$proId){
    $query_ex_limit = "select id,num_per_person,num_per_time from weixin_commonshop_exchange_products where  isvalid=1 and  exchange_id='".$exchange_act_id."' and  pid=$proId";
    $query_ex_limit_res  = _mysql_query($query_ex_limit) or die('get_ex_limit Query failed: ' . mysql_error());
    while ($row  = mysql_fetch_object($query_ex_limit_res))
    {
        $num_per_person = $row->num_per_person;  //每人可购买次数
        $num_per_time = $row->num_per_time;       //每次可购买数量
    }
    if($num_per_person == -1){
        $limit_arr[0] = 999999;
    }else{
        $limit_arr[0] = $num_per_person;
    }
    if($num_per_time == -1){
        $limit_arr[1] = 999999;
    }else{
        $limit_arr[1] = $num_per_time;
    }

    return $limit_arr;
}


/**
 * [查询换购产品的价格]
 * @param  [type] $exchange_act_id          [description:换购活动ID]
 * @param  [type] $proId                    [description:换购产品ID]
 * @param  [type] $rcount                   [description:产品数量]
 * @param  [type] $priceCount               [description:换购产品价格（单价* 数量）]
 */
function get_ex_goods_price($exchange_act_id,$proId,$rcount){
    $query_exch_p = "select id,storenum,exchange_price from weixin_commonshop_exchange_products where  isvalid=1 and  exchange_id='".$exchange_act_id."' and  pid=$proId";
    $query_exch_p_res  = _mysql_query($query_exch_p) or die('get_ex_goods_price Query failed: ' . mysql_error());
    while ($row  = mysql_fetch_object($query_exch_p_res))
    {
        $exchange_p_id =  $row->id;
        $exchange_price = $row->exchange_price;  //换购活动的价格
        $ex_storenum = $row->storenum;             //换购活动的库存
    }
    if($rcount > $ex_storenum){
        $p_name = select_pro_name($proId);
        $json["status"] = 400;
        $json["msg"] 	=   "[".$p_name."]换购产品库存不足，请重新选购！";
        $jsons			= json_encode($json);
        die($jsons);
    }else{
        $priceCount = $exchange_price*$rcount;
        return $priceCount;
    }
}


/**
 * [验证参与的所有活动是否符合叠加规则]
 * @param  [type: array] $act_id_arr             [description:活动ID]
 * @param  [type: string] $customer_id           [description:商家ID]
 */
function check_act_append_rule($act_id_arr ,$customer_id){
    $act_id_arr = array_unique($act_id_arr);
    $no_superposition_count = 0;
    for($i=0; $i<count($act_id_arr); $i++){
        $query = "select is_superposition from weixin_commonshop_exchange where status=3 and isvalid=1 and  id='".$act_id_arr[$i]."' and customer_id=".$customer_id;
        $query_res      = _mysql_query($query) or die('check_act_append_rule Query failed: ' . mysql_error());
        while ($row  = mysql_fetch_object($query_res))
        {
            $is_superposition = $row->is_superposition;  //是否叠加
            if(!$is_superposition){
                $no_superposition_count += 1;
                if($no_superposition_count > 1){
                    $json["status"] = 400;
                    $json["msg"] 	=   "选择的换购产品不符合活动叠加规则！";
                    $jsons			= json_encode($json);
                    die($jsons);
                }
            }
        }
    }
}


/**
 * [检测该用户参与该活动的换购次数]
 * @param  [type] $user_id                    [description:用户ID]
 * @param  [type] $exchange_act_id            [description:换购活动ID]
 * @param  [type] $proId                      [description:产品ID]
 */
function check_limit_goods_num($user_id,$exchange_act_id,$proId){
    $limit_goods_num = "select count(distinct batchcode) as o_count from weixin_commonshop_orders where user_id=".$user_id." and  pid=".$proId." and is_exchange=1 and  exchange_id='".$exchange_act_id."' and status>=0";
    $query_imit_goods_num  = _mysql_query($limit_goods_num) or die('check_limit_goods_num Query failed: ' . mysql_error());
	while ($row  = mysql_fetch_object($query_imit_goods_num)){
        $limit_rcount = $row->o_count;  //已经购买的次数
    }
    return $limit_rcount;
}


/**
 * [查询换购活动的门槛]
 * @param  [type] $exchange_act_id        [description:换购活动ID]
 * @param  [type] $customer_id            [description:商家ID]
 * @param  [type] $threshold              [description:该活动的换购门槛价格]
 */
function get_act_threshold($exchange_act_id, $customer_id){
    $query_exch = "select threshold from weixin_commonshop_exchange where status=3 and isvalid=1 and  id='".$exchange_act_id."' and customer_id=".$customer_id;
    $query_exch_res      = _mysql_query($query_exch) or die('get_act_threshold Query failed: ' . mysql_error());
    while ($row  = mysql_fetch_object($query_exch_res))
    {
        $threshold = $row->threshold;  //换购活动的门槛
    }
    return $threshold;
}


/**
 * [检测参与的活动是否“已发布”或“进行中”，而且在活动时间范围内]
 * @param  [type :array] $act_id_arr            [description:活动idID]
 * @param  [type] $card_discount         [description:折扣]
 */
function check_status_time($act_id_arr ,$customer_id){
    $act_id_arr = array_unique($act_id_arr);
    $nowtime = strtotime(date('Y-m-d H:i:s',time()));
    for($i=0; $i<count($act_id_arr); $i++){
        $query = "select title,status,starttime,endtime from weixin_commonshop_exchange where status=3 and isvalid=1 and  id='".$act_id_arr[$i]."' and customer_id=".$customer_id;
        $query_res      = _mysql_query($query) or die('check_status_time Query failed: ' . mysql_error());
        while ($row  = mysql_fetch_object($query_res))
        {
            $status =    $row->status;           //状态
            $starttime = $row->starttime;       //开始时间
            $endtime =   $row->endtime;         //结束时间
            $title =  $row->title;
            if($status == 2 || $status == 3 ){
                if(strtotime($starttime) > $nowtime &&  strtotime($endtime)< $nowtime){
                    $json["status"] = 400;
                    $json["msg"] 	=   "[".$title."]换购活动不在活动时间范围！";
                    $jsons			= json_encode($json);
                    die($jsons);
                }
            }else{
                $json["status"] = 400;
                $json["msg"] 	=   "[".$title."]换购活动不在进行中！";
                $jsons			= json_encode($json);
                die($jsons);
            }
        }
    }
}

/**
 * [获取会员卡折扣]
 * @param  [type] $card_member_id        [description:会员卡ID]
 * @param  [type] $card_discount         [description:折扣]
 */
function get_card_discount($card_member_id){
    /*查找会员卡等级、余额、折扣开始*/
    $level_id 			= -1;	//会员卡等级
    $card_discount 		= 0;	//会员卡折扣

    if( 0 < $card_member_id ){
        // 查找会员等级
        $sql 	= "select level_id from weixin_card_members where  id=" . $card_member_id . " limit 0,1";
        $result = _mysql_query($sql) or die('w308 Query failed: ' . mysql_error());
        while ($row = mysql_fetch_object($result)) {
            $level_id = $row->level_id;
            break;
        }

        //查询个人会员卡等级折扣
        $query2  = "SELECT discount from weixin_card_levels where isvalid=true and  id=".$level_id. " limit 0,1";
        $result2 = _mysql_query($query2) or die('w314 Query failed: ' . mysql_error());
        while ($row2 = mysql_fetch_object($result2)) {
            $card_discount = $row2->discount;
            break;
        }
    }
    /*查找会员卡等级、折扣结束*/

    return $card_discount;
}


/**
 * [查询产品名字]
 * @param  [type] $proId          [description:产品ID]
 * @param  [type] $p_name         [description:产品名字]
 */
function select_pro_name($proId){
    //产品名字查询
    $query_pro = 'SELECT name FROM weixin_commonshop_products where id=' . $proId;
    $result_pro = _mysql_query($query_pro) or die('select_pro_name Query failed: ' . mysql_error());
    while ($row = mysql_fetch_object($result_pro)) {
        $p_name = $row->name;
    }
    return $p_name;
}

