module(..., package.seeall)




--充电检测中断
local function chargeFnc(msg)
    log.info("Gpio.chargeFnc",msg)
    if msg==cpu.INT_GPIO_POSEDGE then--上升沿中断
        --插上充电器了
    else--下降沿中断
        --拔掉充电器了
    end
end

charge = pins.setup(pio.P0_7,chargeFnc)
