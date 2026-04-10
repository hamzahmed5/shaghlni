@echo off
title Shaghlni Setup
echo.
echo  =========================================
echo   Shaghlni - Automatic Setup
echo  =========================================
echo.

REM Check if Laragon is installed
if not exist "C:\laragon\laragon.exe" (
    echo  [ERROR] Laragon is not installed!
    echo  Please download and install Laragon from: https://laragon.org/download/
    echo  Install it then run this file again.
    echo.
    pause
    exit
)

echo  [1/4] Copying project files to Laragon...
if not exist "C:\laragon\www\jobpilot" mkdir "C:\laragon\www\jobpilot"
xcopy /E /Y /Q "%~dp0*" "C:\laragon\www\jobpilot\" >nul
echo  Done!

echo.
echo  [2/4] Starting Laragon services...
start "" "C:\laragon\laragon.exe"
timeout /t 6 /nobreak >nul

REM Start Apache and MySQL via Laragon CLI
"C:\laragon\bin\laragon\laragon.exe" startapache >nul 2>&1
"C:\laragon\bin\laragon\laragon.exe" startmysql >nul 2>&1
timeout /t 4 /nobreak >nul
echo  Done!

echo.
echo  [3/4] Setting up database automatically...

REM Find MySQL binary
set MYSQL="C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"

if not exist %MYSQL% (
    REM Try to find any MySQL version
    for /d %%i in ("C:\laragon\bin\mysql\*") do set MYSQL="%%i\bin\mysql.exe"
)

REM Create database and import schema
%MYSQL% -u root -e "CREATE DATABASE IF NOT EXISTS jobs_platform;" 2>nul
%MYSQL% -u root jobs_platform < "C:\laragon\www\jobpilot\database\schema.sql" 2>nul

if %errorlevel% == 0 (
    echo  Done! Database created successfully!
) else (
    echo  [!] Database setup had an issue.
    echo  Please manually import: C:\laragon\www\jobpilot\database\schema.sql
    echo  into a database named: jobs_platform
    echo.
    pause
)

echo.
echo  [4/4] Opening Shaghlni...
timeout /t 2 /nobreak >nul
start "" "http://localhost/jobpilot/frontend/index.html"

echo.
echo  =========================================
echo   Setup Complete! Enjoy Shaghlni!
echo  =========================================
echo.
pause
