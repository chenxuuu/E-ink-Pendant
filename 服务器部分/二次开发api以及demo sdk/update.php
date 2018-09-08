<?php
$vnew = "1.0.1";
$download_url = "http://open.papapoi.com/E-INK_1.0.1_Luat_V0028_8955_SSL.bin";
$v = $_GET["v"];
$imei = $_GET["imei"];
if($v != $vnew)
{
echo $download_url;
include('conn.php');
$d = "update:".$v."->".$vnew;
$sql = "INSERT INTO e_ink_log(time,imei,type,data)VALUES('$timestring','$imei','update','$d')";
mysql_query($sql,$conn);
}
?>