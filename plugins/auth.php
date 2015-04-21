<?php

plugin_require(array('url'));

$areas = hook_do('plugin/auth/areas');

current_url();

$url = var_get('site/url/path');

if( is_array($areas) ){
	foreach ($areas as $key => $area) {
		foreach ($area['routes'] as $route) {
			if( preg_match("#^" . $route . "$#", $url, $m) ){

				$username = $password = null;
				if( isset($_SERVER['PHP_AUTH_USER']) ){
					$username = $_SERVER['PHP_AUTH_USER'];
					if( isset($_SERVER['PHP_AUTH_USER']) ){
						$password = $_SERVER['PHP_AUTH_PW'];
					}
				}elseif( isset($_SERVER['HTTP_AUTHORIZATION']) && strpos(strtolower($_SERVER['HTTP_AUTHORIZATION']), 'basic') === 0){
					list($username, $password) = explode(':', base64_decode(trim(substr($_SERVER['HTTP_AUTHORIZATION'], 5))));
				}
				
				if( is_null($username) || !array_key_exists($username, $area['accounts']) || $password !== $area['accounts'][$username] ){
					header('WWW-Authenticate: Basic realm="' . $r['area']);
					header('HTTP/1.0 401 Unauthorized');

					print isset($area['authMessage']) ? $area['authMessage'] : 'HTTP/1.0 401 Unauthorized';
					die;
				}
			}
		}	
	}
}