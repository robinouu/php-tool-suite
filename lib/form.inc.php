<?php
/**
 * Forms
 * @package php-tool-suite
 * @subpackage Forms
 */

function form_token($tokenName) {
	$token = array(
		'time' => time(),
		'salt' => sha1(uniqid().time()),
		'sessid' => session_id(),
		'ip' => $_SERVER['REMOTE_ADDR']
	);
	
	session_var_set('csrf_token/'.$tokenName, $token);

	$hash = object_hash($token);
	return base64_encode($hash);
}

function form_token_validate($tokenName, $timeout = 300, $csrf = null) {
	$token = session_var_get('csrf_token/' . $tokenName);
	if( $token ){
		if( $_SERVER['REQUEST_TIME'] - $token['time'] < $timeout ){
			if( session_id() ){
				if( !$csrf ){
					$csrf = $_REQUEST['csrf'];
					$tokenHash = base64_decode($csrf);
					return object_hash($token) === $tokenHash;
				}
			}
		}
	}
	return FALSE;
}
?>