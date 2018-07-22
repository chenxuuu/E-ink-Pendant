<?php
$imei = $_GET["imei"];
$lat = $_GET["lat"];
$lng = $_GET["lng"];
$v = $_GET["v"];

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
//$result = '{"HeWeather6": [{"basic": {"cid": "CN101190404","location": "齐齐哈尔","parent_city": "苏州","admin_area": "江苏","cnty": "中国","lat": "31.38192558","lon": "120.95813751","tz": "+8.00"},"update": {"loc": "2018-07-22 10:48","utc": "2018-07-22 02:48"},"status": "ok","daily_forecast": [{"cond_code_d": "307","cond_code_n": "302","cond_txt_d": "大雨","cond_txt_n": "雷阵雨","date": "2018-07-22","hum": "82","mr": "14:21","ms": "00:44","pcpn": "4.0","pop": "74","pres": "996","sr": "05:06","ss": "18:58","tmp_max": "30","tmp_min": "27","uv_index": "4","vis": "14","wind_deg": "10","wind_dir": "北风","wind_sc": "7-8","wind_spd": "65"},{"cond_code_d": "302","cond_code_n": "101","cond_txt_d": "雷阵雨","cond_txt_n": "多云","date": "2018-07-23","hum": "79","mr": "15:17","ms": "01:23","pcpn": "5.0","pop": "80","pres": "1000","sr": "05:07","ss": "18:57","tmp_max": "34","tmp_min": "27","uv_index": "6","vis": "18","wind_deg": "172","wind_dir": "南风","wind_sc": "4-5","wind_spd": "27"},{"cond_code_d": "101","cond_code_n": "101","cond_txt_d": "多云","cond_txt_n": "多云","date": "2018-07-24","hum": "76","mr": "16:10","ms": "02:03","pcpn": "1.0","pop": "55","pres": "1003","sr": "05:08","ss": "18:56","tmp_max": "35","tmp_min": "28","uv_index": "5","vis": "18","wind_deg": "135","wind_dir": "东南风","wind_sc": "4-5","wind_spd": "28"}]}]}';
$data=json_decode($result, true);


$location = $data['HeWeather6']['0']['basic']['location'];
$date_time = $data['HeWeather6'][0]['daily_forecast'][0]['date'];
$day_w = $data['HeWeather6'][0]['daily_forecast'][0]['cond_txt_d'];
$night_w = $data['HeWeather6'][0]['daily_forecast'][0]['cond_txt_n'];
$t_max = $data['HeWeather6'][0]['daily_forecast'][0]['tmp_max'];
$t_min = $data['HeWeather6'][0]['daily_forecast'][0]['tmp_min'];
$wind = $data['HeWeather6'][0]['daily_forecast'][0]['wind_dir'];
$wind_s = $data['HeWeather6'][0]['daily_forecast'][0]['wind_sc'];
$hum = $data['HeWeather6'][0]['daily_forecast'][0]['hum'];


imagettftext($im, 25, 0, 0, 28, $tc, "SIMYOU.TTF", $location);
imagettftext($im, 20, 0, 110, 55, $tc, "SIMYOU.TTF", $t_min.'-'.$t_max.'℃');
imagettftext($im, 18, 0, 0, 80, $tc, "SIMYOU.TTF", "白天：".$day_w);
imagettftext($im, 18, 0, 0, 105, $tc, "SIMYOU.TTF", "夜间：".$night_w);
imagettftext($im, 18, 0, 0, 145, $tc, "SIMYOU.TTF", $wind.$wind_s.'级');
imagettftext($im, 18, 0, 0, 170, $tc, "SIMYOU.TTF", '相对湿度'.$hum.'%');

ImageString ( $im, 20, 100, 180, $date_time, $tc );

// ImageString ( $im, 20, 0, 0, "just is English code", $tc );






// //设定http输出格式
// header("Content-type: image/png");
// //将二进制文件流输出到网页，用于测试
// imagePng($im);

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
echo '{"jump": false,"data": "'.$pic_result.'"}';
?>