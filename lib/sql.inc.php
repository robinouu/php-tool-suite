<?php
/**
 * SQL Databases
 * @package php-tool-suite
 * @subpackage SQL
 */

plugin_require(array('log', 'var'));

/**
 * Connects to an SQL database using PDO, or return the current PDO object if already connected
 * @param array $options The connection options
 * <ul>
 * 	<li>host string The host. '127.0.0.1' by default.</li>
 * 	<li>db string The SQL database to connect to.</li>
 * 	<li>user string The SQL username. 'root' by default.</li>
 * 	<li>pass string The user password. Empty by default.</li>
 * 	<li>charset string The connection charset to use. 'utf8' by default.</li>
 * </ul>
 * @return mixed The PDO object used for the connection or FALSE if the connection could not be established.
 */
function sql_connect($options = array()) {
	
	if( ($sql = var_get('sql/dbConnection')) !== null ){
		return $sql;
	}

	$options = array_merge(array(
		'host' => '127.0.0.1',
		'db' => '',
		'user' => 'root',
		'pass' => '',
		'charset' => 'utf8'
	), $options);

	try {
		$sql = new PDO('mysql:host='.$options['host'].';dbname='.$options['db'], $options['user'], $options['pass']);	
	}catch (Exception $e){
		return false;
	}

	var_set('sql/dbConnection', $sql);

	$sql->exec('SET NAMES ' . $options['charset'] . ';');
	$sql->exec('USE ' . $options['db'] . ';');
	$sql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$sql->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);	
	$sql->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

	return $sql;
}

/**
 * Disconnects from the SQL connection started with sql_connect()
 * @return boolean TRUE if the connection has been unset. FALSE otherwise.
 */
function sql_disconnect() {
	if( ($sql = var_set('sql/dbConnection')) !== null ){
		return var_unset('sql/dbConnection');
	}
	return TRUE;
}

/**
 * Returns the current table prefix.
 * @return string The current table prefix.
 */
function sql_prefix() {
	return var_get('sql/prefix');
}

/**
 * Queries the database.
 * @param string $query The query string to execute on the current database.
 * @param array $values The values to prepare. See 
 * @param int $fetchMode The PDO fetch mode. Default to PDO::FETCH_ASSOC.
 * @return mixed The actual result of the query, or a boolean result if $fetchMode is NULL.
 */
function sql_query($query, $values = array(), $fetchMode = PDO::FETCH_ASSOC) {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$q = $sql->prepare($query);
	//var_dump($query);
	if( !$q ){
		print $sql->debugDumpParams();
		print $sql->errorInfo();
	}
	$result = $q->execute($values);
	if( $fetchMode != null ){
		$res = $q->fetchAll($fetchMode);
		$q->closeCursor();
	}else{
		$q->closeCursor();
		return $result;
	}
	if( sizeof($res) == 0 ){
		return false;
	}
	return $res;
}

/**
 * Returns the last auto-incremented field inserted in database.
 * @return int The last auto-incremented field inserted in database.
 */
function sql_last_id() {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	return (int)$sql->lastInsertId();
}


/**
 * Inserts a row in database.
 * @param string $table The table name where to insert data. It will automatically be prefixed.
 * @param array $fields An associative array containing the data to insert
 * <pre><code>array('column1' => 'value1', 'column2' => 'value2')</code></pre>
 * @return boolean TRUE if the data has been inserted in the table. FALSE otherwise.
 */
function sql_insert($table, $fields) {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$prefix = var_get('sql/prefix', '');

	$query = 'INSERT INTO ' . sql_quote($prefix . $table, true);
	$query .= ' (' . implode(',', array_keys($fields)) . ') VALUES (' . implode(',', array_fill(0, sizeof($fields), '?')) . ');';
	$q = $sql->prepare($query);
	if( !$q ){
		print $sql->debugDumpParams();
		print $sql->errorInfo();
	}

	return $q->execute(array_values($fields));
}


/**
 * Updates rows in database.
 * @param string $table The table name where to insert data. It will automatically be prefixed.
 * @param array $fields An associative array containing the columns to update
 * <pre><code>array('column1' => 'value1', 'column2' => 'value2')</code></pre>
 * @param string $where An optional SQL filter. You can use sql_logic() to prepare your conditions.
 * @return boolean TRUE if the data has been updated in the table. FALSE otherwise.
 */
