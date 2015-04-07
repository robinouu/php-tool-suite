<?php

require_once('i18n.inc.php');

function field($field = array()) {
	$defaultSqlField = var_get('sql/defaultField');
	$database = var_get('sql/schema');
	$prefix = var_get('sql/prefix');

	$field = array_merge($defaultSqlField, $field);

	$html = '';
	$id = isset($field['id']) ? $field['id'] : 'input-'.$prefix.$fieldName;
	$fieldName = isset($field['name']) ? $field['name'] : $prefix.$fieldName;

	if( isset($field['value']) ){
		$value = $field['value'];
	}else{
		$value = isset($field['default']) ? $field['default'] : null;
	}

	switch ($field['type']) {
		case 'text':
		case 'hidden':
		case 'password':
		case 'date':
		case 'datetime':
		case 'search':
		case 'float':
		case 'double':
		case 'int':
			$isTextArea = false;
			if( $field['type'] === 'text' && isset($field['maxlength']) && $field['maxlength'] > 255){
				$id = !$field['id'] ? $field['id'] : 'textarea-' . $fieldName;
				$isTextArea = true;
				$html .= '<textarea name="' . $fieldName . '" id="' . $id . '" role="textbox" aria-multiline="true" ';
			}else{
				$fieldType = $field['type'];
				if( $fieldType === 'float' || $fieldType === 'double' ){
					$html .= '<input type="number" step="any" name="' . $fieldName . '" id="' . $id . '" ';
				}elseif ($fieldType === 'int' ){
					$html .= '<input type="number" name="' . $fieldName . '" id="' . $id . '" ';
				}else{
					$html .= '<input type="' . $fieldType . '" name="' . $fieldName . '" id="' . $id . '" ';
				}
			}

			if( !in_array($field['type'], array('int','float','double')) ){
				$html .= (isset($field['maxlength']) && is_int($field['maxlength'])) ? 'maxlength="' . $field['maxlength'] . '" ' : '';
			}
			
			if( $field['type'] === 'datetime' ){
				$field['class'] = 'datetimepicker';
			}elseif( $field['type'] === 'date' ){
				$field['class'] = 'datepicker';
			}

			if( isset($field['readOnly']) && $field['readOnly'] === true ){
				 $html .= 'readonly aria-readonly="true" ';
			}

			if( isset($field['placeholder']) && is_string($field['placeholder']) ) {
				$html .= 'placeholder="' . $field['placeholder'] . '" ';
			}else{
				$html .= 'placeholder="' . (isset($field['label']) ? $field['label'] : '') . '" ';
			}

			if( isset($field['required']) ){
				$html .= 'aria-required="true" ';
			}

			$html .= 'class="' . $field['class'] . '" ';

			if( !$isTextArea ){
				$html .= 'value="' . $value . '" ';
			}
			$html .= ' >';

			if( isset($isTextArea) && $isTextArea == true ){
				$html .= $value . '</textarea>';
			}
			break;
		case 'select':
		case 'relation':
		case 'enum':
			if( $field['type'] === 'relation' ){
				$data = scrud_list($field['data'], array());
				//$fieldName = $pkey;
			}else{
				$data = $field['data'];
			}
			if( is_array($data) ){
				$id = isset($field['id']) ? $field['id'] : 'select-' . $fieldName;
				$html .= '<select name="' . $fieldName . '" id="' . $id . '">';
				$assoc = is_assoc_array($data);
				foreach ($data as $key => $value) {
					$html .= '<option value="' . ($assoc ? $key : $value) . '">' . $value . '</option>';
				}
				$html .= '</select>';
			}else{
				$html .= 'Aucune donnée de type ' . $field['data'];
			}
		break;
		case 'phone':
		case 'email':
		case 'url':
			$type = array('phone' => 'tel', 'email' => 'email', 'url' => 'url');
			$html .= '<input type="' . (isset($type[$field['type']]) ? $type[$field['type']] : '') . '" name="' . $fieldName . '" id="' . $id . '" ';
			$html .= (isset($field['maxlength']) && is_int($field['maxlength'])) ? 'maxlength="' . $field['maxlength'] . '" ' : '';
			$html .= (isset($field['placeholder']) && is_string($field['placeholder'])) ? 'placeholder="' . $field['placeholder'] . '" ' : (isset($field['placeholder']) && !$field['placeholder']) ? '' : 'placeholder="' . $field['label'] . '" ';
			$html .= (isset($field['class']) && is_string($field['class'])) ? 'class="' . $field['class'] . '" ' : '';
			$html .= 'value="' . $value . '" ';
			$html .= '>';
			break;
		default:
			# code...
			break;
	}
	
	if( isset($field['label']) && $field['type'] !== 'hidden' ){
		$label = '<label for="' . $id . '">' . ucfirst($field['label']) . '</label>';
	}else{
		$label = '';
	}
	return $label . $html;

}


