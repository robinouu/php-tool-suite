<?php
/**
 * Response
 * @package php-tool-suite
 * @subpackage response
 */

function redirect($url) {
	header('Location: ' . $url);
	exit;
}

function response_route($route = '/', $callback = null, $verbs = null){
	$url = request_url(true);
	$path = isset($url['path']) ? $url['path'] : '';

	if( is_string($verbs) ){
		$verbs = array($verbs);
	}
	if( (is_null($verbs) || in_array($_SERVER['REQUEST_METHOD'], $verbs)) && preg_match("#^" . $route . "$#ui", $path, $m) ){
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

function response_download_bytes($filename, $bytes = null) {
	if( $filename ){
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . $filename);
		header('Content-Length: ' . strlen($bytes));

		print $bytes;
		exit;
	}
	return false;
}

function response_download_file($filePath = null, $filename = null) {
	if( $filePath ){
		if( !$filename ){
			$filename = basename($filePath);
		}
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . $filename);
		header('Content-Length: ' . filesize($filePath));

		readfile($filePath);
		exit;
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


function response_throttler_start($options = array()) {
	if(function_exists('apache_setenv')) {
		// disable gzip HTTP compression so it would not alter the transfer rate
		apache_setenv('no-gzip', '1');
	}
	@set_time_limit(0);

	$downloadRate = 1024*256; // 256k connection by default
	$now = microtime(true);

	$options = array_merge(array(
		'downloadRate' => $downloadRate,
		'burstRate' => $downloadRate*8,
		'burstTimeout' => 15,
		'burstSize' => 5000,

	), $options);

	$options = array_merge($options, array(
		'initTime' => $now,
		'bytesSent' => 0,
		'currentRate' => $options['burstRate']
	));

	var_set('throttler/enabled', TRUE);

	ob_start(function ($buffer) use (&$options) {

		if( $buffer === '' || var_get('throttler/enabled', FALSE) === FALSE ) {
			return $buffer;
		}

		$bufferLength = strlen($buffer);
		
		$usleep = $bufferLength / $options['currentRate'];
		if($usleep > 0) {
			usleep($usleep * 1000000);
		}

		if( $options['currentRate'] === $options['burstRate'] && $options['burstRate'] !== $options['downloadRate'] ) {
			if( $options['burstSize'] ) {
				if($options['burstSize'] < $options['bytesSent'] + $bufferLength) {
					$options['currentRate'] = $options['downloadRate'];
				}
			}else{
				if( $options['initTime'] > ($options['initTime'] + $options['burstTimeout']) ) {
					$options['currentRate'] = $options['downloadRate'];
				}
			}
		}

		$options['bytesSent'] += $bufferLength;

		return $buffer;

	}, $options['downloadRate']);
}

function response_throttler_end() {
	var_set('throttler/enabled', FALSE);
	$c = ob_get_contents();
	ob_end_clean();
	return $c;
}
