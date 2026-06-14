@echo off
REM Event Manager - Setup Test Runner (Windows)
REM Double-click this file to test your Event Manager setup

echo ========================================
echo Event Manager - Setup Test
echo ========================================
echo.

REM Change to the correct directory
cd /d "%~dp0.."

REM Run the setup test
C:\xampp\php\php.exe event-manager\test_setup.php

echo.
pause
