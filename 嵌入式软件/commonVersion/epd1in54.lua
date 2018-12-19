module(..., package.seeall)
require"misc"
require"utils"

local function getBusyFnc(msg)
    log.info("Gpio.getBusyFnc",msg)
    if msg==cpu.INT_GPIO_POSEDGE then--上升沿中断
        --不动作
    else--下降沿中断
        sys.publish("BUSY_DOWN")
    end
end

local getBusy         = pins.setup(pio.P0_6,getBusyFnc)
local setRST          = pins.setup(pio.P0_5,1)
local setDC           = pins.setup(pio.P0_4,1)

lut_full_update = {
    0x66,
    0x66,
    0x44,
    0x66,
    0xAA,
    0x11,
    0x80,
    0x08,
    0x11,
    0x18,
    0x81,
    0x18,
    0x11,
    0x88,
    0x11,
    0x88,
    0x11,
    0x88,
    0x00,
    0x00,
    0xFF,
    0xFF,
    0xFF,
    0xFF,
    0x5F,
    0xAF,
    0xFF,
    0xFF,
    0x2F,
    0x00
}
lut_partial_update = {
    0x10
    ,0x18
    ,0x18
    ,0x28
    ,0x18
    ,0x18
    ,0x18
    ,0x18
    ,0x08
    ,0x00
    ,0x00
    ,0x00
    ,0x00
    ,0x00
    ,0x00
    ,0x00
    ,0x00
    ,0x00
    ,0x00
    ,0x00
    ,0x13
    ,0x11
    ,0x22
    ,0x63
    ,0x11
    ,0x00
    ,0x00
    ,0x00
    ,0x00
    ,0x00
}

-- Display resolution
local EPD_WIDTH       = 200
local EPD_HEIGHT      = 200

-- EPD1IN54 commands
local DRIVER_OUTPUT_CONTROL                       = 0x01
local BOOSTER_SOFT_START_CONTROL                  = 0x0C
local GATE_SCAN_START_POSITION                    = 0x0F
local DEEP_SLEEP_MODE                             = 0x10
local DATA_ENTRY_MODE_SETTING                     = 0x11
local SW_RESET                                    = 0x12
local TEMPERATURE_SENSOR_CONTROL                  = 0x1A
local MASTER_ACTIVATION                           = 0x20
local DISPLAY_UPDATE_CONTROL_1                    = 0x21
local DISPLAY_UPDATE_CONTROL_2                    = 0x22
local WRITE_RAM                                   = 0x24
local WRITE_VCOM_REGISTER                         = 0x2C
local WRITE_LUT_REGISTER                          = 0x32
local SET_DUMMY_LINE_PERIOD                       = 0x3A
local SET_GATE_TIME                               = 0x3B
local BORDER_WAVEFORM_CONTROL                     = 0x3C
local SET_RAM_X_ADDRESS_START_END_POSITION        = 0x44
local SET_RAM_Y_ADDRESS_START_END_POSITION        = 0x45
local SET_RAM_X_ADDRESS_COUNTER                   = 0x4E
local SET_RAM_Y_ADDRESS_COUNTER                   = 0x4F
local TERMINATE_FRAME_READ_WRITE                  = 0xFF

--打开SPI引脚的供电
pmd.ldoset(5,pmd.LDO_VMMC)
--[[
功能：配置SPI

参数：
id：SPI的ID，spi.SPI_1表示SPI1，Air201、Air202、Air800只有SPI1，固定传spi.SPI_1即可
cpha：spi_clk idle的状态，仅支持0和1，0表示低电平，1表示高电平
cpol：第几个clk的跳变沿传输数据，仅支持0和1，0表示第1个，1表示第2个
dataBits：数据位，仅支持8
clock：spi时钟频率，支持110K到13M（即110000到13000000）之间的整数（包含110000和13000000）
duplex：是否全双工，仅支持0和1，0表示半双工（仅支持输出），1表示全双工。此参数可选，默认半双工

返回值：number类型，1表示成功，0表示失败
]]
log.info("spi.setup",spi.setup(spi.SPI_1,0,0,8,13000000,0,0))

local function wait()
    if getBusy() == 1 then  -- 0: idle, 1: busy
        sys.waitUntil("BUSY_DOWN",5000)
    end
end

setDC(1)
local function sendCommand(data)
    --log.info("epd1in45.sendCommand",data)
    setDC(0)
    spi.send(spi.SPI_1,string.char(data))
end

local function sendData(data)
    --log.info("epd1in45.sendData",data)
    setDC(1)
    spi.send(spi.SPI_1,string.char(data))
end

local function sendDataString(data)
    --log.info("epd1in45.sendData",data)
    setDC(1)
    spi.send(spi.SPI_1,data)
end

local function reset()
    log.info("epd1in45.reset","")
    setRST(0)
    sys.wait(200)
    setRST(1)
    sys.wait(200)
end

function deepSleep()
    log.info("epd1in45.deepSleep","")
    sendCommand(0x10)
    wait()
end

local function set_lut(lut)
    log.info("epd1in45.set_lut","")
    sendCommand(WRITE_LUT_REGISTER)
    for i= 1, #lut do
        sendData(lut[i])
    end
