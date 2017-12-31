<?php

require_once __DIR__.'/func.php';

$bx_key = FALSE;
$bx_secret = FALSE;

function bx_get($path, $query=[], $retries=0) { // public api methods
  global $bx_secret;
  if ( !$bx_secret ) { echo 'bx-secret:'; $bx_secret = input(FALSE); }
  $path = trim($path, '/ ');
  $post = FALSE;
  $headers = NULL;
  if ( !is_array($query) ) $query = [];
  if ( isset($query['apikey']) ) {
    $query['nonce'] = time();
    $headers = array('apisign:'.hash_hmac('sha512', $url, $bx_secret));
  }
  $url = 'https://bittrex.com/api/v1.1/'.$path.( !empty($query) ? '?'.http_build_query($query, '', '&') : '' );
  $bx_ch = curl_init();
  curl_setopt($bx_ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($bx_ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; Bittrex PHP bot; '.php_uname('a').'; PHP/'.phpversion().')');
  curl_setopt($bx_ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($bx_ch, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($bx_ch, CURLOPT_TIMEOUT, 9);
  curl_setopt($bx_ch, CURLOPT_URL, $url);
  if ( $post ) {
    curl_setopt($bx_ch, CURLOPT_POST, TRUE);
    curl_setopt($bx_ch, CURLOPT_POSTFIELDS, $post);
  }
  if ( $headers ) curl_setopt($bx_ch, CURLOPT_HTTPHEADER, $headers);
  if ( ($response = curl_exec($bx_ch)) === FALSE || !($data = json_decode($response, TRUE)) ) {
    curl_close($bx_ch);
    echo __FUNCTION__.' '.$path.' '.json_encode($query).' failed:'.json_encode($response).PHP_EOL;
    if ( $retries > 0 ) { $retries--; return bx_get($path, $query, $retries); }
    return FALSE;
  } else curl_close($bx_ch);
  if ( isset($data['success']) && $data['success'] && isset($data['result']) ) return $data['result'];
  return $data;
}
function bx_query($path, $query=[], $retries=0) { // private api methods
  global $bx_key;
  if ( !$bx_key ) { echo 'bx-key:'; $bx_key = input(FALSE); }
  if ( !is_array($query) ) $query = [];
  $query['apikey'] = $bx_key;
  return bx_get($path, $query, FALSE, $retries);
}

//

function bx_tickers() { return bx_get('public/getmarketsummaries'); }
function bx_depth($market='BTC-LTC', $type='both', $depth=50) { return bx_get('public/getorderbook', ['market' => $pair, 'type' => 'both', 'depth' => 50]); }

//

function bx_price_pair($pair, $type='sell', $volume=0) {
  if ( !($depth = bx_get('public/getorderbook', ['market' => $pair, 'type' => 'both', 'depth' => 100])) ) return $depth;
  if ( strtolower($type) == 'sell' ) $data = $depth['buy']; else $data = array_reverse($depth['sell']);
  if ( empty($data) ) { echo '! bx_price_pair empty depth:'.json_encode($depth).PHP_EOL; return 0; }
  if ( !$volume ) return $data[0]['Rate'];
  $vol = $volume; $summ = 0;
  foreach ( $data as $item ) {
    $summ+= min($vol, $item['Quantity']) * $item['Rate'];
    $vol-= min($vol, $item['Quantity']);
    if ( $vol <= 0 ) break;
  }
  return $summ / ($volume - $vol);
}
function bx_prices($coin, $type='sell', $volume=0) {
  $prices = array();
  $coin = strtoupper($coin);
  $pair = 'BTC-'.$coin;
  $tickers = bx_tickers();
  $exist = FALSE; foreach ( $tickers as $item ) if ( $item['MarketName'] == $pair ) { $exist = TRUE; break; }
  if ( $exist ) $prices[$pair] = bx_price_pair($pair, $type, $volume);
  else echo '- bx_prices failed pair:'.$pair.PHP_EOL;
  return $prices;
}
function bx_best_price($coin, $type='sell', $volume=0) {
  $prices = bx_prices($coin, $type, $volume);
  return ( count($prices) > 1 ? max($prices) : reset($prices) );
}

//-
