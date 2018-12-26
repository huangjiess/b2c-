<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]

$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('select_skin.php');
//头文件----start
require('../common/common_from.php');
//头文件----end


$tid = -1;
if(!empty($_GET["tid"])){
    $tid=$configutil->splash_new($_GET["tid"]);
}

/******搜索内容******/
$search_data = '';

if(!empty($_GET["search_data"])){
	$search_data = $configutil->splash_new($_GET["search_data"]);
}

/******搜索内容******/


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


?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>兑换积分列表</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta content="no" name="apple-touch-fullscreen">
    <meta name="MobileOptimized" content="320" />
    <meta name="format-detection" content="telephone=no">
    <meta name=apple-mobile-web-app-capable content=yes>
    <meta name=apple-mobile-web-app-status-bar-style content=black>
    <meta http-equiv="pragma" content="nocache">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8">
    <link type="text/css" rel="stylesheet" href="/weixinpl/mshop/assets/css/amazeui.min.css" />
    <link type="text/css" rel="stylesheet" href="/weixinpl/mshop/css/order_css/global.css" />
    <link type = "text/css" rel = "stylesheet" href = "/weixinpl/mshop/css/goods/search.css">
    <link href="/mshop/web/static/css/style.css" type="text/css" rel="stylesheet">
    <!-- <link rel="stylesheet" id="twentytwelve-style-css" href="/weixinpl/mshop/css/list_css/style.css" type="text/css" media="all"> -->
    <link href="/weixinpl/mshop/css/css_green.css" type="text/css" rel="stylesheet">
    <link type="text/css" rel="stylesheet" href="/weixinpl/mshop/css/list_css/r_style.css" />
    <link type="text/css" rel="stylesheet" href="/weixinpl/mshop/css/goods/list_style2.css" />
    <link type = "text/css" rel = "stylesheet" href = "/weixinpl/mshop/css/goods/global.css" />
    <script type="text/javascript" src="/weixinpl/mshop/assets/js/jquery.min.js"></script> 
    <!-- css写在head部分-->
    <style type="text/css">
