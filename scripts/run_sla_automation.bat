@echo off
setlocal enabledelayedexpansion

set "SCRIPT_DIR=%~dp0"
set "ROOT_DIR=%SCRIPT_DIR%.."
set "PHP_SCRIPT=%ROOT_DIR%\php\run_sla_automation.php"
set "LOG_DIR=%ROOT_DIR%\php\logs\automation"

if not exist "%LOG_DIR%" (
    mkdir "%LOG_DIR%"
)

set "PHP_BIN=C:\xampp\php\php.exe"
if not exist "%PHP_BIN%" (
    set "PHP_BIN=php"
)

for /f %%i in ('powershell -NoProfile -Command "(Get-Date).ToString('yyyyMMdd-HHmmss')"') do set "TS=%%i"
set "LOG_FILE=%LOG_DIR%\sla-automation-%TS%.log"

"%PHP_BIN%" "%PHP_SCRIPT%" > "%LOG_FILE%" 2>&1
set "EXIT_CODE=%ERRORLEVEL%"

if not "%EXIT_CODE%"=="0" (
    echo SLA automation failed. See log: %LOG_FILE%
) else (
    echo SLA automation completed. Log: %LOG_FILE%
)

exit /b %EXIT_CODE%

