@echo off
setlocal
if exist "%~dp0.tools\php\php.exe" (
    set "PHP_CMD="%~dp0.tools\php\php.exe" -c "%~dp0.tools\php\php.ini""
) else (
    set "PHP_CMD=php.exe"
)

if exist "%~dp0.tools\composer.phar" (
    %PHP_CMD% "%~dp0.tools\composer.phar" %*
) else (
    composer %*
)
