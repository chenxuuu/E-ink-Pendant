<?php
//本代码为输出图片数据的一个示例，具体请自行开发
//自定义的php代码可以放到我服务器上进行代挂，也可以用自己服务器去跑
//其他语言的http api请自行处理，不提供源码

//=======================创建图片对象
//如果指定图片不存在，则创建指定大小的空图片模板对象，宽800，高500
$im = ImageCreate ( 200, 200 );

//=======================创建颜色对象
//依据一个模板对象，生成颜色对象，0为red值，100为green值，30为blue值
//ImageCreate 创建的对象在此会直接将颜色填充至模板对象中，ImageCreateTrueColor 创建的对象则只创建颜色对象，不填充
$bgc = ImageColorAllocate ( $im, 255, 255, 255 );         //背景颜色
$tc  = ImageColorAllocate ( $im, 0,0,0 );   //字体颜色

//=======================填充颜色
//填充方法为左上角横纵坐标，但这里坐标对两种方法创建的模板对象都不起作用，完全填充
ImageFill($im, 0, 0, $bgc);

//=======================摆放文字
//20字体粗度，0字体左边距距离，0字体上边距距离，$tc字体颜色，这种方式只能填充英语，填中文乱码
ImageString ( $im, 20, 0, 0, "just is English code", $tc );
//添加中文18为字体大小，0字体旋转程度，0左边距距离，40上边距距离，项目目录下要有"MSYH.TTF"这个字体文件
imagettftext($im, 18, 0, 0, 35, $tc, "SIMYOU.TTF", "中文填充测试");

//这是模块传上来的几个数据
$imei = $_GET["imei"];
$lat = $_GET["lat"];
$lng = $_GET["lng"];
$v = $_GET["v"];
ImageString($im, 12, 0, 50, "imei:".$imei, $tc);
ImageString($im, 12, 0, 65, "lat:".$lat, $tc);
ImageString($im, 12, 0, 80, "lng:".$lng, $tc);
ImageString($im, 12, 0, 95, "battery:".$v, $tc);

if(!empty($_GET['debug']))  //网址参数里加上&debug=1可以直接看输出的图片效果
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

//输出结果，格式为json（已更改）
//jump如果为true，data存储的就是api网址，模块会去重新向新网址获取数据
//jump如果为false，data就为屏幕数据内容，内容为从左到右，从上到下，转成16进制字符串
//如用其他语言，请自行测试
/*
如：
黑白白白黑黑白白 -> 01110011 -> "73"
从上到下，从左到右
*/
//echo '{"jump": false,"data": "'.$pic_result.'"}';

//已改为：（又被更改）
//<符号，1字节
//下次等待多久后重启（单位小时，最大255），1字节，0x00表示不自启刷新
//图片数据，为16进制字符串转ascii字符串，编码方式参加上文原json方案
//该方案比json方案节约了50%流量消耗
//echo "<".pack("H*","01".$pic_result);


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

echo encode_result($pic_result,0);
?>