function sql_update($table, $fields = array(), $where = null, $join = null) {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$prefix = var_get('sql/prefix', '');

	$query = 'UPDATE ' . sql_quote($prefix . $table, true);
	
	$sql_fields = array();
	foreach ($fields as $key => $value) {
		if( is_numeric($key) && is_string($value) ){
			$sql_fields[] = $value;	
		}else{
			$sql_fields[] = $key . ' = ' . sql_quote($value);
		}
	}
	$query .= ' SET ' . implode(', ', $sql_fields) . ' ';

	if( $join ){
		if( is_string($join) ){
			$query .= ' ' . $join;
		}elseif( is_array($options['join']) ){
			$query .= ' ' . sql_join($join, $table);
		}
	}

	$query .= !$where ? '' : ' WHERE ' . sql_logic($where);

	$q = $sql->prepare($query . ';');
	if( !$q ){
		print $sql->debugDumpParams();
		print $sql->errorInfo();
	}
	return $q->execute();
}
/**
 * Deletes rows from database.
 * @param string $table The table name where to delete data. It will automatically be prefixed.
 * @param string $where An optional SQL filter. You can use sql_logic() to prepare your conditions.
 * @return boolean TRUE if the data has been delete from the table. FALSE otherwise.
 */
function sql_delete($table, $where = null) {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$prefix = var_get('sql/prefix', '');

	$query = 'DELETE FROM ' . sql_quote($prefix . $table, true);
	$query .= !$where ? '' : ' WHERE ' . sql_logic($where);
	//var_dump($query);
	$q = $sql->prepare($query . ';');
	if( !$q ){
		print $sql->debugDumpParams();
		print $sql->errorInfo();
	}
	return $q->execute();
}

function sql_select($table, $fields = '*', $alias = '', $prefixed = true) {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$prefix = $prefixed ? var_get('sql/prefix', '') : '';
	
	$query = 'SELECT ';
	if( is_string($fields) ){
		$fields = array($fields);
	}

	$query .= implode(',', $fields) . ' FROM ' . sql_quote($prefix . $table, true);
	if( $alias ){
		$query .= ' ' . sql_quote($alias, true);
	}
	return $query;
}

/**
 * Truncates a table.
 * @param string $table The table to truncate. It will automatically be prefixed.
 * @return boolean TRUE if the table has been truncated. FALSE otherwise.
 */
function sql_truncate_table($table) {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$prefix = var_get('sql/prefix', '');
	$sql->query('SET FOREIGN_KEY_CHECKS = 0;');
	$res = sql_query('TRUNCATE TABLE ' . sql_quote($prefix . $table, true) . ';', null, null);
	$sql->query('SET FOREIGN_KEY_CHECKS = 1;');
	return $res;
}

/**
 * Deletes a table.
 * @param string $table The table to delete. It will automatically be prefixed.
 * @return boolean TRUE if the table has been deleted. FALSE otherwise.
 */
function sql_delete_table($table) {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$prefix = var_get('sql/prefix', '');
	$sql->query('SET FOREIGN_KEY_CHECKS = 0');
	$res = sql_query('DROP TABLE IF EXISTS ' . sql_quote($prefix . $table, true), null, null);
	$sql->query('SET FOREIGN_KEY_CHECKS = 1');
	return $res;
}


/**
 * Deletes multiple tables.
 * @param boolean $foreignKeyCheck By default, and if set to FALSE, foreign key checks will be ignored.
 * @param array $table The tables to delete. They will automatically be prefixed. By default, and if set to NULL, all tables are deleted.
 * @return boolean TRUE if the tables have been deleted. FALSE otherwise.
 */
function sql_delete_tables($tables = null, $foreignKeyCheck = false) {

	$sql = sql_connect();
	if( !$sql || (is_array($tables) && !sizeof($tables)) ){
		return false;
	}
	if( is_null($tables) ){
		$tables = sql_list_tables();
	}
	$res = true;
	$prefix = var_get('sql/prefix', '');
	if( !$foreignKeyCheck ){
		$sql->query('SET FOREIGN_KEY_CHECKS = 0');
	}
	foreach ($tables as $table) {
		$res = sql_query('DROP TABLE IF EXISTS ' . sql_quote($prefix . $table, true), null, null) && $res;
	}
	if( !$foreignKeyCheck ){
		$sql->query('SET FOREIGN_KEY_CHECKS = 1');
	}
	return $res;
}

