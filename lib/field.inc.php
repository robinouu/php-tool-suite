<?php
/**
 * Fields
 * @package php-tool-suite
 * @subpackage fields
 */

plugin_require(array('i18n', 'html'));


var_set('fields', array(
	'text' => array(
		'labels' => array('singular' => t('Texte'), 'plural' => t('Textes')),
		'validate' => function (&$instance, &$str) {
			if( !is_string($str) ){
				$instance['errors']['invalid'] = t('le champ %s n\'est pas une chaîne de charactères', array($instance['label']));
			}else{
				if( isset($instance['maxlength']) && strlen($str) > (int)$instance['maxlength'] ){
					$instance['errors']['maxlength'] = t('le champ %s ne peut comporter plus de %d charactères', array($instance['label'], $instance['maxlength']));
				}elseif( isset($instance['minlength']) && strlen($str) < (int)$instance['minlength'] ){
					$instance['errors']['minlength'] = t('le champ %s ne peut comporter moins de %d charactères', array($instance['label'], $instance['minlength']));
				}
				elseif( isset($instance['required']) && trim($str) === '' ){
					$instance['errors']['required'] = t('le champ %s est requis', array($instance['label']));
				}
			}
		},
		'convertTo' => array(
			'field' => function(&$instance) {
				$attrs = array(
					'type' => 'text',
					'value' => isset($instance['value']) ? $instance['value'] : ''
				);				

				if( isset($instance['readonly']) && $instance['readonly'] === true ){
					$attrs['readonly'] = 'readonly';
					$attrs['aria-readonly'] = 'true';
				}
				if( isset($instance['required']) && $instance['required'] === true ){
					$attrs['aria-required'] = 'true';
				}
				if( isset($instance['placeholder']) && is_string($instance['placeholder']) ) {
					$attrs['placeholder'] = $instance['placeholder'];
				}
				if( isset($instance['disabled']) && $instance['disabled'] === true ) {
					$attrs['disabled'] = $instance['disabled'];
				}
				if( isset($instance['maxlength']) ){
					if( $instance['maxlength'] > 255 ){
						$isTextArea = true;
						$attrs['role'] = 'textbox';
						$attrs['aria-multiline'] = 'true';
					}
					$attrs['maxlength'] = $instance['maxlength'];
				}
				if( isset($isTextArea) && $isTextArea ){
					$value = $attrs['value'];
					unset($attrs['value']);
					$html = tag('textarea', $value, $attrs);
				}else{
					$html = tag('input', '', $attrs, true);
				}
				return $html;
			},
			'sql' => function (&$instance, &$sqlField) {
				$maxlength = isset($instance['maxlength']) ? $instance['maxlength'] : 256;
				if( $maxlength > 256 || $maxlength < 0 ){
					$sqlField['type'] = 'text';
				}else{
					$sqlField['type'] = 'varchar(' . (int)$maxlength . ')';
				}
			}
		)
	),
	'int' => array(
		'labels' => array('singular' => t('Nombre entier'), 'plural' => t('Nombres entiers')),
		'validate' => function (&$instance, &$int) {
			if( !is_int($int) ){
				$instance['errors']['invalid'] = t('le champ %s n\'est pas un nombre entier', array($instance['label']));
			}else{
				if( isset($instance['minValue']) && $int < (int)$instance['minValue'] ){
					$instance['errors']['minValue'] = t('le champ %s doit être supérieur à %d', array($instance['label'], $instance['value']));
				}elseif( isset($instance['minValue']) && $int > (int)$instance['maxValue'] ){
					$instance['errors']['minValue'] = t('le champ %s doit être inférieur à %d', array($instance['label'], $instance['value']));
				}
			}
		},
		'convertTo' => array(
			'field' => function (&$instance) {
				$attrs = array(
					'type' => 'number',
					'value' => isset($instance['value']) ? $instance['value'] : ''
				);

				if( isset($instance['readonly']) && $instance['readonly'] === true ){
					$attrs['readonly'] = 'readonly';
					$attrs['aria-readonly'] = 'true';
				}
				if( isset($instance['required']) && $instance['required'] === true ){
					$attrs['aria-required'] = 'true';
				}
				if( isset($instance['disabled']) && $instance['disabled'] === true ) {
					$attrs['disabled'] = $instance['disabled'];
				}
				if( isset($instance['placeholder']) && is_string($instance['placeholder']) ) {
					$attrs['placeholder'] = $instance['placeholder'];
				}
				if( isset($instance['minValue']) ){
					$attrs['min'] = $instance['minValue'];
				}
				if( isset($instance['maxValue']) ){
					$attrs['max'] = $instance['maxValue'];
				}
				if( isset($instance['step']) ){
					$attrs['step'] = $instance['step'];
				}
				return tag('input', '', $attrs, true);
			},
			'sql' => function (&$instance, &$sqlField) {
				$sqlField['type'] = 'int(11)';
			}
		)
	),
	'boolean' => array(
		'labels' => array('singular' => t('Booléen'), 'plural' => t('Booléens')),
		'validate' => function (&$instance, &$boolean) {
			if( !is_bool($boolean) ){
				$instance['errors']['invalid'] = t('le champ %s n\'est pas un booléen');
			}
		},
		'convertTo' => array(
			'field' => function (&$instance) {
				$attrs = array(
					'type' => 'checkbox',
					'value' => isset($instance['value']) ? $instance['value'] : ''
				);
				if( isset($instance['readonly']) && $instance['readonly'] === true ){
					$attrs['readonly'] = 'readonly';
					$attrs['aria-readonly'] = 'true';
				}
				if( isset($instance['required']) && $instance['required'] === true ){
					$attrs['aria-required'] = 'true';
				}
				if( isset($instance['disabled']) && $instance['disabled'] === true ) {
					$attrs['disabled'] = $instance['disabled'];
				}
				return tag('input', '', $attrs, true);
			},
			'sql' => function (&$instance, &$sqlField) {
				$sqlField['type'] = 'bool';
			}
		)
	),
	'password' => array(
		'labels' => array('singular' => t('Mot de passe'), 'plural' => t('Mots de passe')),
		'extends' => 'text',
		'convertTo' => array(
			'field' => function (&$instance) {
				$attrs = array(
					'type' => 'password',
					'value' => isset($instance['value']) ? $instance['value'] : ''
				);
				if( isset($instance['readonly']) && $instance['readonly'] === true ){
					$attrs['readonly'] = 'readonly';
					$attrs['aria-readonly'] = 'true';
				}
				if( isset($instance['required']) && $instance['required'] === true ){
					$attrs['aria-required'] = 'true';
				}
				if( isset($instance['disabled']) && $instance['disabled'] === true ) {
					$attrs['disabled'] = $instance['disabled'];
				}
				if( isset($instance['placeholder']) && is_string($instance['placeholder']) ) {
					$attrs['placeholder'] = $instance['placeholder'];
				}
				$html = tag('input', '', $attrs, true);
				return $html;
			}
		)
	),
	'email' => array(
		'labels' => array('singular' => t('Email'), 'plural' => t('Emails')),
		'extends' => 'text',
		'validate' => function (&$instance, &$email) {
			plugin_require('vendor/is_email');
			$empty = trim($email) === '';
			if( isset($instance['required']) && $empty ){
				$instance['errors']['required'] = t('le champ %s est requis', array($instance['label']));
			}elseif( !$empty && !is_email($email) ){
				$instance['errors']['invalid'] = t('le champ %s n\'est pas une adresse e-mail valide.', array($instance['label']));
			}
		},
		'convertTo' => array(
			'field' => function(&$instance) {
				$attrs = array(
					'type' => 'email',
					'value' => isset($instance['value']) ? $instance['value'] : ''
				);
				if( isset($instance['readonly']) && $instance['readonly'] === true ){
					$attrs['readonly'] = 'readonly';
					$attrs['aria-readonly'] = 'true';
				}
				if( isset($instance['required']) && $instance['required'] === true ){
					$attrs['aria-required'] = 'true';
				}
				if( isset($instance['disabled']) && $instance['disabled'] === true ) {
					$attrs['disabled'] = $instance['disabled'];
				}
				if( isset($instance['placeholder']) && is_string($instance['placeholder']) ) {
					$attrs['placeholder'] = $instance['placeholder'];
				}
				$html = tag('input', '', $attrs, true);
				return $html;
			},
			'sql' => function (&$instance, &$sqlField) {
				$sqlField['type'] = 'varchar(256)';
			}
		)
	),
	'phoneNumber' => array(
		'labels' => array('singular' => t('Numéro de téléphone'), 'plural' => t('Numéros de téléphone')),
		'extends' => 'text',
		'validate' => function (&$instance, &$phoneNumber) {
			if( isset($instance['required']) && trim($phoneNumber) === '' ){
				$instance['errors']['required'] = t('le champ %s est requis', array($instance['label']));
			}elseif( !preg_match('#\+(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d|2[98654321]\d|9[8543210]|8[6421]|6[6543210]|5[87654321]|4[987654310]|3[9643210]|2[70]|7|1)\d{1,14}$#', $phoneNumber) ){
				$instance['errors']['invalid'] = t('le champ %s n\'est pas un numéro de téléphone valide.', array($instance['label']));
			}
		},
		'convertTo' => array(
			'field' => function(&$instance) {
				$attrs = array(
					'type' => 'tel',
					'value' => isset($instance['value']) ? $instance['value'] : ''
				);
				if( isset($instance['readonly']) && $instance['readonly'] === true ){
					$attrs['readonly'] = 'readonly';
					$attrs['aria-readonly'] = 'true';
				}
				if( isset($instance['required']) && $instance['required'] === true ){
					$attrs['aria-required'] = 'true';
				}
				if( isset($instance['disabled']) && $instance['disabled'] === true ) {
					$attrs['disabled'] = $instance['disabled'];
				}
				if( isset($instance['placeholder']) && is_string($instance['placeholder']) ) {
					$attrs['placeholder'] = $instance['placeholder'];
				}
				$html = tag('input', '', $attrs, true);
				return $html;
			}
		)
	),
	'date' => array(
		'labels' => array('singular' => t('Date'), 'plural' => t('Dates')),
		'validate' => function (&$instance, &$date) {
			if( isset($instance['required']) && trim($date) === '' ){
				$instance['errors']['required'] = t('le champ %s est requis', array($instance['label']));
			}else{
				$format = isset($instance['format']) ? $instance['format'] : 'Y-m-d';
				$d = DateTime::createFromFormat($format, $date);
				$valid = $d && $d->format($format) == $date;
				if( !$valid ){
					$instance['errors']['invalid'] = t('le champ %s n\'est pas une date valide.', array($instance['label']));
				}elseif( isset($instance['min']) ){
					$dMin = DateTime::createFromFormat($format, $instance['min']);
					if( $d < $dMin ){
						$instance['errors']['min'] = t('la date %s est plus ancienne que la date minimale acceptée (%s).', array($date, $instance['min']));
					}
				}elseif( isset($instance['max']) ){
					$dMax = DateTime::createFromFormat($format, $instance['max']);
					if( $d > $dMax ){
						$instance['errors']['max'] = t('la date %s est plus récente que la date maximale acceptée (%s).', array($date, $instance['max']));
					}
				}
			}
		},
		'convertTo' => array(
			'field' => function(&$instance) {
				$attrs = array(
					'type' => 'date',
					'value' => isset($instance['value']) ? $instance['value'] : ''
				);
				if( isset($instance['readonly']) && $instance['readonly'] === true ){
					$attrs['readonly'] = 'readonly';
					$attrs['aria-readonly'] = 'true';
				}
				if( isset($instance['required']) && $instance['required'] === true ){
					$attrs['aria-required'] = 'true';
				}
				if( isset($instance['disabled']) && $instance['disabled'] === true ) {
					$attrs['disabled'] = $instance['disabled'];
				}
				if( isset($instance['min']) && is_string($instance['min']) ) {
					$attrs['min'] = $instance['min'];
				}
				if( isset($instance['max']) && is_string($instance['max']) ) {
					$attrs['max'] = $instance['max'];
				}
				$html = tag('input', '', $attrs, true);
				return $html;
			},
			'sql' => function (&$instance, &$sqlField) {
				$sqlField['type'] = 'date';
			}
		)
	),
	'datetime' => array(
		'labels' => array('singular' => t('Date et heure'), 'plural' => t('Dates et heures')),
		'validate' => function (&$instance, &$datetime) {
			if( isset($instance['required']) && trim($datetime) === '' ){
				$instance['errors']['required'] = t('le champ %s est requis', array($instance['label']));
			}else{
				$format = isset($instance['format']) ? $instance['format'] : 'Y-m-d H:i:s';
				$d = DateTime::createFromFormat($format, $datetime);
				$valid = $d && $d->format($format) == $datetime;
				if( !$valid ){
					$instance['errors']['invalid'] = t('le champ %s n\'est pas une date valide.', array($instance['label']));
				}elseif( isset($instance['min']) ){
					$dMin = DateTime::createFromFormat($format, $instance['min']);
					if( $d < $dMin ){
						$instance['errors']['min'] = t('la date %s est plus ancienne que la date minimale acceptée (%s).', array($datetime, $instance['min']));
					}
				}elseif( isset($instance['max']) ){
					$dMax = DateTime::createFromFormat($format, $instance['max']);
					if( $d > $dMax ){
						$instance['errors']['max'] = t('la date %s est plus récente que la date maximale acceptée (%s).', array($datetime, $instance['max']));
					}
				}
			}
		},
		'convertTo' => array(
			'field' => function(&$instance) {
				$attrs = array(
					'type' => 'datetime',
					'value' => isset($instance['value']) ? $instance['value'] : ''
				);
				if( isset($instance['readonly']) && $instance['readonly'] === true ){
					$attrs['readonly'] = 'readonly';
					$attrs['aria-readonly'] = 'true';
				}
				if( isset($instance['required']) && $instance['required'] === true ){
					$attrs['aria-required'] = 'true';
				}
				if( isset($instance['disabled']) && $instance['disabled'] === true ) {
					$attrs['disabled'] = $instance['disabled'];
				}
				if( isset($instance['min']) && is_string($instance['min']) ) {
					$attrs['min'] = $instance['min'];
				}
				if( isset($instance['max']) && is_string($instance['max']) ) {
					$attrs['max'] = $instance['max'];
				}
				$html = tag('input', '', $attrs, true);
				return $html;
			},
			'sql' => function (&$instance, &$sqlField) {
				$sqlField['type'] = 'datetime';
			}
		)
	),
	'time' => array(
		'labels' => array('singular' => t('Heure'), 'plural' => t('Heures')),
		'validate' => function (&$instance, &$datetime) {
			if( isset($instance['required']) && trim($datetime) === '' ){
				$instance['errors']['required'] = t('le champ %s est requis', array($instance['label']));
				return false;
			}else{
				$format = isset($instance['format']) ? $instance['format'] : 'H:i:s';
				$d = DateTime::createFromFormat($format, $datetime);
				$valid = $d && $d->format($format) == $datetime;
				if( !$valid ){
					$instance['errors']['invalid'] = t('le champ %s n\'est pas une date valide.', array($instance['label']));
				}elseif( isset($instance['min']) ){
					$dMin = DateTime::createFromFormat($format, $instance['min']);
					if( $d < $dMin ){
						$instance['errors']['min'] = t('la date %s est plus ancienne que la date minimale acceptée (%s).', array($datetime, $instance['min']));
					}
				}elseif( isset($instance['max']) ){
					$dMax = DateTime::createFromFormat($format, $instance['max']);
					if( $d > $dMax ){
						$instance['errors']['max'] = t('la date %s est plus récente que la date maximale acceptée (%s).', array($datetime, $instance['max']));
					}
				}
			}
		},
		'convertTo' => array(
			'field' => function(&$instance) {
				$attrs = array(
					'type' => 'time',
					'value' => isset($instance['value']) ? $instance['value'] : ''
				);
				if( isset($instance['readonly']) && $instance['readonly'] === true ){
					$attrs['readonly'] = 'readonly';
					$attrs['aria-readonly'] = 'true';
				}
				if( isset($instance['required']) && $instance['required'] === true ){
					$attrs['aria-required'] = 'true';
				}
				if( isset($instance['disabled']) && $instance['disabled'] === true ) {
					$attrs['disabled'] = $instance['disabled'];
				}
				if( isset($instance['min']) && is_string($instance['min']) ) {
					$attrs['min'] = $instance['min'];
				}
				if( isset($instance['max']) && is_string($instance['max']) ) {
					$attrs['max'] = $instance['max'];
				}
				$html = tag('input', '', $attrs, true);
				return $html;
			},
			'sql' => function (&$instance, &$sqlField) {
				$sqlField['type'] = 'time';
			}
		)
	),
	'select' => array(
		'labels' => array('singular' => t('Sélection'), 'plural' => t('Sélections')),
		'extends' => 'text',
		'validate' => function (&$instance, &$select) {
			if( isset($instance['required']) && trim($select) === '' ){
				$instance['errors']['required'] = t('le champ %s est requis', array($instance['label']));
			}elseif( !in_array($select, $instance['datas']) ){
				$instance['errors']['invalid'] = t('la valeur %s ne fait pas parti de la liste de sélection.', array($select));
			}
		},
		'convertTo' => array(
			'field' => function(&$instance) {
				$attrs = array();
				if( isset($instance['readonly']) && $instance['readonly'] === true ){
					$attrs['readonly'] = 'readonly';
					$attrs['aria-readonly'] = 'true';
				}
				if( isset($instance['required']) && $instance['required'] === true ){
					$attrs['aria-required'] = 'true';
				}
				if( isset($instance['disabled']) && $instance['disabled'] === true ) {
					$attrs['disabled'] = $instance['disabled'];
				}
				$content = '';
				foreach ($instance['datas'] as $key => $value) {
					$key = is_string($key) ? $key : $value;
					$optAttrs = array('value' => $key);
					if( isset($instance['value']) && $instance['value'] === $key ){
						$optAttrs['selected'] = 'selected';
					}
					$content .= tag('option', $value, $optAttrs);
				}
				return tag('select', $content, $attrs);
			}
		)
	)
));


