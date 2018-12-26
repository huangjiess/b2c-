<?php
/* 优惠券 */
class Coupon_Utility{

	public function show_coupon($customer_id,$user_id,$type,$coupon_id=0,$pid=0,$class_type=0,$single=0,$is_use_free_coupon=0,$coupon_onoff=0){
		/*
		方法说明:
			自定义菜单栏生成优惠券
		参数说明:
			$customer_id : 商家ID
			$user_id	 : 用户ID
			$type	 	 : 加载类型 1、我的优惠券 2、领券中心 3、使用优惠券 5、活动赠送优惠券
			$coupon_id	 : 领取普通优惠券：优惠券ID  type=3时，表示当前正在使用的优惠券
			$pid	     : 订单使用优惠券：商品ID, 活动赠送优惠券(type=5):订单号
			$class_type	 : 领取优惠券类型 1、普通优惠券 2、首次领取优惠券 3、拼团团长免单券
			$is_use_free_coupon : 是否可以使用团长免单券，1是，0否
			$coupon_onoff : 拼团优惠券开关，1是，0否
		*/
		
		$data = array();//最终返回的数组
		$query="select is_coupon from weixin_commonshops where isvalid=true and customer_id=".$customer_id." limit 0,1";
		$is_coupon=0; //是否开启优惠券
		$result = _mysql_query($query) or die('W3667 Query failed: ' . mysql_error());
		while ($row = mysql_fetch_object($result)) {
			$is_coupon = $row->is_coupon;
			break;
		}
		if($is_coupon>0){
		$query_parameter = "SELECT u.id,u.Money,u.deadline,u.NeedMoney,c.connected_id,u.class_type,u.use_roles,u.is_receive,u.is_used,u.startline,c.getStartTime,c.getEndTime,c.couponNum,c.personNum,c.DaysType,c.Days,c.MoneyType,c.MinMoney,c.MaxMoney,c.CanGetNum,c.get_roles,c.title";//查询参数
		$query = " FROM weixin_commonshop_couponusers u left join weixin_commonshop_coupons c on u.coupon_id=c.id WHERE u.user_id=".$user_id." AND u.customer_id=".$customer_id." AND u.isvalid=true AND u.type=1 ";//查询条件
		$show = 0;
		switch($type){
			case 1:
				$query .= " AND u.is_used=0  AND u.deadline >='".date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")."' AND is_receive=true";
				if($coupon_id>0){
					$query .= " AND c.id=".$coupon_id;
				}
				$query .= ' limit 0,8';
			break;
			case 2:
				//查询用户身份
		        $promoter_id = 0;
		        $promoter_status = 0;
		        $promoter_is_consume = 0;
		        $query = "select id,status,is_consume from promoters where isvalid=true and user_id=".$user_id." and customer_id=".$customer_id."";
		        $result = _mysql_query($query) or die('W10635 Query failed: ' . mysql_error());
		        while ($row = mysql_fetch_object($result)) {
		            $promoter_id         = $row->id;
		            $promoter_status     = $row->status;
		            $promoter_is_consume = $row->is_consume;
		        }

				$query_parameter = "select id,MinMoney,MaxMoney,title,NeedMoney,connected_id,CanGetNum,Days,DaysType,get_roles,use_roles,is_coupon_inentity_use,MoneyType,couponNum,personNum,getStartTime,getEndTime,startline,class_type,is_showcouponlist";
				$query = " from weixin_commonshop_coupons where is_open=1 and isvalid=true and customer_id=".$customer_id." AND getEndTime >='".date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")."'  AND getStartTime <='".date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")."'";
				if($coupon_id>0){
					$query .=" and id=".$coupon_id." limit 1 ";
					/*if( $single > 0 ){
						$query .=" and id=".$coupon_id." limit 1 ";
					}else{

						if($class_type==1){
							$query .=" and id=".$coupon_id." limit 1 UNION ALL ".$query_parameter.$query." and class_type=2";
							$query .=" and id=".$coupon_id." limit 1 UNION ALL ".$query_parameter.$query." and class_type=2";
						}else{//链接过来的首次优惠券，设置的优惠券排在前面，用UNION查询
							$query .=" and id=".$coupon_id." UNION ALL ".$query_parameter.$query." and id!=".$coupon_id." and class_type=2";
						}
					} */


				}else{
					//2017-2-20，邓继勇，屏蔽，没传优惠券id过来的话，应该是显示所有的优惠券，而不是只显示首次类型的优惠券；
					/* $query .= " and class_type=2"; */
					if( $single > 0 ){
						$query .= " and class_type=2";
					}
				}
				$show = 1;
			break;
			case 3:
				$query .= " AND u.is_used=0 AND u.deadline >='".date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")."'  AND u.startline <='".date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")."' and u.is_receive=true";

				if( $is_use_free_coupon ){	//非拼团订单不能选择团长免单券
                    if(!$coupon_onoff){
                        $query .= " AND u.class_type=3";
                    }					
				} else {
					$query .= " AND u.class_type!=3";
				}

				if($pid>0){//从订单页面进来接收到单品id

					$pid_arr = explode(',', $pid);
					$str = '';
					foreach ($pid_arr as $k => $v) {
					    $str .= " c.connected_id LIKE '%".$v."%' or ";
					}
					$str = substr($str,0,strlen($str)-3);
					$query .= " and (c.user_scene=0 or (c.user_scene=1 and ".$str."))";
					
					//查询当前已点击的优惠券
					$sql = "select couponusers_id from weixin_commonshop_coupon_using where isvalid=true and customer_id=".$customer_id." and user_id=".$user_id." and couponusers_id !=".$coupon_id;
					$result = _mysql_query($sql) or die('selectUsing Query failed: ' . mysql_error());
					$usingStr = '';
					while ($row = mysql_fetch_object($result)) {
						$usingStr .= $row->couponusers_id.",";
					}
					$usingStr = substr($usingStr,0,strlen($usingStr)-1);
					if(!empty($usingStr)){
						$query .= " and u.id not in(".$usingStr.")";
					}
				}

				$query .= ' limit 0,8';
			break;
			case 5:
                //活动赠送优惠券
                $sql = "SELECT id from weixin_commonshop_couponusers where batchcode='{$pid}' and isvalid=true ";
                $result_sql = _mysql_query($sql) or die('W36441 Query failed: ' . mysql_error());
                while ( $row = mysql_fetch_row($result_sql) ) {
                	$user_coupon_ids[] = $row[0];
                }
                $user_coupon_ids = implode(',', $user_coupon_ids);
                if( $user_coupon_ids ){
                	$query = " FROM weixin_commonshop_couponusers u left join weixin_commonshop_coupons c on u.coupon_id=c.id WHERE u.id in({$user_coupon_ids}) and u.customer_id=".$customer_id." AND u.isvalid=true";
                }else{
                	return false;
                }
            break;
			default:
				#code...
			break;
		}

