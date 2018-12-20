module(..., package.seeall)
require"epd1in54"
require"utils"
require"common"

--显示保存的图片文件
function showPictureFile(name)
    if io.exists("/ldata/"..name..".pic") then
        local pictureData = io.readFile("/ldata/"..name..".pic")
        log.info("picdata",pictureData:sub(1,50):toHex())
        epd1in54.showPicture(pictureData)
    else
        log.error("picdata","no file named "..name)
    end
end

sys.taskInit(function ()
    epd1in54.init()   --初始化
    --showPictureFile("open")--显示开机画面
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
    disp.puttext(common.utf8ToGb2312("合宙openluat墨水屏测试"),0,0)
    disp.putimage("/ldata/test.bmp",30,30)
    local pic = disp.getframe()
    sys.wait(200)
    epd1in54.showPicturePage(pic)
end)


