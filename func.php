<?php

function input($echo=TRUE) {
  if ( !$echo ) {
    $oldStyle = shell_exec('stty -g'); // get current style
    shell_exec('stty -echo');
  }
  $password = rtrim(fgets(STDIN), "\r\n");
  if ( !$echo ) {
    shell_exec('stty ' . $oldStyle); // reset old style
    echo '***'.PHP_EOL;
  }
  return $password;
}

//-
