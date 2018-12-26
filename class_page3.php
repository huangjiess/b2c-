<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../customer_id_decrypt.php');
require('../common/utility.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../proxy_info.php');
//头文件----start
require('../common/common_from.php');
//头文件----end
require('select_skin.php');
require_once('../common/common_ext.php');
$new_baseurl = Protocol.$http_host; //新商城图片显示
$owner_id =-1; //我的微店分类
if(!empty($_GET["owner_id"])){
    $owner_id=$configutil->splash_new($_GET["owner_id"]);
    $query="select title from weixin_commonshop_owners where isvalid=true and id=".$owner_id;
   
    $result = _mysql_query($query) or die('Query failed: ' . mysql_error());   
    while ($row = mysql_fetch_object($result)) {
        $shop_name = $row->title;
      
        $query2="select type_id from weixin_commonshop_owner_types where isvalid=true and owner_id=".$owner_id;
        $result2 = _mysql_query($query2) or die('Query failed: ' . mysql_error());   
        while ($row2 = mysql_fetch_object($result2)) {
            $o_type_id = $row2->type_id;
            if($owner_typeids==""){
                $owner_typeids= $owner_typeids.$o_type_id;
            }else{
                $owner_typeids= $owner_typeids.",".$o_type_id;
            }
        }      
    }
}

//V7.0分类新排序
$sort_str="";
$type_sort="select sort_str from weixin_commonshop_type_sort where customer_id=".$customer_id."";
$result_type=_mysql_query($type_sort) or die ('type_sort faild' .mysql_error());
while($row=mysql_fetch_object($result_type)){
    $sort_str=$row->sort_str;                                       
}

$query= "select id,name,is_privilege from weixin_commonshop_types where isvalid=true and parent_id=-1 and is_shelves=1 and customer_id=".$customer_id; 
if(!empty($owner_typeids)){
    $query = $query." and id in(".$owner_typeids.")";
}

if($sort_str){
    $query =$query.' order by field(id'.$sort_str.')';  
}
$i=0;
$typearr=[];
$result = _mysql_query($query) or die('Query failedff: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
    $pt_id = $row->id;
    $pt_name = $row->name;
    $pt_privilege = $row->is_privilege;//是否特权分类------2016-11-28--qiao
    $typearr[]=$pt_id."_".$pt_name."_".$pt_privilege;
    
}
$typefirstarr=explode('_',$typearr[0]);
$typefirstid=$typefirstarr[0]; //获取第一个分类ID

$brand_adimg="";
$brand_adurl="#";
$brand="select wc.isOpenBrandSupply as isOpenBrandSupply,wcs.brand_adimg as brand_adimg ,wcs.brand_adurl as brand_adurl from weixin_commonshop_supplys wcs inner join weixin_commonshops wc on wc.isvalid=true and wcs.customer_id=wc.customer_id and wc.customer_id=".$customer_id."";
$result_brand=_mysql_query($brand) or die ('brand2 faild' .mysql_error());
while($row=mysql_fetch_object($result_brand)){
    $isOpenBrandSupply=$row->isOpenBrandSupply;
    $brand_adimg=$row->brand_adimg;
    $brand_adurl=$row->brand_adurl;
}
$brandsupply=-1;
if($isOpenBrandSupply){
    $brandsupply=1; //是否开启品牌供应商
}
$page_type="class_page";// 作为底部菜单高亮的判断 list为列表页，class_page 为分类页

//广告轮播图
$query_img = "select brand_adimg,brand_adurl from weixin_commonshop_supply_album where isvalid=true and supply_id = -1 and customer_id=".$customer_id." order by id desc limit 5";
$result_img = _mysql_query($query_img) or die("Query failed : ".mysql_error());

// var_dump($typearr);

$user_level = 0;//0:普通粉丝;1：推广员；2：青铜(代理)；3：白银(渠道)；4：黄金(总代)；5：白金(股东)
$promoter_id = -1;          
$promoter_status = -1;
$promoter_is_consume = -1;


//查询是否推广员，是否拥有股东身份 
$query = "SELECT id,status,is_consume FROM promoters WHERE isvalid=true AND customer_id=$customer_id AND user_id=$user_id LIMIT 1";
$result= _mysql_query($query) or die('Query failed:24 ' . mysql_error());
while( $row = mysql_fetch_object($result) ){
    $promoter_id = $row->id;
    $promoter_status = $row->status;
    $promoter_is_consume = $row->is_consume;
}

if( $promoter_is_consume > 0 && $promoter_status == 1 && $promoter_id > 0 ){    //股东
    $user_level = $promoter_is_consume+1;
}elseif( $promoter_is_consume <= 0 && $promoter_status == 1 && $promoter_id > 0 ){  //普通推广员
    $user_level = 1;
}elseif( $promoter_id < 0 ){    //粉丝
    $user_level = 0;
}

$is_privilege_link = 0;
$privilege_link = "";
$query = "SELECT is_privilege_link,privilege_link FROM weixin_commonshops_extend WHERE isvalid=true AND customer_id=$customer_id LIMIT 1";
//echo $query;
$result = _mysql_query($query) or die('Query failed:47 ' . mysql_error());
while( $row = mysql_fetch_object($result)){
    $is_privilege_link = $row->is_privilege_link;
    $privilege_link = $row->privilege_link;
}

/******搜索内容******/
$search_data = i2get("searchname","");
$search_data = i2get("search_data","");
/******搜索内容******/

//search_from:1全站搜索2供应商ID商店内搜索
$search_from = i2get("search_from",1);

$supply_id = i2get("supply_id",-1);
if ($supply_id){
    $search_from = 2;
}


$isnew = i2get("isnew",0);//新品上市

$ishot = i2get("ishot",0);//热卖产品

$isvp = i2get("isvp",0);//VP产品

$isscore = i2get("isscore",0);//积分专区

$exp_user_id = -1;
if( !empty($_GET['exp_user_id']) ){
    $exp_user_id = $configutil->splash_new($_GET["exp_user_id"]);
    $exp_user_id = passport_decrypt((string)$exp_user_id);
}

//是否立刻搜索   
$s_n = i2get("s_n",-1);  //-2热门搜索分类,-1关键词搜索

$searchpage = i2get("searchpage",-1);   //是否搜索页过来，1是

$is_privilege_type = 0;
//查询分类是否属于特权分类
if(!empty($_GET["tid"])){
    $type_id =$configutil->splash_new($_GET["tid"]);
    $query = "SELECT is_privilege FROM weixin_commonshop_types WHERE isvalid=true AND customer_id=$customer_id AND id=$type_id";
    $result= _mysql_query($query) or die ('query faild 529' .mysql_error());
    while( $row = mysql_fetch_object($result) ){
        $is_privilege_type = $row->is_privilege;
    }
}

//接收参数
$type_id = '';  //分类ID
if(!empty($_GET["type_id"])){
    $type_name = '';
    $type_id=$configutil->splash_new($_GET["type_id"]);
    $type_sql="select name from weixin_commonshop_types where isvalid=true and id=".$type_id." and customer_id=".$customer_id."";
    $result=_mysql_query($type_sql) or die ('query faild' .mysql_error());
        while($row=mysql_fetch_object($result)){
            $type_name=$row->name;
        }
}


$curCostMin = i2get("curCostMin","");   //最低价

$curCostMax = i2get("curCostMax","");   //最高价

$curScoreMin = i2get("curScoreMin",""); //最低积分

$curScoreMax = i2get("curScoreMax","");  //最高积分

$searchKey = i2get("searchKey","");    //关键词

$op_sort = i2get("op_sort","");  //排序

$pageNum = i2get("pageNum","");  //翻页数



$tid = -1;
$sendstyle=-1;
$isOpenSales=0;
$isshowdiscount=0;
$define_share_image="";//分享图片
$shop_introduce="";//商城简介
if(!empty($_GET["tid"])){
    $tid=$configutil->splash_new($_GET["tid"]);
    if($tid>0){  //搜索分类选择的模板，优先级最高
        $query="select sendstyle from weixin_commonshop_types where isvalid=true and customer_id=".$customer_id." and  id=".$tid." limit 0,1";
        //echo $query;
        $result=_mysql_query($query) or die ('query faild' .mysql_error());
        while($row=mysql_fetch_object($result)){
            $sendstyle=$row->sendstyle;
        }
        
    }
}
//查找全局分类页模板,开启销量，显示折扣
$list_style="select list_type,isOpenSales,isshowdiscount,define_share_image,introduce from weixin_commonshops where isvalid=true and customer_id=".$customer_id." limit 0,1";
$list_result=_mysql_query($list_style) or die ('list_style faild' .mysql_error());
while($row=mysql_fetch_object($list_result)){
    $list_type=$row->list_type; //模板ID
    $isOpenSales=$row->isOpenSales;//显示销量
    $isshowdiscount=$row->isshowdiscount;//显示折扣
    $list_tempid=$list_type;
    $define_share_image = $row->define_share_image; ///分享图片
    $shop_introduce = $row->introduce; //商城介绍
    $shop_introduce=str_replace(PHP_EOL, '', $shop_introduce);//过滤换行
    $shop_introduce = str_replace(chr(10),'',$shop_introduce); 
    $shop_introduce = str_replace(chr(13),'',$shop_introduce);
    
}