		$keyid 		  = -1;	// 优惠劵id
		$Money 		  = 0;	//优惠劵金额
		$NeedMoney 	  = 0;	//使用优惠劵的限制金额
		$startline	  = "1970-01-01";//开始日期
		$deadline	  = "1970-01-01";//截止日期
		$getStartTime = "1970-01-01";//领取日期
		$getEndTime	  = "1970-01-01";//领取截止日期
		$type_str     = "仅限在线支付";//类型
		$connected_id = 0;//店铺优惠券ID
		$overload     = 0;	//是否继续加载 1:不加载 0:继续加载
		$use_roles = 0;	//优惠卷是否要推广员以上身份使用：0否1是
		$get_roles = 0;	//优惠卷是否要推广员以上身份领取：0否1是
		$is_receive   = 0;	//领取状态
		$is_used      = 0;	//使用状态
		$couponNum    = 0;	//优惠券数量
		$personNum    = 0;	//每人可领数量
		$DaysType     = 0;	//截止日期类型
		$Days         = 0;	//截止日期
		$MoneyType    = 0;	//金额类型
		$MinMoney     = 0;	//优惠券最小金额
		$MaxMoney     = 0;	//优惠券最大金额
		$CanGetNum    = 0;	//每人每天可领数量
		$c_class_type = 0;	//优惠券类型
		$query = $query_parameter .$query;
		// echo $query;
		/*判断是否为推广员开始*/
		$promoter_status = 0;
        $promoter_is_consume = 0;
		$sql_pro = "select status,is_consume from promoters where isvalid=true and customer_id=".$customer_id." and user_id=".$user_id;
		$result_pro = _mysql_query($sql_pro) or die('W3644 Query failed: ' . mysql_error());
		while ($row_pro = mysql_fetch_object($result_pro)) {
			$promoter_status = $row_pro->status;
			$promoter_is_consume = $row_pro->is_consume;
		}

