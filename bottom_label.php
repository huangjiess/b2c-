<?php
//$link_fun = empty($_GET['link_fun'])?"":$_GET['link_fun'];

if(!empty($_GET['fun'])){
    $fun = $_GET['fun'];
}

if($fun == "order_cart"){   // 当为购物车页面时，获取customer_id，异步获取数据
    header("Content-type: text/html; charset=utf-8");
    require_once('../config.php');
	$is_publish = $_GET['is_publish'];
    $link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
    mysql_select_db(DB_NAME) or die('Could not select database');

}

if(empty($customer_id_en)){
    $customer_id_en = $customer_id;
}
/*查找使用中的底部标签*/
//$label_sql = "select us.id,us.name,us.icon_url,us.icon_url_selected,us.page_url,us.column_id,page.funs from bottom_label_using as us inner join page_column_t as page on page.id = us.column_id where us.isvalid=true and page.isvalid=true and page.type=2 and us.customer_id=".$customer_id." order by us.`sort` desc";
//    $label_sql = "select id,name,icon_url,icon_url_selected,page_url,column_id,funs from ".WSY_SHOP.".bottom_label_using where isvalid=true and customer_id=".$customer_id." order by `sort` desc";
$sql_position = "SELECT position from ".WSY_SHOP.".bottom_label_template_setting where isvalid =true and id = '{$is_publish}' and is_shelve = true";
$result_position = _mysql_query($sql_position) or die("position_sql fail:".mysql_error());
while ($row_position = mysql_fetch_object($result_position)) {
    $position = $row_position->position;
}
$label_sql = "select ns.id,ns.icon_url,ns.icon_url_selected,ns.page_url,ns.column_id,ns.name,ns.color,ns.color_selected from ".WSY_SHOP.".bottom_label_icon_setting as ns inner join ".WSY_SHOP.".bottom_label_template_setting as ts on ts.id = ns.tmp_id and ts.isvalid =true and ts.is_shelve = true where ns.customer_id=".$customer_id." and ns.display= 1 and ns.isvalid=true and ts.id = '{$is_publish}' order by ns.sort asc";

$result = _mysql_query($label_sql) or die("label_sql fail:".mysql_error());

