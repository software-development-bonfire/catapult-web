ECHO OFF
set PORT=6001
set RULE_NAME="Catapult %PORT%"

netsh advfirewall firewall add rule name=%RULE_NAME% dir=in action=allow protocol=TCP localport=%PORT%
netsh advfirewall firewall add rule name=%RULE_NAME% dir=out action=allow protocol=TCP localport=%PORT%
