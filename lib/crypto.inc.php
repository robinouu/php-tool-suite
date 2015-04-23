<?php
/**
 * Crypto
 * @package php-tool-suite
 */
require_once('var.inc.php');
require_once('random.inc.php');

if( !var_get('crypto/iv') ){
	if( extension_loaded('mcrypt') ){
		var_set('crypto/iv', $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_RAND));
	}
}

function generate_key($key) {
	return hash("SHA256", $key, true);
}

function encrypt(&$content, $key){
	if( !var_get('crypto/iv') ){
		return false;
	}
	$encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $content, MCRYPT_MODE_CBC, var_get('crypto/iv'));
	$content = rtrim(base64_encode($encrypted), "\0\3");
	return (bool)$encrypted;
}

function decrypt(&$content, $key) {
	if( !var_get('crypto/iv') ){
		return false;
	}
	$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, base64_decode($content), MCRYPT_MODE_CBC, var_get('crypto/iv'));
	$content = rtrim($decrypted, "\0\3");
	return (bool)$decrypted;
}

?>