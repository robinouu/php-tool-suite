<?php
require_once('log.inc.php');
require_once('var.inc.php');

var_set('sql/defaultField', array(
	'type' => 'text',
	'maxlength' => 255,
	'default' => null,
	'unique' => false,
	'required' => false,
	'formatter' => null,
	'class' => '',
	'searchable' => true,
	'hasMany' => false, // for many to one relationships
));

function sql_connect($options = array()) {
	//LOG_ARRAY($GLOBALS);
	if( ($sql = var_get('sql/dbConnection')) !== null ){
		return $sql;
	}

	$options = array_merge(array(
		'host' => var_get('sql/host', '127.0.0.1'),
		'db' => var_get('sql/database', 'datas'),
		'user' => var_get('sql/user', 'root'),
		'pass' => var_get('sql/pass', '')
	), $options);

	try {
		//var_dump('mysql:host='.var_get('sql/host', '127.0.0.1').';dbname='.var_get('sql/database', 'cms'));
		$sql = new PDO('mysql:host='.$options['host'].';dbname='.$options['db'], $options['user'], $options['pass'] );	
	}catch (Exception $e){
		LOG_ERROR($e->getMessage());
		die;
	}

	var_set('sql/dbConnection', $sql);

	$sql->exec('SET NAMES utf8;');
	$sql->exec('USE ' . $options['db'] . ';');
 	$sql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    return $sql;
}

function sql_disconnect() {
	if( ($sql = var_set('sql/dbConnection')) !== null ){
		var_unset('sql/dbConnection');
	}
}

function sql_prefix() {
	return var_get('sql/prefix');
}

function sql_query($query, $values = array(), $fetchMode = PDO::FETCH_ASSOC, $transactional = false) {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$q = $sql->prepare($query);
	if( !$q ){
		print $sql->debugDumpParams();
		print $sql->errorInfo();
	}
	$q->execute($values);
	$res = $q->fetchAll($fetchMode);

	if( sizeof($res) == 0)
		return false;
	return $res;
}

function sql_last_id() {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	return (int)$sql->lastInsertId();
}

function sql_insert($table, $fields) {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$prefix = var_get('sql/prefix', '');

	$query = 'INSERT INTO ' . sql_quote($prefix . $table, true);
	$query .= ' (' . implode(',', array_keys($fields)) . ') VALUES (' . implode(',', array_fill(0, sizeof($fields), '?')) . ')';
	$q = $sql->prepare($query);
	if( !$q ){
		print $sql->debugDumpParams();
		print $sql->errorInfo();
	}
	//var_dump($query, $fields, '<hr />');
	return $q->execute(array_values($fields));
}

function sql_update($table, $fields = array(), $where = array()) {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$prefix = var_get('sql/prefix', '');

	if( !sizeof($fields) ){
		LOG_ERROR('sql_update: no field set.');
	}

	$query = 'UPDATE ' . sql_quote($prefix . $table, true);
	$sql_fields = array();
	foreach ($fields as $key => $value) {
		$sql_fields[] = $key . ' = ' . sql_quote($value);
	}
	$query .= ' SET ' . implode(',', $sql_fields) . ' ';
	$q = $sql->prepare($query . ' WHERE ' . sql_where($where));
	if( !$q ){
		print $sql->debugDumpParams();
		print $sql->errorInfo();
	}
	//var_dump($query.$where, $fields, '<hr />');
	return $q->execute();
}

function sql_select($table, $fields = '*') {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$prefix = var_get('sql/prefix', '');
	
	$query = 'SELECT ';
	if( is_string($fields) ){
		$fields = array($fields);
	}

	$query .= implode(',', $fields) . ' FROM ' . sql_quote($prefix . $table, true);
	return $query;
}

function sql_delete_table($table) {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$prefix = var_get('sql/prefix', '');
	$sql->query('SET FOREIGN_KEY_CHECKS = 0');
	$res = $sql->query('DROP TABLE IF EXISTS ' . sql_quote($prefix . $table, true));
	$sql->query('SET FOREIGN_KEY_CHECKS = 1');
	return $res;
}

