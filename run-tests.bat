@echo off
echo Running Log Viewer Tests
echo ========================

echo.
echo Running PHPUnit Tests...
echo ------------------------
phpunit

echo.
echo Running JavaScript Tests...
echo --------------------------
npm test

echo.
echo All tests completed!