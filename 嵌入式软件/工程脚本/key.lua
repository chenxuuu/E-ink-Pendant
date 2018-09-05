--- 模块功能：电源按键检测

module(...,package.seeall)

require"powerKey"

--长按三秒关机
powerKey.setup(3000,function ()
    rtos.poweroff()
end)


