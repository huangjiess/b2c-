<?php
header("Content-type: text/html; charset=utf-8"); //svn
require_once('../config.php');
require_once('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require_once('../proxy_info.php');
require_once('../common/utility_msg.php');
require_once('../common/utility_fun.php');
require_once('../common/utility_app.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/weixinpl/function_model/sms/sms.class.php');

//zhou 2017-7-6 请求app接口
require_once('../common/app_api/login_api_class.php');
$api = new login_api_class();

_mysql_query("SET NAMES UTF8");

$username=-1;
$password=-1;
$weixin_fromuser=-1;//用户标识
$weixin_headimgurl="";//用户头像
$status=-1; //返回的状态
$phone="";//电话号码
$op="";//操作，1为登陆，2为修改密码
//+韦工 11:34 2016/9/3 检测$str如果不存在，就新建一个PHP5的基类，避免出现警告信息，也避免不可预测的错误
if(!isset($str)){
    $str = new stdClass();
}
//+
$str->url="";
$urltype=-1; //跳转参数
if(!empty($_POST["op"])){
	$op = $configutil->splash_new($_POST["op"]);
}
if(!empty($_GET["urltype"])){
	$urltype = $configutil->splash_new($_GET["urltype"]);
}
/*用于 艺人项目*/
// if($urltype==32){
// 	$str->yiren_web_time="";
// 	$str->yiren_is_yz="";
// }
/*用于 艺人项目*/
if(!empty($_GET["customer_id"])){ //判断页面传过来的customer_id值
	$customer_id = $configutil->splash_new($_GET["customer_id"]);
	if(sql_check($customer_id)){
		$status=-2;
		$str->status=$status;
		echo json_encode($str);
		return;
	}
	$customer_id=passport_decrypt((string)$customer_id);  //解密customer_id
}


//电商直播开始
$url = "";
if(!empty($_POST["urltype"])){
	$urltype = $configutil->splash_new($_POST["urltype"]);
}
if(!empty($_POST["url"])){
	$url = $configutil->splash_new($_POST["url"]);
}
if(!empty($_POST["topic_id"])){
	$topic_id = $configutil->splash_new($_POST["topic_id"]);
}
//电商直播结束

//艺人项目 开始
// if(!empty($_GET["urltype"])){
// 	$urltype = $configutil->splash_new($_GET["urltype"]);
// }
//艺人项目 结束

if($customer_id<0){ //假如获取不了customer_id，就提示找不到商家
	$status=-3;
	$str->status=$status;
	echo json_encode($str);
	return;
}

/*-------封装发送短信方法------*/
function send_yzm($customer_id,$mobile){

		/*---组装随机数---*/
		session_start();//开启缓存
		$_SESSION['time'.$mobile] = date("Y-m-d H:i:s");
		srand((double)microtime()*1000000);//create a random number feed.
		$ychar="0,1,2,3,4,5,6,7,8,9";
		$list=explode(",",$ychar);
		for($i=0;$i<6;$i++){
			$randnum=rand(0,9);
			$authnum.=$list[$randnum]; //生成的验证码
		}
		$result = "";
		/*---组装随机数---*/

		if(!empty($mobile)){
			if($mobile) {
				$_SESSION['phone'] = $mobile;
				$_SESSION['msg_mcode_'.$mobile] = $authnum;

				$mcode = $_SESSION['msg_mcode_'.$mobile];

				/*$acount=0;
				$query="select acount from sms_settings where isvalid=true and customer_id=".$customer_id;
				//echo $query;
				$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
				while ($row = mysql_fetch_object($result)) {
					$acount = $row->acount;
				}
					if( $acount>0){
						$shop_name = '微商城';
						$query = "select name from weixin_commonshops where isvalid=true and customer_id=".$customer_id."";
						$result=_mysql_query($query)or die('Query failed'.mysql_error());
						while($row=mysql_fetch_object($result)){
							$shop_name = $row->name;
						}
						$content="【".$shop_name."】欢迎您注册，您的验证码是：".$mcode.".如非本人操作请联系商家";

						$commUtil=new publicmessage_Utlity;

						if($commUtil->remindMsgNum($customer_id,$mobile,$content)){
							$result="短信发送成功";
						}else{
							$result="短信发送失败";
						}

				} //调试不成功自行查询数据库*/
				$shop_name = '微商城';
				$query = "select name from weixin_commonshops where isvalid=true and customer_id=".$customer_id."";
				$result=_mysql_query($query)or die('Query failed'.mysql_error());
				while($row=mysql_fetch_object($result)){
					$shop_name = $row->name;
				}
				//$content="【".$shop_name."】用户正在修改密码，验证码是：".$mcode.".如非本人操作请联系商家";

				//$commUtil=new publicmessage_Utlity;
                //$commUtil->remindMsgNum($customer_id,$mobile,$content)

                $sms_class = new sms($customer_id);
                $sms_res = $sms_class->send_sms(array('mobile'=>$mobile,'sceneid'=>1,'if_test'=>0,'variable_parm'=>array('shopName'=>array(0,$shop_name),'product'=>array(0,$shop_name),'code'=>array(0,$mcode),'min'=>array(0,'60s'))));

				if($sms_res['code']==20000){
					$result="短信发送成功";
				}else{
					$result="短信发送失败";
				}
			}
		}
		$out = json_encode($result);
		echo $out;
}
/*-------封装发送短信方法------*/


switch($op){
	case "login": //登陆操作
		if(!empty($_POST["username"])){
			$username = $configutil->splash_new($_POST["username"]);
		}
		if(!empty($_POST["password"])){
			$password = $configutil->splash_new($_POST["password"]);
		}
		if(sql_check($username) or sql_check($password)){
			$status=-2;
			$str->status=$status;
			echo json_encode($str);
			return;
		}
		$password_en=md5($password);//MD5加密密码
		$query="select user_id,customer_id,password from system_user_t where isvalid=true and account='$username' and customer_id=".$customer_id." limit 0,1";
		//echo $query;
		$user_id = -1;
		$result=_mysql_query($query) or die ("login query faild" .mysql_error());
		while($row=mysql_fetch_object($result)){
			$user_id=$row->user_id;
			$customer_id=$row->customer_id;
			$passowrd   = $row->password;

		}

		if($user_id == -1)//用户不存在
		{
			$status=-3;
			$str->status=$status;
			echo json_encode($str);
			return;
		}

		//zhou快捷登陆2017-7-5
		$login_type = $configutil->splash_new($_POST["login_type"]);

		$islogin = false;
		if($login_type == 1)//普通登陆
		{
			if($password_en != $passowrd)
			{
				$islogin = false;
			}
			else
			{
				$islogin = true;
			}

		}
		elseif($login_type == 2)//快捷登陆
		{
			$verify_code = $configutil->splash_new($_POST["verify_code"]);
			if($_SESSION['msg_mcode'.$username] != $verify_code)
			{
				$str->status = -5;//验证码错误
				echo json_encode($str);
				return;
			}
			if($_SESSION['msg_time'.$username] < time())
			{
				$str->status = -6;//验证码超时
				echo json_encode($str);
				return;
			}
			$islogin = true;
			unset($_SESSION['msg_mcode'.$username]);
			unset($_SESSION['msg_time'.$username]);
		}
		else
		{
			$str->status = -2;//参数错误
			echo json_encode($str);
			return;
		}

		//***** 修改该登陆session时需同时修改微视p3p登陆 wsy_user/api/controller/weixin.php shop_p3p_login *****
		if($islogin){ //当查询到用户才去查询fromuser


			//app消息互通：登陆成功吧需要退出登陆的用户进行软删除 zhou 2017-7-10
			$up_query = "update h5_loginout set isvalid=0 where user_id=".$user_id." and type=1";
			//echo $up_query;exit;
			_mysql_query($up_query) or die("sql h5_loginout faild:".mysql_error());

			$opid_query="select weixin_fromuser,weixin_headimgurl from weixin_users where isvalid=true and id=".$user_id." limit 0,1";
			//echo $opid_query;exit;
			$opid_result=_mysql_query($opid_query) or die ("opid_query faild" .mysql_error());
			while($row=mysql_fetch_object($opid_result)){
				$weixin_fromuser=$row->weixin_fromuser;
				$weixin_headimgurl=$row->weixin_headimgurl;
			}
			$_SESSION["customer_id"] = $customer_id;		// by yehecong 2017-1-10
			$_SESSION["user_id_".$customer_id]		=$user_id;
			$_SESSION["myfromuser_".$customer_id]	=$weixin_fromuser;
			$_SESSION["fromuser_".$customer_id]		=$weixin_fromuser;
			$_SESSION["is_bind_".$customer_id]		=1;//已经注册
			setcookie("login_headimgurl",$weixin_headimgurl, time()+604800,'/');//设置用户头像COOKIE

            /*微视绑定登陆 s*/
            require_once (ROOT_DIR."/wsy_user/public/weishi_common.php");
            $ws_common = new weishi_common($customer_id);
            $str->p3p_url = $ws_common->shop_p3p_login($user_id) ?: false;
            /*微视绑定登陆 e*/

			//不要在缓存里面保存用户的账号密码，应该交给浏览器去做。 20180228 屏蔽--by lqh
			//setcookie("login_username",$username, time()+604800,'/');//设置用户登录账号
			//setcookie("login_password",$password, time()+604800,'/');//设置用户登录密码
			/*zpq 郑培强 添加 用于 艺人*/
			$_SESSION['yiren_user_id_en']=passport_encrypt($user_id);
			/*zpq 郑培强 添加 用于 艺人*/
			$status=1;//登陆成功状态

			//$str->url="../common_shop/jiushop/index.php?customer_id=".passport_encrypt($customer_id)."";
			// echo '//'.$_SESSION["nurl_".$customer_id].'--';
			// echo '//'.$urltype.'--';
			// exit();
			if(empty($_SESSION["nurl_".$customer_id])){
				if($urltype){
					switch ($urltype){
						//自行添加
						case 20://线下商城
							$str->url="../city_area/shop/index.php?customer_id=".passport_encrypt($customer_id)."";
							break;
                        case 31://电商直播
							$str->url=$url;//."/tp/1/user_id/".$user_id;
							break;
						case 32://艺人项目
							$str->url="../yiren/front/web/index.html?customer_id_en=".passport_encrypt($customer_id)."&user_id_en=".$_SESSION['yiren_user_id_en'].'&web_time='.$_SESSION['yiren_web_time'];
							$str->yiren_web_time=$_SESSION['yiren_web_time'];
							$str->yiren_is_yz=$user_id;
							break;
						case 33://砍价项目
							$status=33;
							$_SESSION['haggling_web_time']=1;
							$str->url="../haggling/front/web/index.html?customer_id_en=".passport_encrypt($customer_id)."&user_id_en=".passport_encrypt($user_id).'&activity_id='.$_SESSION['activity_id'];
							break;
						case 34://众筹项目
							$status=34;
							$_SESSION['sustain_web_time']=1;
							$str->url="../sustain/front/web/index.html?customer_id_en=".passport_encrypt($customer_id)."&user_id_en=".passport_encrypt($user_id).'&activity_id='.$_SESSION['activity_id'];
							break;
						default://线上商城首页
							$str->url="../common_shop/jiushop/index.php?customer_id=".passport_encrypt($customer_id)."";
							break;
					}

				}else{ //没有session 以及type就跳转首页
                        $str->url="../common_shop/jiushop/index.php?customer_id=".passport_encrypt($customer_id)."";
				}
			}else{//优先跳转session
               // $str->url=$_SESSION["nurl_".$customer_id];

               $str->url=$_SESSION["nurl_".$customer_id]."&user_id=".$user_id;

			}
		}

		$str->user_id=$user_id;
		$str->status=$status;

		echo json_encode($str);
		break;

	case "send"://发送验证码
		if(!empty($_POST["phone"])){
			$mobile = $configutil->splash_new($_POST["phone"]);
		}
		$graph_code = "";
		if(!empty($_POST["graph_code"])){
			$graph_code = $configutil->splash_new($_POST["graph_code"]);
		}
		session_start();//开启缓存

        //判断是否开启短信图形验证码验证
        $login_api_class = new login_api_class();
        if ($login_api_class->sms_check($_POST['c_id'])) {
            //开启
            //图形验证码检查
            if(empty($_SESSION['vdcode'])){
                echo json_encode(['errcode'=>50001,'msg'=>'图形验证码已过期'], JSON_UNESCAPED_UNICODE);
                return;
            }
            if(strtolower($graph_code) != strtolower($_SESSION['vdcode'])){
                echo json_encode(['errcode'=>50002,'msg'=>'图形验证码错误'], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        //图形验证码检查结束
        $_SESSION['time'.$mobile] = date("Y-m-d H:i:s");

        srand((double)microtime()*1000000);//create a random number feed.
        $ychar="0,1,2,3,4,5,6,7,8,9";
        $list=explode(",",$ychar);
        for($i=0;$i<6;$i++){
            $randnum=rand(0,9);
            $authnum.=$list[$randnum]; //生成的验证码
        }

        $result = "";


        if(!empty($mobile)){
			if($mobile) {
				$_SESSION['phone'] = $mobile;
				$_SESSION['msg_mcode_'.$mobile] = $authnum;

				$mcode = $_SESSION['msg_mcode_'.$mobile];

				/*$acount=0;
				$query="select acount from sms_settings where isvalid=true and customer_id=".$customer_id;
				//echo $query;
				$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
				while ($row = mysql_fetch_object($result)) {
					$acount = $row->acount;
				}
					if( $acount>0){*/
						$shop_name = '微商城';
						$query = "select name from weixin_commonshops where isvalid=true and customer_id=".$customer_id."";
						$result=_mysql_query($query)or die('Query failed'.mysql_error());
						while($row=mysql_fetch_object($result)){
							$shop_name = $row->name;
						}
						//$content="【".$shop_name."】用户正在修改密码，验证码是：".$mcode.".如非本人操作请联系商家";

						//$commUtil=new publicmessage_Utlity;
                        //$commUtil->remindMsgNum($customer_id,$mobile,$content)

                        $sms_class = new sms($customer_id);
                        $sms_res = $sms_class->send_sms(array('mobile'=>$mobile,'sceneid'=>1,'if_test'=>0,'variable_parm'=>array('shopName'=>array(0,$shop_name),'product'=>array(0,$shop_name),'code'=>array(0,$mcode),'min'=>array(0,'60s'))));
                        $result = [];
						if($sms_res['code']==20000){
							$result['msg']="短信发送成功";
						}else{
							$result['msg']="短信发送失败";
						}

					//} //调试不成功自行查询数据库

			}

		}
		
		echo json_encode($result);
		break;

	case "edit"://修改密码

		if(!empty($_POST["phone"])){ //电话号码
			$mobile = $configutil->splash_new($_POST["phone"]);
		}
		if(!empty($_POST["yzm"])){	//验证码
			$yzm = $configutil->splash_new($_POST["yzm"]);
		}
		/*$graph_code = "";
		if(!empty($_POST["graph_code"])){	//验证码
			$graph_code = $configutil->splash_new($_POST["graph_code"]);
		}*/
		if(!empty($_POST["password"])){	//密码
			$password = $configutil->splash_new($_POST["password"]);
		}

		if((strtotime($_SESSION['time'.$mobile])+180)<time()) {//将获取的缓存时间转换成时间戳加上180秒后与当前时间比较，小于当前时间即为过期

			$_SESSION['time'.$mobile] = '';		//清空
			$arr = array('code' => 10003, 'msg' => '验证码已过期！');
			echo json_encode($arr,JSON_UNESCAPED_UNICODE);
			exit;
		}

		if(!isset($_SESSION['msg_mcode_'.$mobile])){
			$arr = array('code' => 10001, 'msg' => '验证码已过期');
			echo json_encode($arr,JSON_UNESCAPED_UNICODE);
			exit;
		}

		if($_SESSION['msg_mcode_'.$mobile] != $yzm){
			$arr = array('code' => 10004, 'msg' => '验证码错误');
			echo json_encode($arr,JSON_UNESCAPED_UNICODE);
			exit;
		}
		//----当验证码成功
		if($_SESSION['msg_mcode_'.$mobile] == $yzm){
			if(sql_check($username) or sql_check($password)){ //判断非法参数
				$arr = array('code' => 10009, 'msg' => '非法参数');
				echo json_encode($arr,JSON_UNESCAPED_UNICODE);
				exit;
			}
			//图形验证码处理 lml 20180130
			/*if($graph_code == ""){
				$arr = array('code' => 10012, 'msg' => '图形验证码不能为空');
				echo json_encode($arr,JSON_UNESCAPED_UNICODE);
				exit;
			}
			if( strtolower($graph_code) != strtolower($_SESSION['vdcode']) ){
				$arr = array('code' => 10013, 'msg' => '图形验证码错误');
				echo json_encode($arr,JSON_UNESCAPED_UNICODE);
				exit;
			}*/

			$password=md5($password);

			//请求app接口 2017-7-6 zhou
			$data['phone']    	 = $mobile;
			$data['password'] 	 = $password;
			$data['customer_id'] = $customer_id;
			$type 				 = 6;//忘记密码
			$api->h5_api($type,$data);

			$update_password="update system_user_t set password='".$password."' where isvalid=true and account='$mobile' and customer_id=".$customer_id;
			_mysql_query($update_password) or die ("update_password faild " .mysql_error());
			$count = mysql_affected_rows();
			if($count>0){
				$arr = array('code' => 10010, 'msg' => '密码修改成功，请重新登录');
			}else{
				$arr = array('code' => 10011, 'msg' => '密码修改失败');
			}

			echo json_encode($arr,JSON_UNESCAPED_UNICODE);

		}
		break;

		case "edit_paypassword":

			if(!empty($_POST["phone"])){ //电话号码
				$mobile = $configutil->splash_new($_POST["phone"]);
			}

			$query = "SELECT user_id FROM system_user_t WHERE isvalid=true AND customer_id=".$customer_id." AND account=".$mobile." LIMIT 1";
			$result= _mysql_query($query) or die ("query faild 230" .mysql_error());
			while( $row = mysql_fetch_object($result) ){
				$user_id = $row->user_id;
			}

			if(!empty($_POST["yzm"])){	//验证码
				$yzm = $configutil->splash_new($_POST["yzm"]);
			}
			if(!empty($_POST["password"])){	//密码
				$password = $configutil->splash_new($_POST["password"]);
			}

			if((strtotime($_SESSION['time'.$mobile])+180)<time()) {//将获取的缓存时间转换成时间戳加上180秒后与当前时间比较，小于当前时间即为过期

				$_SESSION['time'.$mobile] = '';		//清空
				$arr = array('code' => 10003, 'msg' => '验证码已过期！');
				echo json_encode($arr,JSON_UNESCAPED_UNICODE);
				exit;
			}

			if(!isset($_SESSION['msg_mcode_'.$mobile])){
				$arr = array('code' => 10001, 'msg' => '验证码已过期');
				echo json_encode($arr,JSON_UNESCAPED_UNICODE);
				exit;
			}

			if($_SESSION['msg_mcode_'.$mobile] != $yzm){
				$arr = array('code' => 10004, 'msg' => '验证码错误');
				echo json_encode($arr,JSON_UNESCAPED_UNICODE);
				exit;
			}
			//----当验证码成功
			if($_SESSION['msg_mcode_'.$mobile] == $yzm){
				if(sql_check($username) or sql_check($password)){ //判断非法参数
					$arr = array('code' => 10009, 'msg' => '非法参数');
					echo json_encode($arr,JSON_UNESCAPED_UNICODE);
					exit;
				}

				$password=md5($password);

				$update_password="update user_paypassword set paypassword='".$password."' where isvalid=true and user_id=".$user_id." and customer_id=".$customer_id."";
				_mysql_query($update_password) or die ("update_password faild " .mysql_error());
				$count = mysql_affected_rows();
				//if($count>0){
					$arr = array('code' => 10010, 'msg' => '支付密码修改成功');
				// }else{
				// 	$arr = array('code' => 10011, 'msg' => '支付密码修改失败');
				// }

				echo json_encode($arr,JSON_UNESCAPED_UNICODE);

			}
		break;

		case "send_paypw_msg":

			if(!empty($_POST["phone"])){
				$mobile = $configutil->splash_new($_POST["phone"]);
			}
			session_start();//开启缓存
			$_SESSION['time'.$mobile] = date("Y-m-d H:i:s");

			srand((double)microtime()*1000000);//create a random number feed.
			$ychar="0,1,2,3,4,5,6,7,8,9";
			$list=explode(",",$ychar);
			for($i=0;$i<6;$i++){
				$randnum=rand(0,9);
				$authnum.=$list[$randnum]; //生成的验证码
			}

			$result = "";

			if(!empty($mobile)){
				if($mobile) {
					$_SESSION['phone'] = $mobile;
					$_SESSION['msg_mcode_'.$mobile] = $authnum;

					$mcode = $_SESSION['msg_mcode_'.$mobile];

					/*$acount=0;
					$query="select acount from sms_settings where isvalid=true and customer_id=".$customer_id;
					//echo $query;
					$result = _mysql_query($query) or die('Query failed 313 : ' . mysql_error());
					while ($row = mysql_fetch_object($result)) {
						$acount = $row->acount;
					}
						if( $acount>0){
							$shop_name = '微商城';
							$query = "select name from weixin_commonshops where isvalid=true and customer_id=".$customer_id."";
							$result=_mysql_query($query)or die('Query failed 320 '.mysql_error());
							while($row=mysql_fetch_object($result)){
								$shop_name = $row->name;
							}
							$content="【".$shop_name."】用户正在修改支付密码，验证码是：".$mcode.".如非本人操作请联系商家";

							$commUtil=new publicmessage_Utlity;

							if($commUtil->remindMsgNum($customer_id,$mobile,$content)){
								$result="短信发送成功";
							}else{
								$result="短信发送失败";
							}

					} //调试不成功自行查询数据库*/
					$shop_name = '微商城';
					$query = "select name from weixin_commonshops where isvalid=true and customer_id=".$customer_id."";
					$result=_mysql_query($query)or die('Query failed'.mysql_error());
					while($row=mysql_fetch_object($result)){
						$shop_name = $row->name;
					}
					//$content="【".$shop_name."】用户正在修改密码，验证码是：".$mcode.".如非本人操作请联系商家";

					//$commUtil=new publicmessage_Utlity;
                    //$commUtil->remindMsgNum($customer_id,$mobile,$content)

                    $sms_class = new sms($customer_id);
                    $sms_res = $sms_class->send_sms(array('mobile'=>$mobile,'sceneid'=>1,'if_test'=>0,'variable_parm'=>array('shopName'=>array(0,$shop_name),'product'=>array(0,$shop_name),'code'=>array(0,$mcode),'min'=>array(0,'60s'))));

					if($sms_res['code']==20000){
						$result="短信发送成功";
					}else{
						$result="短信发送失败";
					}

				}

			}

		$out = json_encode($result);
		echo $out;
		break;

		//发送验证码
		case 'register_yzm':
			if(!empty($_POST["phone"])){
				$mobile = $configutil->splash_new($_POST["phone"]);
			}
			send_yzm($customer_id,$mobile);
		break;

		//验证验证码以及注册操作
		case 'register_user':

			$yz_type = 0;//0：短信验证；1：验证码验证
			$query = "SELECT yz_type FROM register_set WHERE isvalid=true AND customer_id=".$customer_id." LIMIT 1";
			$result= _mysql_query($query)or die('Query failed 419 '.mysql_error());
			while( $row = mysql_fetch_object($result)){
				$yz_type = $row->yz_type;
			}

			if(!empty($_POST["phone"])){ //电话号码
				$mobile = $configutil->splash_new($_POST["phone"]);
			}
			if(!empty($_POST["yzm"])){	//验证码
				$yzm = $configutil->splash_new($_POST["yzm"]);
			}
			if(!empty($_POST["password"])){	//密码
				$password = $configutil->splash_new($_POST["password"]);
			}
			if(!empty($_POST["parent_phone"])){
				$parent_phone = $configutil->splash_new($_POST["parent_phone"]);
			}
			if(!empty($_POST["customer_id"])){
				$customer_id = $_POST["customer_id"];
			}

			if($yz_type == 0){

				if((strtotime($_SESSION['time'.$mobile])+180)<time()) {//将获取的缓存时间转换成时间戳加上180秒后与当前时间比较，小于当前时间即为过期

				$_SESSION['time'.$mobile] = '';		//清空
				$arr = array('code' => 10003, 'msg' => '验证码已过期！');
				echo json_encode($arr,JSON_UNESCAPED_UNICODE);
				exit;
				}

				if(!isset($_SESSION['msg_mcode_'.$mobile])){
					$arr = array('code' => 10001, 'msg' => '验证码已过期');
					echo json_encode($arr,JSON_UNESCAPED_UNICODE);
					exit;
				}

				if($_SESSION['msg_mcode_'.$mobile] != $yzm){
					$arr = array('code' => 10004, 'msg' => '验证码错误');
					echo json_encode($arr,JSON_UNESCAPED_UNICODE);
					exit;
				}

				if($_SESSION['msg_mcode_'.$mobile] == $yzm){
					$yzm_state = true;
				}else{
					$yzm_state = false;
				}

			}elseif($yz_type == 1){

				$code = $_SESSION["VerifyCode"];//获取验证码session
				if ( !$yzm ) {
		            $yzm_state = false;
		        }

				if( strtoupper($yzm) !=strtoupper($code) ){
					$yzm_state = false;
				}else{
					$yzm_state = true;
				}



			}

			//----当验证码成功
			if($yzm_state==true){
				if(sql_check($username) or sql_check($password)){ //判断非法参数
					$arr = array('code' => 10009, 'msg' => '非法参数');
					echo json_encode($arr,JSON_UNESCAPED_UNICODE);
					exit;
				}

				$app = new App_Utlity();
				$result = array(
					'errcode'=>10000,
					'yzm'=>'',
					'msg'=>''
				);
				$wechat_id 			= -1; 						//微信号
				$wechat_code 		= ""; 						//微信推广二维
				$pay_password 	  	= -1;	//支付登陆密码
				$user_id 	  		= -1;		//user_id
				$parent_phone 	  	= $_POST['parent_phone'];	//推荐人手机号

				$res = $app->systemUserRegist($customer_id,$mobile,'',$user_id,md5($password),md5($pay_password),$parent_phone,9);

				if($res["result"] ==1)
				{
					$_SESSION['pc_user_name'] = $mobile;
					$arr = array('code' => 20001, 'msg' => '网页注册成功');

					//注册请求app接口 zhou 2017-7-7
					// $data['customer_id']  = $customer_id;
					// $data['user_name']    = $mobile;
					// $data['password']     = md5($password);
					// $data['parent_phone'] = $parent_phone;
					// $data['user_id']      = $res['user_id'];
					// $type                 = 1; //注册
					// $api->h5_api($type,$data);
				}
				else
				{
					$arr = array('code' => 14000, 'msg' => $res["msg"]);
				}

				echo json_encode($arr,JSON_UNESCAPED_UNICODE);
				return;

			}

		break;

}

mysql_close($link);


?>