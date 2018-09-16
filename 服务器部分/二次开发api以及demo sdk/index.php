<?php
error_reporting(0);

//适当压缩，节省流量
//编码算法：遇到n个f，写作“fn”，n取值：1-f
//遇到n个0，写作“0n”，n取值：1-f
//输入值：
//$trans_data原16进制字符串
//$delay_time需要延时的时间,数值型
//返回值：
//1字节"z" + 1字节延时时间 + 编码过的16进制转ascii字符串结果
//若编码过比没编码还大，就返回编码前的结果：
//1字节"<" + 1字节延时时间 + 原16进制转ascii字符串结果
function encode_result($trans_data,$delay_time)
{
    $trans_data = strtolower($trans_data);
    $last_str = "";
    $zip_result = "00";
    for ($x=0;$x<strlen($trans_data);$x++)
    {
        if($trans_data[$x] == "f")
        {
            if($zip_result[strlen($zip_result)-2] != "f" or $last_str != "f")
            {
                $zip_result .= $trans_data[$x]."1";
            }
            elseif(hexdec($zip_result[strlen($zip_result)-1]) < 15)
            {
                $zip_result[strlen($zip_result)-1] = dechex(hexdec($zip_result[strlen($zip_result)-1])+1);
            }
            else
            {
                $zip_result .= $trans_data[$x]."1";
            }
        }
        elseif($trans_data[$x] == "0")
        {
            if($zip_result[strlen($zip_result)-2] != "0" or $last_str != "0")
            {
                $zip_result .= $trans_data[$x]."1";
            }
            elseif(hexdec($zip_result[strlen($zip_result)-1]) < 15)
            {
                $zip_result[strlen($zip_result)-1] = dechex(hexdec($zip_result[strlen($zip_result)-1])+1);
            }
            else
            {
                $zip_result .= $trans_data[$x]."1";
            }
        }
        else
        {
            $zip_result .= $trans_data[$x];
        }
        $last_str = $trans_data[$x];
    }
    $zip_result = substr($zip_result,2);

    if(strlen($zip_result) < strlen($trans_data))
        return "z".pack("H*",dechex($delay_time/256).dechex($delay_time%256).$zip_result);
    else
        return "<".pack("H*",dechex($delay_time/256).dechex($delay_time%256).$trans_data);
}


$timestring = date("Y-m-d H:i:s");
$imei = mysql_real_escape_string($_GET["imei"]);
$ver = $_GET["ver"];
$ver_now = "1.0.2";
$download_url = "http://open.papapoi.com/E-INK_1.0.1_Luat_V0028_8955_SSL.bin";
//包含数据库连接文件
include('conn.php');

if($ver != $ver_now)    //版本不一致，返回升级包地址
{
echo "u".$download_url;
$d = "update:".$ver."->".$ver_now;
$sql = "INSERT INTO e_ink_log(time,imei,type,data)VALUES('$timestring','$imei','update','$d')";
mysql_query($sql,$conn);
exit(0);
}