		$result = _mysql_query($query) or die('W333 Query failed: ' . mysql_error());
		while ($row = mysql_fetch_object($result)) {
				$keyid 	      =  $row->id;
				$deadline     =  $row->deadline;
				$startline    =  $row->startline;
				$getStartTime =  $row->getStartTime;
				$getEndTime   =  $row->getEndTime;
				$couponNum    =  $row->couponNum;
				$personNum    =  $row->personNum;
				$DaysType     =  $row->DaysType;
				$Days         =  $row->Days;
				$MoneyType    =  $row->MoneyType;
				$MinMoney     =  $row->MinMoney;
				$MaxMoney     =  $row->MaxMoney;
				$CanGetNum    =  $row->CanGetNum;
				$c_class_type =  $row->class_type;
				$title        =  $row->title;
				if($startline<1){
					$startline = date('Y-m-d H:i:s',time());
				}
				$is_used	  =  $row->is_used;
				$usestr       =  $is_used==1?'has_used':'has_lost';
				$connected_id =  $row->connected_id;
				$p_content    = '仅限购买商城内的商品使用';
				$c_content    = '平台专用券';
				$c_top        = 'top_r';
				$word_color   = 'red';
				$arrow_color  = 'arrow_red';
				$is_receive   =  $row->is_receive;
				if($connected_id>0){//查询单品名字
					$p_name = '';
					$query_p = 'select name from weixin_commonshop_products where isvalid=true and id in ('.$connected_id.')';
					$result_p = _mysql_query($query_p) or die('W221 Query failed: ' . mysql_error());
					while ($row_p = mysql_fetch_object($result_p)) {
						$p_name .= $row_p->name . ',';
					}
					$p_name = substr($p_name,0,strlen($p_name)-1);
					$p_content = $p_name.'专用';
					$c_content = '商品专用券';
					$c_top     = 'top_y';
					$word_color = 'yellow';
					$arrow_color = 'arrow_yellow';
				}
				$img = '';
				if($startline<1){
					$startline = date('Y-m-d H:i:s',time());
				}
				$Money 	      = round($row->Money,2);
				$NeedMoney    = round($row->NeedMoney,2);
				$overload     = 1 ;
				$get_roles 	  = $row->get_roles;
				$use_roles    = $row->use_roles;
				$is_showcouponlist = $row->is_showcouponlist;

				if($show == 1) {
					if($is_showcouponlist != 1) {
						continue;
					}
				}

				switch($promoter_status){
			        case 1:
		                $promoter_status = 2;
		                break;
		            default:
		                $promoter_status = 0;
		                break;
			    }

		        switch($promoter_is_consume){
		            case 0:
		                $promoter_is_consume = $promoter_status;
		                break;
		            case 1:
		                $promoter_is_consume = 3;
		                break;
		            case 2:
		                $promoter_is_consume = 4;
		                break;
		            case 3:
		                $promoter_is_consume = 5;
		                break;
		            case 4:
		                $promoter_is_consume = 6;
		                break;
		        }

		        // 判断是否符合身份
                include_once(LocalBaseURL.'common/utility_shop.php');
                $utlity = new shopMessage_Utlity();
                $use_roles = explode('_', $use_roles);
                $user_roles = $utlity->check_user_roles($use_roles,$customer_id,$user_id);
                if( $user_roles['code'] == 0 ){
                	$is_inentity = 1;
                }
		        

				//查找个人用户当天所领取优惠券数量
				$query_count = "SELECT COUNT(1) AS C_Count FROM weixin_commonshop_couponusers WHERE user_id=".$user_id." AND customer_id=".$customer_id." AND isvalid=true AND coupon_id=".$keyid." AND createtime LIKE '".date("Y")."-".date("m")."-".date("d")."%'";
				$C_Count = 0;
				$result_count = _mysql_query($query_count) or die('w9787 Query failed: ' . mysql_error());
				while ($row_count = mysql_fetch_object($result_count)) {
					$C_Count=  $row_count->C_Count;
				}

				//查找优惠券已发放数量
				$query_count = "SELECT COUNT(1) AS H_Count FROM weixin_commonshop_couponusers WHERE  customer_id=".$customer_id." AND isvalid=true AND coupon_id=".$keyid." ";
				$H_Count = 0;
				$result_count = _mysql_query($query_count) or die('w97871 Query failed: ' . mysql_error());
				while ($row_count = mysql_fetch_object($result_count)) {
					$H_Count=  $row_count->H_Count;
				}
				//发放给主播数量
				$query_anchor = "SELECT SUM(surplus) AS sum_surplus from mb_account_coupon where isvalid=true and c_id=".$keyid;
				$sum_surplus = 0;
				$result_count = _mysql_query($query_anchor) or die('w97871 Query failed: ' . mysql_error());
				while ($row_count = mysql_fetch_object($result_count)) {
					$sum_surplus =  $row_count->sum_surplus;
				}
				$H_Count = $H_Count + $sum_surplus;

				//查找用户共拿此优惠券数量
				$query_count = "SELECT COUNT(1) AS U_Count FROM weixin_commonshop_couponusers WHERE user_id=".$user_id." AND customer_id=".$customer_id." AND isvalid=true AND coupon_id=".$keyid." ";
				$U_Count = 0;
				$result_count = _mysql_query($query_count) or die('w97872 Query failed: ' . mysql_error());
				while ($row_count = mysql_fetch_object($result_count)) {
					$U_Count=  $row_count->U_Count;
				}

				if($type==2){			//发放优惠券，自定义菜单领取优惠券，图文领取优惠劵
					$getStart_day = strtotime($getStartTime);   //领取日期
					$getEnd_day = strtotime($getEndTime);
					$now_day = time();							//当日时间戳
					$deadline = "1970-01-01 00:00";
					if($DaysType==1){
						$deadline = $Days;
					}else{
						$deadline = date('Y-m-d H:i:s',strtotime("+".$Days." day"));
					}
					if($startline<1){
						$startline = date('Y-m-d H:i:s',time());
					}
					$code = "d".$user_id.strtotime(date("Y-m-d H:i:s"));
					if($MoneyType==0){
						$CouponMoney = $MaxMoney;//固定金额
					}else{
						$CouponMoney = $MinMoney.'~'.$MaxMoney;//获取随机金额
					}

					$canget = 0;		//可领取数量
					if($couponNum == -1 || $couponNum>$H_Count){					//优惠券无上限或总数未达上限
						$all_num = $couponNum>0?$couponNum-$H_Count:$couponNum;				//总数剩余数量
						if($personNum==-1 || $personNum>$U_Count){					//每人可领取数量无上限或总数未达上限
							$person_num = $personNum>0?$personNum-$U_Count:$personNum;//每人可领剩余数量
							if($CanGetNum==-1 || $CanGetNum>$C_Count){				//每人每天可领取数量无上限或总数未达上限
								$day_num = $CanGetNum>0?$CanGetNum-$C_Count:$CanGetNum;//每人每天可领取剩余数量
							/*满足领取条件 start*/
								if($all_num>0){
									if($person_num>0){
										if($day_num>0){
											if($person_num>$all_num){
												if($all_num>$day_num){
													$canget = $day_num;		//1、总量、每人量、每人每天量都大于0，每人量>总量>每人每天量
												}else{
													$canget = $all_num;		//2、总量、每人量、每人每天量都大于0，每人量>每人每天量>总量
												}

											}else{
												if($person_num>$day_num){
													$canget = $day_num;		//3、总量、每人量、每人每天量都大于0，总量>每人量>每人每天量
												}else{
													$canget = $person_num;	//4、总量、每人量、每人每天量都大于0，总量>每人每天量>每人量
												}
											}
										}else{
											if($person_num>$all_num){
												$canget = $all_num;			//5、每人每天量不限，总量、每人量都大于0，每人每天量>每人量>总量
											}else{
												$canget = $person_num;		//6、每人每天量不限，总量、每人量都大于0，每人每天量>总量>每人量
											}
										}
									}else{
										if($day_num>0){
											if($all_num>$day_num){
												$canget = $day_num;			//7、每人量不限，总量、每人每天量都大于0，每人量>总量>每人每天量
											}else{
												$canget = $all_num;			//8、每人量不限，总量、每人每天量都大于0，每人量>每人每天量>总量
											}
										}else{
											$canget = $all_num;				//9、每人量、每人每天量都不限，总量大于0，每人量=每人每天量>总量
										}
									}
								}else{
									if($person_num>0){
										if($day_num>0){
											if($person_num>$day_num){
												$canget = $day_num;			//10、总量不限，每人量、每人每天量都大于0，总量>每人量>每人每天量
											}else{
												$canget = $person_num;		//11、总量不限，每人量、每人每天量都大于0，总量>每人每天量>每人量
											}
										}else{
											$canget = $person_num;			//12、总量、每人每天量都不限，每人量大于0，总量=每人每天量>每人量
										}
									}else{
										if($day_num>0){
											$canget = $day_num;				//13、总量、每人量都不限，每人每天量大于0，总量=每人量>每人每天量
										}else{
											$canget = 1;					//14、总量、每人量、每人每天量都不限，，总量=每人量=每人每天量
										}
									}
								}
							/*
								$day_num = $CanGetNum>0?$CanGetNum-$C_Count:$CanGetNum;//每人每天可领取剩余数量
								if($day_num<0 && $person_num<0){					//每人可领取数量和每人每天可领取数量不限
									$canget = $all_num;
								}else{
									if($day_num<0){									//每人每天可领取数量不限
										$canget = $person_num;
									}else{
										if($person_num<0){							//每人可领取数量不限
											$canget = $day_num;
										}else{
											$canget = $day_num<$person_num?$day_num:$person_num;
											$canget = $canget<$all_num?$canget:$all_num;
										}
									}
								}
								*/
							/*满足领取条件 end*/
							}
						}
					}
					$array_tmp = array();
					if($class_type==1){			//普通优惠券
						if($deadline<date("Y")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")){
							$c_top        = 'top_g';
							$img = 'images/coupom_img/has_lost.png';
						}elseif($is_receive==1){
							$c_top        = 'top_g';
							$img = 'images/coupom_img/has_got.png';
						}
						//for($n=0;$n<$canget;$n++){
							$tmp = array(
								"keyid"=>$keyid,
								"deadline"=>$deadline,
								"startline"=>$startline,
								"Money"=>$CouponMoney,
								"NeedMoney"=>$NeedMoney,
								"p_content"=>$p_content,
								"c_content"=> $c_content,
								"c_top"=> $c_top,
								"word_color"=> $word_color,
								"MoneyType"=> $MoneyType,
								"arrow_color"=> $arrow_color,
								"img"=> $img,
								"c_class_type"=> $c_class_type,
                                "title"       =>$title,
								);
							array_push($array_tmp,$tmp);
						//}
					}else{					//首次赠送优惠券
						//for($i=0;$i<$canget;$i++){
							$tmp = array(
								"keyid"=>$keyid,
								"deadline"=>$deadline,
								"MoneyType"=>$MoneyType,
								"startline"=>$startline,
                                "getstartline"=>date("Y-m-d H:i:s",$getStart_day),
                                "getendline"=>date("Y-m-d H:i:s",$getEnd_day),
								"Money"=>$CouponMoney,
								"NeedMoney"=>$NeedMoney,
								"p_content"=>$p_content,
								"c_content"=> $c_content,
								"c_top"=> $c_top,
								"word_color"=> $word_color,
								"arrow_color"=> $arrow_color,
								"c_class_type"=> $c_class_type,
                                "title"       =>$title,
							);
							array_push($array_tmp,$tmp);
						//}
					}

			        $code = 0;
			        $get_roles = explode('_',$get_roles);
			        // 判断是否符合身份
	                include_once(LocalBaseURL.'common/utility_shop.php');
	                $utlity = new shopMessage_Utlity();

	                $user_roles = $utlity->check_user_roles($get_roles,$customer_id,$user_id);
	                
					if($user_roles['code'] == 0){
						if($getStart_day<=$now_day && $getEnd_day>=$now_day){										//在领取时间之内
							if($couponNum>$H_Count || $couponNum==-1){				//领取数量大于所有用户已领取的数量，-1为不限
								if($personNum>$U_Count || $personNum==-1){			//每人可领取总数大于该用户领取的总数，-1为不限
									if($CanGetNum>$C_Count || $CanGetNum==-1){	//成功：每人每天可领取总数大于该用户当日领取的总数，-1为不限
										$data_tmp = array("code"=>1001,'data'=>$array_tmp,'msg'=>'此优惠券可领取');
									}else{							//错误：已经达到每人可领取总数，无法再领取
										$data_tmp = array("code"=>4006,'data'=>$array_tmp,'msg'=>'已经达到每人可领取总数，无法再领取');
									}
								}else{								//错误：已经达到每人可领取总数
									$data_tmp = array("code"=>4005,'data'=>$array_tmp,'msg'=>'每人可领取总数大于该用户领取的总数');
								}
							}else{									//错误：已经达到领取数量
								$data_tmp = array("code"=>4001,'data'=>$array_tmp,'msg'=>'已达到优惠券总领取数量');
							}
						}else{										//在领取时间之外
							if($getStart_day>$now_day){									//错误：未到领取时间
								$data_tmp = array("code"=>4002,'data'=>$array_tmp,'msg'=>'未到领取时间');
							}else{									//错误：已经过期
								$data_tmp = array("code"=>4003,'data'=>$array_tmp,'msg'=>'已过领取时间');
							}
						}

					}else{											//错误：未达到领取身份
						$data_tmp = array("code"=>4004,'data'=>$array_tmp,'msg'=>'未达到领取身份');
					}


					if($class_type==1){		//普通优惠券直接输出
						array_push($data,$data_tmp);
					}else{					//首次优惠券继续循环
						array_push($data,$data_tmp);
					}
				}else{		//我的优惠券，订单使用优惠券查询数据
					$array_tmp = array(
							"keyid"=>$keyid,
							"deadline"=>$deadline,
							"startline"=>$startline,
							"Money"=>$Money,
							"NeedMoney"=>$NeedMoney,
							"is_inentity"=>$is_inentity,
							"connected_id"=>$connected_id,
							"p_content"=>$p_content,
							"c_content"=> $c_content,
							"usestr"=> $usestr,
							"c_top"=> $c_top,
							"word_color"=> $word_color,
							"arrow_color"=> $arrow_color,
							"is_receive"=> $is_receive,
							"img"=> $img,
							"c_class_type"=> $c_class_type,
							"type_str"=> $type_str,
                            "title"   => $title,
						);
					array_push($data,$array_tmp);
				}
			}
		}
		return 	$data;
	}
}
 ?>