$is_division_show     =  0;//返现与购物币显示开关
$is_promoter_show     =  0;//只有推广员显示返现与购物币开关
$is_show_original      =  0;//显示原价开关
$sql = "select is_division,is_promoter,is_show_original from weixin_commonshops_extend where isvalid=true and customer_id=".$customer_id." limit 0,1";
$result1 = _mysql_query($sql) or die('Query failed: ' . mysql_error());
while ($row1 = mysql_fetch_object($result1)) {
        
        $is_division_show   = $row1->is_division;
        $is_promoter_show   = $row1->is_promoter;
        $is_show_original   = $row1->is_show_original;
}

/*判断是否显示购物币以及返现*/
 /*require('../common/own_data.php');
$info = new my_data();//own_data.php my_data类
$showAndCashback = $info->showCashback($customer_id,$user_id,-1,-1,-1);*/
/*判断是否显示购物币以及返现结束*/

if($define_share_image){
    $define_share_image=$new_baseurl."/".$define_share_image;
}
if($sendstyle>0){
    $list_tempid=$sendstyle; //模板ID
}
if($isOpenSales){
    $isOpenSales=1;
}
if($isshowdiscount){
    $isshowdiscount=1;
}
/*显示vp值 */
$isvp_switch = 0;
$query_vp = "select isvp_switch from weixin_commonshop_vp_bases where isvalid=true and customer_id=".$customer_id." limit 0,1";
$result_vp = _mysql_query($query_vp) or die('Query failed: ' . mysql_error());
while ($row_vp = mysql_fetch_object($result_vp)) {
    $isvp_switch = $row_vp->isvp_switch;
}
if($isvp_switch){
    $isvp_switch=1;
}
$brand_typeid=i2get("brand_typeid",-1); //品牌供应商的分类ID

$tid=i2get("brand_typeid",-1); //分类页传过来的分类ID

$placeholder="搜索";
if(0<$supply_id){
    $placeholder="搜索本店内宝贝";
}
//猜你喜欢，购物车以及产品详情传过来
//购物车: cartlike 商品详情：morelike
$like_op="";
$like_pid=-1;//产品ID
if(!empty($_GET["op"])){ //操作，购物车还是商品详情
    $like_op = $configutil->splash_new($_GET["op"]);
    $list_tempid=4;//猜你喜欢显示列表统一为模板4
}
if($isscore>0){
    $list_tempid=2;//积分专区使用模板2
}

if(!empty($_GET["pid"])){ //分类页传过来的分类ID
    $like_pid = $configutil->splash_new($_GET["pid"]);
}

$page_type="list";// 作为底部菜单高亮的判断 list为列表页，class_page 为分类页

/*** 图片是否自动轮播 ***/
$is_stockOut       = 0; //是否库存不足自动下架
$query_productShuf = "select is_stockOut from weixin_commonshops_extend where isvalid=true and customer_id=".$customer_id;
$result_productShuf = _mysql_query($query_productShuf) or die("Query_productShuf failed:".mysql_error());
while($row_productShuf = mysql_fetch_object($result_productShuf)){
    $is_stockOut = $row_productShuf->is_stockOut;
}
/*** 图片是否自动轮播 ***/

$query="select name,init_reward,issell_model,advisory_telephone,advisory_flag,pro_card_level,shop_card_id,isshowdiscount,is_identity,is_showdiscuss,isOpenSales,issell from weixin_commonshops where isvalid=true and customer_id=".$customer_id;

$result = _mysql_query($query) or die('商品归属Query failed2: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
    $issell             = $row->issell;//是否开启分销
    $pro_card_level     = $row->pro_card_level;//购买产品需要会员卡开关
    $shop_card_id       = $row->shop_card_id;//分销会员卡
    $is_showdiscuss     = $row->is_showdiscuss;
    $isOpenSales        = $row->isOpenSales;
    $isshowdiscount     = $row->isshowdiscount;
    $is_identity        = $row->is_identity;
    $advisory_flag      = $row->advisory_flag;
    $brand_supply_name  = $row->name;
    $brand_tel          = $row->advisory_telephone;
    $init_reward        = $row->init_reward;
    $issell_model       = $row->issell_model;
}


/* 检测是否符合首次推广奖励 start */

//查询上级id
$my_parent_id       = -1;

if($user_id>0) {
    $query_parent = "select parent_id,weixin_name from weixin_users where id=".$user_id." and customer_id=".$customer_id." and isvalid=true";

    $result_parent = _mysql_query($query_parent) or die("Query_parent failed:".mysql_error());
    while( $row_parent = mysql_fetch_object($result_parent) ){
        $my_parent_id = $row_parent -> parent_id;
        $weixin_name = $row_parent -> weixin_name;
    }
    //上级是否推广员
    $query_parent_p = "select count(1) as p_is_promoter from promoters where user_id=".$my_parent_id." and status=1 and isvalid=true and customer_id=".$customer_id;
    $p_is_promoter = 0;
    $result_parent_p = _mysql_query($query_parent_p) or die('Query_parent_p failed:'.mysql_error());
    while ( $row_parent_p = mysql_fetch_object($result_parent_p) ){
        $p_is_promoter = $row_parent_p ->p_is_promoter;
    }
    
    
}
/* 检测是否符合首次推广奖励 end */
?>

<!DOCTYPE html>
<html>
<head>
    <title>分类</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta content="no" name="apple-touch-fullscreen">
    <meta name="MobileOptimized" content="320"/>
    <meta name="format-detection" content="telephone=no">
    <meta name=apple-mobile-web-app-capable content=yes>
    <meta name=apple-mobile-web-app-status-bar-style content=black>
    <meta http-equiv="pragma" content="nocache">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8">
    
    <link type="text/css" rel="stylesheet" href="./css/goods/global.css" />
    <link type="text/css" rel="stylesheet" href="./css/goods/product_detail.css" />
    <link type="text/css" rel="stylesheet" href="css/order_css/global.css" />
    <link type="text/css" rel="stylesheet" href="./css/css_<?php echo $skin ?>.css" />
    <link type="text/css" rel="stylesheet" href="./css/product_coupon.css" />
    <link href="./css/goods/mobiscroll/mobiscroll.custom-2.17.1.min.css" rel="stylesheet" type="text/css">

    <link rel="stylesheet" type="text/css" href="css/classify.css"/>
  <style>
   .topNav::-webkit-scrollbar {display:none}
   .leftBigBox::-webkit-scrollbar {display:none}
    #mount_count{width:25px;height:25px;line-height:27px;border:1px solid #d1d1d1;display:inline-block;box-sizing:border-box;}
  </style>
        <div class="seach-box" id="tvKeyword">
            <img src="images/icon_search_gray.png" /><input type="text"  placeholder="搜索您想寻找的商品">
        </div>
        <div class="topNav" style="overflow:scroll;overflow-scrolling: touch;-webkit-overflow-scrolling:touch;">
            <ul>
                <?php if($brandsupply>0){//判断有无开启品牌供应商?>
                    <li><span class="type-top-button">品牌分类</span></li>
                <?php }?>
                <?php
                    for($i=0;$i<sizeof($typearr);$i++){
                        $typestr=explode('_',$typearr[$i]);
                    
                ?>
                <li>
                    <span typeid="<?php echo $typestr[0];?>" id="act_<?php echo $typestr[0];?>"  class="type-top-button <?php if($i==0) echo "active";?>">
                        <?php if($typestr[2]==1){?>
                            <img src="images/special.png" class="privilege_one" style="width:28px;"/>
                        <?php }?>
                        <?php echo $typestr[1];?>
                    </span>
                </li>
                <?php }?>
            </ul>
        </div>
        
        
        <div class="all_Box">
            <div class="leftBigBox">
                <div class="left_s_box">
                    <p>全部分类</p>
                </div>
                <div class="left_s_box">
                    <p class="s_boxSelect">本店推荐</p>             
                </div>

            </div>
         
            <div class="right_contentBox" id="right_contentBox">
