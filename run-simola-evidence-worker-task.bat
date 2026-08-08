@echo off
setlocal EnableExtensions

cd /d "C:\xampp\htdocs\simola-dev"

if not exist "storage\logs" (
    mkdir "storage\logs"
)

set "SIMOLA_WORKER_LOG=C:\xampp\htdocs\simola-dev\storage\logs\evidence-worker-task.log"

echo.>>"%SIMOLA_WORKER_LOG%"
echo ======================================================>>"%SIMOLA_WORKER_LOG%"
echo [%date% %time%] SIMOLA Evidence Worker task started.>>"%SIMOLA_WORKER_LOG%"
echo Project: C:\xampp\htdocs\simola-dev>>"%SIMOLA_WORKER_LOG%"
echo ======================================================>>"%SIMOLA_WORKER_LOG%"

:WORKER_LOOP
echo [%date% %time%] Starting queue worker...>>"%SIMOLA_WORKER_LOG%"

"C:\xampp\php\php.exe" artisan queue:work database --queue=evidence --sleep=2 --timeout=21600 --tries=3 --memory=512 >>"%SIMOLA_WORKER_LOG%" 2>&1

set "WORKER_EXIT_CODE=%ERRORLEVEL%"

echo [%date% %time%] Worker stopped with exit code %WORKER_EXIT_CODE%.>>"%SIMOLA_WORKER_LOG%"
echo [%date% %time%] Restarting worker in 15 seconds...>>"%SIMOLA_WORKER_LOG%"

timeout /t 15 /nobreak >nul
goto WORKER_LOOP

