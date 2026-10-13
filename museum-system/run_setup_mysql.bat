@echo off
echo ===================================================
echo Setting up Museo Sang Bata sa Negros (MySQL)...
echo Make sure MySQL is running in XAMPP Control Panel.
echo ===================================================
"C:\xampp\php\php.exe" artisan museum:setup
echo.
pause