<!---->
<!--                <div class="contentBox">-->
<!--                    <img src=""/>-->
<!--                    <div class="con_mid_box">-->
<!--                        <p class="bot01">夏季新款 蕾丝上衣打底名媛气质衬衫会场仙女衣气质衬衫</p>-->
<!--                        <p class="bot03">-->
<!--                            <span>折</span>-->
<!--                            <span>VP</span>-->
<!--                            <span>返</span>-->
<!--                        </p>-->
<!--                        <p class="bot02"><span class="mon01">￥</span><span class="mon02">150</span><span class="mon03">￥35,000</span></p>-->
<!--                        <p class="ps-tips"><span class="free-post">包邮</span><span class="have-sale">已销22546</span></p>-->
<!--                    </div>-->
<!--                    <div class="num-count">-->
<!--                        <p class="reduce">-</p>-->
<!--                        <input type="text" name="" id="" value="5" class="produce-num" />-->
<!--                        <p class="add">+</p>-->
<!--                    </div>-->
<!--                </div>-->
<!--                <div class="contentBox">-->
<!--                    <img src=""/>-->
<!--                    <div class="con_mid_box">-->
<!--                        <p class="bot01">夏季新款 蕾丝上衣打底名媛气质衬衫会场仙女衣气质衬衫</p>-->
<!--                        <p class="bot03">-->
<!--                            <span>折</span>-->
<!--                            <span>VP</span>-->
<!--                            <span>返</span>-->
<!--                        </p>-->
<!--                        <p class="bot02"><span class="mon01">￥</span><span class="mon02">150</span><span class="mon03">￥35,000</span></p>-->
<!--                        <p class="ps-tips"><span class="free-post">包邮</span><span class="have-sale">已销22546</span></p>-->
<!--                    </div>-->
<!--                    <div class="num-count">-->
<!--                        <div class="size-btn">-->
<!--                            选规格-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
            </div>
            
        </div>
        
        
        <div class="fix_bottom" style="display:none;">
			<div class="black_model" onclick="hide_pos()"></div>
			<div class="container">
				<div class="first_box">
					<div class="imgbox inline-block">
						<img src="">
					</div>
					<div class="textbox inline-block">
						<p class="black_font fs15 p01">马甲 春秋英伦休闲男士西装</p>
						<p class="red_font fs17">￥259.00</p>
					</div>
					<div class="close_btn" onclick="hide_pos()">
						
					</div>
				</div>
				<div class="divider"></div>
				<div class="second_box">
					<p class="label_text inline-block fs15" style="padding-left: 15px;font-size: 13px;">属性：</p>
					<div class="sel_box inline-block small_pro_div">
						<ul>
							<li class="inline-block fs15 gray_font pos_div sel_btn">S</li>
							<li class="inline-block fs15 gray_font pos_div">S</li>
							<li class="inline-block fs15 gray_font pos_div">S</li>
							
						</ul>
					</div>
					
				</div>
				<div class="second_box">
					<p class="label_text inline-block fs15" style="padding-left: 15px;font-size: 13px;">属性：</p>
					<div class="sel_box inline-block small_pro_div">
						<ul>
							<li class="inline-block fs15 gray_font pos_div sel_btn">S</li>
							<li class="inline-block fs15 gray_font pos_div">S</li>
							<li class="inline-block fs15 gray_font pos_div">S</li>
							<li class="inline-block fs15 gray_font pos_div">S</li>
							<li class="inline-block fs15 gray_font pos_div">S</li>
						</ul>
					</div>
					
				</div>
				
				<div class="divider m20"></div>
				<div class="third_box">
					<p class="numBox"><span class="jian">-</span><input type="text" id="mount_count" name="" id="" value="1" /><span class="jiajia">+</span></p>
				    <p class="saveBox">仓存 : 758</p>
				</div>					
				<div class="orange_btn">加入购物车</div>			
			</div>
		</div>