function sql_delete_tables($tables = array()) {
	foreach ($tables as $table) {
		sql_delete_table($table);
	}
}

function sql_where($where = array(), $op = 'AND') {
	$res = array();
	if( is_string($where) ){
		return $where;
	}
	foreach ($where as $key => $value) {
		if( is_string($value) ) {
			$res[] = $key . ' = ' . sql_quote($value);
		}elseif (is_numeric($value) ) {
			$res[] = $key . ' = ' . $value;
		}
	}
	return implode(' ' . $op . ' ', $res);
}

function sql_list_tables() {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$query = 'SHOW TABLES';
	$query = $sql->query($query);
	return $query->fetchAll(PDO::FETCH_COLUMN);
}

function sql_quote($text, $column_or_table = false){
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	if( is_null($text) || $text === ''){
		return 'NULL';
	}
	if( !is_string($text) && is_numeric($text) ){
		return $text;
	}
	if( !$column_or_table ){
		return $sql->quote($text);
	}
	return preg_replace('/[^0-9a-zA-Z_]/', '', $text);
}
function sql_describe($table) {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$query = 'DESCRIBE ' . sql_quote($table, true);
	return sql_query($query);
}

function sql_schema($schema, $forceDeletion = false) {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$prefix = var_get('sql/prefix', '');
	$defaultSqlField = var_get('sql/defaultField');
	$tables = sql_list_tables();

	if( $forceDeletion === true ){
		$newContentTypes = array_keys($schema);
		// suppression des types qui sont obsolètes dans la base
		foreach( $tables as $table ) {
			$ct = substr($table, strlen($prefix));
			if( substr($table, 0, strlen($prefix)) === $prefix && !in_array($ct, $newContentTypes) ){
				sql_delete_table($ct);
				continue;
			}
		}
	}

	foreach ($schema as $contenttype => $info) {
		
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
					$inject[] = 'ALTER TABLE ' . sql_quote($tableName, true) . ' ADD COLUMN ' . sql_quote($fieldName, true) . ' ' . $fieldType . $default . $notnull . ' AFTER ' . sql_quote($lastColumn, true);
					if( $field['type'] == 'relation' ){
						$inject[] = 'ALTER TABLE ' . sql_quote($tableName, true) . ' DROP CONSTRAINT ' . sql_quote('FK_id_' . $contenttype . '_' . $fieldName, true);
						$inject[] = 'ALTER TABLE ' . sql_quote($tableName, true) . ' ADD CONSTRAINT ' . sql_quote('FK_id_' . $contenttype . '_' . $fieldName, true) . ' FOREIGN KEY (`' . $fieldName . '`) REFERENCES ' . sql_quote($prefix . $field['data'], true) . '(`id`) ';
					}
				}				
			}else{
				
			}

			$lastColumn = $fieldName;
			++$i;
		}

		$rel = array_merge($rel, $uniques);

		if( !$tableExists ){
			if( isset($info['primaryKey']) && is_array($info['primaryKey']) ){
				$primaryKey = implode(',', $info['primaryKey']);
			}else{
				$primaryKey = 'id';
			}
			$query = 'CREATE TABLE ' . sql_quote($tableName, true) . ' (id INT NOT NULL AUTO_INCREMENT, ' . implode(',', $inject) . ', PRIMARY KEY(' . $primaryKey . ')' . (sizeof($rel) ? ',' . implode(',', $rel) : '') . ') COLLATE utf8_general_ci ENGINE=InnoDB;';
			//print ($query);
			sql_query($query);
		}
		else {
			// On supprime les champs en trop dans la base de donnée
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
				sql_query($query, null, null, true);
			}
		}
	}

}


function sql_table_exists($table) {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	try {
		$result = $sql->query(sql_select($table, '1'));
	} catch (Exception $e) {
		return FALSE;
	}
    return $result !== FALSE;
}