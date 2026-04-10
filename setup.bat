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
    echo.
    pause
    exit
)

echo  [1/4] Copying project files to Laragon...
if not exist "C:\laragon\www\jobpilot" mkdir "C:\laragon\www\jobpilot"
xcopy /E /Y /Q "%~dp0*" "C:\laragon\www\jobpilot\" >nul
echo  Done!

echo.
echo  [2/4] Starting Laragon...
start "" "C:\laragon\laragon.exe"
timeout /t 5 /nobreak >nul
echo  Done!

echo.
echo  [3/4] Setting up the database...
echo  Please do the following:
echo.
echo   1. Open Laragon and click "Start All"
echo   2. Click "Database" to open HeidiSQL
echo   3. Right-click on the left panel - Create new - Database
echo   4. Name it: jobs_platform
echo   5. Click on jobs_platform
echo   6. Go to File - Run SQL file
echo   7. Choose: C:\laragon\www\jobpilot\database\schema.sql
echo.
echo  Press any key when the database is ready...
pause >nul

echo.
echo  [4/4] Opening Shaghlni...
start "" "http://localhost/jobpilot/frontend/index.html"

echo.
echo  =========================================
echo   Setup Complete! Enjoy Shaghlni!
echo  =========================================
echo.
pause
