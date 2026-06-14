@echo off
REM Event Manager - Queue Worker Runner (Windows)
REM Double-click this file to run the queue worker manually

echo ========================================
echo Event Manager - Queue Worker
echo ========================================
echo.

REM Change to the correct directory
cd /d "%~dp0.."

REM Run the queue worker
C:\xampp\php\php.exe event-manager\workers\queue_worker.php

echo.
echo ========================================
echo Queue worker completed
echo ========================================
echo.
echo Check the output above for results.
echo.
pause
