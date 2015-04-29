<?php
/**
 * Crypto
 * @package php-tool-suite
 */
require_once('var.inc.php');
require_once('random.inc.php');

function generate_key($key) {
	return hash("SHA256", $key, true);
}

function encrypt(&$content, $key){
	if( !extension_loaded('mcrypt') ){
		return false;
	}
	$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_RAND);
	$encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $content, MCRYPT_MODE_CBC, $iv);
	$content = $iv . rtrim(base64_encode($encrypted), "\0\3");
	return (bool)$encrypted;
}

function decrypt(&$content, $key) {
	if( !extension_loaded('mcrypt') ){
		return false;
	}
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
	$iv = substr($content, 0, $iv_size);
	$to_decrypt = substr($content, $iv_size);
	$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, base64_decode($to_decrypt), MCRYPT_MODE_CBC, $iv);
	$content = rtrim($decrypted, "\0\3");
	return (bool)$decrypted;
}

?>