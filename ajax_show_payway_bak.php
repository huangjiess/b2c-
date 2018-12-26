<?
	header("Content-type: text/html; charset=utf-8");
	require_once('../config.php');
	require_once("../common/common_ext.php");
	$customer_id=i2post("customer_id",0);
    require_once('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
	require_once("../public_method/show_pay_way.php");
    require_once(LocalBaseURL."function_model/moneybag.php");
    include_once(LocalBaseURL."public_method/member_card_operation.php");
    include_once(LocalBaseURL."public_method/order_operation.php");
    include_once(LocalBaseURL."public_method/handle_order_function.php");

	$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
	mysql_select_db(DB_NAME) or die('Could not select database');
	_mysql_query("SET NAMES UTF8");


    $op = i2post("op","pay_way");
    if($op == "pay_way"){ //显示支付方式
        $industry_type=i2post("industry_type","shop");
        $page_port=i2post("page_port","");
        $show_pay_way = new show_pay_way($customer_id,$industry_type,$page_port);
        $result=$show_pay_way->show();
        echo json_encode($result);
    }else if($op == "pay_way_industry"){

    }else if($op == "show_all"){
        $industry_type=i2post("industry_type","shop");
        $page_port=i2post("page_port","");
        $opt=i2post("opt","");
        $pay_user_id=i2post("pay_user_id","");
        $ordering_receive_mode=i2post("ordering_receive_mode","");
        $ordering_isopen_account=i2post("ordering_isopen_account","");
        $extra = array(
            'pay_user_id' => $pay_user_id,
            'ordering_receive_mode' => $ordering_receive_mode,
            'ordering_isopen_account' => $ordering_isopen_account
        );

        $show_pay_way = new show_pay_way($customer_id,$industry_type,$page_port);
        $result=$show_pay_way->show_all($opt,$extra);
        echo json_encode($result);
    }
    else if($op == "get_balance"){ //获取支付方式余额
        $pay_type = i2post("pay_type","");
        $user_id = i2post("pay_user_id",0);
        $industry_type=i2post("industry_type","shop");
        $city_type = i2post('city_type',-1);
        $sql_check_user = "select id from weixin_users where id = ".$user_id ." and customer_id = ".$customer_id;
        $exits_user = mysql_find($sql_check_user);
        $balance = 0 ;
        if(!empty($exits_user)){
            if($pay_type == "card"){
                $member_card_class = new member_card_operation();
                // _file_put_contents("log/remain.log","调用方法get_membercard_info($industry_type , $customer_id , $user_id,$city_type)",FILE_APPEND);
                $card_info = $member_card_class -> get_membercard_info($industry_type , $customer_id , $user_id,$city_type);
                $card_member_id = $card_info["card_member_id"];

                if($card_member_id > 0){
                    $balance = $member_card_class -> get_membercard_balance($card_member_id);
                }
            }else if($pay_type == "moneybag"){
                $moneybag_class = new moneybag();
                $balance = $moneybag_class -> get_moneybag_balance($customer_id,$user_id)["balance"];
            }
        }
        $balance = round($balance,2,PHP_ROUND_HALF_DOWN);
        echo json_encode(array("balance" => $balance));
    }else if ($op == "get_paycode"){
        $order_operation = new order_operation();
        $user_id = i2post("pay_user_id",0);
        $industry_type=i2post("industry_type","shop");
        $pay_batchcode = $order_operation ->get_order_paybatchcode($industry_type,$user_id);
        echo json_encode(array("status" => 1 , "pay_batchcode" => $pay_batchcode));
    }else if($op == "sel_store"){  //订货系统虚拟库存支付，判断库存是否足够
        $proxy_id = i2post("proxy_id",-1);
        $order_batchcode = i2post("order_batchcode");
        $query_item = "select or_product_id,proids,product_id,counts from ".WSY_DH.".orderingretail_order_item where batchcode='".$order_batchcode."' and customer_id=".$customer_id;
        $result_item = _mysql_query($query_item) or die('Query failed: ' . mysql_error());
        while($row = mysql_fetch_object($result_item)){
            //查询每件产品虚拟库存数量
            $or_product_id = $row->or_product_id;
            $proids = $row->proids;
            $product_id = $row->product_id;
            $counts = $row->counts;
            //查询每件产品虚拟库存数量
            $product_query = "select id,virtual_store_count from ".WSY_DH.".orderingretail_proxy_product where or_product_id='".$or_product_id."' and proids='".$proids."' and proxy_id=".$proxy_id." limit 1";
//                $product_count = mysql_num_rows($product_query) or die('Query failed: ' . mysql_error());

            $product_res = _mysql_query($product_query) or die('Query failed: ' . mysql_error());
            if(mysql_num_rows($product_res)<1){
                $result["is_allow_virtual"]    = -1;
                echo json_encode($result);die;
            }
            while($row1 = mysql_fetch_object($product_res)){
                $proxy_pro_id = $row1->id;
                $virtual_store_count = $row1->virtual_store_count;
                //如果属性虚拟库存小于进货量，则返回
                if($virtual_store_count<$counts){
                    $result["is_allow_virtual"]    = -1;
                    echo json_encode($result);die;
                }
            }
        }
        $result["is_allow_virtual"]    = 1;
        echo json_encode($result);
    }else if( $op == 'query_order' ){
        $order_id = i2post("order_id");
        $industry_type = i2post("industry_type",'shop');
        $result = query_order($customer_id,$order_id,$industry_type);
        echo json_encode($result);
    }else if( $op == "pay_data" ){//获取订单支付信息
        $pay_batchcode = i2post("pay_batchcode");
        $customer_id = i2post("customer_id");
        $industry_type = i2post("industry_type",'shop');
        $order_order_operation = new order_operation();
        $result = $order_order_operation->get_order_price($industry_type,$customer_id,$pay_batchcode);
        echo json_encode($result);

    }



?>