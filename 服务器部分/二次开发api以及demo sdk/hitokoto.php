<?php
mb_internal_encoding("UTF-8"); // 设置编码

function autowrap($fontsize, $angle, $fontface, $string, $width) {
// 这几个变量分别是 字体大小, 角度, 字体名称, 字符串, 预设宽度
    $content = "";

    // 将字符串拆分成一个个单字 保存到数组 letter 中
    for ($i=0;$i<mb_strlen($string);$i++) {
        $letter[] = mb_substr($string, $i, 1);
    }

    foreach ($letter as $l) {
        $teststr = $content." ".$l;
        $testbox = imagettfbbox($fontsize, $angle, $fontface, $teststr);
        // 判断拼接后的字符串是否超过预设的宽度
        if (($testbox[2] > $width) && ($content !== "")) {
            $content .= "\n";
        }
        $content .= $l;
    }
    return $content;
}


error_reporting(0);
$v = $_GET["v"];
$ttt = $_GET["t"];   //延时再次启动的时间间隔（小时）
$ctype = $_GET["cc"];

$hour = intval(date("H"));//获取当前时间
if($hour+$ttt > 21)
    $ttt = 21 - $hour + 6;
elseif($hour+$ttt < 6)
    $ttt = 6 - $hour;


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
$result = file_get_contents("https://v1.hitokoto.cn/?c=".$ctype, false, $context);
$data=json_decode($result, true);


$hitokoto = $data["hitokoto"];
$from = $data["from"];

imagettftext($im, 15, 0, 7, 25, $tc, "SIMYOU.TTF", autowrap(15, 0, "SIMYOU.TTF", $hitokoto, 200));

imagettftext($im, 15, 0, 10, 150, $tc, "SIMYOU.TTF", autowrap(15, 0, "SIMYOU.TTF", "--".$from, 190));

$battery = ($v - 3400)/700;
if($battery>1)
$battery=1;
elseif($battery<0)
{
    $battery=0;
    $ttt = 0;//电量过低，禁用自动开机，保护电池
}


ImageString ( $im, 20, 0, 180, intval($battery*100).'%', $tc );
ImageString ( $im, 20, 50, 180, date("Y-m-d H:i"), $tc );

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
$pic_result = dechex($ttt/256).dechex($ttt%256).$pic_result;
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
echo "<".pack("H*",$pic_result);
?>