<script type="text/javascript" src="./js/jquery-2.1.3.min.js"></script>    
<script src="./js/TouchSlide.1.1.js"></script> 
<script src="./js/r_global_brain.js" type="text/javascript"></script>
<script src="./js/CheckUserLogin.js"></script>
<!--引入侧边栏 end--> 
<script type="text/javascript">
    var winWidth          = $(window).width();
    var winheight         = $(window).height();
    var imgheight         = $("#type_adimg2").height();
    var customer_id       ='<?php echo $customer_id_en;?>';
    var customer_id2       ='<?php echo $customer_id;?>';
    var from_type         ="<?php echo $from_type; ?>";
    var owner_typeids     ="<?php echo $owner_typeids;?>";
    var new_baseurl       ="<?php echo $new_baseurl;?>";
    var brand_adimg       ="<?php echo $brand_adimg;?>"; 
    var brand_adurl       ="<?php echo $brand_adurl;?>";   
    var brandsupply       ="<?php echo $brandsupply;?>";
    var user_level        ="<?php echo $user_level;?>";
    var user_id           = "<?php echo $user_id ;?>";
    var my_parent_id      ="<?php echo $my_parent_id;?>";
    var exp_user_id       ="<?php echo $exp_user_id; ?>";
    var pro_card_level    ="<?php echo $pro_card_level; ?>";
    var shop_card_id      ="<?php echo $shop_card_id; ?>";
    var p_is_promoter     ="<?php echo $p_is_promoter; ?>";
    var is_publish        ="<?php echo $is_publish; ?>";

    var brand_typeid      = "<?php echo $brand_typeid ;?>";
    var tid               = "<?php echo $tid ;?>";
    var like_op           = "<?php echo $like_op ;?>";
    var like_pid          = "<?php echo $like_pid ;?>";
    var s_n               = '<?php echo $s_n ;?>'; 
    var searchpage        = "<?php echo $searchpage;?>"; 
    var $images_skin      = "<?php echo $images_skin?>";
    var type_id           = "<?php echo $type_id; ?>";
    var type_name         = "<?php echo $type_name; ?>";
    var curCostMin        = "<?php echo $curCostMin; ?>";
    var curCostMax        = "<?php echo $curCostMax; ?>";
    var curScoreMin       = "<?php echo $curScoreMin; ?>";
    var curScoreMax       = "<?php echo $curScoreMax; ?>";
    var searchKey         = "<?php echo $searchKey; ?>";
    var op_sort           = "<?php echo $op_sort; ?>";
    var is_privilege_link = "<?php echo $is_privilege_link;?>";
    var privilege_link    = "<?php echo $privilege_link;?>";
    var is_privilege_type = "<?php echo $is_privilege_type;?>";

    var downFlag = false;       //是否已经加载全部
    var pageNum = 0;            //起始编号
    var isLock = false;         //锁定：0未锁，1上锁
    var limit_num  = 16;        //加载数据的数目(数据库方面)
    var pinterestObj=document.getElementById("right_contentBox");// 包含所有商品的标签

    var list_tempid = "<?php echo $list_tempid;?>";
    var isOpenSales = "<?php echo $isOpenSales;?>";
    var isshowdiscount = "<?php echo $isshowdiscount;?>";
    var isvp_switch = "<?php echo $isvp_switch;?>";              
    var isscore= "<?php echo $isscore;?>";
    var is_showOriginal= "<?php echo $is_show_original;?>";

    var reset = 0;

    var cart_list = []; //购物车数组
    var pos_list = [];  //属性数组

     console.log($images_skin);
     
    var search_data = { 
    
        'searchKey'         : '',   //关键词
        'type_id'           : '',   //分类ID      
        'curCostMin'        : '',   //最低价
        'curCostMax'        : '',   //最高价
        'curScoreMin'       : '',   //最低积分
        'curScoreMax'       : '',   //最高积分
        'supply_id'         : '',   //供应商ID 
        'op_sort'           : 'default_d',  //排序    
        'search_from'       : 1 ,   //search_from 1平台的分类 2品牌代理商分类
        'brand_typeid'      : brand_typeid  ,   //品牌供应商分类ID 
        'tid'               : -1,   //分类页过来的ID          
        'is_shaixuan'       : 0,    //0搜索 1筛选   
        'isnew'             : 0,    //新品上市  
        'ishot'             : 0,    //热卖产品  
        'isvp'              : 0,    //VP产品
        'like_op'           : like_op,  //猜你喜欢动作 cartlike,morelike
        'like_pid'          : like_pid, //猜你喜欢产品ID
        'isscore'           : 0,        //积分专区
        'user_level'        : user_level,   
        'user_id'           : user_id       
            
    }

    $(function(){
        var navLength = $('.topNav ul li').length;
        if(navLength>5){
            $('.topNav ul').css('width',20*navLength+"%");
        }
        
         
                    
        $('.topNav li span').click(function(){
            $('.topNav li span').removeClass("active");
            $(this).addClass("active");
        })
        
        $('.left_s_box p').click(function(){            
                    $('.left_s_box p').removeClass("s_boxSelect");
                    $(this).addClass("s_boxSelect");            
                })

        $('.rs_box').click(function(){
            $(this).addClass("right_active").siblings().removeClass("right_active");
        })

        // searchData(<?php echo $typefirstid;?>);

        var pt_id=sessionStorage.getItem('pt_id');

        if (  pt_id == null ) {
            searchData(<?php echo $typefirstid;?>);
        }else{
            searchData(pt_id);
            $(".type-top-button").removeClass("active");

            $('#act_'+sessionStorage.getItem('pt_id')).addClass("active");
            // if($(con))
        }


        // var 
        // if(sessionStorage.getItem('act')){
            
        // }
        
        addEvent("right_contentBox", "scroll", function(){
            if ($("#right_contentBox").prop("scrollHeight")-$("#right_contentBox").height() <=$("#right_contentBox").scrollTop()+2){
                if(!downFlag){//如果没有全部加载完毕，显示loading图标
                    search_product_ajax();
                }
            }
        });

        $("#tvKeyword").click(function(){  //点击搜索栏跳转
            window.location.href="search.php?customer_id="+customer_id+"";
        })
    })


    

    

    var BHeight = $('.all_Box').offset().top;
    var slideHeight = $(window).height()-BHeight;
    $('.leftBigBox').css("height",slideHeight+"px");
    $('.right_contentBox').css("height",slideHeight+"px");

    function searchData(pt_id) { //点击分类
        var content = "";
        sessionStorage.setItem('pt_id',pt_id);
        $(".leftBigBox").empty();

        $.ajax({
            type: "post",
            url: 'save_class_page.php',
            data: { pt_id: pt_id,customer_id: customer_id,owner_typeids: owner_typeids,page_type:"page3"},
            success: function (result) {

                var Json = JSON.parse(result);

                for (var i = 0; i < Json.length; i++) {
                    var LJson = Json[i].brandinfo;
                    for (var j = 0; j < LJson.length; j++) {
                        content += '<div class="left_s_box">';
                        if (j==0){
                            content += '    <p id="type_1_'+LJson[j].gb_id+'" onclick="search_product_detail(this)" tid="'+LJson[j].gb_id+'" search_from="1" supply_id="-1" res="1" class="s_boxSelect">'+LJson[j].gb_name.toString()+'</p>';
                        }else{
                            content += '    <p id="type_1_'+LJson[j].gb_id+'" onclick="search_product_detail(this)" tid="'+LJson[j].gb_id+'" search_from="1" supply_id="-1" res="1" >'+LJson[j].gb_name.toString()+'</p>';
                        }
                        content += '</div>';
                    }
                } 
                $(".leftBigBox").html(content);
                 var gc_id=sessionStorage.getItem('search_data_id');
                    if(gc_id==null){
                        search_product_detail($("#type_1_"+LJson[0].gb_id));
                    }else{
                        search_product_detail($("#type_1_"+gc_id));
                        $(".s_boxSelect").removeClass("s_boxSelect");
                         $('#type_1_'+sessionStorage.getItem('search_data_id')).addClass("s_boxSelect");
            
                    }

                

                $('.left_s_box p').click(function(){            
                    $('.left_s_box p').removeClass("s_boxSelect");
                    $(this).addClass("s_boxSelect");            
                })
            }    
        });
            
    }
    
    function brandimg(){
        var content = "";
        $.ajax({
            type: "post",
            url: 'save_class_page_brand.php',
            data: {customer_id: customer_id},
            success: function (result) {
                var Json = JSON.parse(result);
                // console.log(Json[0])
                for (var i = 0; i < Json[0].length; i++) {
                    content += '<div class="left_s_box">';
                    if (i==0){
                        content += '    <p id="type_2_'+Json[0][i].user_id+'" onclick="search_product_detail(this)" tid="-1" search_from="2" supply_id="'+Json[0][i].user_id+'" res="1" class="s_boxSelect">'+Json[0][i].brand_name+'</p>';
                    }else{
                        content += '    <p id="type_2_'+Json[0][i].user_id+'" onclick="search_product_detail(this)" tid="-1" search_from="2" supply_id="'+Json[0][i].user_id+'" res="1" >'+Json[0][i].brand_name+'</p>';
                    }
                    content += '</div>';
                } 
                $(".leftBigBox").html(content);

                search_product_detail($("#type_2_"+Json[0][0].user_id));

                $('.left_s_box p').click(function(){            
                    $('.left_s_box p').removeClass("s_boxSelect");
                    $(this).addClass("s_boxSelect");            
                })
            }    
        });
    }

    function search_product_detail (obj) {

        search_data.tid = $(obj).attr("tid");
        sessionStorage.setItem('search_data_id',search_data.tid);
        search_data.search_from = $(obj).attr("search_from");
        search_data.supply_id = $(obj).attr("supply_id");

        isLock = false;
        downFlag = false;
        pageNum = 0;
        reset = 1;
        //解决服务器卡顿时连续点击出现多个相同的产品
        $(obj).removeAttr("onclick").parent().siblings().children().attr("onclick","search_product_detail(this)");
        search_product_ajax();
     
    }

    function search_product_ajax () {

        if (isLock ==false &&  downFlag ==false){           //上锁或者数据加载完毕则不继续加载数据
            isLock=true;



            var curCostMin    = search_data.curCostMin; // 选择的最小价格
            var curCostMax    = search_data.curCostMax; // 选择的最大价格
            var curScoreMin   = search_data.curScoreMin; // 选择的最小积分
            var curScoreMax   = search_data.curScoreMax; // 选择的最大积分
            var searchKey     = search_data.searchKey; // 关键字
            var supply_id     = search_data.supply_id; // 供应商   
            var brand_typeid  = search_data.brand_typeid; // 品牌供应商分类ID
            var tid           = search_data.tid; // 分类页过来的ID
            var op_sort       = search_data.op_sort; // 排序类型 
            var search_from   = search_data.search_from; // 1为平台，2为店铺
            var isnew         = search_data.isnew; // 新品上市
            var ishot         = search_data.ishot; // 热卖产品
            var isvp          = search_data.isvp; // VP产品
            var type_id       = search_data.type_id; // 分类ID
            var like_op       = search_data.like_op; // 猜你喜欢操作
            var like_pid      = search_data.like_pid; // 猜你喜欢产品ID
            var isscore       = search_data.isscore; // 新品上市
            var user_level    = search_data.user_level;
            var user_id       = search_data.user_id;
            
            $.ajax({
                    url: "search.class.php?customer_id="+customer_id+"&op=search",
                    data:{
                            type_id      :type_id,
                            curCostMin   :curCostMin,
                            curCostMax   :curCostMax,
                            curScoreMin  :curScoreMin,
                            curScoreMax  :curScoreMax,
                            searchKey    :searchKey,
                            supply_id    :supply_id,
                            brand_typeid :brand_typeid,
                            tid          :tid,
                            op_sort      :op_sort,
                            search_from  :search_from,
                            isnew        :isnew,
                            ishot        :ishot,
                            isvp         :isvp,
                            like_op      :like_op,
                            like_pid     :like_pid,
                            isscore      :isscore,
                            pageNum      :pageNum,
                            limit_num    :limit_num,
                            user_level   :user_level,
                            user_id      :user_id,
                            my_parent_id :my_parent_id
                    },
                    type: "POST",
                    dataType:'json',
                    async: true,     
                    success:function(result){
                        // callback:callback(result,is_reset,_data); //带参回调
                        var content = "";
                        for (var i=0;i<result.length;i++){
                            content += '<div class="contentBox" id="pro_'+result[i].pro_id+'" shopId="'+result[i].pro_supply_id+'">';
                            content += '    <img src="'+result[i].default_imgurl+'" onclick="gotoProduct('+result[i].pro_id+');" />';
                            content += '    <div class="con_mid_box" onclick="gotoProduct('+result[i].pro_id+');" >';
                            content += '        <p class="bot01">'+result[i].pro_name+'</p>';
                            content += '        <p class="bot03">';
                            //折扣计算
                            if((result[i].discount>0 && result[i].discount<10) && isshowdiscount>0){
                                content += '            <span>折</span>';
                            }   
                            //是否VP
                            if(result[i].isvp>0 && isvp_switch>0){
                                content += '            <span>VP</span>';
                            }   
                            //是否返现
                            if(result[i].display>0 && result[i].is_cashback > 0  && result[i].pro_cash_money > 0){
                                content += '            <span>返</span>';
                        
                            } 
                            content += '        </p>';
                            content += '        <p class="bot02"><?php if(OOF_P != 2) echo OOF_S ?><span class="mon02">'+result[i].now_price+'<?php if(OOF_P == 2) echo OOF_S ?></span>';

                            if(result[i].orgin_price>0 && is_showOriginal>0){
                                content += '            <span class="mon03"><?php if(OOF_P != 2) echo OOF_S ?>'+result[i].orgin_price+'<?php if(OOF_P == 2) echo OOF_S ?></span>';
                            }
                            content += '        </p>';

                            


                            content += '        <p class="ps-tips">';
                            //是否免邮
                            if(result[i].is_free_shipping==1){
                                content += '            <span class="free-post">包邮</span>';
                            }
                            if(isOpenSales >0){                     
                                content += '            <span class="have-sale">已销'+result[i].show_sell_count+'</span>';
                            }                            
                            content += '        </p>';
                            content += '    </div>';

                            if(result[i].propertyids!='' || result[i].is_wholesale==1){
                                content += '    <div class="num-count" id="choose_'+result[i].pro_id+'" onclick="show_pos('+result[i].pro_id+')" delivery_id="'+result[i].delivery_id+'" delivery_name="'+result[i].delivery_name+'" is_QR="'+result[i].is_QR+'" is_wholesale="'+result[i].is_wholesale+'" wholesale_num="'+result[i].wholesale_num+'" is_virtual="'+result[i].is_virtual+'" is_stockOut="'+result[i].is_stockOut+'" pro_card_level_id="'+result[i].pro_card_level_id+'" stock="'+result[i].storenum+'" islimit="'+result[i].islimit+'" limit_num="'+result[i].limit_num+'" supply_id="'+result[i].pro_supply_id+'" is_first_extend="'+result[i].is_first_extend+'" extend_id="'+result[i].extend_id+'" need_score="'+result[i].need_score+'" now_price="'+result[i].now_price+'" is_restricted="'+result[i].new_limit_act['is_restricted']+'" restricted_isout="'+result[i].new_limit_act['restricted_isout']+'" mini_num="0">';
                                content += '        <div class="size-btn">';
                                content += '            选规格';
                                content += '        </div>';
                                content += '    </div>';

                            }else{
                                content += '    <div class="num-count">';
                                content += '        <p class="add" id="add_'+result[i].pro_id+'" onclick="addToCart('+result[i].pro_id+',1)" delivery_id="'+result[i].delivery_id+'" delivery_name="'+result[i].delivery_name+'" is_QR="'+result[i].is_QR+'" is_wholesale="'+result[i].is_wholesale+'" wholesale_num="'+result[i].wholesale_num+'" is_virtual="'+result[i].is_virtual+'" is_stockOut="'+result[i].is_stockOut+'" pro_card_level_id="'+result[i].pro_card_level_id+'" stock="'+result[i].storenum+'" islimit="'+result[i].islimit+'" limit_num="'+result[i].limit_num+'" supply_id="'+result[i].pro_supply_id+'" is_first_extend="'+result[i].is_first_extend+'" extend_id="'+result[i].extend_id+'" is_restricted="'+result[i].new_limit_act['is_restricted']+'" restricted_isout="'+result[i].new_limit_act['restricted_isout']+'"><img src="<?php echo $images_skin ?>/shopping-car.png" /></p>';
                                content += '    </div>';
                            }                                 
                                
                            content += '</div>';                            
                        }
                    

                        if (result.length<limit_num){
                            downFlag = true;
                        }else{
                            pageNum += limit_num;
                        }

                        if (!downFlag){
                            content += '<p id="pinterestMore" style="display: block;text-align: center;">----- 向下滚动加载更多 -----</p>';
                        }else{
                            content += '<p id="pinterestDone" style="display: block;text-align: center;">----- 已全部加载完毕 -----</p>';
                        }

                        $('#pinterestMore').remove();
                        if (reset){
                            $(".right_contentBox").html(content);
                            reset = 0;
                        }else{
                            $(".right_contentBox").append(content);
                        }
                        isLock=false;
                    },
                    error:function(er){
                               
                    }
                   

            });
        
        }
    }


    function gotoProduct(prodID){
         window.location.href="product_detail.php?customer_id="+customer_id+"&pid="+prodID;
    }


    $(".type-top-button").click(function(){
        sessionStorage.clear();
        $(".type-top-button").removeClass("active");
        $(this).addClass("active");
        typeid=$(this).attr("typeid");
        if(typeid>0){
            searchData($(this).attr("typeid"));
        }else{
            brandimg();
        }   
    });

    $("#tvKeyword").click(function(){  //点击搜索栏跳转
        window.location.href="search.php?customer_id="+customer_id+"";
    })

    function show_pos (pro_id) {
        var delivery_id = $("#choose_"+pro_id).attr("delivery_id");
        var is_QR = $("#choose_"+pro_id).attr("is_QR");
        var is_wholesale = $("#choose_"+pro_id).attr("is_wholesale");
        var wholesale_num = $("#choose_"+pro_id).attr("wholesale_num");
        var is_virtual = $("#choose_"+pro_id).attr("is_virtual");
        var supply_id = $("#choose_"+pro_id).attr("supply_id");    //供应商ID
        var stock = $("#choose_"+pro_id).attr("stock");    //库存
        var is_stockOut = $("#choose_"+pro_id).attr("is_stockOut"); 
        var pro_card_level_id = $("#choose_"+pro_id).attr("pro_card_level_id"); 
        var islimit = $("#choose_"+pro_id).attr("islimit");
        var is_first_extend = $("#choose_"+pro_id).attr("is_first_extend"); 
        var extend_id = $("#choose_"+pro_id).attr("extend_id");   
        var wid = is_wholesale;
        get_pro_pos(pro_id,wid);
        


    }

    function hide_pos (pro_id) {
        $(".fix_bottom").hide();
    }

    /*加入购物车开始*/
    function addToCart(pro_id,type){
        if(!checkUserLogin()) {
            return;
        }
        
        //预配送产品不能加入购物车
        if (type==1){
            var delivery_id = $("#add_"+pro_id).attr("delivery_id");
            var is_QR = $("#add_"+pro_id).attr("is_QR");
            var is_wholesale = $("#add_"+pro_id).attr("is_wholesale");
            var wholesale_num = $("#add_"+pro_id).attr("wholesale_num");
            var is_virtual = $("#add_"+pro_id).attr("is_virtual");
            var supply_id = $("#add_"+pro_id).attr("supply_id");    //供应商ID
            var stock = $("#add_"+pro_id).attr("stock");    //库存
            var is_stockOut = $("#add_"+pro_id).attr("is_stockOut"); 
            var pro_card_level_id = $("#add_"+pro_id).attr("pro_card_level_id"); 
            var islimit = $("#add_"+pro_id).attr("islimit");
            var is_first_extend = $("#add_"+pro_id).attr("is_first_extend"); 
            var extend_id = $("#add_"+pro_id).attr("extend_id");
            var num_limit = $("#add_"+pro_id).attr("limit_num");
            var is_restricted = $("#add_"+pro_id).attr("is_restricted"); 
            var restricted_isout = $("#add_"+pro_id).attr("restricted_isout");            
        }else{
            var delivery_id = $("#choose_"+pro_id).attr("delivery_id");
            var is_QR = $("#choose_"+pro_id).attr("is_QR");
            var is_wholesale = $("#choose_"+pro_id).attr("is_wholesale");
            var wholesale_num = $("#choose_"+pro_id).attr("wholesale_num");
            var is_virtual = $("#choose_"+pro_id).attr("is_virtual");
            var supply_id = $("#choose_"+pro_id).attr("supply_id");    //供应商ID
            var stock = $("#choose_"+pro_id).attr("stock");    //库存
            var is_stockOut = $("#choose_"+pro_id).attr("is_stockOut"); 
            var pro_card_level_id = $("#choose_"+pro_id).attr("pro_card_level_id"); 
            var islimit = $("#choose_"+pro_id).attr("islimit");
            var is_first_extend = $("#choose_"+pro_id).attr("is_first_extend"); 
            var extend_id = $("#choose_"+pro_id).attr("extend_id");
            var num_limit = $("#choose_"+pro_id).attr("limit_num"); 
            var is_restricted = $("#choose_"+pro_id).attr("is_restricted"); 
            var restricted_isout = $("#choose_"+pro_id).attr("restricted_isout");        
        }

        if ( delivery_id > 0 ) {
            alertAutoClose("抱歉，"+delivery_name+"产品不能加入购物车");
            return;
        }
        if( is_QR == 1 ){
            alertAutoClose("二维码核销商品不能加入购物车！");
            return;
        }

        if( is_restricted == 1 && restricted_isout == 1 ){
            alertAutoClose("限购产品不能加入购物车，请点击产品进入商品详情购买");
            return;
        }
        if( islimit == 1 ){
            //2018-9-19 禅道37000 产品大佬说限购产品不能加入购物车哦！
            alertAutoClose("限购产品不能加入购物车，请点击产品进入商品详情购买");
            return;
        }
        //判断产品为批发产品时，加入购物车数量是否大于等于最低限制
        if(is_wholesale == 1 && type==2){
            var mount_count = $("#mount_count").val();
            if( parseInt(mount_count) < parseInt(wholesale_num) ){
                alertAutoClose("您购买的数量少于产品最低批发数量");
                return;
            }
        }

        if( is_virtual == 1 ){
            alertAutoClose("虚拟商品不能加入购物车");
            return;
        }

        var pro_arr     = [];//产品信息数组：商品id、数量、属性、主播房间id
        var pro_arrs    = [];//产品数组
        var sid_arr     = [];//商店数组
        var json_arr    = [];//整个购物车数组
        var pid         = pro_id;//产品id 
        var pos_len     = $(".sel_btn").length;
        var pos_arr     = "";//选中属性id拼接字符串

        if (type == 1){
            if (is_wholesale == 1){
                var num = parseInt(wholesale_num);//数量
            }else{
                var num = 1;//数量
            }
        }else{
            var num = $("#mount_count").val();//数量
        }

        if (type == 2){
            var call_value = check_pos();//判断是否选择了属性
            if( call_value ){
                return;
            }
        }
        
        if( num == 0 ){
            alertAutoClose("数量必须大于0才能加入购物车！");
            return;
        }

        if( is_stockOut == 1 ){
            var call_value = check_storenum(pid);
            if( call_value ){
                return;
            }
        }

        if( pro_card_level == 1 && pro_card_level_id != -1){
            var call_value = check_cardLevel(pro_card_level_id,shop_card_id);
            if( call_value ){
                return;
            }
        }

        if( parseInt(stock) < parseInt(num) ){
            alertAutoClose("库存不足");
            return;
        }

        if (type == 2){
            for( i = 0; i < pos_len ; i++ ){
            var pos_id = $(".sel_btn").eq(i).attr("pos_id");
                if( pos_arr == "" ){
                    pos_arr += pos_id;
                }else{
                    pos_arr += "_"+pos_id;
                }
            }
        }
        
        //所选地区是否有货
        // selectedPros = pos_arr;
        // if ( is_aog ) {
        //     var _check_aog = check_aog();
        //     if( _check_aog != 1 ) {
        //         alertAutoClose('您所在地区暂时无货！');
        //         return;
        //     }
        // }
        var day_buy_num = 0;
        var cart_num    = 0;
        /*if( islimit == 1 ){
            //2018-9-19 禅道37000 产品大佬说限购产品不能加入购物车哦！
            alertAutoClose("限购产品不能加入购物车，请点击产品进入商品详情购买");
            return;

            day_buy_num = new_is_limit_num(1,islimit,num,pid);
            cart_num    = get_this_product_num(pid);
            if(cart_num >= num_limit){
                alertAutoClose("此商品限购"+num_limit+"件，您已加入购物车！");
                return;
            }
            var can_buy_num = parseInt(num_limit) - parseInt(day_buy_num);
            if( can_buy_num <= 0 ){
                alertAutoClose("此商品限购"+num_limit+"件，您已购买！");
                return;
            }
            add_cart_num = parseInt(can_buy_num) - parseInt(cart_num);
            if( add_cart_num < 0 ){
                add_cart_num = 0;
            }
            if( add_cart_num < num ){
                num = add_cart_num;
            }
        }*/

        var mb_topic = '';

        var check_first_extend = 0;
        //条件：1、从分享页面打开。2、非自己打开分享页面。3、首次推广奖励产品。4、分享人id匹配上级id。5、上级没有获得该产品的首次推广奖励。6、上级是推广员 (在下单页面需要再验证产品是否为首次推广奖励产品)
        if( exp_user_id > 0 && exp_user_id != user_id && is_first_extend == 1 && my_parent_id == exp_user_id && extend_id < 0 && p_is_promoter > 0){
            check_first_extend = 1;
        }

        pro_arr.push(pid,num,pos_arr,mb_topic,check_first_extend,-1,-1,-1)
        
        console.log(pro_arr);
        //localStorage.clear();
        /*判断localStorage.cart是否存在开始*/
        if( !localStorage.getItem("cart_"+user_id) ){
            localStorage.setItem("cart_"+user_id,"");
        }
        /*判断localStorage.cart是否存在结束*/
        if( from_type == 0 && user_id < 0 ){
            if( !localStorage.getItem("cart_visitor") ){
                localStorage.setItem("cart_visitor","");
            }
            var json = localStorage.getItem("cart_visitor");
        }else{
            var json = localStorage.getItem("cart_"+user_id);
        }
        //console.log(json);
        if(json != "" && json != null && json != 'null'){
            json_arr = eval(json);
            var arr_sid = [];
            for( var i = 0; i < json_arr.length; i++){
                console.log(json_arr);
                var sid = json_arr[i][0];//localStorage里的供应商id
                arr_sid.push(sid);
            }
            if( arr_sid.indexOf( supply_id ) > -1 ){
                for( var i = 0; i < json_arr.length; i++){
                    var sid = json_arr[i][0];
                    if( sid == supply_id ){
                        var pid_arr = json_arr[i][1];
                        var join = true;
                        var pid_num = 0;
                        for( var j = 0; j < pid_arr.length; j++){
                            var arr_pid = json_arr[i][1][j][0];//localStorage里的产品id
                            if( arr_pid == pid ){
                                pid_num = parseInt(pid_num) + parseInt(json_arr[i][1][j][1]);
                            }
                        }
                        if( islimit ==1 && pid_num == can_buy_num ){
                            join = false;
                        }
                        for( var j = 0; j < pid_arr.length; j++){
                            var arr_pid 		= json_arr[i][1][j][0];	//localStorage里的产品id
                            var arr_pos 		= json_arr[i][1][j][2];	//localStorage里的产品属性
                            var arr_topic 		= json_arr[i][1][j][3];	//localStorage里的电商直播id
                            var arr_act_type 	= json_arr[i][1][j][5];	//localStorage里的产品活动类型
							var arr_act_id 		= json_arr[i][1][j][6];	//localStorage里的活动id
                            if( arr_pid == pid && arr_pos == pos_arr && mb_topic == arr_topic && (arr_act_type == undefined || arr_act_type == -1) ){
                                var arr_num = json_arr[i][1][j][1];//localStorage里的产品数量
                                var count = 0;
                                can_buy_num = parseInt(limit_num) - parseInt(pid_num) - parseInt(day_buy_num);
                                count = parseInt(arr_num) + parseInt(num);
                                json_arr[i][1][j][1] = count;//修改数量
								
								if (arr_act_type == undefined) {	//修改旧数据的活动类型和活动id
									json_arr[i][1][j][5] = -1;		//修改活动类型
									json_arr[i][1][j][6] = -1;		//修改活动id
								}
								
                                join = false;
                                break;
                            }

                        }
                        if( join ){
                            json_arr[i][1].push(pro_arr);//对应供应商插入商品
                        }
                        break;
                    }
                }
            }else{
                //插入新的供应商商品
                pro_arrs.push(pro_arr);
                sid_arr.push(supply_id,pro_arrs,-1)
                json_arr.push(sid_arr);
            }
        }else{
            //插入新的供应商商品
            pro_arrs.push(pro_arr);
            sid_arr.push(supply_id,pro_arrs,-1)
            json_arr.push(sid_arr);
        }
        if( user_id > 0 ){
            localStorage.setItem("cart_"+user_id,JSON.stringify(json_arr));
            upload_cart();
        }else{
            localStorage.setItem("cart_visitor",JSON.stringify(json_arr));
        }

        hide_pos();
        alertAutoClose("恭喜你，加入购物车成功");

    }
    /*加入购物车结束*/
