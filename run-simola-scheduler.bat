@echo off

cd /d C:\xampp\htdocs\simola

C:\xampp\php\php.exe artisan schedule:run >> C:\xampp\htdocs\simola\storage\logs\scheduler-task.log 2>&1

exit /b %errorlevel%