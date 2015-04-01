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

function data_register($name, $datas, $prefix = ''){
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
			if( isset($datas['meta_newone_'.$pkey]) && (int)$datas['meta_newone_'.$pkey] === 1 ){
				$child_id = data_register($f['data'], $datas, $pkey . '_');
				//var_dump($child_id, 'meta_newone_'.$pkey);
				if( $child_id ){
					$datas['meta_newone_'.$pkey] = 0;
					$datas[$pkey] = $child_id;
					$d[$key] = $child_id;
				}
			}elseif( isset($datas[$pkey]) && is_numeric($datas[$pkey]) ){
				$d[$key] = (int)$datas[$pkey];
			}else{
				//var_dump($key, "BIT");
				$d[$key] = null;
			}

		}else{
			$d[$key] = isset($datas[$pkey]) ? $datas[$pkey] : null;
			/*if( isset($datas[$pkey]) && ( (bool)$f['required'] == true && trim($datas[$pkey]) != '') ){
				$d[$key] = $datas[$pkey];
			}else{
				$d[$key] = $datas[$pkey];
			}*/
			/*elseif( $f['unique'] === true && $f['required'] !== true ){
				var_dump($key, "3MAP");
				$d[$key] = null;
			}*/
		}

	}
	
	foreach ($d as $key => $value) {
		//var_dump(substr($key, 0, strlen('meta_')) === 'meta_', substr($key, 0, strlen('meta_')));	
		if( substr($key, 0, strlen('meta_')) === 'meta_' ){
			unset($d[$key]);
		}
	}

	if( sizeof($d) && sql_insert($name, $d) ){
		return sql_inserted_id();
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

//	var_dump($datas);
	$d = array();
	foreach ($fields as $key => $f) {
		$pkey = trim($prefix) != '' ? $prefix . $key : $key;
		$f = array_merge($defaultSqlField, $f);
		
		if( $f['type'] === 'relation' ){
			//var_dump($datas);
			if( isset($datas['meta_newone_'.$pkey]) && (int)$datas['meta_newone_'.$pkey] === 1) {
				$child_datas = array();
				$child_id = null;
				//var_dump($datas);
				foreach ($datas as $k => $v){
					//var_dump($k, $pkey);
					if( substr($k, 0, strlen($pkey)) === $pkey ){
						$child_datas[$k] = $v;
					}elseif (substr($k, 0, strlen('meta_')) === 'meta_'){
						$child_datas[$k] = $v;
					}
				}
				//var_dump($child_datas);
				if( sizeof($child_datas) ){
					//var_dump($child_datas);
					$child_id = scrud_register($f['data'], $datas, $pkey . '_');

				}
				if( isset($child_id) && $child_id ){
					$d[$pkey] = $child_id;
					$datas['meta_newone_'.$pkey] = 0;
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

	if( !$d ){
		LOG_ERROR('data_update: Could not insert or update row for data "' . $name . '", empty parameter list.');
		//die;
	}

	if( !$id && $autocreate ){
		sql_insert($name, $d);
		return (int)sql_inserted_id();
	}else if( $id ) {
		
		sql_update($name, $d, 'WHERE id = ' . (int)$id);
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

function data_validate($dataName, $data, $id = null, &$validationData) {
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