/*
    上传实时购物车数据
*/
function upload_cart(){
    var timestamp = Date.parse(new Date());//获取当前时间戳
    timestamp = timestamp/1000;
    localStorage.setItem("cart_time_"+user_id,timestamp);//设置加入购物车时间
    var cart_data = localStorage.getItem("cart_"+user_id);
    console.log('cart_data111',cart_data);
    var cart_time = localStorage.getItem("cart_time_"+user_id);
        $.ajax({ 
            type: "post",
            url: "/shop/index.php/Home/H5Cart/h5_cart_data2",
            data: {customer_id:customer_id2,user_id:user_id,cart_data:cart_data,cart_time:cart_time},
            async: false,
            success: function (result) {
                console.log(result);
            }    
        });
}
    /*有属性判断有没有选择属性开始*/
    function check_pos(){
        var call_value  = false;
        var pos         = $(".pos_div").parent().parent(".small_pro_div");//查有多少个属性父级
        for( i = 0; i < pos.length ; i++ ){
            var active = pos.eq(i).find(".sel_btn").length;
            if( active < 1 ){
                var pos_name = pos.eq(i).attr("pos_name");
                //alert('请选择'+pos_name);
                alertAutoClose("请选择"+pos_name);

                call_value = true;
                return call_value;
            }
        }
        return call_value;
    }
    /*有属性判断有没有选择属性结束*/

    /*库存不足下架开始*/
    function check_storenum(pid){
        var call_value = false;
        $.ajax({
            type: "post",
            url: "is_checkOut.php",
            async: false,
            data: { pid: pid,customer_id: customer_id},
            success: function (result) {
                if( 0 >= parseInt(result) ){
                    alertAutoClose("商品已下架！");
                    call_value = true;
                }
            }
        });
        
        return call_value;
    }
    /*库存不足下架结束*/

    /*产品需要会员卡等级购买开始*/
    function check_cardLevel(pro_card_level_id,shop_card_id){
        var call_value =false;
        $.ajax({
            type: "post",
            url: "is_card_level.php",
            async: false,
            data: { pro_card_level_id: pro_card_level_id,shop_card_id: shop_card_id,customer_id: customer_id,user_id:user_id},
            success: function (result) {
                //return result;
                if( 0 >= parseInt(result) ){
                    alertAutoClose("您的会员级别不够！");
                    call_value = true;
                }
            }
        });
        return call_value;
    }
    /*产品需要会员卡等级购买结束*/

    //获取属性信息
    function get_pro_pos(pro_id,wid){//wid 判断是否有批发属性
        var html = "";

        var pid             = pro_id;//产品id
        var p_num           = 0;//产品数量
        var pos             = "";//产品属性
        var pname           = "";
        var isout           = 0;
        var posArr          = "";
        var need_score      = 0;
        var storenum        = 0;
        var now_price       = 0;
        var propertyids     = ""
        var default_imgurl  = "";
        var result_json = "";
        $.ajax({
            type: "post",
            url: "class_page3_ajax.php",
            async: false,
            data: { pid: pid,pos: pos,customer_id: customer_id,'user_id':user_id,rcount: p_num,op:"get_cartPro"},
            success: function (result) {

                result_json = eval('('+result+')');
                //console.log(result_json);
                if( result_json.code == 1 ){
                    pname           = result_json.name;
                    isout           = result_json.isout;
                    posArr          = result_json.posArr;
                    storenum        = result_json.storenum;
                    need_score      = result_json.need_score;
                    now_price       = result_json.now_price;
                    propertyids     = result_json.propertyids;
                    default_imgurl  = result_json.default_imgurl;
                    //console.log(default_imgurl);
                }else{
                    //alert('网络出错！');
                    alertAutoClose("网络出错！");
                    return false;
                }
            }
        })



        var result_json = "";


        html += '   <div class="black_model" onclick="hide_pos()"></div>';
        if (is_publish){
            html += '   <div class="container">';
        }else{
            html += '   <div class="container">';
        }
        html += '       <div class="first_box">';
        html += '           <div class="imgbox inline-block">';
        html += '               <img src="'+default_imgurl+'">';
        html += '           </div>';
        html += '           <div class="textbox inline-block">';
        html += '               <p class="black_font fs15 p01">'+pname+'</p>';

        var need_scores =$("#choose_"+pid).attr("need_score");
        if(need_scores == 0)
        {
            html += '   <p class="red_font fs17" id="price_score"><?php if(OOF_P != 2) echo OOF_S ?>'+$("#choose_"+pid).attr("now_price")+'<?php if(OOF_P == 2) echo OOF_S ?>';
        }
        else
        {
             html += '    <p class="red_font fs17" id="price_score"><?php if(OOF_P != 2) echo OOF_S ?>'+$("#choose_"+pid).attr("now_price")+'<?php if(OOF_P == 2) echo OOF_S ?>+'+$("#choose_"+pid).attr("need_score")+'积分</p>';
        }  

       
        html += '           </div>';
        html += '           <div class="close_btn" onclick="hide_pos()">';
        html += '           </div>';
        html += '       </div>';
        html += '       <div class="divider"></div>';
        html += '<div class="second-allbox">';
        $.ajax({
            type: "post",
            url: "get_pro_pos.php",
            async: false,
            data: { pid: pid,pos: propertyids,customer_id: customer_id},
            success: function (result) {

                result_json = eval('('+result+')');
                pos_price   = result_json.price;
                var arr = [];
                for(var item in result_json){
                    arr.push(result_json[item]);
                }
                var arr1 = [];
                for(var item1 in arr[0]){
                    arr1.push(arr[0][item1]);
                }


                for( var i = 0; i < arr1.length; i++ ){
                    var one_name = arr1[i].one_name;//一级分类名
                    var two_class = arr1[i].two_class;//二级分类
                    var one_id = arr1[i].one_id;//一级分类
                    html += '<div class="second_box">';
                    html += '   <p class="label_text inline-block fs15" style="padding-left: 15px;font-size: 13px;padding-right:5px;margin-top:3px;">'+one_name+'</p>';
                    html += '   <div class="sel_box inline-block small_pro_div" pos_name="'+one_name+'">';
                    html += '       <ul>';

                    var two_l = two_class.length;
                    for( var j = 0; j < two_l; j++ ){
                        var two_id = two_class[j].id;//二级id

                        pos_arr = pos.split("_");
                        if( pos_arr.indexOf(two_id) > -1 ){
                            html += '<li class="inline-block fs15 gray_font pos_div sel_btn pos_'+one_id+'" pos_id="'+two_id+'" id="pro_div_'+one_id+'_'+two_id+'" ontouchstart="chooseDiv('+one_id+','+two_id+');">'+two_class[j].pos_name+'</li>';
                        }else{
                            html += '<li class="inline-block fs15 gray_font pos_div pos_'+one_id+'" pos_id="'+two_id+'" id="pro_div_'+one_id+'_'+two_id+'" ontouchstart="chooseDiv('+one_id+','+two_id+');">'+two_class[j].pos_name+'</li>';
                        }
                    }

                    html += '       </ul>';
                    html += '   </div>';
                    html += '</div>';
                    
                }


            }
        })

        var ch_num = 1;
        //拥有批发属性的情况下
        if(wid > 0){
            $.ajax({
                type: "post",
                url: "get_pro_pos.php",
                async: false,
                dataType:'json',
                data: {pid:pid,customer_id:customer_id,op:"get_wholesale"},
                success:function(result){
                    result = eval(result);
                    //console.log(result);
                    var wpid    = result.pf_id;//wpid 即 批发属性的的id
                    var wpname  = result.pf_title;//wpid 即 批发属性的的名称
                    var wc_arr  = [];
                    wc_arr      = result.ch_wholesale;
                    html += '<div class="second_box">';
                    html += '   <p class="label_text inline-block fs15" style="padding-left: 15px;font-size: 13px;">'+wpname+'</p>';
                    html += '   <div class="sel_box inline-block small_pro_div">';
                    html += '       <ul>';

                    for(var i=0;i<wc_arr.length;i++ ){
                        var ch_id   = wc_arr[i]['wholesale_id'];
                        var ch_name = wc_arr[i]['wholesale_title'];
                        var ch_num  = wc_arr[i]['wholesale_num'];
                        pos_arr = pos.split("_");
                        if( pos_arr.indexOf(ch_id) > -1 ){
                            html += '<li class="inline-block fs15 gray_font pos_div sel_btn pos_'+wpid+'" pos_id="'+ch_id+'" id="pro_div_'+wpid+'_'+ch_id+'" pos_num="'+ch_num+'" onclick="setpfnum('+pid+','+ch_num+')" ontouchstart="chooseDiv('+wpid+','+ch_id+');">'+ch_name+'</li>';

                            $("#mount_count").html(ch_num);
                            $("#mini_num").val(ch_num);//记录已经选好的产品，最低购买数量
                            html += '<input type="hidden" id="wholesale_num" value="'+ch_num+'">';

                        }else{
                            html += '<li class="inline-block fs15 gray_font pos_div pos_'+wpid+'" pos_id="'+ch_id+'" id="pro_div_'+wpid+'_'+ch_id+'" pos_num="'+ch_num+'" onclick="setpfnum('+pid+','+ch_num+')" ontouchstart="chooseDiv('+wpid+','+ch_id+');">'+ch_name+'</li>';
                            //$("#mini").val(ch_num);
                        }

                    }

                    html += '       </ul>';
                    html += '   </div>';
                    html += '</div>';

                }
            })
        }
        html += '</div>';
        html += '       <div class="divider m20"></div>';
        html += '       <div class="third_box">';
        html += '           <p class="numBox"><span class="jian" onclick="pro_reduce('+pid+')">-</span><input type="text" id="mount_count" name="" id="" value="'+p_num+'" onblur="check_input_num('+pid+')" /><span class="jiajia" onclick="pro_add('+pid+')">+</span></p>';
        html += '           <p class="saveBox" id="stock">仓存 : '+$("#choose_"+pid).attr("stock")+'</p>';
        html += '           <input type="hidden" id="storenum" value="'+$("#choose_"+pid).attr("stock")+'" />';
        html += '       </div>';
        html += '       <div class="orange_btn" onclick="addToCart('+pid+',2)">加入购物车</div>';
        html += '   </div>';    

        $(".fix_bottom").html(html);
        $(".fix_bottom").show();
    }

    /*选择属性开始*/
    function chooseDiv(prid,subid){
        var n_pridsubid=prid+"_"+subid;
        var classname = $("#pro_div_"+n_pridsubid).attr("class");
        var ind = classname.indexOf("sel_btn");
        if(classname.indexOf("sel_btn")!=-1){
            $("#pro_div_"+n_pridsubid).removeClass("sel_btn");
        }else{
            $(".pos_"+prid).removeClass("sel_btn");
            $("#pro_div_"+n_pridsubid).addClass("sel_btn");
        }
        var active = $(".sel_btn");
        var pos_arr = "";
        var pos = "";
        for(var j =0;j<active.length;j++){
            pos = active.eq(j).attr("pos_id");
            if( pos_arr == "" ){
                pos_arr += pos;
            }else{
                pos_arr += "_"+pos;
            }
        }
        // console.log(pos_price);
        // console.log(pos_arr);
        var is_pos = false;

        is_pos = check_choose_pos(pos_arr,pos_price);

        //zhou
        if(is_pos.storenum <= 0)
        {
            $("#mount_count").val(0);
        } 
 
        var code = is_pos.code;

        var innerhtmls = '';
        if(code){
            try {

                if(is_pos.need_score == 0)
                {

                    innerhtmls = '<?php if(OOF_P != 2) echo OOF_S ?>'+is_pos.now_price+'<?php if(OOF_P == 2) echo OOF_S ?>';
                }
                else
                {

                    innerhtmls = '<?php if(OOF_P != 2) echo OOF_S ?>'+is_pos.now_price+'<?php if(OOF_P == 2) echo OOF_S ?>+'+is_pos.need_score+'积分';
                }    

                document.getElementById("price_score").innerHTML = innerhtmls;
            } catch (e) {
            }
            try {
                document.getElementById("stock").innerHTML = '仓存 : '+is_pos.storenum;
                $("#storenum").val(is_pos.storenum);
            } catch (e) {
            }
        }
    }
    /*选择属性结束*/

    function check_choose_pos(aa,arr){
        var reulst = new Array();
        reulst['code'] = false;
        for(var i =0;i<arr.length;i++){
            if(arr[i].proids == aa){
                //reulst = true;
                reulst['code'] = true;
                reulst['now_price'] = arr[i].now_price;
                reulst['need_score'] = arr[i].need_score;
                reulst['storenum'] = arr[i].storenum;
                break;
            }else{
                reulst['code'] = true;
                reulst['now_price'] = arr[0].now_price;
                reulst['need_score'] = arr[0].need_score;
                reulst['storenum'] = 0;
            }
        }
        return reulst;
    }

    /*---批发属性点击开始---*/
    function setpfnum(pid,num){
        $("#wholesale_num").val(num);
        $("#mount_count").attr("value",num);
        $(".choose_"+pid).attr("mini_num",num);

    }
    /*---批发属性点击开始---*/

    function pro_reduce (pro_id) {
        var this_storenum = parseInt($("#storenum").val());
        var min_num = parseInt($("#choose_"+pro_id).attr("mini_num"));
        var now_num = parseInt($("#mount_count").val());

        if (now_num>min_num){
            now_num--;
        }
        if (now_num>this_storenum){
            now_num = this_storenum;
        }
        $("#mount_count").val(now_num);
    }

    function pro_add (pro_id) {
        var this_storenum = parseInt($("#storenum").val());
        var min_num = parseInt($("#choose_"+pro_id).attr("mini_num"));
        var now_num = parseInt($("#mount_count").val());

        if (now_num<this_storenum){
            now_num++;
        }
        if (now_num>this_storenum){
            now_num = this_storenum;
        }
        $("#mount_count").val(now_num);
    }

    function check_input_num (pro_id) {
        var this_storenum = parseInt($("#storenum").val());
        var min_num = parseInt($("#choose_"+pro_id).attr("mini_num"));
        var now_num = parseInt($("#mount_count").val());
        if (now_num>this_storenum){
            now_num = this_storenum;
        }
        if (now_num<min_num){
            now_num = min_num;
        }
        $("#mount_count").val(now_num);
    }
