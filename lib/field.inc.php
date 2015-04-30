<?php
/**
 * Fields
 * @package php-tool-suite
 * @subpackage fields
 */

plugin_require(array('i18n', 'html'));

if( !var_get('field/default') ){
	var_set('field/default', array(
		'type' => 'text',
		'comment' => null,
		'maxlength' => 255,
		'default' => null,
		'unique' => false,
		'required' => false,
		'formatter' => null,
		'attrs' => array(),
		'searchable' => true,
		'hasMany' => false,
		'characterSet' => null,
		'collation' => null
	));
}

/**
 * Gets the field value, or field default value if set.
 * @param array $field The field reference 
 * @return mixed The field value or default value. NULL otherwise
 */
function field_value($field) {

	$value = null;
	if( isset($field['value']) ){
		$value = $field['value'];
	}else{
		$value = isset($field['default']) ? $field['default'] : null;
	}

	return $value;
}

/**
 * Gets an HTML representation of a field
 * @param array $field The field reference 
 * @return string The generated HTML for the value, generally along with a label.
 */
function field($field = array()) {
	$field = array_merge(var_get('field/default', array()), $field);

	$html = '';
	$attrs = array();
	$attrs['name'] = $field['name'];
	$attrs['id'] = isset($field['id']) ? $field['id'] : 'input-' . $field['name'];
	$attrs['class'] = '';
	$attrs = array_merge($attrs, $field['attrs']);

	$value = field_value($field);

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
		case 'tel':
		case 'email':
		case 'url':
			$isTextArea = false;
		
			if( $field['type'] === 'text' && isset($field['maxlength']) && $field['maxlength'] > 255){
				$isTextArea = true;
				$attrs['role'] = 'textbox';
				$attrs['aria-multiline'] = 'true';
			}else{
				$attrs['type'] = $field['type'];
				if( $field['type'] === 'float' || $field['type'] === 'double' ){
					$attrs['type'] = 'number';
					$attrs['step'] = 'any';
				}elseif ($field['type'] === 'int' ){
					$attrs['type'] = 'number';
				}
			}

			if( in_array($field['type'], array('int','float','double')) ){
				if( isset($field['minValue']) && is_numeric($field['minValue']) ){
					$attrs['min'] = $field['minValue'];
				}
				if( isset($field['maxValue']) && is_numeric($field['maxValue']) ){
					$attrs['max'] = $field['maxValue'];
				}
			}else{
				if( isset($field['maxlength']) && is_numeric($field['maxlength']) ){
					$attrs['maxlength'] = $field['maxlength'];
				}
			}
			
			if( $field['type'] === 'datetime' ){
				$attrs['class'] .= ' datetimepicker';
			}elseif( $field['type'] === 'date' ){
				$attrs['class'] .= ' datepicker';
			}

			if( isset($field['readOnly']) && $field['readOnly'] === true ){
				$attrs['readonly'] = 'readonly';
				$attrs['aria-readonly'] = 'true';
			}

			if( isset($field['placeholder']) && is_string($field['placeholder']) ) {
				$attrs['placeholder'] = $field['placeholder'];
			}else{
				$attrs['placeholder'] = (isset($field['label']) ? $field['label'] : '');
			}

			if( isset($field['required']) ){
				$attrs['aria-required'] = 'true';
			}

			if( !$isTextArea ){
				$attrs['value'] = $value;
				$html = tag('input', '', $attrs, true);
			}else{
				$html = tag('textarea', $value, $attrs);
			}

			break;
		case 'select':
		case 'enum':
		
			$data = $field['data'];
			
			if( is_array($data) ){
				$options = '';
				foreach ($data as $key => $v) {
					$options .= '<option' . ($key === $value ? ' selected="selected"' : '') . ' value="' . $key . '">' . htmlspecialchars(t($v)) . '</option>';
				}
				$html .= tag('select', $options, $attrs);
			}
		break;
		default:
			# code...
			break;
	}
	
	if( isset($field['label']) && $field['type'] !== 'hidden' ){
		$label = tag('label', $field['label'], array('for' => $attrs['id']));
	}else{
		$label = '';
	}
	return $label . $html;
}

function fields($fields) {
	$html = '';
	foreach ($fields as $value) {
		$html .= field($value);
	}
	return $html;
}

