<?php

include_once(__DIR__ . '/../underscore.php');


function debug($str, $color='Blue') {
  $colors = [
    'Black'       => "\033[0;30m",
    'Blue'        => "\033[0;34m",
    'Green'       => "\033[0;32m",
    'Cyan'        => "\033[0;36m",
    'Red'         => "\033[0;31m",
    'Purple'      => "\033[0;35m",
    'Brown'       => "\033[0;33m",
    'LightGray'   => "\033[0;37m",
    'DarkGray'    => "\033[1;30m",
    'LightBlue'   => "\033[1;34m",
    'LightGreen'  => "\033[1;32m",
    'LightCyan'   => "\033[1;36m",
    'LightRed'    => "\033[1;31m",
    'LightPurple' => "\033[1;35m",
    'Yellow'      => "\033[1;33m",
    'White'       => "\033[1;37m",
    'close'       => "\033[m\n"
  ];

  $color = array_key_exists($color, $colors) ? $colors[$color] : $colors['Cyan'];
  $output = (is_array($str) || is_object($str)) ? print_r($str, true) : $str;
  echo "\n". $color . $output . $colors['close'];
}