</script>
<script type="text/javascript">
		$(function(){
			$('.jian').click(function(){
				var qwe = $(this).next().val();
				if(qwe==1){
					qwe =1;
				}else{
					qwe--;
				}
			$(this).next().val(qwe)	;
			})
			
			$('.jiajia').click(function(){
				var qwe = $(this).prev().val();				
					qwe++;				
			$(this).prev().val(qwe)	;
			})
			
			$('.shopCard').click(function(){
				showBuyDiv(1);
			})
			
		$('.left_s_box p').click(function(){
			$('.left_s_box ul li').removeClass("s_boxSelect");
			$('.left_s_box').find('ul').stop().slideUp();
			$('.left_s_box p').removeClass("s_boxSelect");
			$(this).addClass("s_boxSelect");
			$(this).parent().find('ul').stop().slideDown();
		})
		$('.left_s_box ul li').click(function(){
			$(this).addClass("s_boxSelect").siblings().removeClass("s_boxSelect");
		})
		$('.num-count').click(function(){
              $('.fix_bottom').css("display","block");
                })
		
		var selectSize='' //尺寸
				
		$(".black_model,.close_btn").on('touchstart',function(){
			hidecontain($('.fix_bottom'))
		})
		$(".second_box ul li").on('touchstart',function(){
			selectlabel($(this))
		})	
		$(".orange_btn").on('touchstart',function(){
			confirm_submit(selectSize)
		})	
	
		//选择尺寸
		function selectlabel(obj){
			$(obj).addClass('sel_btn').siblings().removeClass('sel_btn')
			selectSize=$(obj).html()
		}
		
		//隐藏
		function hidecontain(obj){
			obj.hide()
		}
						
		//确认提交
		function confirm_submit(size){
			console.log(size)	
		}
				
		})
	</script> 

