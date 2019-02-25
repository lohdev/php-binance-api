@echo off
c:
cd \stoploss
:loop
php stoploss.php
timeout 1
goto loop