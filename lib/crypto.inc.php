<?php
/**
 * Crypto
 * @package php-tool-suite
 * @subpackage Cryptography
 */

plugin_require(array('var', 'random'));

/**
 * Generates a SHA256 key
 * @param string $key Key to generate from
 * @return string a SHA256 key.
 */
function generate_key($key) {
	return hash("SHA256", $key, true);
}

/**
 * Encrypts the content using a 256 bits key
 * @param string &$content The content to encrypt (reference)
 * @param string $key The key to use. You should use generate_key() for this purpose.
 * @return bool TRUE if the encryption succeed, FALSE otherwise.
 */
function encrypt(&$content, $key){
	if( !extension_loaded('mcrypt') ){
		return false;
	}
	$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_RAND);
	$encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $content, MCRYPT_MODE_CBC, $iv);
	$content = $iv . rtrim(base64_encode($encrypted), "\0\3");
	return (bool)$encrypted;
}

/**
 * Decrypts the content using a 256 bits key.
 * @param string &$content The content to decrypt
 * @param string $key The key to use. You should use generate_key() for this purpose.
 * @return bool TRUE if the encryption succeed, FALSE otherwise.
 */
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