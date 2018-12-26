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

//var_dump($typearr);

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
    <link rel="stylesheet" type="text/css" href="./css/classify04.css"/>
	<style type="text/css">
        .slideBox{ position:relative; overflow:hidden;width:100%;}
        .slideBox .hd{ position:absolute; height:28px; line-height:28px; bottom:0; right:0; z-index:1; }
        .slideBox .hd li{ display:inline-block; width:5px; height:5px; -webkit-border-radius:5px; -moz-border-radius:5px; border-radius:5px; background:#333; text-indent:-9999px; overflow:hidden; margin:0 6px;   }
        .slideBox .hd li.on{ background:#fff;  }
        .slideBox .bd{ position:relative; z-index:0; }
        .slideBox .bd li{ position:relative; text-align:center; height:180px}
        .slideBox .bd li img{ vertical-align:top; width:100%;}
        .slideBox .bd li a{ -webkit-tap-highlight-color:rgba(0,0,0,0);  }  /* 去掉链接触摸高亮 */
        .slideBox .bd li .tit{ display:block; width:100%;  position:absolute; bottom:0; text-indent:10px; height:28px; line-height:28px; background:url(images/focusBg.png) repeat-x; color:#fff;  text-align:left;  }
         .topNav::-webkit-scrollbar {display:none}
   .leftBigBox::-webkit-scrollbar {display:none}
    </style>
</head>
<!-- Loading Screen -->
<body data-ctrl=true class="white-back">
<div class="search-bigbox">
		<div class="seach_box">
		   <img src="images/icon_search_gray.png" /><input type="text" id="tvKeyword" placeholder="搜索"></p>
		</div>
		</div>
		
		<div class="topNav">
			<ul>
                <li class="class_type active" typeid="-2" id="li_typeid_-2"><span>所有分类</span></li>          
                <?php if($brandsupply>0){//判断有无开启品牌供应商?>
					<li class="class_type" typeid="-1" id="li_typeid_-1"><span>品牌分类</span></li>
                <?php }?>
				<?php				
				for($i=0;$i<sizeof($typearr);$i++){
				   $typestr=explode('_',$typearr[$i]);						
				?>
					
				<li class="class_type" typeid="<?php echo $typestr[0];?>" id="li_typeid_<?php echo $typestr[0];?>">
				   <?php if($typestr[2]==1){?>
					<img src="images/special.png" class="privilege_one"/>
					<?php }?>
				   <span><?php echo $typestr[1];?></span>
				</li>
				<?php }?>
			</ul>
		</div>
		<div id="slideBox" class="slideBox">
			<div class="bd">
				<ul class="silde_box02">

				</ul>
			</div>
			<div class="hd" style="opacity:0">
				<ul class="silde_box02">

				</ul>
			</div>
		</div>
        <div id="show_info">
        </div>
		<!--<div class="content-allbox">
			<div class="inner-allbox">
				<div class="nav-line">
					<div class="class-name"></div>
					<div class="more">
						<span><a class="get_more">更多</a></span>
						<img src="./images/iconfont-jiantou.png"/>
					</div>
				</div>	
			</div>
			
			<div class="allcontent">
							
			</div>
			
		</div>-->
<?php
/*判断是否显示底部菜单 start*/
require_once('../common/utility_setting_function.php');
$fun = "classification4";	
$is_publish = check_is_publish(2,$fun,$customer_id);

if($is_publish){
	require_once('./bottom_label.php');
}
/*判断是否显示底部菜单 end*/
?>
<script type="text/javascript" src="./assets/js/jquery.min.js"></script> 
<script type="text/javascript" src="./js/jquery-2.1.3.min.js"></script> 
<script src="./js/TouchSlide.1.1.js"></script>
<script type="text/javascript">
    var winWidth = $(window).width();
    var winheight = $(window).height();
	var imgheight= $("#type_adimg2").height();
    var customer_id='<?php echo $customer_id_en;?>';
	var owner_typeids="<?php echo $owner_typeids;?>";
	var new_baseurl="<?php echo $new_baseurl;?>";
	var brand_adimg="<?php echo $brand_adimg;?>"; 
	var brand_adurl="<?php echo $brand_adurl;?>";	
	var brandsupply="<?php echo $brandsupply;?>";
	
    //$('li').removeClass("active");
	//$("#li_typeid_"+<?php echo $typefirstid;?>).addClass("active");	

	$(function() {

         searchData(-2);        
         adjustSize();  
	});
    
    $(window).resize(function() {
        adjustSize();
    });
    
    function adjustSize(){
    	$('#leftArea').height(winheight-105);
    	$('#rightArea').height(winheight-105);

    }
      
    function searchData(pt_id) { //点击分类
        $(".class_type").removeClass("active");        
		$(".class_type span").removeClass("active");
        $("#li_typeid_"+pt_id).addClass("active");
        
        var content = "";
		var content_ad="";
		var content_parent="";
		$("#type_parent").empty();

		$("#type_adimg2").empty();
		$("#subTypeList").empty();
		$.ajax({
			type: "post",
			url: 'save_class_page.php',
			data: { pt_id: pt_id,customer_id: customer_id,owner_typeids: owner_typeids,page_type:"page4"},
			success: function (result) {				
				var Json = JSON.parse(result);				
                if(pt_id ==-2){//全部分类
                    for (var i = 0; i < Json.length; i++) {
                        content += '<div class="content-allbox">';
                        content += '<div class="inner-allbox">';
                        content += '  <div class="nav-line">';
                        content += '    <div class="class-name">'+Json[i].gc_name+'</div>';
                        content += '        <div class="more">';
                        content += '            <span><a class="get_more" href="javascript:void(0);" onclick="searchData('+Json[i].gc_id+')">更多</a></span>';
                        content += '            <img src="./images/iconfont-jiantou.png"/>';
                        content += '        </div>';
                        content += '  </div>';	
                        content += '</div>';
                        
                        content += '   <div class="allcontent">';
                           var LJson = Json[i].brandinfo;
					
                            for (var j = 0; j < LJson.length; j++) {
                                content += '<div class="box-all">';									
                                content += '  <div class="class-one">';
                                content += '    <a href="list.php?customer_id=' + customer_id + '&tid=' + LJson[j].gb_id +'">';
                                content += '       <img src="' + LJson[j].gb_logo.toString() + '" alt=""/>';
                                content += '    </a>';
                                content += '  </div>';
                                content += '</div>';					
                            }                      
                        content += '   </div>';
                        
                        content += '</div>';
                    }
                    
                    $("#show_info").html(content);
                    $("#slideBox").html();
                }else{//指定分类                                                          
                    content += '<div class="content-allbox">';
                    content += '<div class="inner-allbox">';
                    content += '  <div class="nav-line">';
                    content += '    <div class="class-name">'+Json[0].gc_name+'</div>';
                    content += '  </div>';	
                    content += '</div>';
                    
                    content += '   <div class="allcontent">';
                    
                    for (var i = 0; i < Json.length; i++) {
                        if(Json[i].type_adimg){

                            content_ad +='<li><a class="pic" href="'+ Json[i].type_adurl +'"><img src="'+ new_baseurl+Json[i].type_adimg +'" alt=""/></a></li>';
                        }
                        if(Json[i].type_adimg2){

                            content_ad +='<li><a class="pic" href="'+ Json[i].type_adurl2 +'"><img id="imgAdvert" src="'+ new_baseurl+Json[i].type_adimg2 +'" alt=""/></a></li>';
                        }
                        if(Json[i].type_adimg3){

                            content_ad +='<li><a class="pic" href="'+ Json[i].type_adurl3 +'"><img id="imgAdvert" src="'+ new_baseurl+Json[i].type_adimg3 +'" alt=""/></a></li>';
                        }
                        if(Json[i].type_adimg4){

                            content_ad +='<li><a class="pic" href="'+ Json[i].type_adurl4 +'"><img id="imgAdvert" src="'+ new_baseurl+Json[i].type_adimg4 +'" alt=""/></a></li>';
                        }
                        if(Json[i].type_adimg5){

                            content_ad +='<li><a class="pic" href="'+ Json[i].type_adurl5 +'"><img id="imgAdvert" src="'+ new_baseurl+Json[i].type_adimg5 +'" alt=""/></a></li>';
                        }
                        var LJson = Json[i].brandinfo;
                        
                        for (var j = 0; j < LJson.length; j++) {
                            content += '<div class="box-all">';									
                            content += '  <div class="class-one">';
                            content += '    <a href="list.php?customer_id=' + customer_id + '&tid=' + LJson[j].gb_id +'">';
                            content += '       <img src="' + LJson[j].gb_logo.toString() + '" alt=""/>';
                            content += '    </a>';
                            content += '  </div>';
                            content += '</div>';					
                        }
                    }
                    content += '   </div>';                        
                    content += '</div>';
                }				
				
				$(".silde_box02").html(content_ad);
				$("#show_info").html(content);
                
				if ( content_ad != '' ) {
					TouchSlide({
						slideCell:"#slideBox",
						titCell:".hd ul", //开启自动分页 autoPage:true ，此时设置 titCell 为导航元素包裹层
						mainCell:".bd ul",
						effect:"leftLoop",
						autoPage:true,//自动分页
						autoPlay:true //自动播放
					});
				}
			}    
		});
			
    }
    
	function brandimg(){
		var content = "";
		var content_parent="";
		var content_ad="";
		$.ajax({
			type: "post",
			url: 'save_class_page_brand.php',
			data: {customer_id: customer_id},
			success: function (result) {
				var Json = JSON.parse(result);

				$("#show_info").html('');

				content += '<div class="content-allbox">';
                content += '<div class="inner-allbox">';
                content += '  <div class="nav-line">';
                content += '    <div class="class-name"></div>';
                content += '        <div class="more">';
                content += '            <span><a class="get_more" href="javascript:void(0);">更多</a></span>';
                content += '            <img src="./images/iconfont-jiantou.png"/>';
                content += '        </div>';
                content += '  </div>';	
                content += '</div>';
                content += '   <div class="allcontent">';

				for (var i = 0; i < Json[0].length; i++) {               			  
					content += '<div class="box-all">';
					content += '  <div class="class-one">';
					content += '     <a href="list.php?customer_id='+customer_id+'&supply_id='+Json[0][i].user_id+'">';
					content += '        <img src="' + Json[0][i].brand_logo + '" alt=""/>';	
				    content += '     </a>';
					content += '  </div>';
				    content += '</div>';
				}
				content += '</div>';
				content += '</div>';

				$("#show_info").html(content);

				for(var i=0;i<Json[1].length;i++){
				    if(Json[1][i].brand_adimg && Json[1][i].brand_adimg != ""){
                        content_ad +='<li><a class="pic" href="'+Json[1][i].brand_adurl+'"><img id="imgAdvert" src="'+ new_baseurl+Json[1][i].brand_adimg +'" alt=""/></a></li>';
                    }

				}

				$(".class-name").html('品牌分类');
				$(".more").html("");
                $(".silde_box02").html(content_ad);

				if ( content_ad != '' ) {
					TouchSlide({ 
						slideCell:"#slideBox",
						titCell:".hd ul", //开启自动分页 autoPage:true ，此时设置 titCell 为导航元素包裹层
						mainCell:".bd ul", 
						effect:"leftLoop", 
						autoPage:true,//自动分页
						autoPlay:true //自动播放
					});
				}
			}    
		});
	}

	$(".class_type").click(function(){   	       
        $(".class_type").removeClass("active");        
		typeid=$(this).attr("typeid");		
        $("#li_typeid_"+typeid).addClass("active");

		if(typeid>0 || typeid==-2){
			searchData($(this).attr("typeid"));
		}else{
			brandimg();
		}	

    });
	
    function goShop(shopID){
    	window.location = "wodedianpu.html";
    }
    $("#tvKeyword").click(function(){  //点击搜索栏跳转
		window.location.href="search.php?customer_id="+customer_id+"";
	})
    
    var navLength = $('.topNav ul li').length;
			if(navLength>5){
				$('.topNav ul').css('width',20*navLength+"%");
			}
    $('.topNav li span').click(function(){
				$('.topNav li span').removeClass("active");
				$(this).addClass("active");
			})
</script>
<script>
	<!--微信分享页面参数----start-->
	debug      = false;//调试
	share_url="<?php echo Protocol.$http_host;?>/weixinpl/mshop/class_page.php?customer_id=<?php echo $customer_id_en;?>"; //分享链接
	title      = "商品分类"; //标题
	desc       = "商品分类"; //分享内容
	imgUrl     = "<?php echo $brand_adimg;?>";//分享LOGO
	share_type = 1;//自定义类型
	/*	share_type:菜单类型
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
$nav_is_publish = check_nav_is_publish("category_page_3",$customer_id);
$fun = "category_page_3";
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
<!--引入侧边栏 start-->
<?php  
$nav_is_publish = check_nav_is_publish($fun,$customer_id);
include_once('float.php');
?>
<!--引入侧边栏 end-->   
    <script type="text/javascript" src="./assets/js/amazeui.js"></script>
    <script type="text/javascript" src="./js/global.js"></script>
    <script type="text/javascript" src="./js/loading.js"></script>
    <script src="./js/jquery.ellipsis.js"></script>
    <script src="./js/jquery.ellipsis.unobtrusive.js"></script>
    <script>

    </script>
</body>
</html>