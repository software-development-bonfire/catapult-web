@echo off

:: BatchGotAdmin
:-------------------------------------
REM  --> Check for permissions
>nul 2>&1 "%SYSTEMROOT%\system32\cacls.exe" "%SYSTEMROOT%\system32\config\system"

REM --> If error flag set, we do not have admin.
if '%errorlevel%' NEQ '0' (
    echo Requesting administrative privileges...
    goto UACPrompt
) else ( goto gotAdmin )

:UACPrompt
    echo Set UAC = CreateObject^("Shell.Application"^) > "%temp%\getadmin.vbs"
    set params = %*:"=""
    echo UAC.ShellExecute "cmd.exe", "/c %~s0 %params%", "", "runas", 1 >> "%temp%\getadmin.vbs"

    "%temp%\getadmin.vbs"
    del "%temp%\getadmin.vbs"
    exit /B

:gotAdmin
    pushd "%CD%"
    CD /D "%~dp0"
:--------------------------------------

set web_socket_port=6100
set catapult_port=81

netsh advfirewall firewall show rule name="Catapult inbound websocket %web_socket_port%" >nul
if not ERRORLEVEL 1 (
    rem Rule %RULE_NAME% already exists.
    echo Hey, you already got a out rule by that name, you cannot put another one in!
) else (
    echo Rule "Catapult inbound websocket %web_socket_port%" does not exist. Creating...
    netsh advfirewall firewall add rule name="Catapult inbound websocket %web_socket_port%" dir=in action=allow protocol=TCP localport=%web_socket_port%
)

netsh advfirewall firewall show rule name="Catapult outbond websocket %web_socket_port%" >nul
if not ERRORLEVEL 1 (
    rem Rule %RULE_NAME% already exists.
    echo Hey, you already got a out rule by that name, you cannot put another one in!
) else (
    echo Rule "Catapult outbond websocket %web_socket_port%" does not exist. Creating...
    netsh advfirewall firewall add rule name="Catapult outbond websocket %web_socket_port%" dir=out action=allow protocol=TCP localport=%web_socket_port%
)

netsh advfirewall firewall show rule name="Catapult inbound port %catapult_port%" >nul
if not ERRORLEVEL 1 (
    rem Rule %RULE_NAME% already exists.
    echo Hey, you already got a out rule by that name, you cannot put another one in!
) else (
    echo Rule "Catapult inbound port %catapult_port%" does not exist. Creating...
    netsh advfirewall firewall add rule name="Catapult inbound port %catapult_port%" dir=in action=allow protocol=TCP localport=%catapult_port%
)

netsh advfirewall firewall show rule name="Catapult outbound port %catapult_port%" >nul
if not ERRORLEVEL 1 (
    rem Rule %RULE_NAME% already exists.
    echo Hey, you already got a out rule by that name, you cannot put another one in!
) else (
    echo Rule "Catapult outbound port %catapult_port%" does not exist. Creating...
    netsh advfirewall firewall add rule name="Catapult outbound port %catapult_port%" dir=out action=allow protocol=TCP localport=%catapult_port%
)

::pause