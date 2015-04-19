<?php
/**
 * Data
 * @package php-tool-suite
 */
require_once('scrud.inc.php');

function data($name, $options = null){
	$prefix = var_get("sql/prefix");
	$query = sql_select($name);

	$options = array_merge(array(
		'asArray' => false
	), $options);

	if( is_integer($options) ){
		$query .= ' where id = ' . (int)$options;

	}elseif ( is_array($options) ) {

		$fields = array();
		if( isset($options['fields']) && is_string($options['fields']) ){
			$fields = array_map('trim', explode(',', $options['fields']));
		}

		$query = sql_select($name);

		// WHERE CLAUSE
		if( isset($options['where']) ) {
			if( is_string($options['where']) ){
				$query .= ' WHERE ' . $options['where'];
			}elseif( is_array($options['where']) ){
				$query .= ' WHERE ' . sql_where($options['where']);
			}
		}

		// ORDER BY CLAUSE
		if( isset($options['orderby']) && is_string($options['orderby']) ){
			$query .= ' ORDER BY ' . $options['orderby'];
		}

		// LIMIT CLAUSE
		if( isset($options['limit']) ){
			$query .= ' LIMIT ' . (int)$options['limit'];
		}
	}

	//var_dump($query);
	$res = sql_query($query);
	if( $options['asArray'] !== true && is_array($res) && sizeof($res) == 1 ){
		return $res[0];
	}
	return $res;
}

function data_register($name, $datas){
	$database = var_get('sql/schema');
	$realData = array();
	foreach ($database[$name]['fields'] as $key => $field) {
		if( isset($datas[$key]) ){
			$realData[$key] = $datas[$key];
		}
	}
	//var_dump($realData);
	if( sizeof($realData) && sql_insert($name, $realData) ){
		return sql_last_id();
	}else{
		return null;
	}
}

function data_set($name, $datas, $where = array()) {
	if( !is_array($where) && isset($datas['id']) ){
		$where = array('id' => $datas['id']);
	}

	$defaultSqlField = var_get('sql/defaultField');
	$database = var_get('sql/schema');
	$prefix = var_get('sql/prefix');

	$data = $database[$name];
	$fields = $data['fields'];

	$d = array();
	foreach ($fields as $key => $f) {
		if( !isset($datas[$key]) ){
			continue;
		}
		$f = array_merge($defaultSqlField, $f);
		
		if( $f['type'] === 'relation' ){
			
		}else{
			if( isset($datas[$pkey]) ) {
				$d[$pkey] = $datas[$pkey];
			}
		}
	}

	if( !sizeof($d) ){
		return false;
	}
	
	return (bool)sql_update($name, $d, 'WHERE ' . sql_where($where));
}

function data_populate(&$fields, $data){
	if( !is_array($data) ){
		return;
	}
	//var_dump("populate", $fields, "with", $data);
	foreach ($fields as $key => $value) {
		$fields[$key]['value'] = isset($data[$key]) ? $data[$key] : null;
	}
	//var_dump("result in", $fields);
}

function data_validate($dataName, $data, $id = null, &$validationData = array()) {
	$schema = var_get('sql/schema');

	foreach ($schema[$dataName]['fields'] as $fieldName => $field) {
		$field['name'] = $fieldName;
		$field['data'] = $dataName;
		$back = array();
		field_validate($field, isset($data[$fieldName]) ? $data[$fieldName] : field_value($field), $back);
		$validationData = array_merge_recursive($validationData, $back);
	}

	return !sizeof($validationData['errors']);
}