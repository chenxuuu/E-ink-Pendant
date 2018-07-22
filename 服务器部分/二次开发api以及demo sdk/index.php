<?php
$imei = mysql_real_escape_string($_GET["imei"]);
//包含数据库连接文件
include('conn.php');
//检查imei是否绑定
$check_query = mysql_query("select * from user where eink_imei='$imei' limit 1");
if($result = mysql_fetch_array($check_query)){
    if($result["eink_set"] == "pic")
    {
        echo '{"jump": false,"data": "'.$result["eink_pic"].'"}';
    }
    else
    {
        echo '{"jump": true,"data": "'.htmlspecialchars_decode($result["eink_api"]).'"}';
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
    ImageString ( $im, 20, 100, 180, $date_time, $tc );
    ImageString ( $im, 15, 0, 110, 'imei:'.$imei, $tc );

    $battery = ($v - 3400)/800;
    if($battery>1)
    $battery=1;
    elseif($battery<0)
    $battery=0;


    ImageString ( $im, 20, 0, 180, intval($battery*100).'%', $tc );

    // //设定http输出格式
    // header("Content-type: image/png");
    // //将二进制文件流输出到网页，用于测试
    // imagePng($im);
    // exit(0);

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
    echo '{"jump": false,"data": "'.$pic_result.'"}';
}

