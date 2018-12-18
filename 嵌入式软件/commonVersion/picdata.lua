module(..., package.seeall)
require"epd1in54"
require"utils"

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
    showPictureFile("open")--显示开机画面
    sys.wait(60000)
    rtos.poweroff()
end)
