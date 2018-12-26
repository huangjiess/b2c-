<?php
header("Content-type: text/html; charset=utf-8");     
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]

$link = mysql_connect(DB_HOST,DB_USER,DB_PWD); 
mysql_select_db(DB_NAME) or die('Could not select database');
//头文件----start
require('../common/common_from.php');
//头文件----end
require('select_skin.php');

require('../common/jssdk.php');

$jssdk = new JSSDK($customer_id);
$signPackage = $jssdk->GetSignPackage();

if(empty($user_id) || $user_id < 1){
	echo "<script>document.location='../weicuxiaocommon/myfunding.php?customer_id=".$customer_id."';</script>";
}
$keyid = -1;
if(!empty($_GET["keyid"])){
	$keyid = $configutil->splash_new($_GET["keyid"]);
    
    $sql = "SELECT id FROM currency_recharge_card_key_t WHERE sha(id)='{$keyid}'";  //id是加密过的，查询真实id
    $result = _mysql_query($sql);
    while ($row = mysql_fetch_object($result)) {
        $keyid = $row -> id;
    }

}

$money = "0000".OOF_T;

if($keyid>0){
	$sql  = "select list_t.money,key_t.`key` as card_key,key_t.recharge_id,key_t.account from currency_recharge_card_key_t as key_t inner join currency_recharge_card_list_t as list_t on key_t.recharge_id=list_t.id where key_t.id=".$keyid." and list_t.isvalid=true and key_t.customer_id=".$customer_id;
	$result = _mysql_query($sql) or die('sql failed: ' . mysql_error());
	while ($row = mysql_fetch_object($result)) {
		$money =  $row->money.OOF_T;
		$card_key =  $row->card_key;
		$recharge_id =  $row->recharge_id;
		$account =  $row->account;
	}
  	
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $custom;?>充值 </title>
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
    
    <link type="text/css" rel="stylesheet" href="./assets/css/amazeui.min.css" />
    <link type="text/css" rel="stylesheet" href="./css/order_css/global.css" />
    <link type="text/css" rel="stylesheet" href="./css/css_<?php echo $skin ?>.css" />      
    
<style>  
   .plus-tag-add{width:100%;min-width:350px;line-height:50px;padding-left:10px;}
   .info_middle{width:100%;height:50px;line-height:50px;background-color:white;margin:0 auto;}
   .gray{color:darkgray;}
   .btn{height: 32px;line-height: 32px;vertical-align: middle;}
   .am-btn{width: 100%;height: 50px;}
   .info_right{text-align:left;color:black;} 
   .plus-tag-add img{margin-right:5px;margin-left:10px;height:14px;vertical-align:middle;}
</style>


</head>
<!-- Loading Screen -->
<div id='loading' class='loadingPop'style="display: none;"><img src='./images/loading.gif' style="width:40px;"/><p class=""></p></div>

<body data-ctrl=true style="background:#f8f8f8;">
	<div style="height: 50px;background-color:#f8f8f8;">
		<div class="plus-tag-add gray"><img src="./images/info_image/edit.png" /><span style="vertical-align: middle;"><?php echo defined('PAY_CURRENCY_NAME')? PAY_CURRENCY_NAME: '购物币'; ?>充值<span></div>
    </div>
   <form action="save_currency_recharge?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>" method="post" id="upform" name="upform">
	<div class="infoBox">
	   <input type="hidden" value="<?php echo $recharge_id;?>" name="recharge_id" id="recharge_id" style="border:none;color:;#bcbcbc;">
	   <input type="hidden" value="<?php echo $keyid;?>" name="key_id" id="key_id" style="border:none;color:;#bcbcbc;">
	    <div style="width:100%;font-size:16px;">
        	<div class="info_middle">
		        <div class="gray" style="float:left;padding-left:15px;width:30%;">
		    		充值卡号:
		    	</div>
	    	<div class="info_right_text"><span> <input type="text" value="<?php echo $account;?>" placeholder="请输入您的充值卡号" name="account" id="account" style="border:none;color:;#bcbcbc;width:50%" onkeyup="checkAccount(this.value)" autofocus="autofocus"></span></div>
            </div>
	   </div>
		<div style="width:100%;font-size:16px;">
        	<div class="info_middle">
		        <div class="gray" style="float:left;padding-left:15px;width:30%;">
		    		充值卡密:
		    	</div>
	    	<div class="info_right_text"><span> <input type="text" value="<?php echo $card_key;?>" placeholder="请输入您的卡密" name="key" id="key" style="border:none;color:;#bcbcbc;width:50%;" onkeyup="checkKey(this.value)"></span>
			<?php if ( $from_type == 1 ) { 	//只有微信端才有扫一扫	?>
				<span id="qr_scan" style="display:inline-block;margin-right:5px;margin:0 auto;">扫一扫</span>
			<?php } ?>
			</div>
       </div>
	   </div>
	   <div style="width:100%;font-size:16px;">
        	<div class="info_middle">
		        <div class="gray" style="float:left;padding-left:15px;width:30%;">
		    		验证码:
		    	</div>				
	    	<div class="info_right_text"><span > <input type="text" value="" placeholder="请输入验证码" name="code" id="code" style="border:none;color:;#bcbcbc;width:50%;" ></span></div>
       </div>
	   </div>
	   <div style="width:100%;font-size:16px;">
        	<div class="info_middle">
		        <div class="gray" style="float:left;padding-left:15px;width:30%;visibility:hidden;">
		    		占位符:
		    	</div>				
	    	<div class="info_right_text"><img id="identify_code" style="width:30%;height:30px;" src="./get_identify_code.php?time=<?php echo rand();?>"/><span id="qr_scan" onclick="code_flesh()">换一张</span></div>
       </div>
	   </div>
	   <div style="width:100%;font-size:16px;">
        	<div class="info_middle">
		        <div class="gray" style="float:left;padding-left:15px;width:30%;">
		    		充值额:
		    	</div>
	    	<div class="info_right_text"><span ><input type="text" value="<?php echo $money?>" style="border:none;color:;#bcbcbc;width:50%;height:30px;line-height:20px;" name="money" id="money" disabled="disabled"></div>
       </div>       
    </div>
	</div>
   </form>
     <div data-am-widget="navbar" class="am-cf am-navbar-default  am-no-layout" style="padding: 20px 25px 0px 25px;height: 70px;">
       <div class="am-btn am-btn-warning ui-link" id="submit" style="background-color: #ccc;border-color:#ccc;">
	       <span style="display:inline-block;line-height:30px;">提交</span>
	   </div>
    </div>
     
    <script type="text/javascript" src="./assets/js/jquery.min.js"></script>    
    <script type="text/javascript" src="./assets/js/amazeui.js"></script>
    <script type="text/javascript" src="./js/global.js?v=<?php echo time(); ?>"></script>
    <script type="text/javascript" src="./js/loading.js"></script>
    <script src="./js/jquery.ellipsis.js"></script>
    <script src="./js/jquery.ellipsis.unobtrusive.js"></script>  
    <script src="./js/r_global_brain.js" type="text/javascript"></script>
    <script type="text/javascript" src="./js/r_jquery.mobile-1.2.0.min.js"></script>
    <script src="./js/sliding.js"></script>
</body>	
<script>
  function commit(){
	  var key = $("#key").val();
	  var money = $("#money").val();
	  var recharge_id = $("#recharge_id").val();
	  var key_id = $("#key_id").val();
	  var account = $("#account").val();
	  var code = $("#code").val();
	  
	   if(account==""||account ==null){
          alertAutoClose("充值卡号不能为空，请重新输入");
		  return;
	  }
	  if(key==""||key ==null){
          alertAutoClose("充值卡密不能为空，请重新输入");
		  return;
	  }
	  if(code==""||code ==null){
          alertAutoClose("验证码不能为空，请重新输入");
		  return;
	  }
	$('#gyuji2').hide();
    $(".sharebg").remove();
    var scroll_top = $(window).height(); //浏览器的可视区域高度
    scroll_top = (scroll_top-100)/2;
    $("body").append('<div class="sharebg" style="opacity:0"></div>');
    $(".sharebg").animate({"opacity":1});
    $(".sharebg").append('<div style="width:100px;height:100%;margin: auto;margin-top:'+scroll_top+'px;"><img src="/mshop/web/view/currency/img/timg.gif" style="width:100px;height:100px;"></div>');
    $(".sharebg").addClass("sharebg-active");
	  var url = "check_currency_recharge_ajax.php?customer_id=<?php echo $customer_id;?>";
	  if(key !="" && key!=null){ //查找用户输入的卡密信息
		  $.ajax({type:'POST', async:true, url:url, 
				data:{ value: key,account:account,op:"commit"}, 
				success:function(data){
						 var type = (typeof data);
						 if(type == "string"){
							 var data = JSON.parse(data);
						 }					 
						 var errcode  = data.errcode;
					 
						 if(errcode == -1){
						 	$('#gyuji2').hide();
    						$(".sharebg").remove();
                             alertAutoClose("充值失败");
							 return; 
						 }else if(errcode == -2){
							var recharge_time = data.recharge_time;
							if(recharge_time < 6){
								$('#gyuji2').hide();
    							$(".sharebg").remove();
								alertAutoClose("您今天已累计输入错误"+recharge_time+"次，错误次数超过5次将被锁定");
								//showAlertMsg("提示","您今天已累计输入错误"+recharge_time+"次，错误次数超过5次将被锁定","确定");
							}else{
								$('#gyuji2').hide();
    							$(".sharebg").remove();
								alertAutoClose("错误次数超过5次，请明天再来试试");
								//showAlertMsg("提示","错误次数超过5次，请明天再来试试","确定");
							}
                            return;							
					    }else if(errcode==0){
							 var status  = data.status;
							 var is_overtime  = data.is_overtime;
							 var card_status  = data.card_status;
							 
							 if(status == 2){
							 	$('#gyuji2').hide();
    							$(".sharebg").remove();
                                 alertAutoClose("该充值卡密已被使用，请重新输入");
								 return;
							 }
							 if(card_status == 1){
							 	$('#gyuji2').hide();
    							$(".sharebg").remove();
                                 alertAutoClose("该充值卡还未发布");
								 return;
							 }
							 if(card_status == 3){
							 	$('#gyuji2').hide();
    							$(".sharebg").remove();
                                 alertAutoClose("该充值卡已被冻结！");
								 return;
							 }
							 if(card_status == 4){
							 	$('#gyuji2').hide();
    							$(".sharebg").remove();
                                 alertAutoClose("该充值卡已结束");
								 return;
							 }
							 if(is_overtime ==-1){
							 	$('#gyuji2').hide();
    							$(".sharebg").remove();
                                 alertAutoClose("该充值卡密已过期");
								 return;
							 }
                             if(is_overtime ==-2){
                             	$('#gyuji2').hide();
    							$(".sharebg").remove();
                                 alertAutoClose("该充值卡密还没到可充值时间");
								 return;
							 } 							 
						 }					 
					user_rechange(key,recharge_id,key_id,account,code);//ajax用户充值
				}
		}); 
	  }	  
  }
  function user_rechange(key,recharge_id,key_id,account,code){
	  var url = "save_currency_recharge.php?customer_id=<?php echo $customer_id;?>";
	  $.ajax({type:'POST', async:false, url:url, 
				data:{ value: key,recharge_id: recharge_id,key_id: key_id,account:account,code:code}, 
				dataType: "json",
				success:function(data){					
					var errcode = data.errcode;
					if(errcode==1){
						$('#gyuji2').hide();
    					$(".sharebg").remove();
						showConfirmMsg("提示","恭喜你！充值成功","确定","取消",callbackfunc);
					}else if(errcode == -2){
						$('#gyuji2').hide();
    					$(".sharebg").remove();
                        alertAutoClose("验证码错误，请重新输入");
					}else if(errcode == -3){
						$('#gyuji2').hide();
    					$(".sharebg").remove();
                        alertAutoClose("你已被拉入黑名单，充值失败");
					}else if(errcode == -4){
						$('#gyuji2').hide();
    					$(".sharebg").remove();
						alertAutoClose("错误次数超过5次，请明天再来试试");
					}else{
						$('#gyuji2').hide();
    					$(".sharebg").remove();
                        alertAutoClose("充值失败");
					}
				}
		}); 
  }
  function callbackfunc(){
	  window.location.href="my_currency.php?customer_id=<?php echo $customer_id_en;?>";
  }

//通过扫码需要重新绑定事件
var value = $("#key").val();
var account = $("#account").val();
if (value != '' && value != undefined && account != '' && account != undefined) 
{
	$('#submit').attr('style','');
	$('#submit').attr('onclick','commit()');
}
  function checkKey(value){
  	$('#submit').attr('onclick',false);
  	$('#submit').attr('style','background-color: #ccc;border-color:#ccc;');
	  var account = $("#account").val();
	  var url = "check_currency_recharge_ajax.php?customer_id=<?php echo $customer_id;?>";
	  if(key !="" && key!=null){ //查找用户输入的卡密信息
		  $.ajax({type:'POST', async:true, url:url, 
				data:{ value: value,account:account}, 
				dataType: "json",
				success:function(data){
					var errcode = data.errcode;
                    if(errcode==0){//卡密匹配成功 	
                    	$('#submit').attr('style','');
						$('#submit').attr('onclick','commit()');					
						$("#money").val(data.money+"<?php echo OOF_T ?>");
						$("#recharge_id").val(data.recharge_id);
						$("#key_id").val(data.key_id);
					}else{
						$('#submit').attr('style','');
						$('#submit').attr('onclick','commit()');
						$("#money").val("0000<?php echo OOF_T ?>");
						$("#recharge_id").val(-1);
						$("#key_id").val(-1);
					}					
				}				
		}); 
	  }   
  }
  function checkAccount(account){
  	$('#submit').attr('onclick',false);
  	$('#submit').attr('style','background-color: #ccc;border-color:#ccc;');
	  var value = $("#key").val();
	  var url = "check_currency_recharge_ajax.php?customer_id=<?php echo $customer_id;?>";
	  if(key !="" && key!=null){ //查找用户输入的卡密信息
		  $.ajax({type:'POST', async:true, url:url, 
				data:{ value: value,account:account}, 
				dataType: "json",
				success:function(data){
					var errcode = data.errcode;
                    if(errcode==0){//卡密匹配成功 
                    	$('#submit').attr('style','');
						$('#submit').attr('onclick','commit()');						
						$("#money").val(data.money+"<?php echo OOF_T ?>");
						$("#recharge_id").val(data.recharge_id);
						$("#key_id").val(data.key_id);
					}else{
						$('#submit').attr('style','');
						$('#submit').attr('onclick','commit()');
						$("#money").val("0000<?php echo OOF_T ?>");
						$("#recharge_id").val(-1);
						$("#key_id").val(-1);
					}				
				}				
		}); 
	  }   
  }	
  function code_flesh(){
	  document.getElementById('identify_code').src='./get_identify_code.php?r='+Math.random();
  }
  timer = setInterval("code_flesh()",1000*60*2);   //2分钟刷新一下验证码
</script>	
<script src="//res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script>
  wx.config({
    debug: false,
    appId: '<?php echo $signPackage["appId"];?>',
    timestamp: <?php echo $signPackage["timestamp"];?>,
    nonceStr: '<?php echo $signPackage["nonceStr"];?>',
    signature: '<?php echo $signPackage["signature"];?>',
    jsApiList: [
      // 所有要调用的 API 都要加到这个列表中
	  "scanQRCode",
      "hideOptionMenu" 	  
    ]
  });  	
    wx.ready(function(){
		wx.hideOptionMenu();
		var qr_scan=document.getElementById("qr_scan"); 
		qr_scan.onclick=function(){
			wx.scanQRCode({
			needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
			scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
			success: function (res) {
			    var result = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
				window.location.href = result;
		}
		});
		}

   });
   
    wx.error(function(res){
	   alert(res.errMsg);
  });
  
 
</script>
<!--引入侧边栏 start-->
<?php  include_once('float.php');?>
<!--引入侧边栏 end-->
</html>