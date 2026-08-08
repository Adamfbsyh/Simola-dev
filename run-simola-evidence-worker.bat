@echo off
title SIMOLA Evidence Worker
cd /d "C:\xampp\htdocs\simola-dev"
echo ==============================================
echo SIMOLA - BACKGROUND WORKER FOLDER EVIDENCE
echo Jangan tutup jendela ini saat proses berjalan.
echo Tekan Ctrl+C untuk menghentikan worker.
echo ==============================================
echo.
php artisan queue:work database --queue=evidence --sleep=2 --timeout=21600 --tries=3
pause