//检查imei是否绑定
$check_query = mysql_query("select * from user where eink_imei='$imei' limit 1");
if($result = mysql_fetch_array($check_query)){
    if($result["eink_set"] == "pic")
    {
        //echo '{"jump": false,"data": "'.$result["eink_pic"].'"}';
        //echo "<".pack("H*","00"."00".$result["eink_pic"]);
        echo encode_result($result["eink_pic"],0);
        $d = $_GET["lat"].",".$_GET["lng"].",".$_GET["v"].",picture data";
        $sql = "INSERT INTO e_ink_log(time,imei,type,data)VALUES('$timestring','$imei','pic','$d')";
        mysql_query($sql,$conn);
    }
    else
    {
        if($result["eink_api"] != "")
        {
            //echo '{"jump": true,"data": "'.htmlspecialchars_decode($result["eink_api"]).'"}';
            $d = htmlspecialchars_decode($result["eink_api"]);
            echo ">".$d;
            $d = $_GET["lat"].",".$_GET["lng"].",".$_GET["v"].",".$d;
            $sql = "INSERT INTO e_ink_log(time,imei,type,data)VALUES('$timestring','$imei','api','$d')";
            mysql_query($sql,$conn);
        }
        else
        {
            $d = "https://qq.papapoi.com/e-ink/weather_report.php?t=2&";
            echo ">".$d;
            $d = $_GET["lat"].",".$_GET["lng"].",".$_GET["v"].",".$d;
            $sql = "INSERT INTO e_ink_log(time,imei,type,data)VALUES('$timestring','$imei','api','$d')";
            mysql_query($sql,$conn);
        }
    }
}
else
{
    //没有被绑定
    $lat = $_GET["lat"];
    $lng = $_GET["lng"];
    $v = $_GET["v"];

    $im = ImageCreate ( 200, 200 );
    $bgc = ImageColorAllocate ( $im, 255, 255, 255 );         //背景颜色
    $tc  = ImageColorAllocate ( $im, 0,0,0 );   //字体颜色
    ImageFill($im, 0, 0, $bgc);

    $opts = array(
        'http'=>array(
        'method'=>"GET",
        'timeout'=>2,
        )
    );
    $context = stream_context_create($opts);
    $result = file_get_contents("https://free-api.heweather.com/s6/weather/forecast?location=".$lng.",".$lat."&key=3e91b51b16854a9db5a3c7d8efd2f648", false, $context);
    $data=json_decode($result, true);


    $location = $data['HeWeather6']['0']['basic']['location'];
    $date_time = $data['HeWeather6'][0]['daily_forecast'][0]['date'];
    $day_w = $data['HeWeather6'][0]['daily_forecast'][0]['cond_txt_d'];
    $night_w = $data['HeWeather6'][0]['daily_forecast'][0]['cond_txt_n'];
    $t_max = $data['HeWeather6'][0]['daily_forecast'][0]['tmp_max'];
    $t_min = $data['HeWeather6'][0]['daily_forecast'][0]['tmp_min'];
    $wind = $data['HeWeather6'][0]['daily_forecast'][0]['wind_dir'];
    $wind_s = $data['HeWeather6'][0]['daily_forecast'][0]['wind_sc'];


    imagettftext($im, 25, 0, 0, 28, $tc, "SIMYOU.TTF", $location);
    imagettftext($im, 20, 0, 90, 55, $tc, "SIMYOU.TTF", $t_min.'~'.$t_max.'℃');
    imagettftext($im, 18, 0, 0, 80, $tc, "SIMYOU.TTF", "白天：".$day_w);
    imagettftext($im, 18, 0, 0, 105, $tc, "SIMYOU.TTF", "夜间：".$night_w);
    imagettftext($im, 18, 0, 0, 145, $tc, "SIMYOU.TTF", $wind.$wind_s.'级');
    imagettftext($im, 18, 0, 0, 170, $tc, "SIMYOU.TTF", '设备未绑定账号');
    //ImageString ( $im, 20, 100, 180, $date_time, $tc );
    ImageString ( $im, 15, 0, 110, 'imei:'.$imei, $tc );

    $battery = ($v - 3400)/700;
    if($battery>1)
    $battery=1;
    elseif($battery<0)
    $battery=0;


    ImageString ( $im, 20, 0, 180, intval($battery*100).'%', $tc );
    ImageString ( $im, 20, 40, 180, date("Y-m-d H:i"), $tc );

    if(!empty($_GET['debug']))
    {
        //设定http输出格式
        header("Content-type: image/png");
        //将二进制文件流输出到网页，用于测试
        imagePng($im);
        exit(0);
    }

    $pic_result = "";//存储结果
    $bit_temp = 0;  //临时存储用
    for ($x=0;$x<imagesx($im);$x++) //咋转换的我忘了，反正这么搞就能出来正确结果
    {
        for ($y=0;$y<imagesy($im);$y+=4)
        {
            for ($j=$y;$j<$y+4;$j++)
            {
                $rgb = imagecolorat($im,$j,$x);
                if($rgb==0)
                    $bit_temp = ($bit_temp << 1) + 1;
                else
                    $bit_temp = ($bit_temp << 1);
            }
            $pic_result = $pic_result . dechex($bit_temp);
            $bit_temp = 0;
        }
    }
    imagedestroy($im);
    //输出结果，格式为json
    //jump如果为true，data存储的就是api网址，模块会去重新向新网址获取数据
    //jump如果为false，data就为屏幕数据内容，内容为从左到右，从上到下，转成16进制字符串
    //如用其他语言，请自行测试
    /*
    如：
    黑白白白黑黑白白 -> 01110011 -> "73"
    从上到下，从左到右
    */
    //echo '{"jump": false,"data": "'.$pic_result.'"}';
    //echo "<".pack("H*","00".$pic_result);
    echo encode_result($pic_result,0);
    $d = $_GET["lat"].",".$_GET["lng"].",".$_GET["v"].",picture data";
    $sql = "INSERT INTO e_ink_log(time,imei,type,data)VALUES('$timestring','$imei','pic','$d')";
    mysql_query($sql,$conn);
}

