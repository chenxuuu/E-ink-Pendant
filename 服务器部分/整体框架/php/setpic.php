<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
</head>
<body>
<?php
session_start();
if(isset($_SESSION['userid'])){
    include('../conn.php');
    $userid = $_SESSION['userid'];
    $username = $_SESSION['username'];
}else
{
    exit('你登陆了吗？');
}

$pic = mysql_real_escape_string(htmlspecialchars($_POST['pic']));
mysql_query("update user set eink_pic='$pic' ,eink_set='pic' where uid=$userid");
if(mysql_affected_rows()>0)
    echo '<script>alert("pic修改成功！");window.location.href = "index.php";</script><br/>';
else
    echo '服务器返回值异常，pic修改失败<br/>';
echo '点击此处 <a href="javascript:history.back(-1);">返回</a><br />';
echo $pic;
?>
</body>
</html>