function sql_logic($conditions, $op = 'AND') {
	if( is_string($conditions) ){
		return $conditions;
	}
	$res = '';
	$isArray = false;
	foreach ($conditions as $k => $el) {
		if( is_array($el) && $isArray ){
			$res .= ' ' . $op;
		}else{
			$isArray = false;
		}
		if( is_numeric($k) && is_string($el) ) {
			$res .= ' ' . trim($el);
			continue;
		}
		if (is_numeric($k) && is_array($el) ){
			$res .= ' ' . sql_logic($el, $op);
			$isArray = true;
		}else{
			if( is_string($k) ){
				if( !is_array($el) ){
					$el = array($el);
				}
				$el = array_map('sql_quote', $el);
				$res .= vsprintf($k, $el);
				$isArray = true;
			}else{
				$res .= $k . ' = ' . sql_quote($el);
			}
		}
	}
	return trim($res);
}

function sql_join($joins, $table = ''){
	if( is_assoc_array($joins) ){
		$joins = array($joins);
	}
	$query = '';
	foreach ($joins as $join) {
		$join = array_merge(array(
			'tableLeft' => $table,
			'aliasLeft' => '',
			'aliasRight' => '',
			'columnLeft' => 'id',
			'columnRight' => $table,
			'type' => 'INNER JOIN'), $join);
		$query .= ' ' . $join['type'] . ' ' . sql_quote($join['tableRight'], true) . ($join['aliasRight'] ?  ' ' . sql_quote($join['aliasRight'], true) : '') .
			' ON ' . ($join['aliasRight'] ? sql_quote($join['aliasRight'], true) : sql_quote($join['tableRight'], true)) . '.' . sql_quote($join['columnRight'], true) .
			' = ' . ($join['aliasLeft'] ? sql_quote($join['aliasLeft'], true) : sql_quote($join['tableLeft'], true)) . '.' . sql_quote($join['columnLeft'], true);
	}
	return ltrim($query);
}

/**
 * Lists all tables from the current database.
 * @return array An array of tables that exist in database.
 */
function sql_list_tables() {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$query = 'SHOW TABLES';
	$query = $sql->query($query);
	return $query->fetchAll(PDO::FETCH_COLUMN);
}


/**
 * Converts a value to its SQL representation.
 * @param mixed $value The value to convert. 
 * @param boolean $column_or_table If set to TRUE, sanitize the string to accept a column or table name.
 * @return mixed Returns the SQL representation of a value. If the value is NULL, returns 'NULL'. Otherwhise, PDO::quote is used.
 */
function sql_quote($text, $column_or_table = false){
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	if( is_null($text) ){
		return 'NULL';
	}
	if( !is_string($text) && is_numeric($text) ){
		return $text;
	}
	if( !$column_or_table ){
		return $sql->quote($text);
	}
	return '`' . str_replace('`', '``', $text) . '`';
}


/**
 * Describes a table.
 * @param string $table The table to describe.
 * @return array Returns the table description (Column name, column type, Nullable, Key type, Default value, Extra)
 * @see https://dev.mysql.com/doc/refman/4.1/en/describe.html
 */
function sql_describe($table) {
	$sql = sql_connect();
	if( !$sql ){
		return false;
	}
	$query = 'DESCRIBE ' . sql_quote($table, true);
	return sql_query($query);
}

/**
 * Checks if the table exists in the database.
 * @param string $table The table to check for existence.
 * @return boolean Returns TRUE if the table exists. FALSE otherwise.
 */
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


