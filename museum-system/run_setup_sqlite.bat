@echo off
echo ===================================================
echo Setting up Museo Sang Bata sa Negros (SQLite)...
echo Zero-configuration: Works instantly without MySQL.
echo ===================================================
"C:\xampp\php\php.exe" artisan museum:setup --sqlite
echo.
pause
