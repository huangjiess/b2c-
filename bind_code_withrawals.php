<?php
header("Content-type: text/html; charset=utf-8");
require_once('../config.php');
require_once('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require_once('../proxy_info.php');
_mysql_query("SET NAMES UTF8");
//头文件----start
require_once('../common/common_from.php');
//头文件----end
require_once('select_skin.php');
if(!empty($_GET["supplier_id"])){
    $supplier_id = $_GET["supplier_id"];
}else{
    print_r('缺少参数！');
    return;
}
//查询card_id
$query="select shop_card_id from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
$shop_card_id = -1;
while ($row = mysql_fetch_object($result)) {
    $card_id = $row->shop_card_id;
    break;
}
$data['card_id'] = $card_id;
$data['user_id'] = $supplier_id;
$data['isvalid'] = true;



//插入用户会员卡表
$query="select user_phone,ext_info from weixin_commonshop_applysupplys where isvalid=true and user_id=".$data['user_id'];   //获取供应商申请表中用户号码
$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
    $data['phone'] = $row->user_phone;
    $ext_info = $row->ext_info;
    break;
}
$query="select weixin_name from ".WSY_USER.".weixin_users where isvalid=true and customer_id=".$customer_id." and id=".$data['user_id'];   //获取用户表微信名称
$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
    $data['name'] = $row->weixin_name;
    break;
}
$query="select id from ".WSY_USER.".weixin_card_members where isvalid=true and user_id=".$data['user_id'];   //获取用户表微信名称
$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
    $bind_id = $row->id;
    break;
}

//零钱提现信息
//if(empty($bind_id)){
//////    die();
//////    $sql="insert into weixin_card_members(phone,checked,account_type,createtime,card_id,isvalid,user_id) values(".$data['phone'].",true,0,now(),".$data['card_id'].",true,".$data['user_id'].")";
//////    _mysql_query($sql) or die('sql failed1: ' . mysql_error());
//////    if( mysql_insert_id() > 0 ){
//////        $result = true;
//////    }
////}else{
////    //$sql = "update weixin_card_members set account_type=0,name=1,checked=true,card_id=".$data['card_id'].",phone=".$data['phone']." where user_id=".$data['user_id']." and isvalid=true";
////    $sql = "update weixin_card_members set account_type=0,name=1,checked=true,card_id=123,phone=112233 where user_id=1232132 and isvalid=true";
////    print_r($sql);
////    //_mysql_query($sql);
//////    if( mysql_insert_id() > 0 ){
////        $result = true;
//////    }
////    die();
////}

//零钱提现openid绑定
if(isset($fromuser)){
    while ($row = mysql_fetch_object($ext_info)) {
        $arr = $row->withdraw_open_id;
        break;
    }
    if(isset($ext_info)){
        $ext_info = json_decode($ext_info,TRUE);
    }
    $ext_info['withdraw_open_id'] = $fromuser;
    $ext_info = json_encode($ext_info);
    $sql = "update weixin_commonshop_applysupplys set ext_info='".$ext_info."' where user_id=".$data['user_id']." and isvalid=true";
//    print_r($sql);
    _mysql_query($sql);
    $result = true;
}else{
    print_r('缺少参数！');exit;
}

?>
<!DOCTYPE html>
<html lang="zh-CN"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>合作商微信零钱绑定</title>
    <meta charset="utf-8">
    <meta content="" name="description">
    <meta content="" name="keywords">
    <meta content="eric.wu" name="author">
    <meta content="telephone=no, address=no" name="format-detection">
    <meta content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <link href="../weixin_inter/agent_login/css/reset.css" rel="stylesheet">
    <link href="../weixin_inter/agent_login/css/common.css" rel="stylesheet">
    <link href="../weixin_inter/agent_login/css/register.css" rel="stylesheet">
    <link type="text/css" rel="stylesheet" href="./css/order_css/global.css" />
    <link type="text/css" rel="stylesheet" href="./css/css_<?php echo $skin ?>.css" />
    <link rel="stylesheet" type="text/css" href="css/supplier.css"/>
    <script type="text/javascript" src="../common/js/zepto.min.js"></script>
    <script type="text/javascript" src="./js/global.js"></script>
    <script type="text/javascript" src="../common_shop/common/js/hidetool.js"></script>
    <script type="text/javascript" src="./assets/js/jquery.min.js"></script>
    <script type="text/javascript" src="../common/utility.js"></script>
    <style>

    </style>
</head>
<body>
<div data-role="container" class="container register" style="text-align: center;">
    <?php if($result===true){ ?>
        <div>绑定成功，请关闭此页面！</div>
    <?php } ?>
</div>
<script></script>
</body>
</html>
