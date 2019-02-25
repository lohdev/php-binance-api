MR_LOH's Binance stoploss-tool.

Install PHP7 first. Download the top one and extract to c:\php .
Rename php.ini-development to php.ini. Double-click php.ini to open.
Search for ";extension=curl" and remove the ;
Search for ";extension=openssl" and remove the ;
Save the file.

Go to control-panel - system (search for system on start meny to find it easy).
Click on "advanced system settings", "environment variables" adn click on "path" below 
"system variables" and click "edit". Add c:\php to path. (if everything is on one line,
add c:\php to the start with a ; after c:\php

Now, edit config.ini and add your Binance API-key and secret.

Copy the files to c:\stoploss (create folder first). Run stoploss.bat to start the stoploss-tool. 
It will run continous, just reloading when detecting changes in stoploss.txt (some delay).


To set a stoploss, write a single line per coin in stoploss.txt 

Use ONLY uppercase characters, and use ; as delimiter.

Example:  
WTC;BTC;0.0003100
BTC;USDT;1200

Save the file.

If you have WTC, this will be sold into BTC if the price go below 0.0003100 at the point of the script running.
Active sell-orders will be canceled first (PT's), then all available coins are sold. It will be sold into the
pair you set the stoploss for. 

Try buying a small amount to test, setting some PT's. Just to verify that it works OK for you.

I have added a log-function. It will log stoplosses to log.txt. The date/time will be in UTC. 

Start the tool by double-clicking stoploss.bat