end

function init(lut)
    log.info("epd1in45.init","")
    if lut == nil then
        lut = lut_full_update
    end
    reset()
    sendCommand(DRIVER_OUTPUT_CONTROL)
    sendData(bit.band(EPD_HEIGHT-1,0xff))
    sendData(bit.band(bit.rshift(EPD_HEIGHT-1,8),0xff))
    sendData(0x00)
    sendCommand(BOOSTER_SOFT_START_CONTROL)
    sendData(0xD7)
    sendData(0xD6)
    sendData(0x9D)
    sendCommand(WRITE_VCOM_REGISTER)
    sendData(0xA8)
    sendCommand(SET_DUMMY_LINE_PERIOD)
    sendData(0x1A)
    sendCommand(SET_GATE_TIME)
    sendData(0x08)
    sendCommand(DATA_ENTRY_MODE_SETTING)
    sendData(0x03)
    set_lut(lut)
    log.info("epd1in45.init","done")
end

local function set_memory_area(x_start, y_start, x_end, y_end)
    log.info("epd1in45.set_memory_area",x_start, y_start, x_end, y_end)
    sendCommand(SET_RAM_X_ADDRESS_START_END_POSITION)
    -- x point must be the multiple of 8 or the last 3 bits will be ignored
    sendData(bit.band(bit.rshift(x_start,3),0xff))
    sendData(bit.band(bit.rshift(x_end,3),0xff))
    sendCommand(SET_RAM_Y_ADDRESS_START_END_POSITION)
    sendData(bit.band(y_start,0xff))
    sendData(bit.band(bit.rshift(y_start,8),0xff))
    sendData(bit.band(y_end,0xff))
    sendData(bit.band(bit.rshift(y_end,8),0xff))
    log.info("epd1in45.set_memory_area","done")
end

local function set_memory_pointer(x,y)
    log.info("epd1in45.set_memory_pointer",x,y)
    sendCommand(SET_RAM_X_ADDRESS_COUNTER)
    -- x point must be the multiple of 8 or the last 3 bits will be ignored
    sendData(bit.band(bit.rshift(x,3),0xff))
    sendCommand(SET_RAM_Y_ADDRESS_COUNTER)
    sendData(bit.band(y,0xff))
    sendData(bit.band(bit.rshift(y,8),0xff))
    wait()
    log.info("epd1in45.set_memory_pointer","done")
end

function clear_frame_memory(color)
    log.info("epd1in45.clear_frame_memory",color)
    set_memory_area(0, 0, EPD_WIDTH - 1, EPD_HEIGHT - 1)
    set_memory_pointer(0, 0)
    sendCommand(WRITE_RAM)
    for i= 1, EPD_WIDTH / 8 * EPD_HEIGHT do
        sendData(color)
    end
    log.info("epd1in45.clear_frame_memory","done")
end

function display_frame()
    log.info("epd1in45.display_frame","start")
    sendCommand(DISPLAY_UPDATE_CONTROL_2)
    sendData(0xC7)
    sendCommand(MASTER_ACTIVATION)
    sendCommand(TERMINATE_FRAME_READ_WRITE)
    wait()
    log.info("epd1in45.display_frame","done")
end

--输入值：数组
function showPictureN(pic)
    wait()
    log.info("epd1in45.showPicture","")
    set_memory_area(0, 0, EPD_WIDTH - 1, EPD_HEIGHT - 1)
    set_memory_pointer(0, 0)
    sendCommand(WRITE_RAM)
    for i=1,#pic do
        sendData(pic[i])
    end
    display_frame()
end

--输入值：string
function showPicture(pic)
    wait()
    log.info("epd1in45.showPicture","")
    set_memory_area(0, 0, EPD_WIDTH - 1, EPD_HEIGHT - 1)
    set_memory_pointer(0, 0)
    sendCommand(WRITE_RAM)
    sendDataString(pic)
    display_frame()
end

--输入值：编码过的string
function showPicturez(pic)
    wait()
    log.info("epd1in45.showPicture z","")
    set_memory_area(0, 0, EPD_WIDTH - 1, EPD_HEIGHT - 1)
    set_memory_pointer(0, 0)
    sendCommand(WRITE_RAM)

    local dataHex = pic:toHex():upper()
    local dataResult = ""
    local i = 1
    while i <= dataHex:len() do
        if i % 1000 == 0 then sys.wait(1500) log.info("wait for "..tostring(i)) end
        if i == dataHex:len() and dataHex:sub(i,i) == "0" then break end--末尾可能多一位，忽略
        if dataHex:sub(i,i) == "F" or dataHex:sub(i,i) == "0" then
            for j=1,("0"..dataHex:sub(i+1,i+1)):fromHex():byte() do
                dataResult = dataResult..dataHex:sub(i,i)
            end
            i = i + 2
        else
            dataResult = dataResult..dataHex:sub(i,i)
            i = i + 1
        end
        if dataResult:len() % 2 == 0 then
            sendDataString(dataResult:fromHex())
            log.info("spi send",dataResult)
            dataResult = ""
        end
    end

    display_frame()
end
