<?php

plugin_require(array('url'));

$areas = hook_do('plugin/auth/areas');

current_url();

$url = var_get('site/url/path');

if( is_array($areas) ){
	foreach ($areas as $realm => $area) {
		foreach ($area['routes'] as $route) {
			if( preg_match("#^" . $route . "$#", $url, $m) ){

				if( !isset($area['method']) || $area['method'] === 'basic' ){
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
						header('HTTP/1.0 401 Unauthorized');
						header('WWW-Authenticate: Basic realm="' . $realm);
						
						print isset($area['authMessage']) ? $area['authMessage'] : 'HTTP/1.0 401 Unauthorized';
						die;
					}
				}elseif( $area['method'] === 'digest' ){

					$requireLogin = function ($realm, $nonce, $msg = null){
						header('WWW-Authenticate: Digest realm="'.$realm.'",qop="auth",nonce="'.$nonce.'",opaque="'.md5($realm).'"');
						header('HTTP/1.1 401 Unauthorized');
						
						print is_string($msg) ? $msg : 'HTTP/1.0 401 Unauthorized';
						die;
					};

					$digest = '';
					if( isset($_SERVER['PHP_AUTH_DIGEST']) ){
						$digest = $_SERVER['PHP_AUTH_DIGEST'];
					}elseif( isset($_SERVER['HTTP_AUTHORIZATION']) && strpos(strtolower($_SERVER['HTTP_AUTHORIZATION']), 'digest') === 0){
						$digest = trim(substr($_SERVER['HTTP_AUTHORIZATION'], 6));
					}

					if( !$digest ){
						$requireLogin($realm, uniqid(), isset($area['authMessage']) ? $area['authMessage'] : null);
					}

					$parts = array('nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1);
					$keys = implode('|', array_keys($parts));

					preg_match_all('#(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))#', $digest, $matches, PREG_SET_ORDER);

					$data = array();
					foreach ($matches as $m) {
						$data[$m[1]] = $m[3] ? $m[3] : $m[4];
						unset($parts[$m[1]]);
					}
					if( $parts || !array_key_exists($data['username'], $area['accounts']) ){
						$requireLogin($realm, uniqid(), isset($area['authMessage']) ? $area['authMessage'] : null);
					}

					// Generate response
					$A1 = md5($data['username'] . ':' . $realm . ':' . $area['accounts'][$data['username']]);
					$A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
					$response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);

					if( $data['response'] !== $response ){
						$requireLogin($realm, uniqid(), isset($area['authMessage']) ? $area['authMessage'] : null);
					}
				}
			}
		}	
	}
}	