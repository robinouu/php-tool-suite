<?php

require_once('field.inc.php');
require_once('sql.inc.php');


function models_to_sql($models = array()) {

	$tables = array();
	$manyTables = array();

	foreach ($models as $mod) {
		$mod = array_merge(array(
			'comment' => null,
			'collation' => 'utf8_general_ci'
		), $mod);

		$foreignKeys = array();

		// Construct sql columns by field
		$columns = array();

		$tableName = isset($mod['table']) ? $mod['table'] : $mod['id'];

		foreach ($mod['fields'] as $fieldName => $field) {
			$field = array_merge(var_get('field/default', array()), $field);

			$column = sql_quote($fieldName, true);

			$fieldType = 'VARCHAR(255)';
			if( $field['maxlength'] > 255 || $field['maxlength'] === -1 ){
				$fieldType = 'TEXT';
			}elseif (in_array($field['type'], array('int', 'float', 'double', 'bool', 'datetime', 'date'))){
				$fieldType = $field['type'];
			}elseif( $field['type'] === 'relation' ){
				$fieldType = 'INT(11)';
				if( !is_numeric($field['default']) ){ 
					$field['default'] = 0;
				}

				if( $field['hasMany'] ){
					$manyTableName = $fieldName . '_' . $field['data'];
					$manyTables[] = array(
						'name' => $manyTableName,
						'hasID' => false,
						'columns' => array('id_' . $fieldName . ' int(11) NOT NULL, ', 'id_' . $field['data'] . ' int(11) NOT NULL'),
						'primaryKeys' => array('id_' . $fieldName . ',id_' . $field['data']),
						'foreignKeys' => array(
							'id_' . $fieldName => array('name' => 'FK_id_' . $manyTableName . '_' . $fieldName, 'ref' => $prefix . $fieldName.'(id)'),
							'id_' . $field['data'] => array('name' => 'FK_id_' . $manyTableName . '_' . $field['data'], 'ref' => $prefix . $field['data'].'(id)')
						)
					);

					continue;
				}
			}

			$column .= ' ' . $fieldType;

			if( isset($field['unique']) && $field['unique'] === true ){
				$column .= ' UNIQUE';
			}

			if( isset($field['required']) && $field['required'] === true ){
				$column .= ' NOT NULL';
			}

			if( $field['comment'] ){
				$column .= ' COMMENT ' . sql_quote($field['comment']);
			}

			if( $field['characterSet'] ){
				$column .= ' CHARACTER SET ' . sql_quote($field['characterSet']);
			}elseif( $field['collation'] ){
				$column .= ' COLLATE ' . sql_quote($field['collation']);
			}

			if( $field['type'] == 'relation' && !$field['hasMany'] ){
				$foreignKeys[$fieldName] = array('name' => 'FK_' . $tableName . '_' . $fieldName, 'ref' => $field['data'] . '(id)');
			}

			$columns[] = $column;
		}

		$tables[] = array(
			'name' => $tableName,
			'columns' => $columns,
			'collation' => $mod['collation'],
			'comment' => $mod['comment'],
			'foreignKeys' => $foreignKeys
		);
	}
	foreach ($tables as $table) {
		sql_create_table($table);

	}

	foreach( $manyTables as $manyTable ) {
		sql_create_table($manyTable);
	}
}


function model_name($model) {
	return isset($model['labels']['singular']) ? $model['labels']['singular'] : ucfirst($model['id']);
}