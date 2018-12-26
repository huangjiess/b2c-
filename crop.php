<?php
header("Content-type: text/html; charset=utf-8"); //svn
require('../config.php');
$customer_id = $_GET['customer_id'];
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../proxy_info.php');
$uptypes=array('image/jpg', //上传文件类型列表
'image/jpeg',
'image/png',
'image/pjpeg',
'image/gif',
'image/bmp',
'image/x-png');
$user_id = $_POST['user_id'];
$max_file_size=1000000; //上传文件大小限制, 单位BYTE
$upload_name = "Filedata";
$destination_folder = "../up/1/".$customer_id."/my_microshop/"; 
if (!is_uploaded_file($_FILES[$upload_name]["tmp_name"]))
//是否存在文件
{
	echo "缺少文件";
}else{
	$file = $_FILES[$upload_name];
	if($max_file_size < $file["size"])
	//检查文件大小
	{
		echo "<font color='red'>文件太大！</font>";
		exit;
	}
	if(!in_array($file["type"], $uptypes))
	//检查文件类型
	{
		echo "<font color='red'>不能上传此类型文件！</font>";
		exit;
	}
	if(!file_exists($destination_folder)){
		// echo "folder=====".$destination_folder;
		mkdir($destination_folder,0777,true);
	}

	$filename=$file["tmp_name"];

	$image_size = getimagesize($filename);//获得图片大小

	$pinfo=pathinfo($file["name"]);

	$ftype=$pinfo["extension"];
	$destination = $destination_folder.time().".".$ftype;
	$overwrite=true;
	if (file_exists($destination) && $overwrite != true)
	{
		echo "<font color='red'>同名文件已经存在了！</a>";
		exit;
	}
	if(!_move_uploaded_file ($filename, $destination))//创造本地图片
	{
		echo $filename."<br/>";
		echo $destination."<br/>";
		echo "<font color='red'>移动文件出错！</a>";
	}

	$type= end (getimagesize($destination)); //获取存储的文件类型

	$rate = 2.7;//截取图片的参数
	$targ_w = 360;//截取图片的参数
	$targ_h = $targ_w/$rate;//截取图片的参数
	$jpeg_quality = 90;//截取图片的参数
	
	if( $type == "image/jpeg" ){//根据图片的格式进行分类
		$img_r = imagecreatefromjpeg($destination);
	}elseif( $type == "image/gif" ){
		$img_r = imagecreatefromgif($destination);
	}elseif( $type == "image/png" ){
		$img_r = imagecreatefrompng($destination);
	}
	
	$dst_r = ImageCreateTrueColor( $targ_w, $targ_h );
	imagecopyresampled($dst_r,$img_r,0,0,$_POST['x'],$_POST['y'],$targ_w,$targ_h,$_POST['w'],$_POST['h']);//获取图片的长宽高等参数后对原图片进行截取
	//header('Content-type: image/jpeg');
	
	if( $type == "image/jpeg" ){//根据图片的格式进行分类
		imagejpeg($dst_r,$destination,$jpeg_quality); 
	}elseif( $type == "image/gif" ){
		imagegif($dst_r,$destination);         
	}elseif( $type == "image/png" ){
		imagepng($dst_r,$destination);
	}  
	$destination = substr($destination,3);
	$destination = "http://".CLIENT_HOST."/weixinpl/".$destination;
	$query = "update weixin_commonshop_microshop set shop_bgimgurl='".$destination."' where isvalid=true and customer_id=".$customer_id." and user_id=".$user_id;//更新微店的背景图片
	
	_mysql_query($query) or die('Query failed: ' . mysql_error());
	Header("HTTP/1.1 303 See Other");
	$url="my_microshop/my_microshop.php?customer_id=".$customer_id_en; 
	Header("Location: $url"); //跳转回微店页面
	exit;
}
	
?>