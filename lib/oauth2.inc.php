<?php
/**
 * @package php-tool-suite
 * @subpackage OAuth2 authentication
 */

plugin_require(array('request', 'response'));

function oauth_connect($options = array()) {
	$options = array_merge(array(
		'authType' => 'uri',
		'clientID' => '',
		'certificate' => null,
		'redirectTo' => request_url(),
		'parameters' => array()
	), $options);

	$params = array_merge(array(
		'response_type' => 'code',
		'client_id' => $options['clientID'],
		'redirect_uri' => $options['redirectTo'],
	), $options['parameters']);

	$authURL = $options['authEndPoint'] . '?' . http_build_query($options['parameters'], null, '&');
	
	redirect($authURL);
}

function oauth_token(&$options = array()) {
	$options = array_merge(array(
		'code' => $_GET['code'],
		'redirectTo' => request_url(),
		'parameters' => array()
	), $options);

	$params = array_merge(array(
		'client_id' => $options['parameters']['client_id'],
		'client_secret' => isset($options['clientSecretKey']) ? $options['clientSecretKey'] : '',
		'redirect_uri' => $options['parameters']['redirect_uri'],
		'grant_type' => 'authorization_code',
		'code' => isset($_GET['code']) ? $_GET['code'] : ''
	), $options['parameters']);

	$endPointURL = $options['tokenEndPoint'];

	$res = oauth_request($endPointURL, $params);

	return $res;
}

function oauth_fetch($url, $options) {

	$options = array_merge(array(
		'token_type' => '',
		'http_method' => 'GET',
		'http_headers' => array(),
		'params' => array(),
	), $options);

	$headers = array();

	$options['params']['access_token'] = $options['access_token'];

	return oauth_request($url, $options['params'], $options['http_method'], $options['http_headers']);
}

function oauth_signature($url, $params, $clientID, $clientSecret, $http_method = 'POST' ) {
	foreach ($params as $key => $value) {
		$params[$key] = rawurlencode($value);
	}
	ksort($params);
	$parameters = array();
	foreach ($params as $key => $value) {
		$parameters[] = $key . '=' . $value;
	}
	$signature = strtoupper($http_method);
	$signature .= '&' . rawurlencode($url) . '&' . rawurlencode(implode('&', $parameters));
	
	if( !$clientID && $clientSecret ){
		$consumer = rawurlencode($clientSecret) . '&';
	}elseif( $clientID ){
		$consumer = rawurlencode($clientID) . '&' . rawurlencode($clientSecret);
	}

	$hmac = base64_encode(hash_hmac('sha1', $signature, $consumer, true));
	return $hmac;
}
function oauth_request($url, $params = array(), $verb = 'POST', $headers = array()) {

	if( $params ){
		$params = http_build_query($params, null, '&');
	}
	
	$verb = strtoupper($verb);
	if( $verb === 'GET' && sizeof($params) ){
		$url .= '?' . $params;
	}

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, 'OAuth2 Client');

	if( is_array($headers) ){
		$curl_headers = array();
		foreach( $headers as $key => $value) {
			$curl_headers[] = $key . ': ' . $value;
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
	}
	
	if( $verb === 'POST' ){
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	}

	$result = curl_exec($ch);

	curl_close($ch);

	if( ($jsonData = json_decode($result, true)) ){
		return $jsonData;
	}
	return $result;
}

function oauth_result() {
	
	return $res;
}