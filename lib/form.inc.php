<?php
/**
 * Forms
 * @package php-tool-suite
 * @subpackage Forms
 */

/**
 * Creates a token for a specified form
 * @param string $tokenName The name of the token. Can be the form name for example.
 * @return string The token
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

/**
 * Checks if the token is valid and has not expired
 * @param string $tokenName The token name. Can be the form name for example.
 * @param int $timeout Maximum time for token validation.
 * @param string|null $csrf The CRSF token that comes with the HTTP request.
 * @return bool TRUE if the token is valid, FALSE otherwise.
 * @subpackage Forms
 */
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

/**
 * Crud doit avoir la possibilité d'afficher un formulaire
 * avec sous-données (dans un select) ou ajouter nouvelle
 * 
 * Les erreurs sont renvoyées en PHP ou un état de succès de l'ajout/édition du formulaire
 * 
 * Une validation et un enregistrement des données est faite en SQL.
 * Un trigger est fait avant validation et après validation 
 * /
?>