<script>
    <!--微信分享页面参数----start-->
    debug      = false;//调试
    share_url="<?php echo Protocol.$http_host;?>/weixinpl/mshop/class_page3.php?customer_id=<?php echo $customer_id_en;?>"; //分享链接
    title      = "商品分类"; //标题
    desc       = "商品分类"; //分享内容
    imgUrl     = "<?php echo $brand_adimg;?>";//分享LOGO
    share_type = 1;//自定义类型
    /*  share_type:菜单类型
    -1：显示所有，除去复制链接以及查看公众号。
    1 ：只显示 发送给朋友，分享到朋友圈，收藏，刷新，调整字体，投诉。
    2 ：只显示 发送给朋友，分享到朋友圈，分享到QQ，分享到QQ空间，收藏，刷新，调整字体，投诉。
    3 : 只显示收藏，刷新，调整字体，投诉。
    */
    
    <!--微信分享页面参数----end-->
</script>
<!--引入侧边栏 start-->
<?php
require_once('../common/utility_setting_function.php');
$nav_is_publish = check_nav_is_publish("quick_purchase_page",$customer_id);
$fun = "quick_purchase_page";
$is_publish = check_is_publish(2,$fun,$customer_id);
include_once('float.php');
/*判断是否显示底部菜单 start*/
if($is_publish){
    require_once('./bottom_label.php');
}
/*判断是否显示底部菜单 end*/
?>
<!--引入微信分享文件----start-->
<?php require('../common/share.php');?>
<!--引入微信分享文件----end-->
  
