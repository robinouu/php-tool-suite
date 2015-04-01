<?php

// tiny version of sanitize_title from WP
function slug( $str ) {
	$str = strip_tags($str);
	$str = mb_strtolower($str, 'UTF-8');
	$str = strtolower($str);
	$str = preg_replace('/&.+?;/', '', $str); // kill entities
	$str = str_replace('.', '-', $str);

	// Convert nbsp, ndash and mdash to hyphens
	$str = str_replace( array( '%c2%a0', '%e2%80%93', '%e2%80%94' ), '-', $str );

	// Strip these characters entirely
	$str = str_replace( array(
		// iexcl and iquest
		'%c2%a1', '%c2%bf',
		// angle quotes
		'%c2%ab', '%c2%bb', '%e2%80%b9', '%e2%80%ba',
		// curly quotes
		'%e2%80%98', '%e2%80%99', '%e2%80%9c', '%e2%80%9d',
		'%e2%80%9a', '%e2%80%9b', '%e2%80%9e', '%e2%80%9f',
		// copy, reg, deg, hellip and trade
		'%c2%a9', '%c2%ae', '%c2%b0', '%e2%80%a6', '%e2%84%a2',
		// acute accents
		'%c2%b4', '%cb%8a', '%cc%81', '%cd%81',
		// grave accent, macron, caron
		'%cc%80', '%cc%84', '%cc%8c',
	), '', $str );

	// Convert times to x
	$str = str_replace( '%c3%97', 'x', $str );

	$str = preg_replace('/[^%a-z0-9 _-]/', '', $str);
	$str = preg_replace('/\s+/', '-', $str);
	$str = preg_replace('|-+|', '-', $str);
	$str = trim($str, '-');

	return $str;
}


function server_is_secure() {
	return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
}