/**
 * Creates a table in database
 * @param array $options The table configuration
 * <ul>
 * 	<li>name string The table to create. It will automatically be prefixed.</li>
 * 	<li>hasID boolean If set to TRUE, an 'id' primary column with auto-increment behaviour will be inserted at the beginning of the table. TRUE by default.</li>
 * 	<li>columns array The columns to add to the table. Example : <pre><code>array('username VARCHAR(255) NOT NULL UNIQUE');</code></pre></li>
 * 	<li>primaryKeys array An array of column names to use for primary keys.</li>
 * 	<li>foreignKeys array An array of foreign keys. Example :
 *  <pre><code>array(
 *		'user_id' => 'user(id)',
 * 		'news_id' => array('name' => 'FK_news_ref', 'ref' => 'news(id)')
 *	);</pre></code></li>
 * 	<li>collation string The collation to use. 'utf8_general_ci' by default.</li>
 * 	<li>engine string The engine to use. 'InnoDB' is used by default or when foreign keys have been set.</li>
 * </ul>
 * @return boolean Returns TRUE if the table has been created. FALSE otherwise.
 */
function sql_create_table($options) {
	$options = array_merge(array(
		'hasID' => true,
		'comment' => null,
		'columns' => array(),
		'primaryKeys' => array(),
		'uniqueKeys' => array(),
		'foreignKeys' => array(),
		'collation' => 'utf8_general_ci',
		'engine' => 'InnoDB',
	), $options);

	if( $options['hasID'] ){
		array_unshift($options['columns'], 'id INT(11) NOT NULL AUTO_INCREMENT');
		array_unshift($options['primaryKeys'], 'id');
	}

	$attributes = $options['columns'];

	if( sizeof($options['primaryKeys']) ){
		$attributes[] = 'PRIMARY KEY (' . implode(', ', $options['primaryKeys']) . ')';
	}

	if( sizeof($options['uniqueKeys']) ){
		// array('id', 'test')
		if( is_simple_array($options['uniqueKeys']) ){
			$options['uniqueKeys'] = array(array('name' => 'uniqueKeys', 'columns' => $options['uniqueKeys']));
		}elseif( is_assoc_array($options['uniqueKeys']) ){
			$options['uniqueKeys'] = array($options['uniqueKeys']);
		}
		foreach ($options['uniqueKeys'] as $uk) {
			$attributes[] = 'CONSTRAINT ' . sql_quote($uk['name'], true) . ' UNIQUE (' . implode(', ', $uk['columns']) . ')';
		}
	}

	if( sizeof($options['foreignKeys']) ){
		$options['engine'] = 'InnoDB';
		foreach ($options['foreignKeys'] as $key => $ref) {
			if( is_string($ref) ){
				$attributes[] = 'FOREIGN KEY (' . $key . ') REFERENCES ' . $ref;
			}elseif( is_array($ref) ){
				if( !isset($ref['name']) ){
					$attributes[] = 'FOREIGN KEY (' . $key . ') REFERENCES ' . $ref;
				}else{
					$attributes[] = 'CONSTRAINT ' . $ref['name'] . ' FOREIGN KEY (' . $key . ') REFERENCES ' . $ref['ref'];
				}
			}
		}
	}

	$comment = '';
	if( $options['comment'] ){
		$comment = ' COMMENT=' . sql_quote($options['comment']);
	}

	$collation = '';
	if( $options['collation'] ){
		$comment = ' COLLATE ' . sql_quote($options['collation']);
	}

	$engine = '';
	if( $options['engine'] ){
		$engine = ' ENGINE=' . sql_quote($options['engine']);
	}

	$prefix = sql_prefix();

	$query = 'CREATE TABLE IF NOT EXISTS ' . sql_quote($prefix . $options['name'], true) . ' (' . implode(', ', $attributes) . ' ) ' . $comment . $collation . $engine . ';';
	return sql_query($query, null, null);
}

function sql_alter_table($table, $options = array()) {

	$inject = array();


	// Alter table collation (mysql only)
	if( sql_driver() === 'mysql' ){
		if( isset($options['collation']) && is_string($options['collation']) ){
			$res = sql_query('SHOW COLLATION WHERE Collation = "' . $options['collation'] . '" ');
			if( $res ){
				$inject[] = 'ALTER TABLE ' . sql_quote($table, true) . ' CONVERT TO CHARACTER SET ' . $res[0]['Charset'] . ' COLLATE ' . $options['collation'] . ';';
			}
		}elseif( isset($options['charset']) && is_string($options['charset']) ){
			$inject[] = 'ALTER TABLE ' . sql_quote($table, true) . ' CONVERT TO CHARACTER SET ' . $options['charset'] . ';';
		}
	}

	
	$tableDescription = sql_describe($table);
	//var_dump($tableDescription);


	if( isset($options['tableName']) && $options['tableName'] !== $table ){
		$inject[] = 'ALTER TABLE ' . sql_quote($table, true) . ' RENAME TO ' . $options['tableName'] . ';';
	}

	$res = true;
	foreach ($inject as $query) {
		$res = $res && sql_query($query, null, null);
	}
	return $res;
}


