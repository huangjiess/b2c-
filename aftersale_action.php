<?php
header("Content-type: text/html; charset=utf-8"); 
require('../config.php'); //配置
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
_mysql_query("SET NAMES UTF8");

$customer_id = 3243;
$user_id = 196282;

$type = $configutil->splash_new($_POST['aftersale_type']);	//售后类型
$batchcode = $configutil->splash_new($_POST['batchcode']);	//订单号
$pid = $configutil->splash_new($_POST['pid']);				//商品ID

$fromuser = $_SESSION["fromuser_".$customer_id];

switch($type){
	case 'refund':
		$refund_reason = $configutil->splash_new($_POST['refund_reason']);		//退款原因
		$refund_describe = $configutil->splash_new($_POST['refund_describe']);	//退款原因描述
		$return_account = $configutil->splash_new($_POST['return_account']);	//退款金额
		
		$sql="update weixin_commonshop_orders set sendstatus=5,return_status = 0 ,backgoods_reason='".$refund_reason."',return_account='".$return_account."' where batchcode='".$batchcode."' and pid=".$pid;
		_mysql_query($sql) or die("L14 : query error  : ".mysql_error());


		$sql = "insert into weixin_commonshop_order_logs(batchcode,operation,descript,operation_user,createtime,isvalid) values('".$batchcode."',10,'用户申请退款(申请金额：".$return_account."),原因：".$refund_reason.";备注:".$refund_describe."','".$fromuser."',now(),1)";
		_mysql_query($sql) or die("L28 query error  : ".mysql_error());
		
		break;
		
	case 'returngoods':
		$op = $configutil->splash_new($_POST['op']);	
		
		if($op == 1){	//申请退货
			$re_type = $configutil->splash_new($_POST['re_type']);
			$re_reason = $configutil->splash_new($_POST['re_reason']);
			$re_describe = $configutil->splash_new($_POST['re_describe']);
			$return_account = $configutil->splash_new($_POST['return_account']);
			
			$pic_num = count($_FILES["Filedata"]["tmp_name"]);
			
			/*图片上传*/	
			 $uptypes=array('image/jpg', //上传文件类型列表
			'image/jpeg',
			'image/png',
			'image/pjpeg',
			'image/gif',
			'image/bmp',
			'image/x-png');
			$max_file_size=10000000; //上传文件大小限制, 单位BYTE
			$path_parts=pathinfo($_SERVER['PHP_SELF']); //取得当前路径
			$destination_folder="../../up/mshop/aftersale/".$customer_id.'/'.$pid.'/'; //上传文件路径

			$imgpreview=1; //是否生成预览图(1为生成,0为不生成);
			$imgpreviewsize=1/1; //缩略图比例
			$destination = "";
			$website_default= "http://".CLIENT_HOST."/weixin/plat/app/html/";

			if ($_SERVER['REQUEST_METHOD'] == 'POST')
			{
				$destination_a = '';
				$imgstr = "";
				for($j=0;$j<$pic_num;$j++){
					if (!is_uploaded_file($_FILES["Filedata"]["tmp_name"][$j]))	//判断是否上传文件，是则不上传文件，使用旧文件
					{
						$destination = '';
					   //echo  $destination;
					}else{
						$file = $_FILES["Filedata"];
						if($max_file_size < $file["size"][$j])
						//检查文件大小
						{
							echo "<font color='red'>文件太大！</font>";
							exit;
						}
						if(!in_array($file["type"][$j], $uptypes))
						//检查文件类型
						{
						  echo "<font color='red'>不能上传此类型文件！</font>";
						  exit;
						}
						if(!file_exists($destination_folder))
						   mkdir($destination_folder,0777,true);

						  $filename=$file["tmp_name"][$j];

						  $image_size = getimagesize($filename);

						  $pinfo=pathinfo($file["name"][$j]);

						  $ftype=$pinfo["extension"];
						  $destination = $destination_folder.time().$j.".".$ftype;
						  $overwrite=true;
						  if (file_exists($destination) && $overwrite != true)
						  {
							 echo "<font color='red'>同名文件已经存在了！</a>";
							 exit;
						   }
						  if(!_move_uploaded_file ($filename, $destination))
						  {
							 echo "<font color='red'>移动文件出错！</a>";
							 exit;
						  }
						  $save_str = "../../".$destination;
						  $imgstr .= "<a href=\"".$save_str."\" target=\"_blank\">图片".($j+1)."</a>";
						  $destination_a = $destination_a.$destination.',';
					}
				}
				$destination_a = rtrim($destination_a,',');
			}
			/*图片上传end*/	
			
			$sql = "update weixin_commonshop_orders set sendstatus=3,return_status = 0 , backgoods_reason='".$re_reason."' , return_type = ".$re_type.",return_account='".$return_account."' where batchcode='".$batchcode."' and pid=".$pid;
			_mysql_query($sql) or die("L40 query error  : ".mysql_error());

			$sql = "insert into weixin_commonshop_order_rejects(batchcode,remark,createtime,isvalid,operation_role,record_type,images,account,reason) values('".$batchcode."','".$re_describe."',now(),1,0,0,'".$destination_a."','".$return_account."','".$re_reason."')";
			_mysql_query($sql) or die("L45 query error  : ".mysql_error());

			
			$return_str = "";
			if($re_type == 1){
				$return_str = "退货 , 申请金额：".$return_account;
			}else if($re_type == 2){
				$return_str = "换货";
			}else if($re_type == 0){
				$return_str = "退款 , 申请金额：".$return_account;
			}
			$sql = "insert into weixin_commonshop_order_logs(batchcode,operation,descript,operation_user,createtime,isvalid) 
				values('".$batchcode."',8,'用户申请".$return_str.",原因：".$re_reason.",备注：".(empty($re_describe)?"无":$re_describe)."".$imgstr."','".$fromuser."',now(),1)";
			_mysql_query($sql) or die("L50 query error  : ".mysql_error());
			
		}else if($op == 2){ //输入退货单号退货
			$code = $configutil->splash_new($_POST["code"]);
			$express_type = $configutil->splash_new($_POST["express_type"]);
			$remark = $configutil->splash_new($_POST["remark"]);
			
			$sql="update weixin_commonshop_orders set return_status = 5  where isvalid = true and batchcode='".$batchcode."' and pid=".$pid;
			_mysql_query($sql) or die("L30 : query error  : ".mysql_error());
			
			$sql = "insert into weixin_commonshop_order_logs(batchcode,operation,descript,operation_user,createtime,isvalid) 
				values('".$batchcode."',13,'用户填写退货单：快递类型：".$express_type."单号<a href=\'//m.kuaidi100.com/result.jsp?nu=".$code."\'>".$code."</a>,;备注:".$remark."','".$fromuser."',now(),1)";
			_mysql_query($sql) or die("L34query error  : ".mysql_error());
			
		}
		
		break;
		
	case 'aftersale':
			$re_type = 2; //退货
			
			$re_reason = $configutil->splash_new($_POST['re_reason']);
			$re_describe = $configutil->splash_new($_POST['re_reason']);
			$return_account = $configutil->splash_new($_POST['return_account']);
			
			$pic_num = count($_FILES["Filedata"]["tmp_name"]);
			
			/*图片上传*/	
			 $uptypes=array('image/jpg', //上传文件类型列表
			'image/jpeg',
			'image/png',
			'image/pjpeg',
			'image/gif',
			'image/bmp',
			'image/x-png');
			$max_file_size=10000000; //上传文件大小限制, 单位BYTE
			$path_parts=pathinfo($_SERVER['PHP_SELF']); //取得当前路径
			$destination_folder="../../up/mshop/aftersale/".$customer_id.'/'.$pid.'/'; //上传文件路径

			$imgpreview=1; //是否生成预览图(1为生成,0为不生成);
			$imgpreviewsize=1/1; //缩略图比例
			$destination = "";
			$website_default= "http://".CLIENT_HOST."/weixin/plat/app/html/";

			if ($_SERVER['REQUEST_METHOD'] == 'POST')
			{
				$destination_a = '';
				$imgstr = "";
				for($j=0;$j<$pic_num;$j++){
					if (!is_uploaded_file($_FILES["Filedata"]["tmp_name"][$j]))	//判断是否上传文件，是则不上传文件，使用旧文件
					{
						$destination = '';
					   //echo  $destination;
					}else{
						$file = $_FILES["Filedata"];
						if($max_file_size < $file["size"][$j])
						//检查文件大小
						{
							echo "<font color='red'>文件太大！</font>";
							exit;
						}
						if(!in_array($file["type"][$j], $uptypes))
						//检查文件类型
						{
						  echo "<font color='red'>不能上传此类型文件！</font>";
						  exit;
						}
						if(!file_exists($destination_folder))
						   mkdir($destination_folder,0777,true);

						  $filename=$file["tmp_name"][$j];

						  $image_size = getimagesize($filename);

						  $pinfo=pathinfo($file["name"][$j]);

						  $ftype=$pinfo["extension"];
						  $destination = $destination_folder.time().$j.".".$ftype;
						  $overwrite=true;
						  if (file_exists($destination) && $overwrite != true)
						  {
							 echo "<font color='red'>同名文件已经存在了！</a>";
							 exit;
						   }
						  if(!_move_uploaded_file ($filename, $destination))
						  {
							 echo "<font color='red'>移动文件出错！</a>";
							 exit;
						  }
						  $save_str = "../../".$destination;
						  $imgstr .= "<a href=\"".$save_str."\" target=\"_blank\">图片".($j+1)."</a>";
						  $destination_a = $destination_a.$destination.',';
					}
				}
				$destination_a = rtrim($destination_a,',');
			}
			/*图片上传end*/	
			
			$sql = "update weixin_commonshop_orders set aftersale_type = ".$re_type.",aftersale_state = 1  where batchcode='".$batchcode."' and pid=".$pid;
			_mysql_query($sql) or die("L17 query error  : ".mysql_error());

			$sql = "insert into weixin_commonshop_order_rejects(batchcode,remark,createtime,isvalid,operation_role,record_type,images,reason) values('".$batchcode."','".$re_describe."',now(),1,0,1,'".$imgurl."','".$re_reason."')";
			_mysql_query($sql) or die("L21 query error  : ".mysql_error());

			$return_str = "维权";
			if($re_type == 2){
				$return_str = $return_str."(退货)";
			}else if($re_type == 3){
				$return_str = $return_str."(换货)";
			}
			$sql = "insert into weixin_commonshop_order_logs(batchcode,operation,descript,operation_user,createtime,isvalid) values('".$batchcode."',18,'用户申请售后".$return_str.",原因：".$re_reason."，备注：".(empty($re_describe) ? "无" : $re_describe)."".$imgstr."','".$fromuser."',now(),1)";
			_mysql_query($sql) or die("L31 query error  : ".mysql_error());
			
			break;
			
	default:
			break;
}
?>