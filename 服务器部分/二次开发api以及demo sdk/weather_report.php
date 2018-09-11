<?php

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

error_reporting(0);
$imei = $_GET["imei"];
$lat = $_GET["lat"];
$lng = $_GET["lng"];
$v = $_GET["v"];
$key = $_GET["key"];  //用自己的key
$ttt = $_GET["t"];   //延时再次启动的时间间隔（小时）

$hour = intval(date("H"));//获取当前时间
if($hour+$ttt > 21)
    $ttt = 24 - $hour + 6;
elseif($hour+$ttt < 6)
    $ttt = 6 - $hour;


$im = ImageCreate ( 200, 200 );
$bgc = ImageColorAllocate ( $im, 255, 255, 255 );         //背景颜色
$tc  = ImageColorAllocate ( $im, 0,0,0 );   //字体颜色
ImageFill($im, 0, 0, $bgc);


//https://apis.map.qq.com/ws/geocoder/v1/?location=031.3942245,120.9767247&key=T5FBZ-3NVK4-VPVUQ-D4ZOG-JMAQ6-5HBLC
//https://search.heweather.com/find?location=120.9767247,031.3942245&key=3e91b51b16854a9db5a3c7d8efd2f648
//https://free-api.heweather.com/s6/weather/forecast?location=120.9767247,031.3942245&key=3e91b51b16854a9db5a3c7d8efd2f648

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
//$date_time = $data['HeWeather6'][0]['daily_forecast'][0]['date'];
$day_w = $data['HeWeather6'][0]['daily_forecast'][0]['cond_txt_d'];
$night_w = $data['HeWeather6'][0]['daily_forecast'][0]['cond_txt_n'];
$t_max = $data['HeWeather6'][0]['daily_forecast'][0]['tmp_max'];
$t_min = $data['HeWeather6'][0]['daily_forecast'][0]['tmp_min'];
$wind = $data['HeWeather6'][0]['daily_forecast'][0]['wind_dir'];
$wind_s = $data['HeWeather6'][0]['daily_forecast'][0]['wind_sc'];
$hum = $data['HeWeather6'][0]['daily_forecast'][0]['hum'];


imagettftext($im, 25, 0, 0, 28, $tc, "SIMYOU.TTF", $location);
imagettftext($im, 20, 0, 90, 55, $tc, "SIMYOU.TTF", $t_min.'~'.$t_max.'℃');
imagettftext($im, 18, 0, 0, 80, $tc, "SIMYOU.TTF", "白天：".$day_w);
imagettftext($im, 18, 0, 0, 105, $tc, "SIMYOU.TTF", "夜间：".$night_w);
imagettftext($im, 18, 0, 0, 145, $tc, "SIMYOU.TTF", $wind.$wind_s.'级');
imagettftext($im, 18, 0, 0, 170, $tc, "SIMYOU.TTF", '相对湿度'.$hum.'%');

$battery = ($v - 3400)/700;
if($battery>1)
$battery=1;
elseif($battery<0)
{
    $battery=0;
    $ttt = 0;//电量过低，禁用自动开机，保护电池
}


ImageString ( $im, 20, 0, 180, intval($battery*100).'%', $tc );
ImageString ( $im, 20, 40, 180, date("Y-m-d H:i"), $tc );

// ImageString ( $im, 20, 0, 0, "just is English code", $tc );


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
//echo "<".pack("H*",$pic_result);
echo encode_result($pic_result,$ttt);
?>
