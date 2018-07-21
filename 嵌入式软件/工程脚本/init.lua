--get config to connect to wifi
local function connect_wifi(sum)
    -- start connect to wifi
    if file.open("wifi_name"..tostring(sum)) then
        local wifi_name = file.read()
        file.close()
        file.open("wifi_password"..tostring(sum))
        local wifi_password = file.read()
        file.close()
        --connect saved wifi
        wifi.setmode(wifi.STATION)
        wifi.sta.config{ssid=wifi_name, pwd=wifi_password}
        print("set wifi connection:",wifi_name,wifi_password)
    else
        --connect default wifi
        wifi.setmode(wifi.STATION)
        wifi.sta.config{ssid="kxct", pwd="12345678"}
        print("set wifi connection:","kxct","12345678")
    end
end

--change the config of wifi
function set_wifi_config(wifi_name,wifi_password,sum)
    print("try to set wifi config",wifi_name,wifi_password)
    file.open("wifi_name"..tostring(sum),"w+")
    file.write(wifi_name)
    file.close()
    file.open("wifi_password"..tostring(sum),"w+")
    file.write(wifi_password)
    file.close()
    print("set finish!",wifi_name,wifi_password)
end

try_count,wifi_count = 0,1
--check the default wifi connection,and do other things after connected successfully.
function check_default_wifi()
    if wifi.sta.getip() == nil then
        try_count = try_count + 1
        print("not connect,try "..try_count.." times.")
        if try_count > 10 then  --wait over 20 seconds,set to default config
            print("over 20 seconds,try next wifi")
            wifi_count = wifi_count + 1
            connect_wifi(wifi_count)
            if wifi_count > 3 then
                print("no wifi can connect,set to default")
                wifi.setmode(wifi.STATION)
                wifi.sta.config{ssid="kxct", pwd="12345678"}
                print("set wifi connection:","kxct","12345678")
            end
            try_count = 0
        end
    else
        tmr.stop(0)
        print("connect success!\r\nlocal ip:",wifi.sta.getip())
    end
end

connect_wifi(1)
tmr.alarm(0, 1000, 1,check_default_wifi)

