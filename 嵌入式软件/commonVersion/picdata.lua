module(..., package.seeall)
require"epd1in54"
require"utils"
require"common"

sys.taskInit(function ()
    epd1in54.init()   --初始化
    disp.new({
        width = 200, --分辨率宽度，128像素；用户根据屏的参数自行修改
        height = 200, --分辨率高度，64像素；用户根据屏的参数自行修改
        bpp = 1, --位深度，1表示单色。单色屏就设置为1，不可修改
        yoffset = 0, --Y轴偏移
        xoffset = 0, --X轴偏移
        hwfillcolor = 0xffff, --填充色，黑色
    })
    sys.wait(3000)
    disp.clear()
    log.info("pic data","start")
    disp.puttext(common.utf8ToGb2312("纽约"),0,0)
    disp.puttext(common.utf8ToGb2312("23C~32C"),120,16)
    disp.puttext(common.utf8ToGb2312("白天：多云"),0,32)
    disp.puttext(common.utf8ToGb2312("夜间：火山爆发"),0,48)
    disp.puttext(common.utf8ToGb2312("湿度：70%"),0,64)
    disp.puttext(common.utf8ToGb2312("风力：19级"),0,80)
    disp.puttext(common.utf8ToGb2312("空气质量：重度污染"),0,96)
    --disp.putimage("/ldata/test.bmp",30,30)
    local pic = disp.getframe()
    sys.wait(200)
    epd1in54.showPicturePage(pic)
end)


