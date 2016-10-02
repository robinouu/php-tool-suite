<?php
/**
 * Random
 * @package php-tool-suite
 * @subpackage Random
 */

if (version_compare(phpversion(), '4.2.0', '<') ) {
	mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
}

function frand($min, $max, $decimals = 2) {
	$scale = pow(10, $decimals);
	return mt_rand($min * $scale, $max * $scale) / $scale;
}