/**
 * Validates a field value.
 * @param array $field The field reference 
 * @param array $value An optional value. If NULL, use field_value().
 * @param array $data The returned validation data.
 * @return boolean TRUE if the value has been validated. FALSE otherwise. Error details can be found in $data['errors'].
 */
function field_validate($field, $value = null, &$data = null){
	$field = array_merge(var_get('field/default', array()), $field);

	$key = $field['name'];
	$errors = array($key => array());
	$d = !is_null($value) ? $value : field_value($field);

	if ($field['required'] && !$d ) {
		$errors[$key][] = field_error_message($field, 'required');
	}

	switch ($field['type']) {
		case 'int':
		case 'float':
		case 'double':
			if( is_numeric($field['min']) && $d < $field['min'] ){
				$errors[$key][] = field_error_message($field, 'min');
			}
			if( is_numeric($field['max']) && $d > $field['max'] ){
				$errors[$key][] = field_error_message($field, 'max');
			}
		break;
		case 'phone':
			if( $field['required'] && !preg_match('#\+(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d|2[98654321]\d|9[8543210]|8[6421]|6[6543210]|5[87654321]|4[987654310]|3[9643210]|2[70]|7|1)\d{1,14}$#', $d)){
				$errors[$key][] = field_error_message($field);
			}
		break;
		case 'data':
		case 'datetime':
			if( $field['required'] && !strtotime($d) ){
				$errors[$key][] = field_error_message($field, 'date');
			}
		break;
		case 'text':
		case 'password':
		case 'email':
			if( $field['required'] && !is_string($d) ){
				$errors[$key][] = field_error_message($field, 'not_string');
			}else{
				if( isset($field['maxlength']) && (int)$field['maxlength'] > 0 && (int)$field['maxlength'] !== -1){
					if( strlen($d) > (int)$field['maxlength'] ){
						$errors[$key][] = field_error_message($field, 'maxlength'); //'Le champ "' . $field['label'] . '" ne peut pas comporter plus de ' . $field['maxlength'] . ' caractères';
					}
				}
				if( isset($field['minlength']) && (int)$field['minlength'] > 0 ){
					if( strlen($d) < (int)$field['minlength'] ){
						$errors[$key][] = field_error_message($field, 'minlength'); //'Le champ "' . $field['label'] . '" ne peut pas comporter moins de ' . $field['minlength'] . ' caractères';
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
							$errors[$key][] = field_error_message($field, 'invalid_email'); //'L\'adresse email est invalide.';
						}
					}
				}
			}
			break;
		default:
			# code...
			break;
	}

	if( !sizeof($errors[$key]) ){
		unset($errors[$key]);
	}

	if( !is_array($data) || !sizeof($data) ){
		$data = array('data' => $d, 'errors' => $errors);
	}else{
		$data['data'] = $d;
		$data['errors'] = $errors;
	}

	return !sizeof($errors);
}


/**
 * Validates multiple field values
 * @param array $field An array of fields to validate
 * @param array $value An optional associate array containing the field keys and values to test for.
 * @param array $data The returned validation data.
 * @return boolean TRUE if the values have been validated. FALSE otherwise. Error details can be found in $data['errors'].
 */
function fields_validate($fields, $values = null, &$data = null) {
	// try the default $_REQUEST values
	if( !$values ){
		$values = $_REQUEST; 
	}

	$data = array();
	foreach ($fields as $fieldName => $field) {
		$field['name'] = $fieldName;
		$back = array();
		field_validate($field, isset($values[$fieldName]) ? $values[$fieldName] : field_value($field), $back);
		$data = array_merge_recursive($data, $back);
	}
	return !sizeof($data['errors']);
}


/**
 * Gets a human readable message from a field error code
 * @param array $field The field reference
 * @param string $error The error code
 * @return string An internationalized human readable error message.
 */
