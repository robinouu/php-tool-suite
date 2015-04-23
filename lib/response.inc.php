<?php
/**
 * Response
 * @package php-tool-suite
 * @subpackage response
 */

function response_route($route = '/', $callback = null){
	$url = request_url();
	$path = isset($url['path']) ? $url['path'] : '';

	if( preg_match("#^" . $route . "$#ui", $path, $m) ){
		ob_start();
		$callback($m);
		$content = ob_get_contents();
		ob_end_clean();
		print $content;
		var_set('routeFound', true);
		return true;
	}
	return false;
}


function response_no_route($callback) {
	if( !var_get('routeFound', false) ){
		$callback();
	}
}

function response_code($code = 200) {
	if( function_exists('http_response_code') ){
		http_response_code($code);
	}else{
		response_header('ResponseCode', $code, true);
	}
}

function response_header($property, $value, $replace = true){
	if( is_array($value) ){
		$values = array();
		foreach ($value as $key => $value) {
			if( is_numeric($key) ){
				$values[] = $value;
			}else{
				$values[] = $key . '=' . (string)$value;
			}
		}
		$value = implode('; ', $values);
	}
	header(ucfirst($property) . ': ' . $value, $replace);
}

