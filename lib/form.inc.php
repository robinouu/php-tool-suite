<?php

require_once('html.inc.php');

function form($content, $attrs = array()) {
	$html = '<form method="POST" ' . attrs($attrs) . '>';
	$html .= $content;
	//$html .= tag('input', '', array('type' => 'hidden', 'name' => $id, 'value' => true));
	$html .= '</form>';
	return $html;
}

function form_errors($errors) {
	$c = array();
	foreach ($errors as $key => $errs) {
		$c[] = implode(br(), $errs);
	}
	return tag('div', implode(br(), $c), array('class' => 'msg msg-error'));
}

function fieldset($name = '', $content = ''){
	$html = '<fieldset><legend>' . htmlspecialchars($name) . '</legend>';
	$html .= $content;
	$html .= '</fieldset>';
	return $html;
}

function field($args = array(), $prefix = '') {
	return scrud_field(null, $args['name'], $args, $prefix);
}


function validate_field($field, $value = '', &$data = null, $prefix = ''){
	$defaultSqlField = var_get('sql/defaultField', array());
	$field = array_merge($defaultSqlField, $field);
	$errors = array();

	/*$field = array_merge(var_get('sql/defaultField', array()), $field);
	$field = array_merge($field, array('value' => $value));
	if( $field['required'] && (is_null($value) || $value === '') ){
		$errors[$field['name']] = 'required';
	}*/
	//scrud_validate(null, $field)
	$key = isset($field['name']) && is_string($field['name']) ? $field['name'] : uniqid();
	$pkey = trim($prefix) != '' ? $prefix . $key : $key;

	$errors[$pkey] = array();
	
	$d = isset($field['value']) ? $field['value'] : (isset($field['default']) ? $field['default'] : null);
	//is_null($value) ? (isset($field['default']) ? $field['default'] : null) : $value;

	if( $field['type'] !== 'relation' || ($field['type'] === 'relation' && (
			!isset($datas['meta_newone_'.$pkey]) || (isset($datas['meta_newone_'.$pkey]) && (int)$datas['meta_newone_'.$pkey] !== 1)
			)) ) {
		if (($field['required'] && !isset($field['default']) && trim($d) == '')) {
			$errors[$pkey][] = scrud_get_error_message($key, $field, 'required');
		}else if( $field['required'] && (is_null($d) || $d == '') ){
			$errors[$pkey][] = scrud_get_error_message($key, $field, 'required');
		}
	}

	switch ($field['type']) {
		case 'phone':
			if( $field['required'] && !preg_match('#\+(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d|2[98654321]\d|9[8543210]|8[6421]|6[6543210]|5[87654321]|4[987654310]|3[9643210]|2[70]|7|1)\d{1,14}$#', $d)){
				$errors[$pkey][] = scrud_get_error_message($key, $field);
			}
		break;
		case 'text':
		case 'password':
		case 'email':
			if( $field['required'] && isset($field['maxlength']) && (int)$field['maxlength'] > 0 && (int)$field['maxlength'] !== -1){
				if( strlen($d) > (int)$field['maxlength'] ){
					$errors[$pkey][] = scrud_get_error_message($key, $field, 'maxlength'); //'Le champ "' . $field['label'] . '" ne peut pas comporter plus de ' . $field['maxlength'] . ' caractères';
				}
			}
			if( isset($field['minlength']) && (int)$field['minlength'] > 0 ){
				if( strlen($d) < (int)$field['minlength'] ){
					$errors[$pkey][] = scrud_get_error_message($key, $field, 'minlength'); //'Le champ "' . $field['label'] . '" ne peut pas comporter moins de ' . $field['minlength'] . ' caractères';
				}
			}
			if( $field['required'] && $field['type'] === 'email' ){
				if( !filter_var($d, FILTER_VALIDATE_EMAIL) ) {
					$errors[$pkey][] = scrud_get_error_message($key, $field, 'invalid_email'); //'L\'adresse email est invalide.';
				}
			}
				

			break;
		case 'relation':
			if( isset($datas['meta_newone_'.$pkey]) && (int)$datas['meta_newone_'.$pkey] === 1){
				$d = null;
				$child_datas = array();
				$child_id = null;

				$hasOne = false;
				$relData = $database[$field['data']]['fields'];

				$requiredChildren = $field['required'];
				foreach ($relData as $relKey => $relField) {
					$relField = array_merge($defaultSqlField, $relField);
					$value = isset($datas[$pkey . '_' . $relKey]) ? $datas[$pkey . '_' . $relKey] : null;
				
					if( sql_quote($value) !== 'NULL' ){
						$requiredChildren = false;
						$child_datas[$pkey . '_' . $relKey] = $value;
					}
				}
				foreach ($datas as $k => $v){
					if( substr($k, 0, strlen('meta_')) === 'meta_'){
						$child_datas[$k] = $v;
					}
				}

				if( $requiredChildren ){
					$errors[$pkey][] = scrud_get_error_message($key, $field, 'required');
				}
				else{
					//var_dump($child_datas, '<hr/>');
					$validation = scrud_validate($field['data'], $child_datas, $d, $pkey . '_');
					if( !$validation['valid'] ){
						$errors += $validation['errors'];
					}else{
						$back = array_merge_recursive($back, $validation['data']);
					}
				}
			}else if( isset($datas[$pkey]) ) {
				$back[$pkey] = $datas[$pkey];
			}
		break;
		default:
			# code...
			break;
	}

	if( (bool)$field['unique'] === true && $d && (!isset($datas['meta_newone_'.$pkey]) || (int)$datas['meta_newone_'.$pkey] !== 1 ) ){//&& ($datas['meta_mode'] !== 'edit') ){

		$query = sql_select('data_'.$dataName, 'id') . ' WHERE ' . sql_quote($key, true) . ' = ' . sql_quote($d);
		if( $id > 0 ){
			$query .= ' AND id <> ' . $id;
		}
		$query .= ' LIMIT 1';
		//var_dump($query);
		$exists = sql_query($query);
		if( $exists ){
			$errors[$pkey][] = scrud_get_error_message($key, $field, 'unique');
		}
	}

	if( !sizeof($errors[$pkey]) ){
		unset($errors[$pkey]);
	}

	if( $field['type'] === 'relation' ) {
		if( isset($validation) ){
			if( $validation['valid'] ){
				//var_dump($validation);
				$back = array_merge_recursive_unique($back, $validation['data']);
			}
		}elseif (isset($d)) {
			$back[$pkey] = $d;
		}
	}else{
		$back[$pkey] = $d ? $d : null;
	}

	$valid = !sizeof($data['errors']);

	if( is_array($data) ){
		if( !isset($data['errors']) ){ 
			$data['errors'] = array();
		}
		$data['errors'] = array_merge($data['errors'], $errors);
		if( !isset($data['data']) ){ 
			$data['data'] = array();
		}
		$data['data'] = array_merge($data['data'], $back);
	}

	return $valid;
}

function validate_fields($fields, $values = null, &$data = null) {
	$success = false;
	// try default request values
	if( !$values ){
		$values = $_REQUEST; 
	}


	$data = array();
	foreach ($fields as $fieldName => $field) {
		$field['name'] = $fieldName;
		$back = array();

		//var_dump($values[$fieldName]);
		validate_field($field, isset($values[$fieldName]) ? $values[$fieldName] : (isset($field['value']) ? $field['value'] : (isset($field['default']) ? $field['default'] : null) ), $back);
		//var_dump($back);
		$data = array_merge_recursive($data, $back);
	}
	//$data = $back;

	return !sizeof($data['errors']);
}

?>