@echo off
REM هذا السكربت يقوم بتشغيل سكريبت PHP لمسح قاعدة البيانات.
REM يجب تشغيله كمسؤول (Run as Administrator) إذا كانت هناك مشاكل في الأذونات.

REM =========================================================================
REM  Variables Definition - تحديد المتغيرات
REM =========================================================================

REM Path to the XAMPP installation directory
SET "XAMPP_PATH=C:\xampp"

REM Path to the PHP executable
SET "PHP_EXE_PATH=%XAMPP_PATH%\php\php.exe"

REM Path to the PHP script you want to run
REM Make sure this path is correct relative to the batch file's location
SET "PHP_SCRIPT_PATH=%~dp0clear_database.php"

REM =========================================================================
REM  Execution - التنفيذ
REM =========================================================================

ECHO.
ECHO ===================================================
ECHO Starting the Database Cleanup Script...
ECHO ===================================================
ECHO.

REM Check if the PHP executable exists
IF NOT EXIST "%PHP_EXE_PATH%" (
    ECHO ERROR: The PHP executable was not found at "%PHP_EXE_PATH%".
    ECHO Please check your XAMPP installation path.
    PAUSE
    EXIT /B 1
)

REM Check if the PHP script exists
IF NOT EXIST "%PHP_SCRIPT_PATH%" (
    ECHO ERROR: The PHP script was not found at "%PHP_SCRIPT_PATH%".
    ECHO Please ensure the script is named clear_database.php and is in the same directory.
    PAUSE
    EXIT /B 1
)

REM Execute the PHP script using the PHP CLI
"%PHP_EXE_PATH%" "%PHP_SCRIPT_PATH%"

ECHO.
ECHO ===================================================
ECHO The Database Cleanup Script has finished.
ECHO ===================================================
ECHO.
PAUSE
EXIT /B 0
