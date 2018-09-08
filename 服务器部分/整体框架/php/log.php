<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>电子墨水屏挂饰日志页</title>
  </head>
  <body>
  <?php
session_start();
if(isset($_SESSION['userid'])){
    include('../conn.php');
    $userid = $_SESSION['userid'];
    $username = $_SESSION['username'];
    $user_query = mysql_query("select * from user where uid=$userid limit 1");
    $row = mysql_fetch_array($user_query);
    $user_type = $row['usr_type']; //建表的时候打错了，而且懒得改了。。。
}else
{
    echo <<<html
<script>alert("请先登录！");</script>
<a href="https://www.chenxublog.com/kxct/">点我去登陆</a>
html;
  exit(0);
}

?>

所有模块的历史刷新记录：<br>
<?php
if($user_type != "owner")
{
	echo "你没权限看这个数据";
	exit(0);
}
$q = "SELECT * FROM e_ink_log ORDER BY uid DESC"; //SQL 查询语句
$result = mysql_query($q); // 获取数据集
$temp_count=0;
while($row = mysql_fetch_array($result))
{
	if($temp_count>2000){break;}else{$temp_count++;}
	echo "时间：".$row["time"].",imei：".$row["imei"].",返回类型：".$row["type"].",返回数据：".$row["data"]."<br/>";
}
?>

  </body>
</html>
