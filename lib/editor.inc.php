<?php
/**
 * Editor
 * @package php-tool-suite
 */
require_once('event.inc.php');
require_once('data.inc.php');

// Permet de déclarer une zone comme étant éditable par l'utilisateur
// INSERT MODE / EDIT MODE
// transfert de l'identifiant du formulaire et 
function editor($uniqueName, &$options) {
	// identifiant obligatoire
	$form_id = 'form-editor-' . slug($uniqueName);

	// identifiant unique

	$insert = true;
	if( (!isset($options['id']) || !$options['id'] ) && isset($_REQUEST[$form_id]) ){
		$options['id'] = (int)$_REQUEST[$form_id];
		$insert = true;
	}

	$options = array_merge(array(
		'dataType' => '',
		'data' => null,
		'id' => null,
		'fields' => null,
		'edit' => true,
		'onSuccess' => null,
		'onError' => null), $options);
	
	if( !$options['dataType'] || !is_string($options['dataType']) ){
		LOG_ERROR('Editor could not find "dataType" parameter. This string must reference the data type used by the current schema.');
		return;
	}

	$schema = var_get("sql/schema", array($options['dataType']));

	
	if( $options['data'] ){
		$options['id'] = $options['data']['id'];
	}

	if( !$options['fields'] ){	
		$options['fields'] = $schema[$options['dataType']]['fields'];
	}else if( is_simple_array($options['fields']) ){
		$newfields = array();
		foreach ($options['fields'] as $fieldName) {
			$newfields[$fieldName] = $schema[$options['dataType']]['fields'][$fieldName];
		}
		$options['fields'] = $newfields;
	}

	if( $options['id'] ){
		$options['id'] = (int)$options['id'];
	}elseif (isset($_REQUEST[$form_id.'_id']) ){
		$options['id'] = $_REQUEST[$form_id . '_id'];
	}

	data_populate($options['fields'], $options['data']);

	if( $options['edit'] ){
		// edit mode
		$errors = '';
		$success = '';
			if( isset($_REQUEST['save']) && isset($_REQUEST['form-editor']) && $_REQUEST['form-editor'] === $form_id ){

			$validationData = array();
			foreach( $options['fields'] as $fieldName => $field ) {
				$options['fields'][$fieldName] = array_merge($schema[$options['dataType']]['fields'][$fieldName], $options['fields'][$fieldName]);
	//			var_dump($schema[$options['dataType']]['fields'][$fieldName]);
				//$options['fields'][$fieldName]['value'] = isset($_REQUEST[$fieldName]) ? $_REQUEST[$fieldName];
			}
			$validate = data_validate($options['dataType'], $_REQUEST, $options['id'], $validationData);
			if ($validate) {
				if( is_callable($options['onSuccess']) ){
					$success = $options['onSuccess']($validationData['data']);
				}
				$options['id'] = data_set($options['dataType'], $validationData['data'], $options['id']);
				
				data_populate($options["fields"], $validationData["data"]);		

				// go to edit mode after insertion/update
				$insert = false;
				
			}else{
				if( is_callable($options['onError']) ){
					$errors = $options['onError']($validationData['errors']);
				}else{
					$errors = form_errors($validationData['errors']);
				}
			}

		}


		$form_fields = '';

		foreach ($options['fields'] as $key => $field) {
			//$field = array_merge(array1)
			$field = array_merge(array('name' => $key, 'id' => $form_id . '-' . $key), $field);
			$form_fields .= field($field);
		}

		if( $options['id'] ){
			$form_fields .= hidden($form_id . '_id', $options['id']);
		}
		/*if ($insert) {

			$form_fields = scrud_create_fields($options['dataType'], $options['fields'], isset($validationData) ? $validationData : null);
		}else{
			$form_fields = scrud_edit_fields($options['dataType'], $options['fields'], $options['id'], isset($validationData) ? $validationData : null, $form_id);
		}*/
		return form($success . $errors . fieldset('Edit data', $form_fields)
		. hidden('form-editor', $form_id) . button_submit('Save', array('name' => 'save')));
	}
	else{
		//hook_register('onValidate', array('form' => $id));
		if( isset($options['fields']) && is_array($options['fields']) ){
			$html = '';
			$defaultSqlField = var_get('sql/defaultField');
			foreach ($options['fields'] as $column => $attrs) {
				$attrs = array_merge($defaultSqlField, $attrs);
				if( $attrs['type'] !== 'hidden' ){
					$html .= isset($attrs['value']) ? $attrs['value'] : (isset($attrs['default']) ? $attrs['default'] : '');
				}
			}
			return $html;
		}
	}
/*
	if( isset($_REQUEST[$id]) && is_string($options['data']) ){
		if( isset($_REQUEST['preview']) ){
			var_dump("fonctionnalité non découverte");
		}elseif( isset($_REQUEST['save']) ){
			var_dump($_REQUEST);
			if( isset($_REQUEST[$form_id]) ){
				$validationData = array();
				if( $validated = validate_fields($options['fields'], $_REQUEST, $validationData) ) {
					var_dump("données validées");
					if( is_callable($options['onSuccess']) ){
						$options['onSuccess']($validationData['data']);
					}
					$options['id'] = data_set($options['data'], $options['id'], $validationData['data']);
					var_dump($options['id']);
				}else{
					if( is_callable($options['onError']) ){
						$options['onError']($validationData['errors']);
					}
				}	
			}
		}
	}	
	++$internalID;
	if( var_get('editor/enabled', false) ){
		$content = '';
		if( isset($options['fields']) && is_array($options['fields']) ){
			foreach ($options['fields'] as $column => $attrs) {
				$content .= scrud_field(null, $column, $attrs);
			}
		}else{
			$content = field(array('type' => 'text', 'maxlength' => 500));
		}
		return form(fieldset('Editer', $content) .
			hidden($id, $options['id']) .
			hidden($form_id, 1) .
			button_submit('Preview') . button_submit('Save'), array('id' => $form_id));
	}else{
		//hook_register('onValidate', array('form' => $id));
		if( isset($options['fields']) && is_array($options['fields']) ){
			$html = '';
			foreach ($options['fields'] as $column => $attrs) {
				$html .= $attrs['default'];
			}
			return $html;
		}
	}
	return '';
	*/
}


?>