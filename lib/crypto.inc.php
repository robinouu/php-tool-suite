<?php

require_once('var.inc.php');

if( !var_get('crypto/iv') ){
	if( extension_loaded('mcrypt') ){
		var_set('crypto/iv', $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_RAND));
	}
}

seed();

function seed($seed = null) {
	if( !var_get('crypto/seedInitialized') ){
		if( !$seed ){
			$seed = (double) microtime() * 1000000;
		}
		srand($seed);
		var_set('crypto/seedInitialized', true);
	}
}

function generate_key($key) {
	return hash("SHA256", $key, true);
}

function encrypt(&$content, $key){
	$content = rtrim(base64_encode($back = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $content, MCRYPT_MODE_CBC, var_get('crypto/iv'))), "\0\3");
	return (bool)$back;
}

function decrypt(&$content, $key) {
	$back = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, base64_decode($content), MCRYPT_MODE_CBC, var_get('crypto/iv'));
	$content = rtrim($back, "\0\3");
	return (bool)$back;
}


?>