function field_validate($field, $value = null, &$data = null, $prefix = ''){
	static $ids = 0;

	$defaultSqlField = var_get('sql/defaultField', array());
	$field = array_merge($defaultSqlField, $field);
	$errors = array();

	$key = isset($field['name']) && is_string($field['name']) ? $field['name'] : ++$ids;
	$pkey = trim($prefix) != '' ? $prefix . $key : $key;

	$errors[$pkey] = array();

	$d = !is_null($value) ? $value : (isset($field['value']) ? $field['value'] : (isset($field['default']) && !is_null($field['default']) ? $field['default'] : null));

	if ($field['required'] && !$d ) {
		$errors[$pkey][] = field_error_message($key, $field, 'required');
	}

	switch ($field['type']) {
		case 'phone':
			if( $field['required'] && !preg_match('#\+(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d|2[98654321]\d|9[8543210]|8[6421]|6[6543210]|5[87654321]|4[987654310]|3[9643210]|2[70]|7|1)\d{1,14}$#', $d)){
				$errors[$pkey][] = field_error_message($key, $field);
			}
		break;
		case 'text':
		case 'password':
		case 'email':
			if( $field['required'] && !is_string($d) ){
				$errors[$pkey][] = field_error_message($key, $field, 'not_string');
			}else{
				if( isset($field['maxlength']) && (int)$field['maxlength'] > 0 && (int)$field['maxlength'] !== -1){
					if( strlen($d) > (int)$field['maxlength'] ){
						$errors[$pkey][] = field_error_message($key, $field, 'maxlength'); //'Le champ "' . $field['label'] . '" ne peut pas comporter plus de ' . $field['maxlength'] . ' caractères';
					}
				}
				if( isset($field['minlength']) && (int)$field['minlength'] > 0 ){
					if( strlen($d) < (int)$field['minlength'] ){
						$errors[$pkey][] = field_error_message($key, $field, 'minlength'); //'Le champ "' . $field['label'] . '" ne peut pas comporter moins de ' . $field['minlength'] . ' caractères';
					}
				}
				if( $field['type'] === 'email' ){
					require_once('lib/vendor/is_email.inc.php'); // validate an email address according to RFCs 5321, 5322 and others, by Dominic Sayers
					if( $field['required'] || $d !== '' ){
						if( function_exists('is_email') ){
							$valid = is_email($d);
						}else{
							$valid = filter_var($d, FILTER_VALIDATE_EMAIL);
						}
						if( !$valid ){
							$errors[$pkey][] = field_error_message($key, $field, 'invalid_email'); //'L\'adresse email est invalide.';
						}
					}
				}
			}
			break;
		case 'relation':
			/*if( $d ){
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
					$errors[$pkey][] = field_error_message($key, $field, 'required');
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
			}*/
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
			$errors[$pkey][] = field_error_message($key, $field, 'unique');
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
				$errors = array_merge($validation['errors'], $errors);
			}
		}elseif (isset($d)) {
			$back[$pkey] = $d;
		}
	}else{
		$back[$pkey] = $d ? $d : null;
	}

	$valid = !sizeof($errors);

	if( is_array($data) ){
		if( !isset($data['data']) ){ 
			$data['data'] = array();
		}
		$data['errors'] = $errors;
		$data['data'] = array_merge($data['data'], $back);
	}

	return $valid;
}

function fields_validate($fields, $values = null, &$data = null) {
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


function field_error_message($key, $field, $error = '')
{
	$label = '<strong>' . ucfirst(isset($field['label']) ? $field['label'] : ($field['type'] === 'relation' ? $database[$field['data']]['labels']['singular'] : $key)) . '</strong>';
	if( $error === 'required' ){
		return t('Le champ') . ' ' . $label . t('est requis');
	}elseif( $error === 'minlength' ){
		return t('Le champ') . ' ' . $label . ' ' . t('ne peut comporter moins de ') . $field['minlength'] . ' ' . t('caractères');
	}elseif( $error === 'maxlength' ){
		return t('Le champ') . ' ' . $label . ' ' . t('ne peut comporter plus de ') . $field['maxlength'] . ' ' . t('caractères');
	}elseif( $error === 'unique' ){
		return t('Le champ') . ' ' . $label . ' ' . t('existe déjà en base de donnée.');
	}else{
		return t('Le champ') . ' ' . $label . ' ' . t('est invalide.');
	}
}