body,html{background-color: #f8f8f8;}
.seach {padding:8px 0 6px;position: fixed;z-index: 100;width: 100%;background-color: #f8f8f8;}
.seach .input {width:94%;margin:0 auto;border:solid 1px #e1e1e1;height:28px;background-color:#FFF;position:relative;font-size:1.3rem;line-height:28px;text-align:center;color:#a1a1a1;}
.seach .input img {width:13px;margin-right:4px;vertical-align:middle;}
.seach .input input {position:absolute;left:0;top:0;width:100%;height:100%;background-color:transparent;text-align:center;color:#a1a1a1;font-size:1.3rem;}
.am-header-icon-custom {
    height: 8px;
    margin-left: 2px;
}
.sort-fld-end .am-header-icon-custom {
    height: 10px;
    margin-left: 2px;
}
.productContainerDiv{
    background-color: #f8f8f8;
}
.productContainerDiv ul{
    width: 100%;
    box-sizing: border-box;
}
.productContainerDiv ul li{
    width: 100%;box-sizing: border-box;
    margin-top: 10px;
}
.productCont-img-div{
    width: 100%;
    position: relative;
}
.productCont-img-div img{
    width: 100%;
    min-height: 60px;
}
.productCont-p{
    width: 100%;
    box-sizing: border-box;
    padding: 5px 10px 2px 10px;
    line-height: 1.6rem;
    font-size: 1.4rem;
    color: #1c1f20;
    background-color: #ffffff;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.productCont-bot-box{
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-sizing: border-box;
    padding: 0 10px 8px 10px;
    background-color: #ffffff;
}
.productCont-bot-left .productCont-bot-left-price{
    font-size: 1.4rem;
    color: #1c1f20;
}
.productCont-bot-left .productCont-bot-left-des{
    font-size: 1.2rem;
    color: #888888;
}
.productCont-bot-right .productCont-btn{
    font-size: 1.6rem;
    color: #ffffff;
     background: -webkit-linear-gradient(left,#ff4b55, #ff3dbc);
      background: -o-linear-gradient(right,#ff4b55, #ff3dbc);
      background: -moz-linear-gradient(right,#ff4b55, #ff3dbc);
      background: linear-gradient(to right,#ff4b55, #ff3dbc);
      line-height: 30px;
      height: 30px;
      border-radius: 4px;
      padding: 0 7px;
      text-align: center;
      max-width: 180px;
      overflow: hidden;
    text-overflow: ellipsis;
      white-space: nowrap;
}
.productCont-img-biaoqian{
    position: absolute;
    top: 22px;
    left: 0;
    background-color: rgba(255,255,255,0.8);
    font-size: 1.2rem;color: #333333;
    line-height: 25px;
    height: 25px;
    padding-left: 10px;
    padding-right: 10px;
}
.productCont-img-biaoqian::after {
            content: "";
            display: block;
            border-width: 13px;
            border-style: solid;
            border-color: transparent transparent transparent rgba(255, 255, 255, .8);
            position: absolute;
            right: -26px!important;
            top: 0;
        }
.productCont-img-biaoqian span{
    color: #ff4b54;
}

    </style>

</head>

<body>
<!-- HTML内容-->
<div class="seach">
    <input id="tid"  type="hidden"  value="<?php echo $tid;?>"><!--分类ID-->
    <!-- <div class="input">
        <img src="/addons/view/ordering_retail/common/images/search.png" />搜索
        <input type="text" name="keyword" id="keyword" value="" />
    </div> -->
    
    <div class="am-input-group">
        <input id="tvKeyword" class="am-form-field search" type="text" placeholder="<?php echo $placeholder;?>" value="<?php echo $search_data;?>">
        <span class="am-input-group-btn">

            <button  class="title_serch  button buttonclick search_btn" type="button" id="search_btn" >搜索</button>
        </span>
    </div>
</div>
<div style="height:52px"></div><!-- 占据seach框的高度 -->
<div class="topDivSel data_search" >
		    <div id="middle-tab6" class="tabbar">
	            <div id="sortDef" class="sort-flds" value="0">
	                <span class="title_sel" >默认<img class="am-header-icon-custom" src="/weixinpl/mshop/images/list_image/tagbg_item_down.png" /></span>
	            </div>
	            
				<div id="sortSaleNum" class="sort-flds" value="0">
	                <span class="title_sel select" >销量<img class="am-header-icon-custom" src="/weixinpl/mshop/images/list_image/tagbg_item_down.png" /></span>
	            </div>
				<div id="sortScore" class="sort-flds" value="0">
	                <span class="title_sel" >积分<img class="am-header-icon-custom" src="/weixinpl/mshop/images/list_image/tagbg_item_down.png" /></span>
	            </div>
				<div id="sortTime" class="sort-flds" value="0">
	                <span class="title_sel" >时间<img class="am-header-icon-custom" src="/weixinpl/mshop/images/list_image/tagbg_item_down.png" /></span>
	            </div>
	            
				<div id="sortsel" class="sort-fld-end" onclick="javascript:showSearch(1);">
	                <span class="title_sel" >筛选<img class="am-header-icon-custom" src="/weixinpl/mshop/images/list_image/tagbg_item5.png" /></span>
	            </div>
	        </div>
	    </div>
	    <div style="height:48px;"></div> <!-- 占据筛选框的高度 -->
    <!-- Marsk Start-->
	 <div id="leftmask" style="display:none;" data-role="none"></div>
	 <div class="search_new" id="seardiv"  style="display:none;" data-role="none">	
		    <!-- 分类 -->
		    <ul class="area c-fix" id="industrydiv" style="display:none;">
		    	 <div class="m_titleDiv" >
				    <div class="btnTitleLeft" onclick="SelectArea(0);" style="visibility: hidden;">返回</div>
				    <font class="str_ftitle">分类</font>
				    <div class="btnTitleRight" onclick="SelectArea(0);" >确认</div>
	            </div>
	            <div class="white-kind" id="white-kind" >
	            		<!-- 分类 List -->
			    </div>
		  	</ul>
		  	<!-- 筛选 -->
			<ul class="area c-fix" id="areadiv">
			   	<div class="m_titleDivSel" >
				    <div class="btnTitleLeft" onclick="popClose();" >取消</div>
	                <font class="str_ftitle">筛选</font>
	                <div class="btnTitleRight" onclick="confirmOpt();" >确认</div>
	            </div>
	            <div class="white-list" style="margin-top:10px;">
			        <div class="list-one" onclick="javascript:SelectCtgr();">
			            <div class="left-title"><span >分类</span></div>
			            <div class="center-content"><span id="ctgrTitle" class="rights-spanStr">全部</span></div>
			            <div class="right-action"><img src="/weixinpl/mshop/images/btn_right.png" class="right-actionImg" alt=""></div>
			        </div>
			        <div class="line"></div>

			    </div>
			    <div class="btndiv_cancel">
	                <button class="small-type-button6" type="button" onclick="popClearClose(0);" style="width:100%;">清除选项</button>
	            </div>
			</ul>
	  
	 </div>
    <!-- Marsk End-->
	<input type="hidden" id="pro_type_id" value="<?php echo $type_id;?>">
	<input type="hidden" id="is_privilege_type" value="<?php echo $is_privilege_type;?>">
<div class="productContainerDiv">
    <ul id="productContainerDiv" fixcols="">
        <!-- <li>
            <div class="productCont-img-div">
                <div class="productCont-img-biaoqian">
                    距结束<span>11</span>时<span>21</span>分<span>33</span>秒
                </div>
                <a href="#">
                    <img src="../web/static/images/1.jpg">
                </a>
            </div>
            <p class="productCont-p">WSYde白紫米萃柔嫩洁颜棒套装 新品 去角质 温和清洁</p>
            <div class="productCont-bot-box">
                <div class="productCont-bot-left">
                    <div class="productCont-bot-left-price">￥1212</div> 
                    <div class="productCont-bot-left-des"><span>已售100</span> &nbsp| <span>库存44</span></div> 
                </div>
                <div class="productCont-bot-right">
                    <div class="productCont-btn">￥99 + 200积分</div>
                </div>
            </div>
        </li> -->
    </ul>
</div>
<!-- 补丁加end -->

<!-- 筛选需要的参数 -->
<input id="isAllClear" type="hidden" value="0" name="">
<input id="selParentCtgr" type="hidden" value="-1" name="全部">
<input id="curParentCtgr" type="hidden" value="-1" name="全部">
<input id="curChildCtgr" type="hidden" value="-1" name="">
<input id="tvKeyword" type="hidden" placeholder="" value="">
<input id="selChildCtgr" type="hidden" value="-1" name="">

<!-- 筛选需要的参数 -->

<!-- body尾部放js-->
<script type="text/javascript" src="/addons/view/ordering_retail/common/js/jquery-1.12.1.min.js"></script>
<script type="text/javascript" src="/addons/common/get.js"></script>
<script src="/addons/common/js/common_function.js"></script>
<script type="text/javascript" src="/addons/view/ordering_retail/common/js/global.js"></script>
<script src="/addons/view/ordering_retail/common/js/js.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript">
<!-- 筛选参数 -->
    var customer_id = '<?php echo $customer_id?>';
    var OOF_P = '<?php echo OOF_P;?>';   
    var OOF_S = '<?php echo OOF_S;?>';  
    var $color='<?php echo $skin?>';
    var search_keyword ='<?php echo $search_data;?>'; ; 
    var tid 			= "<?php echo $tid ;?>";
    var type_id         = "<?php echo $type_id; ?>";
    var searchKey       = "<?php echo $searchKey; ?>";
    var op_sort         = "<?php echo $op_sort; ?>";
    var s_n = '<?php echo $s_n ;?>';  
    var host = window.location.host;
    var protocol = window.location.protocol;
    var product_url = protocol+"//"+host+"/weixinpl/mshop/product_detail.php?customer_id="+customer_id+"&pro_act_type=22&pid=";

    
    <!-- 模拟数据 -->

    var s_n = '-1'; 

<!-- 筛选参数 -->    

	$(document).ready(function(){
		$("#leftmask").bind("click",function(){
			$("#seardiv").hide("slow");
			$("#leftmask").hide("slow");
		});
	});

    
    
    
</script>
	<script type="text/javascript" src="./js/global.js"></script>
	<script type="text/javascript" src="./assets/js/amazeui.js"></script> 
    <script src="./js/r_global_brain.js" type="text/javascript"></script>
	<script src="./js/r_pinterest.js" type="text/javascript"></script>
    <script type="text/javascript" src="./js/loading.js"></script>
    <script src="./js/jquery-cookie.js"></script>
<script type="text/javascript" src="./js/goods/list_integral.js"></script>
<!--引入微信分享文件start-->
<script>	

 	
	// <!--微信分享页面参数start-->
	
	var debug      = false;//调试
	var title="<?php echo "积分兑换列表";?>"; //标题
	var desc="<?php echo $shop_introduce;?>"; //分享内容
	var imgUrl="<?php echo $define_share_image;?>"//分享LOGO
	var share_url = "<?php echo Protocol.HostUrl;?>/weixinpl/common_shop/jiushop/forward.php?type=creditsExchange&customer_id=<?php echo $customer_id_en;?>&exp_user_id=<?php echo passport_encrypt((string)$user_id);?>";//默认分享链接
	share_type=1;//自定义类型
	/*	share_type:菜单类型
	-1：显示所有，除去复制链接以及查看公众号。
	1 ：只显示 发送给朋友，分享到朋友圈，收藏，刷新，调整字体，投诉。
	2 ：只显示 发送给朋友，分享到朋友圈，分享到QQ，分享到QQ空间，收藏，刷新，调整字体，投诉。
	3 : 只显示收藏，刷新，调整字体，投诉。
	*/
	
	// <!--微信分享页面参数end-->
</script> 

<?php require('../common/share.php');?> 
<!--引入微信分享文件end-->
</body>

</html>
<!--引入侧边栏 start-->
<?php  
require_once($_SERVER['DOCUMENT_ROOT'].'/weixinpl/common/utility_setting_function.php');
$fun = 'exchange_area';
$nav_is_publish = check_nav_is_publish($fun,$customer_id);
$is_publish = check_is_publish(2,$fun,$customer_id);
include_once($_SERVER['DOCUMENT_ROOT'].'/weixinpl/mshop/float.php');
/*判断是否显示底部菜单 start*/
if($is_publish){
	require_once($_SERVER['DOCUMENT_ROOT'].'/weixinpl/mshop/bottom_label.php');
}
/*判断是否显示底部菜单 end*/
?>
<!--引入侧边栏 end-->
