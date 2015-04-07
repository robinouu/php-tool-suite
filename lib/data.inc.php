<?php

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

function data_set($name, $datas, $id = null, $where = array()) {
	return data_update($name, $id, $datas, $where, true);
}

function data_update($name, $id, $datas, $where = null, $autocreate = false){
	if( !is_array($where) ){
		$where = array('id' => $id);
	}

	$defaultSqlField = var_get('sql/defaultField');
	$database = var_get('sql/schema');
	$prefix = var_get('sql/prefix');

	$data = $database[$name];
	$fields = $data['fields'];

	$d = array();
	foreach ($fields as $key => $f) {
		$pkey = trim($prefix) != '' ? $prefix . $key : $key;
		$f = array_merge($defaultSqlField, $f);
		
		if( $f['type'] === 'relation' ){
			if( sizeof($child_datas) ){
				//var_dump($child_datas);
				$child_id = scrud_register($f['data'], $datas, $pkey . '_');
				if( isset($child_id) && $child_id ){
					$d[$pkey] = $child_id;
				}
			}else{
				if( isset($datas[$key]) && is_numeric($datas[$key]) ){
					$d[$pkey] = (int)$datas[$pkey];
				}
			}
		}else{
			if( isset($datas[$pkey]) ) {
				$d[$pkey] = $datas[$pkey];
			}
		}
	}

	if( !sizeof($d) ){
		return false;
		//LOG_ERROR('data_update: Could not insert or update row for data "' . $name . '", empty parameter list.');
		//die;
	}

	if( !$id && $autocreate ){
		sql_insert($name, $d);
		return (int)sql_last_id();
	}else if( $id ) {		
		sql_update($name, $d, 'WHERE ' . sql_where($where));
		return (int)$id;
	}
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
	
	$validation = scrud_validate($dataName, $data, $id);

	if( $validation['valid'] ){
		$validationData['data'] = $validation['data'];
		$validationData['errors'] = array();
	}else{
		$validationData['data'] = array();
		$validationData['errors'] = $validation['errors'];
	}
	return sizeof($validationData['errors']) === 0;
}