<script type="text/javascript" src="./assets/js/amazeui.js"></script>
<script type="text/javascript" src="./js/global.js"></script>
<script type="text/javascript" src="./js/loading.js"></script>
<script src="js/goods/limitbuy.js"></script><!--限购方法-->
<script src="./js/jquery.ellipsis.js"></script>
<script src="./js/jquery.ellipsis.unobtrusive.js"></script>
<script type="text/javascript">
    
    /*是否超过限购数量开始*/
function new_is_limit_num(op,islimit,old_num,old_pid){
    if(islimit == 1){   //判断是否限购
        var pid                 = old_pid;//产品id
        var num                 = old_num;  //数量
        //var pname                 = $(".shangpin-neirong-content").text();    //产品名
        var sum_num             = 0;
        var canbuy_num          = 0;
        var this_product_num    = get_this_product_num(pid);    //购物车中该产品数量
        var day_buy_num         = 0;        //当天购买该产品数量
        var type                = 1;    //只获取当天已购买数量
        console.log(pid);
        $.ajax({
            url: "limitbuy_class.php",
            type: "POST",
            dataType: "json",
            async: false,
            data: {'customer_id':customer_id,'type':type,'user_id':user_id,'pid_str':pid,'pidcount_str':num},
            success:function(data){
                day_buy_num = data.product_count;
            }
        });
        if( op == 1 ){  //只返回当天购买该产品数量
            return day_buy_num;
        }
        return check_limit_product_stu(limit_num,day_buy_num,this_product_num,num,pname,2);
    }
    return true;
}
/*是否超过限购数量结束*/
</script>

</body>
</html>