/**
 * Returns the current PDO driver.
 * @return boolean Returns TRUE if the table has been created. FALSE otherwise.
 * @see http://php.net/manual/en/pdo.getavailabledrivers.php
 */
function sql_driver() {
	$sql = sql_connect();
	return $sql->getAttribute(PDO::ATTR_DRIVER_NAME);
}


function sql_get($table, $options = array()){

	$options = array_merge(array(
		'alias' => '',
		'join' => null,
		'groupBy' => null,
		'orderBy' => null,
		'limit' => null,
		'offset' => null
	), $options);

	if( is_integer($options) ){
		$query = sql_select($table) . ' WHERE id = ' . (int)$options;
	}elseif (is_array($options)) {

		$fields = '*';
		$onlyOne = false;

		// SELECT CLAUSE
		if( isset($options['select']) ) {
			if( !$options['select'] ){
				$fields = '*';
			}elseif( is_string($options['select']) ){
				$fields = $options['select'];
			}elseif( is_array($options['select']) ){
				$fields = $options['select'];
				$onlyOne = sizeof($options['select']) == 1;
			}
		}

		$query = sql_select($table, $fields, $options['alias']);

		// JOIN CLAUSE
		if( $options['join'] ){
			if( is_string($options['join']) ){
				$query .= ' ' . $options['join'];
			}elseif( is_array($options['join']) ){
				$query .= ' ' . sql_join($options['join'], $table);
			}
		}

		// WHERE CLAUSE
		if( isset($options['where']) ) {
			$query .= $options['where'] ? ' WHERE ' . sql_logic($options['where']) : '';
		}

		// GROUP BY CLAUSE
		if( $options['groupBy'] ){
			$query .= ' GROUP BY ' . $options['groupBy'];
		}

		// ORDER BY CLAUSE
		if( $options['orderBy'] ){
			$query .= ' ORDER BY ' . $options['orderBy'];
		}

		// LIMIT CLAUSE
		if( $options['limit'] ){
			$query .= ' LIMIT ' . (int)$options['limit'];
			if( $options['offset'] ){
				$query .= ' OFFSET ' . (int)$options['offset'];
			}
		}
	}

	//var_dump($query);
	$res = sql_query($query, null, !$onlyOne ? PDO::FETCH_ASSOC : PDO::FETCH_COLUMN );
	if( $options['limit'] === 1 ){
		return $res[0];
	}
	return $res;
}


function sql_import_csv($options) {
	$sql = sql_connect();
	$options = array_merge(array(
		'charset' => 'utf8',
		'columns' => null,
		'lineStarting' => '',
		'lineEnding' => "\n",
		'fieldEnclosing' => '',
		'fieldEscaping' => '',
		'fieldEnding' => ',',
		'ignoreLines' => 0
	), $options);

	if( !isset($options['filename'], $options['table']) || sql_driver() !== 'mysql' ){
		return FALSE;
	}

	$options['filename'] = str_replace('/', '\\', $options['filename']);
	$query = 'LOAD DATA INFILE ' . sql_quote($options['filename']) . ' INTO TABLE ' . sql_quote($options['table'], true);
	$query .= ' CHARACTER SET ' . sql_quote($options['charset']);
	$query .= ' FIELDS TERMINATED BY ' . $sql->quote($options['fieldEnding']) . ' ENCLOSED BY ' . $sql->quote($options['fieldEnclosing']) . ' ESCAPED BY ' . $sql->quote($options['fieldEscaping']);
	$query .= ' LINES STARTING BY ' . $sql->quote($options['lineStarting']) . ' TERMINATED BY ' . $sql->quote($options['lineEnding']);
	$query .= ' IGNORE ' . $options['ignoreLines'] . ' LINES';
	if( is_array($options['columns']) ){
		$query .= ' (' . implode(', ', $options['columns']) . ')';
	}
	$query .= ';';
	return sql_query($query, null, null);
}