function field_error_message($field, $error = '')
{	
	$label = '<strong>' . (isset($field['label']) ? $field['label'] : ucfirst($field['name'])) . '</strong>';
	if( $error === 'required' ){
		return t('The field') . ' ' . $label . ' ' . t('is required');
	}elseif( $error === 'minlength' ){
		return t('The field') . ' ' . $label . ' ' . t('cannot contain less than') . ' ' . $field['minlength'] . ' ' . t('characters');
	}elseif( $error === 'maxlength' ){
		return t('The field') . ' ' . $label . ' ' . t('cannot contain more than') . ' ' . $field['maxlength'] . ' ' . t('characters');
	}elseif( $error === 'unique' ){
		return t('The field') . ' ' . $label . ' ' . t('already exist in database.');
	}elseif( $error === 'min' ){
		return t('The field') . ' ' . $label . ' ' . t('must be greater than') . ' ' . $field['min'];
	}elseif( $error === 'max' ){
		return t('The field') . ' ' . $label . ' ' . t('must be lower than') . ' ' . $field['max'];
	}elseif( $error === 'max' ){
		return t('The date field') . ' ' . $label . ' ' . t('is invalid');
	}else{
		return t('The field') . ' ' . $label . ' ' . t('is invalid');
	}
}


/*
function fields_to_sql($fields, $forceDeletion = false) {
	$prefix = var_get('sql/prefix', '');
	$defaultSqlField = var_get('sql/defaultField');
	$tables = sql_list_tables();

	if( $forceDeletion === true ){
		$newContentTypes = array_keys($schema);
		foreach( $tables as $table ) {
			$ct = substr($table, strlen($prefix));
			if( substr($table, 0, strlen($prefix)) === $prefix && !in_array($ct, $newContentTypes) ){
				sql_delete_table($ct);
				continue;
			}
		}
	}

	foreach ($fields as $contenttype => $info) {
		
		$fields = $info['fields'];
		if( $forceDeletion === true ){
			$fieldKeys = array_keys($fields);
		}

		$tableName = $prefix.$contenttype;

		$tableExists = in_array($tableName, $tables);
		
		if( $tableExists ) {
			$tableDescribe = sql_describe($tableName);
			$oldFieldTypes = array();
			$oldFieldNames = array();
			foreach ($tableDescribe as $tableField) {
				if( $tableField['Field'] === 'id' ){
					continue;
				}
				$oldFieldTypes[$tableField['Field']] = $tableField['Type'];
				$oldFieldNames[] = $tableField['Field'];
			}
		}
		
		$inject = array();
		$uniques = array();
		$rel = array();
		$many = array();
		$lastColumn = 'id';
		$i = 0;
		foreach ($fields as $fieldName => $field) {
			$default = '';
			$field = array_merge($defaultSqlField, $field);
			$fieldType = 'VARCHAR(' . $field['maxlength'] . ')';
			if( $field['type'] == 'text' && $field['maxlength'] > 255 || $field['type'] === 'password' || $field['maxlength'] === -1 ){
				$fieldType = 'TEXT';
			}elseif( $field['type'] == 'relation' ){
				$fieldType = 'INT(11)';
				if( !is_numeric($field['default']) ){ 
					$field['default'] = 0;
				}
				if( $field['hasMany'] ){
					$manyTableName = $contenttype . '_' . $fieldName;
					
					$many[] = 'CREATE TABLE IF NOT EXISTS ' . sql_quote($manyTableName, true) . ' (' . 
						'id_' . $contenttype . ' int(11) NOT NULL, ' . 
						'id_' . $field['data'] . ' int(11) NOT NULL, 
						PRIMARY KEY(id_' . $contenttype . ',id_' . $field['data'] . '),
						FOREIGN KEY `' . sql_quote('FK_id_' . $manyTableName . '_' . $contenttype, true) . '` (id_'.$contenttype.') REFERENCES `' . sql_quote($prefix . $contenttype, true) . '`(`id`),
						FOREIGN KEY `' . sql_quote('FK_id_' . $manyTableName . '_' . $field['data'], true) . '` (id_'.$field['data'].') REFERENCES `' . sql_quote($prefix . $field['data'], true) . '`(`id`)
						) COLLATE utf8_general_ci ENGINE=InnoDB;';
					continue;
				}
			}elseif (in_array($field['type'], array('int', 'float', 'double', 'bool', 'datetime', 'date'))){
				if( $field['type'] === 'int' ){
					$fieldType = 'int(11)';
				}else{
					$fieldType = $field['type'];
				}
				if( !is_numeric($field['default']) ){ 
					$field['default'] = 0;
				}
			}

			if( isset($field['unique']) && $field['unique'] === true ){
				$uniques[] = ' UNIQUE ('.$fieldName.') ';
			}


			$notnull = '';
			if( isset($field['required']) && $field['required'] === true ){
				$notnull .= ' NOT NULL';
			}

			if( !$tableExists ){

				$inject[] = $fieldName . ' ' . $fieldType . $default . $notnull . ' COLLATE utf8_general_ci';
				if( $field['type'] == 'relation' && !$field['hasMany'] ){
					$rel[] = ' FOREIGN KEY `' . sql_quote('FK_id_' . $contenttype . '_' . $fieldName, true) . '` ('.$fieldName.') REFERENCES `' . sql_quote($prefix . $field['data'], true) . '`(`id`) ';
				}

			}elseif( !isset($oldFieldTypes[$fieldName]) || mb_strtolower($fieldType) !== mb_strtolower($oldFieldTypes[$fieldName]) ){
				if( in_array($fieldName, $oldFieldNames) ){
					$inject[] = 'ALTER TABLE ' . sql_quote($tableName, true) . ' MODIFY COLUMN ' . sql_quote($fieldName, true) . ' ' . $fieldType . $default . $notnull;
				}else{
					if( !$field['hasMany'] ){
						$inject[] = 'ALTER TABLE ' . sql_quote($tableName, true) . ' ADD COLUMN ' . sql_quote($fieldName, true) . ' ' . $fieldType . $default . $notnull . ' AFTER ' . sql_quote($lastColumn, true);
						if( $field['type'] == 'relation' ){
							$inject[] = 'ALTER TABLE ' . sql_quote($tableName, true) . ' DROP CONSTRAINT ' . sql_quote('FK_id_' . $contenttype . '_' . $fieldName, true);
							$inject[] = 'ALTER TABLE ' . sql_quote($tableName, true) . ' ADD CONSTRAINT ' . sql_quote('FK_id_' . $contenttype . '_' . $fieldName, true) . ' FOREIGN KEY (`' . $fieldName . '`) REFERENCES ' . sql_quote($prefix . $field['data'], true) . '(`id`) ';
						}
					}
				}				
			}else{
				
			}

			$lastColumn = $fieldName;
			++$i;
		}

		$rel = array_merge($rel, $uniques);

		if( !$tableExists ){
			$primaryKey = array('id');
			if( isset($info['primaryKey']) ){
				if( is_array($info['primaryKey']) ){
					$primaryKey = $info['primaryKey'];
				}elseif( is_string($info['primaryKey']) ){
					$primaryKey = array($info['primaryKey']);
				}
			}
			if( in_array('id', $primaryKey) ){
				array_unshift($inject, 'id INT NOT NULL AUTO_INCREMENT');
			} 
			$primaryKey = implode(', ', $primaryKey);
			$query = 'CREATE TABLE ' . sql_quote($tableName, true) . ' (' . implode(',', $inject) . ', PRIMARY KEY(' . $primaryKey . ')' . (sizeof($rel) ? ',' . implode(',', $rel) : '') . ') COLLATE utf8_general_ci ENGINE=InnoDB;';
			//print ($query);
			sql_query($query, null, null);
		}
		else {
			if ($forceDeletion){
				$fieldsToDelete = array_keys($fields);
				$fieldsCopy = $oldFieldNames;
				foreach ($fieldsCopy as $key => $v) {
					if( $v == 'id'){
						unset($fieldsCopy[$key]);
					}
				}

				$diff = array_diff($fieldsCopy, $fieldsToDelete);

				if( sizeof($diff) ){
					foreach ($diff as $value) {
						array_unshift($inject, 'ALTER TABLE ' . sql_quote($tableName, true) . ' DROP FOREIGN KEY `FK_id_' . sql_quote($contenttype . '_' . $value, true) . '`');
						array_unshift($inject, 'ALTER TABLE ' . sql_quote($tableName, true) . ' DROP COLUMN ' . sql_quote($value, true) . ';');
					}
				}
			}

			foreach ($inject as $query) {
				sql_query($query, null, null);
			}
		}
	}

	// execute many relations
	foreach ($many as $query) {
		sql_query($query, null, null);
	}
}
*/