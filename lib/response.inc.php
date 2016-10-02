<?php
/**
 * HTTP Responses
 * 
 * Routing is the act of redirecting an HTTP url when matching a specific route.
 * You can use the 'route' method to match one like this :
 * 
 * ```php
 * route('/products/(.*)', function ($req) {
 * 	$slug = $req[1];
 *  [...]
 * });
 * ```
 * 
 * If you want to catch the case when no route have been found.
 * 
 * ```php
 * response_noroute(function() {
 * 	response_code(404);
 * });
 * ```
 * 
 * Or simply redirects to another URL :
 *  
 * ```php
 * redirect('/products');
 * ```
 * &nbsp;
 * @package php-tool-suite
 * @subpackage HTTP response
 */

plugin_require(array('var'));

/**
 * Redirects a user immediately to another page
 * @param string $url The URL to look for.
 * @subpackage HTTP response
 */
function redirect($url) {
	header('Location: ' . $url);
	exit;
}

/**
 * Follows a specific HTTP route
 * @param string $route The URI route to follow.
 * @param callable|null $callback a callback to follow when the route has been found.1
 * @param array|null $verbs The HTTP method to filter for (GET, POST, PUT, ...), null by default
 * @return bool TRUE if the route has been found, FALSE otherwise.
 * @subpackage HTTP response
 */
function route($route = '/', $callback = null, $verbs = null){
	return response_route($route, $callback, $verbs);
}

/**
 * @see route
 * @subpackage HTTP response
 */
function response_route($route = '/', $callback = null, $verbs = null){
	
	plugin_require('request');

	$path = request_route();
	if( is_string($verbs) ){
		$verbs = array($verbs);
	}

	if( (is_null($verbs) || in_array($_SERVER['REQUEST_METHOD'], $verbs)) && preg_match("#^" . $route . "$#ui", $path, $m) ){

		on('routing', function () use ($callback, $m) {
			ob_start();
			if( is_callable($callback) ){
				$callback($m);
			}elseif( is_string($callback) ){
				$callback = include($callback);
				if( is_callable($callback) ){
					$callback($m);
				}else{
					print (string)$callback;
				}
			}
			$content = ob_get_contents();
			ob_end_clean();
			print $content;
		});

		var_set('routeFound', true);
		return true;
	}

	return false;
}

/**
 * Downloads content as a file
 * @param string $filename The filename of the downloaded file
 * @param mixed $bytes The content to download
 */
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

/**
 * Downloads a specific file
 * @param string $filePath The filepath on the server to download (unsecure)
 * @param string $filename The filename of the downloaded file
 * @return type
 */
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

/**
 * Called when no response has been found before.
 * @param callable $callback The callback to call for.
 * @subpackage HTTP response
 */
function response_no_route($callback) {
	if( !var_get('routeFound', false) ){
		$callback();
	}
}

/**
 * Sets a response code for the HTTP response
 * @param int $code The response code
 * @subpackage HTTP response
 */
function response_code($code = 200) {
	if( function_exists('http_response_code') ){
		http_response_code($code);
	}else{
		response_header('ResponseCode', $code, true);
	}
}

/**
 * Sets a response header for the HTTP response
 * @param string $property 
 * @param string $value 
 * @param bool $replace Erases the old header if set to TRUE.
 * @subpackage HTTP response
 */
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


/**
 * Throttles the HTTP response (generally used by clients to download big files)
 * @param array $options
 * <ul>
 * 	<li>downloadRate int The byterate of the throttle : 256*1024 by default (256kb).</li>
 *  <li>burstRate int The burst rate of the throttle : 256*8 by default</li>
 *  <li>burstTimeout int max time for the burst : 15</li>
 *  <li>burstSize int max burst size : 5000</li>
 * </ul>
 * @subpackage HTTP response
 * 
 */
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

/**
 * Ends the content that will be throttle 
 * @return string the throttled content
 * @subpackage HTTP response
 */
function response_throttler_end() {
	var_set('throttler/enabled', FALSE);
	$c = ob_get_contents();
	ob_end_clean();
	return $c;
}
