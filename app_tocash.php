<?php
// require_once($_SERVER['DOCUMENT_ROOT'].'/weixinpl/common/common_from.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/weixinpl/common/common_ext.php');
class app_tocash
{
    /**
     * @param int $customer_id  平台编号
     * @throws Exception customer_id 为非数字时为异常
     */
    public function __construct($customer_id=0){
        $this->customer_id = $customer_id;
        if(i2int($customer_id)==false){
            throw new Exception("请使用正确的customer_id值！");
        }
    }

    /*查询是否 只能在APP提现、登陆过APP才能提现*/
    public function app_cash_switch($user_id){
        $isin_app = 0;              //是否只能在APP提现
        $islogin_app = 0;           //是否登陆过APP才能提现
        $sql = "SELECT islogin_app,isin_app FROM moneybag_rule WHERE isvalid=true AND customer_id=".$this->customer_id." LIMIT 1";
        $res = _mysql_query($sql) or die('Query failed 14: ' . mysql_error());
        while( $row = mysql_fetch_object($res) ){
            $islogin_app        = $row->islogin_app;
            $isin_app           = $row->isin_app;
        }
        
        /*查询是否APP*/
        if ( strpos($_SERVER['HTTP_USER_AGENT'], 'sr_wsy_xy_user') != false ) {
            $is_app = 1;//是否在APP中 1是 0否
        }else{
            $is_app = 0;
        }
        if ($isin_app == 1 and $is_app == 0){
            $in_app = 0;//开启在APP中提现而且不在APP中
        }else{
            $in_app = 1;            
        }

        /*查询绑定的手机号,是否登陆过APP*/
        $phone_id = -1;
        $sys_account = -1;
        $is_login_app = 0;
        $query4="select id,account,is_login_app from system_user_t where isvalid=true and user_id=".$user_id." and customer_id=".$this->customer_id." limit 1";
        $result4 = _mysql_query($query4) or die('Query failed4: ' . mysql_error());
        while ($row4 = mysql_fetch_object($result4)) { 
            $phone_id     = $row4->id;
            $sys_account  = $row4->account;
            $is_login_app = $row4->is_login_app;
        }     
        

        if($islogin_app == 1 and $is_login_app == 0){
            $login_app = 0;//开启登陆过APP才能提现而且未在APP中登陆过
        }else{
            $login_app = 1;
        }

        $result = array(
            'is_app'      => $is_app,
            'in_app'      => $in_app,
            'login_app'   => $login_app,
            'phone_id'    => $phone_id,
            'app_user_id' => $is_login_app
            );

        return $result;
    }

}
?>