if( !var_get('field/default') ){
	var_set('field/default', array(
		'type' => 'text'
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
	$fieldModel = var_get('fields/' . $field['type']);
	if( $fieldModel ){
		$html = $field['convertTo']['field']($field);
	
		if( !isset($field['label']) || (isset($field['hidden']) && $field['hidden'] !== true) ){
			$label = '';
		}else{
			$attrs = array();
			if( isset($field['id']) ){
				$attrs['id'] = $field['id'];
			}
			$label = tag('label', $field['label'], array('for' => $attrs));
		}
		return $label . $html;
	}
	return '';
}

function fields($fields, &$datas = array()) {
	$html = '';
	foreach ($fields as $key => $field) {
		$fieldName = is_string($key) ? $key : $field['name'];
		if( isset($datas[$fieldName]) ){
			$value['value'] = $datas[$fieldName];
		}
		$html .= field($field);
	}
	return $html;
}

/**
 * Validates a field value.
 * @param array $field The field reference 
 * @param array $value An optional value. If NULL, use field_value().
 * @return boolean TRUE if the value has been validated. FALSE otherwise. Error details can be found in $field['errors'].
 */
function field_validate(&$field, $value = null){
	$field = array_merge(var_get('field/default', array()), $field);
	$fieldModel = var_get('fields/' . $field['type']);
	if( $fieldModel ){
		$value = is_null($value) ? field_value($field) : $value;
		unset($field['errors']);
		if( isset($fieldModel['extends']) ){
			$parentFieldModel = var_get('fields/' . $fieldModel['extends']);
			$parentFieldModel['validate']($field, $value);
		}
		if( isset($fieldModel['validate']) && is_callable($fieldModel['validate']) ){
			$fieldModel['validate']($field, $value);
		}
	}
	$hasErrors = isset($field['errors']) && sizeof($field['errors']);
	return !$hasErrors;
}


/**
 * Validates multiple field values
 * @param array $field An array of fields to validate
 * @param array $value An optional associate array containing the field keys and values to test for.
 * @param array $data The returned validation data.
 * @return boolean TRUE if the values have been validated. FALSE otherwise. Error details can be found in each field. See field_validate().
 */
function fields_validate(&$fields, $values = null) {
	// try the default $_REQUEST values
	if( is_null($values) ){
		$values = $_REQUEST;
	}
	$valid = true;
	foreach ($fields as $key => &$field) {
		$key = is_string($key) ? $key : $field['name'];
		$valid = $valid && field_validate($field, isset($values[$key]) ? $values[$key] : null);
	}
	return $valid;
}

