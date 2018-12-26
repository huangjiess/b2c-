<?php

	header("Content-type: text/html; charset=utf-8");    
	require_once('../config.php');
	session_cache_limiter( "private, must-revalidate" ); 
	require_once('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
	//require('../back_init.php'); 
	$link = mysql_connect(DB_HOST,DB_USER,DB_PWD); 
	mysql_select_db(DB_NAME) or die('Could not select database');

	require_once('../common/common_from.php');
    require_once(ROOT_DIR."mp/lib/LogOpe.php");
    $log_ope = new LogOpe("order_from");

    //日志记录 - 方法版v3
    function zlog_insert($log_name, $log_content, $isclean = 0){
        $debugInfo = debug_backtrace();
        $log_name .= "_".date("Ymd").".log";
        $zlog_name = "{$_SERVER['DOCUMENT_ROOT']}/weixinpl/log/{$log_name}";//log文件路径
        $time = date("Y-m-d H:i:s");
        $content_info = "DEBUG --- time:{$time} --- LINE:{$debugInfo[0]['line']} --- func:{$debugInfo[1]['function']} --- URL:{$_SERVER['PHP_SELF']} ---\n";
        $log_content = $content_info.$log_content;
        //file_put_contents($zlog_name ,$log_content,$isclean ?: FILE_APPEND);
        _file_put_contents($log_name ,$log_content,$isclean ?: FILE_APPEND);
    }



	$supply_id = -1;
	if(!empty($_POST['supply_id'])){
		$supply_id = $_POST['supply_id'];   		//品牌供应商ID或者平台ID
	}
	$ii = 0;
	if(!empty($_POST['ii'])){
		$ii = $_POST['ii'];							//定位第几个订单
	}
    
    $sum_express = 0;
	if(!empty($_POST['sum_express'])){
		$sum_express = $_POST['sum_express'];				//运费
	}
    
    $supply_pid_str = -1;	
    if(!empty($_POST["supply_pid_str"])){
        $supply_pid_str = $configutil->splash_new($_POST["supply_pid_str"]);
    }
    
    $supply_pros_str = '';	
    if(!empty($_POST["supply_pros_str"])){
        $supply_pros_str = $configutil->splash_new($_POST["supply_pros_str"]);
    }
    $pidcount_str = '';	
    if(!empty($_POST["pidcount_str"])){
        $pidcount_str = $configutil->splash_new($_POST["pidcount_str"]);
    }
    $city = '';
    if(!empty($_POST["city"])){
        $city = $configutil->splash_new($_POST["city"]);
    }
    //是否为自提产品   2018.2.26
    if(!empty($_POST["is_pickup"])){ 
        $is_pickup = $configutil->splash_new($_POST["is_pickup"]);
    }
    if(empty($is_pickup)){
        $is_pickup = 1;
    }
    
    $sum_express_str = '';
    if ($sum_express == -1)
    {
        $sum_express_str = '无配送方式';
    } else if($sum_express == 0) {
        $sum_express_str = '免邮';
    } else {
        if(OOF_P != 2) $sum_express_str = OOF_S.$sum_express;
        
        if(OOF_P == 2) $sum_express_str = $sum_express.OOF_S;
    }

    $o_shop_id = -1;
    $shop_arr = [];
    if (!empty($_SESSION['o_shop_id_'.$user_id]) && $_SESSION['o_shop_id_'.$user_id] > 0)
    {
        $o_shop_id = $_SESSION['o_shop_id_'.$user_id];
    } else {
        if(!empty($_POST["shop_id"])){
            $o_shop_id = $configutil->splash_new($_POST["shop_id"]);
        }
    }
        $supply_pid_arr     = explode(',', $supply_pid_str);
        $supply_pros_arr    = explode(',', $supply_pros_str);
        $pidcount_arr    = explode(',', $pidcount_str);
        $proxy_id       = []; //代理商id

        //查询是否开启子门店模式
        $isopen_branch = 0;
        $query_branch_setting = "SELECT isopen_branch from ".WSY_DH.".orderingretail_shop_branch_setting where customer_id=".$customer_id." limit 1";
        $result_branch_setting = _mysql_query($query_branch_setting);
        while ($row_branch_setting = mysql_fetch_object($result_branch_setting))
        {
            $isopen_branch = $row_branch_setting -> isopen_branch;
        }


        foreach ($supply_pid_arr as $key => $val)
        {
            if($o_shop_id>0){
                $sql = "SELECT os.proxy_id FROM ".WSY_DH.".orderingretail_proxy_product opp   
                            INNER JOIN 
                              ".WSY_DH.".orderingretail_shop os ON opp.proxy_id = os.proxy_id
                            where os.id=".$o_shop_id." AND opp.product_id = '{$val}' AND (opp.store_count-opp.freeze_count) >= '{$pidcount_str[$key]}' AND os.isvalid=true AND is_freeze=false";
            }else{
                $sql = "SELECT  
                        opp.proxy_id
                    FROM 
                        ".WSY_DH.".orderingretail_proxy_product opp 
                    INNER JOIN 
                        ".WSY_DH.".orderingretail_proxy op ON opp.proxy_id = op.id
                    WHERE 
                        opp.product_id = '{$val}' AND opp.proids = '{$supply_pros_arr[$key]}' 
                    AND (opp.store_count-opp.freeze_count) >= {$pidcount_arr[$key]} AND op.isvalid = TRUE AND op.status = 'checked'";
            }
            $result = _mysql_query($sql) or die('orderingretail_proxy_product failed:'.mysql_error());
            while ($row = mysql_fetch_object($result))
            {
                $proxy_id['shop'][$val."_".$pidcount_str[$key]][] = $row -> proxy_id;
            }
            if($isopen_branch>0){
                //查询子门店
                if($o_shop_id>0){
                    //先判断门店库存是否足够
                    $sql_check_shop = "SELECT os.proxy_id FROM ".WSY_DH.".orderingretail_proxy_product opp   
                            INNER JOIN 
                              ".WSY_DH.".orderingretail_shop os ON opp.proxy_id = os.proxy_id
                            where os.id=".$o_shop_id." AND opp.product_id = '{$val}' AND opp.proids = '{$supply_pros_arr[$key]}'   AND (opp.store_count-opp.freeze_count) >= '{$pidcount_str[$key]}' AND os.isvalid=true AND is_freeze=false limit 1";
                    $result_check_shop = _mysql_query($sql_check_shop);
                    while ($row_check_shop = mysql_fetch_object($result_check_shop))
                    {
                        $sql = "SELECT  
                            osb.id AS branch_id FROM 
                            ".WSY_DH.".orderingretail_shop_branch_pro obp 
                            INNER JOIN 
                              ".WSY_DH.".orderingretail_shop_branch osb ON obp.branch_id = osb.id
                            WHERE 
                                obp.product_id = '{$val}' AND obp.provalues = '{$supply_pros_arr[$key]}'
                            AND osb.longitude !='' AND osb.latitude !='' 
                            AND (obp.store_count-obp.freeze_count) >= '{$pidcount_str[$key]}' AND osb.isvalid = TRUE AND osb.status = 'open'
                            AND osb.shop_id=".$o_shop_id;
                        $result = _mysql_query($sql);
                        while ($row = mysql_fetch_object($result))
                        {
                            $proxy_id['branch_shop'][$val."_".$pidcount_str[$key]][] = $row -> branch_id;
                        }
                    }
                }else{
                    $sql = "SELECT  
                            osb.id AS branch_id
                        FROM 
                            ".WSY_DH.".orderingretail_shop_branch_pro obp 
                        INNER JOIN 
                            ".WSY_DH.".orderingretail_shop_branch osb ON obp.branch_id = osb.id
                        WHERE 
                            obp.product_id = '{$val}' AND obp.provalues = '{$supply_pros_arr[$key]}'
                            AND osb.longitude !='' AND osb.latitude !='' 
                            AND (obp.store_count-obp.freeze_count) >= '{$pidcount_str[$key]}' AND osb.isvalid = TRUE AND osb.status = 'open'";
                    $result = _mysql_query($sql);
                    while ($row = mysql_fetch_object($result))
                    {
                        $proxy_id['branch_shop'][$val."_".$pidcount_str[$key]][] = $row -> branch_id;
                    }
                }
            }
        }
        $p_count = count($supply_pid_arr);//产品数量
        $proxy_id_arr_count_shop = count($proxy_id['shop']);
        $proxy_id_arr_count_branch = count($proxy_id['branch_shop']);
        if( ($p_count > 1 && $proxy_id_arr_count_shop < $p_count)&&($p_count > 1 && $proxy_id_arr_count_branch < $p_count)  ){
            $proxy_id = [];
        }
        
        $shop_id        = [];  //门店id
        $shop_name      = [];  //门店名
        $shop_id_new    = [];  //门店id 新
        $shop_name_new  = [];  //门店名 新

        $branch_id        = [];  //子门店id
        $branch_id_new    = [];  //子门店id 新


        if (!empty($proxy_id['shop'])|| !empty($proxy_id['branch_shop']))
        {
			if($o_shop_id > 0){ //从门店列表进来不需要进行地址判断
                $shop_arr = true; //$shop_arr只做判断，不保存信息
            }else{
                if(!empty($proxy_id['shop'])) {
                    foreach ($proxy_id['shop'] as $key => $val) {
                        $proxy_id_new = implode(',', $val);

                        $sql = "SELECT id, shop_name FROM ".WSY_DH.".orderingretail_shop
                         WHERE customer_id='{$customer_id}' AND proxy_id IN ({$proxy_id_new}) 
                         AND isvalid=TRUE AND is_freeze=FALSE AND open_type='open'";// AND addr_city='{$city}'
                        $result = _mysql_query($sql) or die('orderingretail_shop_progroup_product failed:' . mysql_error());
                        while ($row = mysql_fetch_object($result)) {
                            $shop_id[$key][] = $row->id;
                            $shop_name[$key][] = $row->shop_name;
                        }
                    }

                    //取交集  获取共同的门店
                    $i = 0;
                    foreach ($shop_id as $k => $v) {
                        $i++;

                        if ($i == 1) {
                            $shop_id_new = $v;
                            $shop_name_new = $shop_name[$k];
                            continue;
                        }

                        $shop_id_new = array_intersect($shop_id_new, $v);
                        $shop_name_new = array_intersect($shop_name_new, $shop_name[$k]);
                    }

                    foreach ($shop_id_new as $key => $val) {
                        $shop_arr[] = array(
                            'shop_id' => $val,
                            'branch_id' => -1,  //子门店id设为-1
                            'shop_name' => $shop_name_new[$key]
                        );
                    }
                }

                //子门店
                if(!empty($proxy_id['branch_shop']) && !($p_count > 1 && $proxy_id_arr_count_branch < $p_count)){
                    foreach ($proxy_id['branch_shop'] as $key => $val) {
                        $branch_id_new = implode(',', $val);
    //                    foreach ($val as $k1 => $v1){
    //                        $branch_id[$key][] = $v1;
    //                    }
                        $query_branch_store = "select
                                    id, branch_name 
                                FROM
                                    ".WSY_DH.".orderingretail_shop_branch
                                where
                                    isvalid=true and status='open'
                                    and customer_id='{$customer_id}' and id in ({$branch_id_new}) ";// and city='".$city."'
                        $res_branch_store = _mysql_query($query_branch_store) or die('542 Query failed: ' . mysql_error());
                        while ($row_branch_store = mysql_fetch_object($res_branch_store)) {
                            $branch_id[$key][] = $row_branch_store->id;
                            $branch_name[$key][] = $row_branch_store->branch_name;
                        }
                    }


                    //取交集  获取共同的子门店
                    $i = 0;
                    foreach ($branch_id as $k => $v) {
                        $i++;

                        if ($i == 1) {
                            $branch_id_new = $v;
                            $branch_name_new = $branch_name[$k];
                            continue;
                        }

                        $branch_id_new = array_intersect($branch_id_new, $v);
                        $branch_name_new = array_intersect($branch_name_new, $branch_name[$k]);
                    }
                    foreach ($branch_id_new as $key => $val) {
                        $shop_arr[] = array(
                            'shop_id' => -1,     //门店id设为-1
                            'branch_id' => $val,
                            'shop_name' => $branch_name_new[$key]
                        );
                    }
                }
		    }
        }



    $is_get_data = false;
    if(!empty($_POST['is_get_data'])){
        $is_get_data = $_POST['is_get_data'];   		//品牌供应商ID或者平台ID
    }
    if($is_get_data){
        echo !empty($shop_arr) ? true : false; //是否可以选择自提
        die();
    }
?>



<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<title></title>
		 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta content="no" name="apple-touch-fullscreen">
    <meta name="MobileOptimized" content="320"/>
    <meta name="format-detection" content="telephone=no">
    <meta name=apple-mobile-web-app-capable content=yes>
    <meta name=apple-mobile-web-app-status-bar-style content=black>
    <meta http-equiv="pragma" content="nocache">
    <link rel="stylesheet" type="text/css" href="css/common.css">
    <script type="text/javascript" src="./assets/js/jquery.min.js"></script> 
		<style type="text/css">

		body{background: #F0EFF4;}
		.content-box{background: #FFFFFF;font-size: 1.4rem;position: relative;}
		.box-list{display: flex;justify-content: space-between;padding:0.8rem 2rem 0.8rem 1rem; border-bottom:1px solid #F9F9F9;position: relative;}
		.box-list .box-content{}
		.deliver{color:#797979;}
		.shop{color:#797979;position: absolute;bottom: 10px;right: 30px;}
		.right-arrow{width: 0.6rem;position: absolute;right: 0.6rem;bottom: 1rem;}
		select{appearance:none;-moz-appearance:none;-webkit-appearance:none;border:none;color: #999;outline: none}
		</style>


		
	</head>
	<body>
		<div class="content-box">
			<div class="box-list">
				<div class="title">
					请选择配送方式
				</div>
				<div class="box-content">
					
				</div>
			</div>
			<div class="box-list" <?php if ($sum_express != -1) { ?>onclick="choose_way(this);"<?php } ?> send_type=1 type_name="快递">
				<div class="title">
					快递
				</div>
				<div class="box-content deliver">
					<?php echo $sum_express_str;?>
				</div>
			</div>
            <?php if($is_pickup == 1){ ?>
			<div class="box-list" <?php if( !empty($shop_arr) ){?>  onclick="choose_way(this);" <?php } ?>  send_type=2 type_name="自提">
				<div class="title">
					自提
				</div>
<!--				<img src="images/iconfont-jiantou.png" class="right-arrow"/>-->
			</div>
            <?php } ?>
            <div class="box-content shop">
                <?php
                    if (empty($shop_arr))
                    {
                        echo '暂无门店可选择';
                    }
                ?>

            </div>
		</div>
	</body>

<script type="text/javascript">
	var user_id = '<?php echo $user_id ;?>';
	var supply_id = '<?php echo $supply_id ;?>';
	var ii = '<?php echo $ii; ?>';
	var city = '<?php echo $city; ?>';
	var shop_arr_json = '<?php echo json_encode($shop_arr); ?>';
	var o_shop_id_session = '<?php echo $o_shop_id;?>'
	function choose_way(obj){
		var send_type = $(obj).attr('send_type');
        var o_shop_id = -1;
        var o_branch_id = -1;
        var o_shop_name = '';

        if(o_shop_id_session < 0){
            localStorage.removeItem('orderingretail_store_'+user_id);
        }

        var orderingretail_store_object = localStorage.getItem('orderingretail_store_'+user_id); 	//读取localStorage的数据
        var orderingretail_store_object_arr = new Array();
        orderingretail_store_object_arr = JSON.parse(orderingretail_store_object);
        console.log(orderingretail_store_object_arr);

		//保存到localStorage,方便下次读取
		if(orderingretail_store_object_arr==null){			//创建
			var _A = new Array();				
            var rtn_array_temp = new Array(supply_id, o_shop_id,o_branch_id, o_shop_name, send_type);
            //var rtn_array_temp = new Array(send_type);
			_A[ii] = rtn_array_temp ;
			var rtn_array_json = JSON.stringify(_A);				//转JSON					
			localStorage.setItem('orderingretail_store_'+user_id,rtn_array_json);	//存入localStorage
		
		}else{					 			//修改自己的内容
//            if (send_type == 2)
//            {
//                orderingretail_store_object_arr[ii][1] = o_shop_id;
//                orderingretail_store_object_arr[ii][2] = o_branch_id;
//                orderingretail_store_object_arr[ii][3] = o_shop_name;
//            }
			orderingretail_store_object_arr[ii][4] = send_type ;
			var rtn_array_json = JSON.stringify(orderingretail_store_object_arr);	//转JSON				
			localStorage.setItem('orderingretail_store_'+user_id,rtn_array_json);	//存入localStorage
			//console.log(localStorage.getItem('store_'+user_id));
		}

        var location_info_city = '';
        var location_info_addr = '';//详细地址
        var store_object_l = sessionStorage.getItem('orderingretail_store_addr'+user_id); 	//读取localStorage的数据
        console.log(store_object_l);
        if(!check_empty(store_object_l)){
            var store_object_arr_l = JSON.parse(store_object_l);			//json转数
            location_info_city = store_object_arr_l[0];
            location_info_addr = store_object_arr_l[1];
            console.log('详细地址：'+location_info_addr);
            console.log('市：'+location_info_city);
        }
        var rtn_array_addr_temp = new Array(location_info_city,location_info_addr,send_type);
        var rtn_array_addr_json = JSON.stringify(rtn_array_addr_temp);
        sessionStorage.setItem('orderingretail_store_addr'+user_id,rtn_array_addr_json);

		history.replaceState({},'','order_form.php?customer_id=<?php echo $customer_id_en ;?>&eid=1');
		location.href = "order_form.php?customer_id=<?php echo $customer_id_en ;?>&eid=1";
	}
    function check_empty(str) {
        if(str==null || str=="" || typeof(str) == "undefined") return true;
        else return false;
    }

</script>
</html>