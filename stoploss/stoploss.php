<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';
$ini = parse_ini_file('config.ini');
$key = $ini['key'];
$secret = $ini['secret'];

function cancelo($coin) {
	global $api;
	$api->useServerTime();
	$openorders = $api->openOrders();
	foreach($openorders as $oo) 
	{
		if (preg_match('/^'.$coin.'/',$oo['symbol']))
		{
			if ($oo['side'] == "SELL")
			{
				echo "Open sell-order in ".$coin." found. Canceling...\n";
				$cancelorder = $api->cancel($oo['symbol'], $oo['orderId']);
			}
		}
	}
}

function sellcoin($coin, $coinpair) {
	global $api;
	$api->useServerTime();
	global $ei;
	$coinsymbol = $coin . "" . $coinpair;
	$balances = $api->balances();
	$step = $ei[$coinsymbol][0];
	$minqty = $ei[$coinsymbol][1];
	$available = $balances[$coin]['available'];
	$availablefix = (floor($available * $step) / $step);
	if ($availablefix > $minqty)
	{
		echo "\n Selling..\n\n";
		$marketorder = $api->sell($coinsymbol, $availablefix, 0, "MARKET");
	}
}

$md5list = md5_file('stoploss.txt');
$lines = file('stoploss.txt');

$timestart = date('Y-m-d H:i:s');
$lastrefresh = strtotime($timestart);
$lastshowstops = strtotime($timestart);
$stoplosses = array();
$ignorecoin = array();
$pairs = array();

foreach($lines as $line){
	$lineexplode = explode(";", $line);
	$pairing = $lineexplode[0] . "" . $lineexplode[1];
	$pairs[] = $pairing;
	$stoplosses[$pairing] = array($lineexplode[0],$lineexplode[2],$lineexplode[1]);
	echo "Stoploss for  {$lineexplode[0]} in {$lineexplode[1]}-pair is {$lineexplode[2]}";
	if (isset($lineexplode[3]))
	{
		#echo "Sell-parameter set\n";
	}
}
echo "\n";

$api = new Binance\API($key,$secret);
$api->useServerTime();

#This part is to get the minimum trade-size and the number of decimals for the trading-pairs.
$exchangeInfo = $api->exchangeInfo();
$ei = array();

foreach($exchangeInfo['symbols'] as $symbol) 
{
	if (preg_match('/BREAK/',$symbol['status']))
	{
		continue 1;
	}
	$tick = (1 / $symbol['filters'][0]['tickSize']);
	$step = (1 / $symbol['filters'][2]['stepSize']);
	$lengthtick = (strlen((string)$tick) - 1);
	$minqty =  $symbol['filters'][2]['minQty'];

	$ei[$symbol['symbol']] = array($step,$minqty);
}

$api->trades($pairs, function($api, $symbol, $trades) {
	$timenow = date('Y-m-d H:i:s');
	$timestamp = strtotime($timenow);
	global $md5list;
	global $i;
	global $lastrefresh;
	global $lastshowstops;
	## Show the current stoplosses every 5 min
	if ($timestamp > ($lastshowstops + 300))
	{
		$lines = file('stoploss.txt');
		echo "\n\n";
		foreach($lines as $line)
		{
			$lineexplode = explode(";", $line);
			echo "Stoploss for  {$lineexplode[0]} in {$lineexplode[1]}-pair is {$lineexplode[2]}";
			$lastshowstops = $timestamp;
		}
		global $ignorecoin;
		if (count($ignorecoin) > 0)
		{
			echo "\n";
			foreach($ignorecoin as $key => $ignoreline)
			{
				echo "\nStoploss is already triggered for {$key}";
			}
		}
		echo "\n";
	}
	## Check for changes in stoploss-file every 30 sec
	if ($timestamp > ($lastrefresh + 30))
	{
		global $md5list;
		$lastrefresh = $timestamp;
		$md5new = md5_file('stoploss.txt');
		if ($md5list != $md5new)
		{
			exit("\nStoploss-file changed. Reloading...");
		}
	}
	
	$stoplossed = array();
	global $stoplosses; 
	global $ignorecoin;
	
	if (!isset($ignorecoin[$symbol])) 
	{
		#Show a dot for every trade in any pair. Just to show that script is receiving data
		echo ".";
		#If last trade for pair was below stop-price, start stoploss-process
		if ($trades['price'] < $stoplosses[$symbol][1] && $trades['price'] > 0)
		{
			global $stoplossed;
			if (isset($stoplossed[$symbol])) 
			{
				#If price below stop after 3 seconds, sell cancel orders and sell coins into selected pair.
				if ($timestamp > ($stoplossed[$symbol] + 3))
				{
					echo "\nStop triggered for {$symbol}\n";
					global $ignorecoin;
					$ignorecoin[$symbol] = "1";
					$api->useServerTime();
					cancelo($stoplosses[$symbol][0]);
					sellcoin($stoplosses[$symbol][0], $stoplosses[$symbol][2]);
					date_default_timezone_set('Etc/UTC');
					$dataString = date("Y.m.d h:i:s A")." - Symbol: {$symbol} - Stop price: {$stoplosses[$symbol][1]} - Current Price: {$trades['price']}";
					$dataString .= "\n"; 
					$fWrite = fopen("log.txt","at");
					$wrote = fwrite($fWrite, $dataString);
					fclose($fWrite);
				}
				else
				{
					echo "\nStop initiated for {$symbol}\n";
				}
			}
		else
		{
			$stoplossed[$symbol] = $timestamp;
		}
		echo "{$symbol} Below stop: {$stoplosses[$symbol][1]}\n";
		}
	}
});

?>
