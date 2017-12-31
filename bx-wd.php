<?php

require_once __DIR__.'/func.php';
require_once __DIR__.'/bx-api.php';

//$bx_key = 'abc'; // bittrex api key for withdrawal
//$bx_secret = 'abc; // api secret

//$addr = '3JDxVzSioDJ2NrMAcw1jzS6uTS8hEE8He7'; // withdrawal address

if ( empty($addr) ) { echo 'addr:'; $addr = input(FALSE); }

$bx_day_limit = 0.4; // day limit withdrawal (https://support.bittrex.com/hc/en-us/articles/231701788-Withdraw-Limits-and-Troubleshooting)
$bx_min_limit = 0.1; // min withdrawal per transaction
$bx_limit_step = 0.05; // withdraw step
$sleep = 600;

while ( TRUE ) {

  $bx_bal = bittrex_query('account/getbalances', NULL);
  foreach ( $bx_bal as $item ) if ( $item['Currency'] == 'BTC' ) { $bx_bal = $item['Available']; break; }
  // {"Currency":"BTC","Balance":0.5,"Available":0.5,"Pending":0,"CryptoAddress":"123"}

  echo '['.date('Y-m-d H:i:s').'] bal bx:'.cc6($bx_bal).PHP_EOL;

  for ( $limit = $bx_day_limit; $limit >= $min_limit; $limit-= $limit_step ) {
    $wd = round($limit, 8);
    if ( $bx_bal <= $wd - $limit_step ) continue;
    if ( $bx_bal < $wd ) $wd = $bx_bal;
    if ( $wd < $min_limit ) break;

    $resp = bittrex_query('account/withdraw', ['currency' => 'BTC', 'quantity' => $wd, 'address' => $address]);
    // {"success":false,"message":"WITHDRAWAL_LIMIT_REACHED_24H_BASIC","result":null}
    // {"uuid":"824e1f61-eb1c-4add-877c-2889fd4b8ecd"}

    if ( isset($resp['success']) && $resp['success'] == FALSE && $resp['message'] == 'WITHDRAWAL_LIMIT_REACHED_24H_BASIC' ) continue; // limit reached

    $log = '['.date('Y-m-d H:i:s').'] '.'wd:'.$wd.' '.json_encode($resp).PHP_EOL;
    echo $log;
    if ( $wd ) file_put_contents(__DIR__.'/bx-wd.log', $log, FILE_APPEND | LOCK_EX);
    break;
  }

  sleep($sleep);
}

//-
