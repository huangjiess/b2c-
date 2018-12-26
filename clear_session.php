<?php

$_SESSION["myfromuser_".$customer_id] = "";
$_SESSION["fromuser_".$customer_id] = "";
$_SESSION["user_id_".$customer_id] =  -1;
$_SESSION["customer_id_".$customer_id] =  -1;
$_SESSION["is_bind_".$customer_id] =  0;

echo 'fromuser=='.$_SESSION["fromuser_".$customer_id]."<br/>";
echo 'user_id_=='.$_SESSION["user_id_".$customer_id]."<br/>";
echo 'myfromuser_=='.$_SESSION["myfromuser_".$customer_id]."<br/>";
echo 'is_bind_=='.$_SESSION["is_bind_".$customer_id]."<br/>";
?>