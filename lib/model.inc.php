<?php

require_once(dirname(__FILE__).'/core.inc.php');

plugin_require(array('field', 'sql'));

function models_to_sql(&$models) {

	$tables = array();
	$manyTables = array();

	$prefix = sql_prefix();

	foreach ($models as $id => $mod) {
		$mod = array_merge(array(
			'comment' => null,
			'collation' => 'utf8_general_ci'
		), $mod);

		if( !isset($mod['id']) ){
			$mod['id'] = $id;
		}

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
					$manyTableName = $tableName . '_' . $fieldName;
					$manyTables[] = array(
						'name' => $manyTableName,
						'hasID' => false,
						'columns' => array(
							'id_' . $tableName . ' int(11) NOT NULL',
							'id_' . $fieldName . ' int(11) NOT NULL'
						),
						'primaryKeys' => array('id_' . $tableName . ',id_' . $fieldName),
						'foreignKeys' => array(
							'id_' . $tableName => array('name' => 'FK_id_' . $manyTableName . '_' . $fieldName, 'ref' => $prefix . $tableName.'(id)'),
							'id_' . $fieldName => array('name' => 'FK_id_' . $manyTableName . '_' . $field['data'], 'ref' => $prefix . $field['data'].'(id)')
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

function model_select_fields($fields, $aliasPrefix = '') {
	$back = array();
	foreach ($fields as $fieldName => $field) {
		$field = array_merge(var_get('field/default', array()), $field);
		if( $field['type'] !== 'relation' ){
			$back[] = $fieldName . ' AS ' . sql_quote($aliasPrefix . $fieldName, true);
		}
	}
	return $back;
}

function model_get(&$models, $modelName, $options = array()){
	

	$model = $models[$modelName];
	$tableName = isset($model['table']) ? $model['table'] : $model['id'];
	$selects = array();
	$joins = array();
	foreach( $model['fields'] as $fieldName => $field) {
		$field = array_merge(var_get('field/default', array()), $field);
		

		if( $field['type'] === 'relation' ){
			if( !$field['hasMany'] ){
				$joins[] = array(
					'type' => ($field['required'] ? 'INNER JOIN' : 'LEFT JOIN'),
					'tableRight' => $field['data'],
					'aliasRight' => $fieldName,
					'columnRight' => 'id',
					'columnLeft' => $fieldName,
				);
				$selects = array_merge($selects, model_select_fields($models[$fieldName]['fields'], $fieldName . '.'));
			}else{
				$joins[] = array(
					'tableRight' => $tableName . '_' . $fieldName,
					'columnRight' => 'id_' . $tableName,
				);
				$joins[] = array(
					'tableLeft' => $tableName . '_' . $fieldName,
					'columnLeft' => 'id_' . $fieldName,
					'tableRight' => $field['data'],
					'aliasRight' => $fieldName,
					'columnRight' => 'id'
				);
				$selects = array_merge($selects, model_select_fields($models[$field['data']]['fields'], $fieldName . '.'));
			}
		}else{
			$selects[] = sql_quote($fieldName, true);
		}
	}
	
	$finalOptions['select'] = array(implode(', ', $selects));
	$finalOptions['join'] = $joins;
	$finalOptions['alias'] = $modelName;

	$finalOptions = array_merge($finalOptions, $options);

	return sql_get($modelName, $finalOptions);
}


function model_insert(&$models, $modelName, $datas){

	$model = $models[$modelName];
	$tableName = isset($model['table']) ? $model['table'] : $model['id'];
	$relations_id = array();

	foreach( $model['fields'] as $fieldName => $field) {
		$field = array_merge(var_get('field/default', array()), $field);
		
		if( $field['type'] === 'relation' && isset($datas[$fieldName]) && is_array($data = $datas[$fieldName]) ){
			$relation_id = null;
			
			if( !$field['hasMany'] ){
				$relation_id = model_insert($models, $field['data'], $data);
			}else{
				$relations_id[$fieldName] = array();
				if( is_assoc_array($data) ){
					$relationsData = array($data);
				}else{
					$relationsData = &$data;
				}
				foreach( $relationsData as $relationData ) {
					$relations_id[$fieldName][] = model_insert($models, $field['data'], $relationData);
				}
			}
			unset($datas[$fieldName]);
			if( $relation_id ){
				$datas[$fieldName] = $relation_id;
			}
		}
	}

	sql_insert($tableName, $datas);
	$id = sql_last_id();

	if( sizeof($relations_id) ){
		foreach( $relations_id as $fieldName => $relation_ids ) {
			foreach ($relation_ids as $relation_id) {
				sql_insert($tableName . '_' . $fieldName, array('id_' . $tableName => $id, 'id_' . $fieldName => $relation_id));
			}
		}
	}

	return $id;
}