if($fun == "order_cart"){   // 当为购物车页面时，返回底部菜单数据
    if(!empty($_GET['customer_id'])){
        $customer_id = $_GET['customer_id'];
    }

    require_once('../common/utility_setting_function.php');

    $return_arr = Array();

    while ($row = mysql_fetch_object($result)) {
        $keyid =  $row->id ;
        $name =  $row->name ;
        $icon_url =  $row->icon_url ;
        $icon_url_selected =  $row->icon_url_selected ;
        $page_url =  $row->page_url ;
        $column_id =  (int)$row->column_id ;
        $funs = $row->funs ;
        $color = $row->color;
        $color_selected = $row->color_selected;
        if(strpos($icon_url,Protocol) == false){
            $icon_url = Protocol.$_SERVER['HTTP_HOST'].$icon_url;
        }
        if(strpos($icon_url_selected,Protocol) == false){
            $icon_url_selected = Protocol.$_SERVER['HTTP_HOST'].$icon_url_selected;
        }

        /*获取跳转链接*/
        if($column_id > 0){
            $jump_url = location_link($funs,$customer_id_en);

            $jump_url = Protocol.$_SERVER['HTTP_HOST'].$jump_url;
        }else{
            $jump_url = $page_url;
        }

        array_push($return_arr,array("keyid"=>$keyid,"name"=>$name,"icon_url"=>$icon_url,"icon_url_selected"=>$icon_url_selected,"page_url"=>$page_url,"column_id"=>$column_id,"funs"=>$funs,"jump_url"=>$jump_url,"color"=>$color,"color_selected"=>$color_selected));
    }
    echo json_encode($return_arr);
    exit;
}
$hasname = false;
while ($row = mysql_fetch_object($result)) {
    if($row->name!=""){
        $hasname = true;
    }
}
mysql_data_seek($result, 0);
?>
<style type="text/css">
    <?php
    //当页面为订货系统中心，订货系统申请，订货系统登录，F2C系统中心,续费专区时，这些页面样式限制，重新编辑
    if(!empty($fun)){
        if($fun =="ordering_retail" || $fun == "proxy_apply" || $fun == "proxy_login" || $fun == "f2c" || $fun == "promotion_renewal" || $fun == "my_shop_reward" || $fun == 'proxy_request_order' || $fun == 'proxy_sale_order_list' || $fun == 'proxy_son_store_login' || $fun == 'proxy_order_login' || $fun == 'proxy_store_center' || $fun == 'renew_area' || $fun == 'proxy_son_order_list' || $fun == 'proxy_my_reward' || $fun == 'ordering_retail' || $fun == 'proxy_apply' || $fun == 'proxy_login' || $fun == 'retail_main_funs' || $fun == 'proxy_purchase_product_list' || $fun == 'proxy_order_retail' || $fun == 'proxy_account_manager' || $fun == 'proxy_store_list' || $fun == 'proxy_near_store' || $fun == 'proxy_store_center' || $fun == 'proxy_request_store' || $fun =="proxy_request_order" || $fun == "proxy_request_warehouse" || $fun == "proxy_request_son_store" || $fun == "proxy_order_login" || $fun == "proxy_son_store_login" || $fun == "proxy_order_list" || $fun == 'proxy_purchase_order_list' || $fun == 'proxy_sale_order_list' || $fun == 'proxy_retail_order_list' || $fun == 'proxy_send_order_list' || $fun == 'proxy_son_order_list' || $fun == 'proxy_my_deal_order' || $fun == 'proxy_store_deal_order' || $fun == 'proxy_son_deal_order' || $fun == 'proxy_stock_count' || $fun == 'proxy_account_award' || $fun == 'proxy_my_reward' || $fun == 'proxy_my_team' || $fun == 'proxy_son_stock' || $fun == 'proxy_send_reward' || $fun == 'proxy_team_list' || $fun == 'proxy_my_sales' || $fun == 'proxy_my_orders' || $fun == 'proxy_order_detail' || $fun == 'proxy_purchase_order_detail' || $fun == 'proxy_retail_order_detail' || $fun == 'proxy_send_order_detail' || $fun == 'proxy_reward_detail' || $fun == 'proxy_my_reward_detail' || $fun == 'proxy_my_team_reward_detail' || $fun == 'proxy_stock' || $fun == 'proxy_sale_order_detail' || $fun == 'f2c_apply' || $fun == 'f2c_entrance' || $fun == 'f2c_main_funs' || $fun == 'f2c_purchase' || $fun == 'f2c_order_list' || $fun == 'f2c_purchase_order' || $fun == 'f2c_sale_order' || $fun == 'f2c_agent_order' || $fun == 'f2c_data_statistics' || $fun == 'f2c_my_reward' || $fun == 'f2c_order_detail' || $fun == 'f2c_purchase_detail' || $fun == 'f2c_sale_detail' || $fun == 'f2c_agent_order_detail' || $fun == 'cityarea_coach'){
    ?>
    .footer{position: fixed;bottom: 0px;left: 0px;width: 100%;height: 0.98rem;background:#fff;z-index: 50;line-height: 0.48rem;border-top: 0.02rem solid #eeeeee;box-shadow: 0 0 10px 0 rgba(155,143,143,0.6);
        -webkit-box-shadow: 0 0 10px 0 rgba(155,143,143,0.6);    padding: 0px;font-size: 0;}
    .footer .footer-box{margin:0 auto;width: 100%;height: 0.98rem;font-size: 0;box-sizing: border-box;display: -webkit-box;display: -webkit-flex;display: -ms-flexbox;display: flex;-webkit-box-align: center;-webkit-align-items: center;-ms-flex-align: center;align-items: center;-webkit-box-pack: center;-webkit-justify-content: center;-ms-flex-pack: center;justify-content: center;}
    .footer .footer-box .weidian{height: 0.98rem;text-align: center;align-items:center;justify-content:center;float: none;-webkit-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;}
    .footer .footer-box .weidian img{width: 0.98rem;height: 0.98rem;vertical-align: middle;}
    .footer .footer-box .weidian p{font-size: 0.24rem;color: #a1a1a1;margin: 0;}
    .footer .footer-box .weidian.active p{color:#64b83c;white-space:nowrap;text-overflow:clip;overflow: hidden;}
    .footer .footer-box .weidian p.foot_grey{color: #a1a1a1;}
    .paddingBottom{height:0.98rem;clear: both;}

    .footer.hasname{position: fixed;bottom: 0px;left: 0px;width: 100%;height: 0.98rem;background:#fff;z-index: 50;line-height: 0.48rem;border-top: 0.02rem solid #eeeeee;box-shadow: 0 0 10px 0 rgba(155,143,143,0.6);
        -webkit-box-shadow: 0 0 10px 0 rgba(155,143,143,0.6);    padding: 0px;font-size: 0;}
    .footer.hasname .footer-box{margin:0 auto;width:100%;height: 0.98rem;font-size: 0;box-sizing: border-box;display: -webkit-box;display: -webkit-flex;display: -ms-flexbox;display: flex;-webkit-box-align: center;-webkit-align-items: center;-ms-flex-align: center;align-items: center;-webkit-box-pack: center;-webkit-justify-content: center;-ms-flex-pack: center;justify-content: center;}
    .footer.hasname .footer-box .weidian{height:0.98rem;text-align: center;align-items:center;justify-content:center;float: none;-webkit-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;}
    .footer.hasname .footer-box .weidian p{font-size:0.24rem;color:#a1a1a1;margin:0;line-height:0.28rem;overflow:hidden}
    .footer.hasname .footer-box .weidian.active p{color:#64b83c;white-space:nowrap;text-overflow:clip;overflow:hidden}
    .footer.hasname .footer-box .weidian p.foot_grey{color:#a1a1a1}
    .paddingBottom{height:0.98rem;clear: both;}
    .footer.hasname .footer-box .weidian img{width:0.64rem;height:0.64rem;margin:0 auto;vertical-align:middle}
    .footer.hasname .footer-box .weidian img.noname{width: 0.98rem;height: 0.98rem;margin:0 auto;vertical-align:middle}
    .footer.hasname .footer-box .weidian .foot-text{font-size:0.2rem;line-height:0.28rem;white-space:nowrap;overflow:hidden}
    <?php }else{ ?>
    .footer{position: fixed;bottom: 0px;left: 0px;width: 100%;height: 49px;background:#fff;z-index: 50;line-height: 24px;border-top: 1px solid #eeeeee;box-shadow: 0 0 10px 0 rgba(155,143,143,0.6);
        -webkit-box-shadow: 0 0 10px 0 rgba(155,143,143,0.6);padding: 0px;font-size: 0;}
    .footer .footer-box{margin:0 auto;width: 100%;height: 49px;font-size: 0;box-sizing: border-box;display: -webkit-box;display: -webkit-flex;display: -ms-flexbox;display: flex;-webkit-box-align: center;-webkit-align-items: center;-ms-flex-align: center;align-items: center;-webkit-box-pack: center;-webkit-justify-content: center;-ms-flex-pack: center;justify-content: center;}
    .footer .footer-box .weidian{height: 49px;text-align: center;align-items:center;justify-content:center;float: none;-webkit-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;}
    .footer .footer-box .weidian img{width: 49px;height: 49px;vertical-align: middle;}
    .footer .footer-box .weidian p{font-size: 12px;color: #a1a1a1;margin: 0;}
    .footer .footer-box .weidian.active p{color:#64b83c;white-space:nowrap;text-overflow:clip;overflow: hidden;}
    .footer .footer-box .weidian p.foot_grey{color: #a1a1a1;}
    .paddingBottom{height:49px;clear: both;}

    .footer.hasname{position:fixed;bottom:0px;left:0px;width:100%;height:49px;background:#fff;z-index:50;line-height:24px;border-top:1px solid #eeeeee;box-shadow:0 0 10px 0 rgba(155,143,143,0.6);-webkit-box-shadow:0 0 10px 0 rgba(155,143,143,0.6);padding:0px;font-size: 0;}
    .footer.hasname .footer-box{margin:0 auto;width:100%;height:49px;font-size: 0;box-sizing: border-box;display: -webkit-box;display: -webkit-flex;display: -ms-flexbox;display: flex;-webkit-box-align: center;-webkit-align-items: center;-ms-flex-align: center;align-items: center;-webkit-box-pack: center;-webkit-justify-content: center;-ms-flex-pack: center;justify-content: center;}
    .footer.hasname .footer-box .weidian{height:49px;text-align: center;align-items:center;justify-content:center;float: none;-webkit-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;}
    .footer.hasname .footer-box .weidian p{font-size:12px;color:#a1a1a1;margin:0;line-height:14px;overflow:hidden}
    .footer.hasname .footer-box .weidian.active p{color:#64b83c;white-space:nowrap;text-overflow:clip;overflow:hidden}
    .footer.hasname .footer-box .weidian p.foot_grey{color:#a1a1a1}
    .paddingBottom{height:49px;clear: both;}
    .footer.hasname .footer-box .weidian img{width:32px;height:32px;margin:0 auto;vertical-align:middle}
    .footer.hasname .footer-box .weidian img.noname{width: 49px;height: 49px;margin:0 auto;vertical-align:middle}
    .footer.hasname .footer-box .weidian .foot-text{font-size:10px;line-height:14px;white-space:nowrap;overflow:hidden}
    <?php }}?>
</style>
<!--底部按钮-->
<!-- 固定底部或者不固定底部  -->
<div class="footer <?php if($hasname) echo 'hasname'; ?>" style="position: <?php if($position==1) echo 'static'; ?>;">
    <div class="footer-box">
        <?php
        while ($row = mysql_fetch_object($result)) {
            $keyid =  $row->id ;
            $name =  $row->name ;
            $icon_url =  $row->icon_url ;
            $icon_url_selected =  $row->icon_url_selected ;
            $page_url =  $row->page_url ;
            $column_id =  (int)$row->column_id ;
            $funs = $row->funs ;
            $color = $row->color;
            $color_selected = $row->color_selected;
            
            if(strpos($icon_url,Protocol) == false){
                if($icon_url == '/weixinpl/back_newshops/Common/images/Base/personal_center/gift.png'){
                    $icon_url = Protocol.$_SERVER['HTTP_HOST'].$icon_url;
                }else{
                    $icon_url = Protocol.$_SERVER['HTTP_HOST'].$icon_url."?x-oss-process=image/resize,w_200";
                }
            }
            if(strpos($icon_url_selected,Protocol) == false){
                if($icon_url_selected == '/weixinpl/back_newshops/Common/images/Base/personal_center/gift.png'){
                    $icon_url_selected = Protocol.$_SERVER['HTTP_HOST'].$icon_url_selected;
                }else{
                    $icon_url_selected = Protocol.$_SERVER['HTTP_HOST'].$icon_url_selected."?x-oss-process=image/resize,w_200";
                }
            }

            /*获取跳转链接*/
            if($column_id > 0){
                $jump_url = location_link($funs,$customer_id_en);

                $jump_url = Protocol.$_SERVER['HTTP_HOST'].$jump_url;
            }else{
                $jump_url = $page_url;
            }
            
            if(!empty($_GET['yundian_id'])){
                if(strpos($jump_url,"?") === false)
                {
                    $jump_url = $jump_url."?yundian=".$_GET['yundian_id'];
                }else{
                    $jump_url = $jump_url."&yundian=".$_GET['yundian_id'];
                }
            }elseif(!empty($_GET['yundian'])){
                if(strpos($jump_url,"?") === false)
                {
                    $jump_url = $jump_url."?yundian=".$_GET['yundian'];
                }
                else
                {
                    $jump_url = $jump_url."&yundian=".$_GET['yundian'];
                }
            }

            $page_url_array = explode('?',$page_url);
            $page_url1 = $page_url_array[0];
            $page_url2 = $page_url_array[1];
            parse_str($page_url2,$page_url2_1);

            //获取当前url
            $page_url1_2 = $_SERVER['PHP_SELF'];
            parse_str($_SERVER['QUERY_STRING'],$page_url2_2);
			/*
            $a = count($page_url2_1);
            $b = count($page_url2_2);

            $show_select = 0;
            if( $page_url1 == $page_url1_2 && $a == $b && $a > 0 && $b >0){
                foreach ($page_url2_2 as $v){
                    if(in_array($v,$page_url2_1)){
                        $show_select = 1;
                    }else{
                        $show_select = 0;break;
                    }
                }
            }*/
            $show_select = 0;
            if($_SERVER['PHP_SELF']==$page_url1){
                $show_select = 1;
            }
            if($page_url1 == '/weixinpl/mshop/personal_center.php'){	//判断个人中心自定义页面
                $temp_url = $_SERVER['REQUEST_URI'];
                $temp_url = strstr($temp_url, '&customer_id', TRUE);
                if($temp_url == '/mshop/web/index.php?m=personal_center&a=diy_personal_center'){
                    $show_select = 1;
                }
            }
			
			//判断首页自定义模板页面
			if($page_url1 == '/weixinpl/common_shop/jiushop/index.php'){  
				$show_select = 0;
				if($page_url2_1['diy_template_id'] > 0){		//自定义模板
					if($page_url2_2['diy_template_id'] > 0){
						$show_select = 1;
					}else{
						$show_select = 0;
					}
				}else{											//普通首页
					if($page_url1 == $page_url1_2){	
						$show_select = 1;
					}
				}
			}
            ?>
            <div class="weidian">
                <a onclick="onloadP('<?php echo $jump_url;?>')">
                    <?php if($show_select == 1){?>
                        <img src="<?php echo $icon_url_selected;?>" alt="">
                        <!-- name -->
                        <p style="color:#<?php echo $color_selected;?>;" class="foot-text"><?php echo $name;?></p>
                    <?php }else{?>
                        <img src="<?php echo $icon_url;?>" alt="">
                        <p style="color:#<?php echo $color;?>;" class="foot-text"><?php echo $name;?></p>
                    <?php }?>
                </a>
            </div>
        <?php }?>
    </div>
</div>
<div class="paddingBottom" style="display: <?php if($position==1) echo 'none'; ?>;"></div>
        <!--底部按钮-->
        <script>
        function onloadP(url){ // Tab Selection
        window.location.href = url;
        }
        </script>
