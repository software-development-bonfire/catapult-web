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
:: ============================================================
:: Read configuration from .env
:: ============================================================

set "env_file=%~dp0.env"

if not exist "%env_file%" (
    echo.
    echo ERROR: .env file not found:
    echo "%env_file%"
    echo.
    pause
    exit /b 1
)

for /f "tokens=1,* delims==" %%A in ('findstr /b "PUSHER_APP_PORT=" "%env_file%"') do (
    set "web_socket_port=%%B"
)

for /f "tokens=1,* delims==" %%A in ('findstr /b "CATAPULT_PORT=" "%env_file%"') do (
    set "catapult_port=%%B"
)

:: Validate values
if not defined web_socket_port (
    echo ERROR: PUSHER_APP_PORT is not defined in .env
    pause
    exit /b 1
)

if not defined catapult_port (
    echo ERROR: CATAPULT_PORT is not defined in .env
    pause
    exit /b 1
)

echo.
echo ==========================================
echo Configuration
echo ==========================================
echo WebSocket Port : %web_socket_port%
echo Catapult Port  : %catapult_port%
echo ==========================================
echo.

:: Set default values in case .env is not found
set web_socket_port=6100
set catapult_port=81

set "env_file=%~dp0.env"

if exist "%env_file%" (
    echo .env found. Reading configuration...
    :: Read PUSHER_APP_PORT
    for /f "tokens=1,* delims==" %%A in ('findstr /b "PUSHER_APP_PORT=" "%env_file%" 2^>nul') do (
        if not "%%B"=="" (
            set "web_socket_port=%%B"
        )
    )
    :: Read CATAPULT_PORT
    for /f "tokens=1,* delims==" %%A in ('findstr /b "CATAPULT_PORT=" "%env_file%" 2^>nul') do (
        if not "%%B"=="" (
            set "catapult_port=%%B"
        )
    )

) else (
    echo .env not found. Using default configuration...
)

echo.
echo Configuration
echo WebSocket Port : %web_socket_port%
echo Catapult Port  : %catapult_port%
echo.

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