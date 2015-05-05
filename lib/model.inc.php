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
							'id_' . $field['data'] . ' int(11) NOT NULL'
						),
						'primaryKeys' => array('id_' . $tableName . ',id_' . $field['data']),
						'foreignKeys' => array(
							'id_' . $tableName => array('name' => 'FK_id_' . $manyTableName . '_' . $tableName, 'ref' => $prefix . $tableName.'(id)'),
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

	$back = true;
	foreach ($tables as $table) {
		$back = $back && sql_create_table($table);
	}
	foreach( $manyTables as $manyTable ) {
		$back = $back && sql_create_table($manyTable);
	}

	return $back;
}

function model_name($model) {
	return isset($model['labels']['singular']) ? $model['labels']['singular'] : ucfirst($model['id']);
}


class Model {
	
	static public $schema = array();

	protected $selects = array();
	protected $aliases = array();
	protected $using = array();
	protected $joins = array();
	protected $filters = array();
	protected $groupBy = array();
	protected $orderBy = array();
	protected $limit = null;
	protected $offset = null;

	protected $insertions = array();
	protected $deletions = array();
	protected $replacements = array();

	private $modelName;
	private $fields;


	const REGEX_MODEL_PATH = '#(?:`((?:[^`]|``)+)`)|([^`\.]+)#'; // matches model paths

	public function __construct($modelName) {

		$this->modelName = $modelName;

		$model = &Model::$schema[$modelName];

		$tableName = Model::getTableName($modelName);

		foreach( $model['fields'] as $fieldName => $field) {
			$this->fields[$fieldName] = $field = array_merge(var_get('field/default', array()), $field);
		}
		return $this;
	}

	static public function getTableName($modelName) {
		$model = &Model::$schema[$modelName];
		return isset($model['table']) ? $model['table'] : $modelName;
	}
	
	public function select($select) {
		if( is_string($select) ){
			$select = array($select);
		}
		$this->selects = array_merge($this->selects, $select);
		return $this;
	}

	public function groupBy($groupBy) {
		if( is_string($groupBy) ){
			$groupBy = array($groupBy);
		}
		$this->groupBy = array_merge($this->groupBy, $groupBy);
		return $this;
	}

	public function orderBy($orderBy) {
		if( is_string($orderBy) ){
			$orderBy = array($orderBy);
		}
		$this->orderBy = array_merge($this->orderBy, $orderBy);
		return $this;
	}

	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}

	public function offset($offset) {
		$this->offset = $offset;
		return $this;
	}

	private function resolveFieldByPath($modelPath) {
		$parent = $this->modelName;
		$field = null;
		foreach ($modelPath as $path ) {
			$field = &Model::$schema[$parent]['fields'][$path];
			$parent = $field['data'];
		}
		return $field;
	}

	private function getModelPath($path) {
		$modelPath = array();
		if( is_string($path) ){
			if( preg_match_all(Model::REGEX_MODEL_PATH, $path, $matches) ){
				foreach( $matches[1] as $k => $v ) {
					$modelPath[] = $matches[1][$k] ? $matches[1][$k] : $matches[2][$k];
				}
			}
		}elseif( is_array($path) ){
			$modelPath = $path;
		}
		return $modelPath;
	}

	public function using($modelPath, $alias = null, $selectFields = false) {
		
		$modelPath = $this->getModelPath($modelPath);
		$usingKey = implode('.', $modelPath);

		if( in_array($usingKey, array_keys($this->using)) ){
			return;
		}

		if( ($len = sizeof($modelPath)) ){
			
			$parentName = array_pop($modelPath);
			$this->using($modelPath);	
			
			$parentModelField = $this->resolveFieldByPath($modelPath);
			$parentModelName = $parentModelField ? $parentModelField['data'] : $this->modelName;
			$parentAliasKey = implode('.', $modelPath);
			$parentTableName = Model::getTableName($parentModelName);

			$modelPath[] = $parentName;
			$usingModelField = $this->resolveFieldByPath($modelPath);
			$usingModelName = $usingModelField['data'];
			$usingTableName = Model::getTableName($usingModelName);
			

			if( $alias ){
				$this->aliases[$alias] = $usingKey;
			}

			$this->using[$usingKey] = true;
			
			
			if( !$usingModelField['hasMany'] ){
				$this->join(array(
					'type' => 'JOIN',
					'tableLeft' => $parentTableName,
					'aliasLeft' => '',
					'columnLeft' => $parentName,
					'tableRight' => $usingTableName,
					'aliasRight' => $alias,
					'columnRight' => 'id'
				));
				
			}else{

				if( array_search($usingKey, $this->aliases) === FALSE ){
					$this->aliases[$usingModelName] = $usingKey;
				}
			
				$this->join(array(
					'type' => 'LEFT OUTER JOIN',
					'tableLeft' => isset($this->aliases[$parentModelName]) ? $this->aliases[$parentModelName] : $parentTableName,
					'tableRight' => $parentTableName . '_' . $parentName,
					'columnRight' => 'id_' . $parentTableName,
				));

				//$this->aliases[$parentTableName] = $aliasRight;
				$this->join(array(
					'type' => 'LEFT OUTER JOIN',
					'tableLeft' => $parentTableName . '_' . $parentName,
					'columnLeft' => 'id_' . $usingModelName,
					'tableRight' => $usingModelName,
					'aliasRight' => $alias ? $alias : (isset($this->aliases[$usingModelName]) ? $this->aliases[$usingModelName] : ''),
					'columnRight' => 'id'
				));
			}
			//var_dump($this->aliases);

			if( $selectFields ){
				$fields = Model::$schema[$usingTableName]['fields'];
				foreach ($fields as $fieldName => $field) {
					$field = array_merge(var_get('field/default', array()), $field);
					if( $field['hasMany'] ) {
						continue;
					}
					$select = sql_quote($alias ? $alias : $usingTableName, true);

					$select .= '.' . sql_quote($fieldName, true);
					if( $alias ){
						$select .= ' AS ' . sql_quote($alias . '.' . $fieldName, true);
					}
					$this->selects[] = $select;
				}
			}
		}
		return $this;
	}

	public function join($joins) {
		$this->joins[] = $joins;
		return $this;
	}

	public function where($where) {
		$this->filters[] = sql_logic($where);
		return $this;
	}

	public function get(){
		$options = $this->prepareGet();
		return sql_get(Model::getTableName($this->modelName), $options);
	}

	protected function prepareGet() {
		$options = array();
		$options['select'] = sizeof($this->selects) ? implode(', ', $this->selects) : sql_quote(Model::getTableName($this->modelName), true) . '.*';
		$options['join'] = $this->joins;
		$options['where'] = implode(' AND ', $this->filters);
		$options['groupBy'] = implode(', ', $this->groupBy);
		$options['orderBy'] = implode(', ', $this->orderBy);
		$options['limit'] = $this->limit;
		$options['offset'] = $this->offset;
		return $options;
	}

	public function insert(array $data) {
		$this->insertions[] = $data;
		return $this;
	}

	public function replace(array $data) {
		$this->replacements[] = $data;
		return $this;
	}
	
	public function delete($deletion = '') {
		if( $deletion ){
			if( isset($this->aliases[$deletion]) ){
				$deletion = explode('.', $this->aliases[$deletion]);
			}else{
				$deletion = $this->getModelPath($deletion);
			}
			$this->using($deletion);
		}
		$this->deletions[] = $deletion;
		return $this;
	}
	
	public function commit() {
		if( sizeof($this->deletions) ){
			$this->inserted_ids = array();
			$where = implode(' AND ', $this->filters);
			foreach ($this->deletions as $index => $deletion) {
			 	$this->doDeletion($index, $where);
		 	}
		}
		if( sizeof($this->replacements) ){
			$options = $this->prepareGet();
			$tableName = Model::getTableName($this->modelName);
			$options['select'] = $tableName . '.id';
			$tmp_ids = sql_get($tableName, $options);
			$replace_ids = array();
			foreach ($tmp_ids as $row) {
				$replace_ids[] = (int)$row['id'];
			}
			foreach ($this->replacements as $index => $replacement) {
			 	$this->doReplacement($index);
		 	}
		}
		if( sizeof($this->insertions) ){
			$this->inserted_ids = array();
			foreach ($this->insertions as $index => $insertion) {
			 	$this->doInsertion($index);
		 	}
		}
		$this->reset();
		return $this;
	}

	public function reset() {
		$this->insertions = array();
		$this->replaces = array();
		$this->using = array();
		$this->aliases = array();
		$this->joins = array();
		$this->filters = array();
		$this->groupBy = array();
		$this->orderBy = array();
		$this->limit = null;
		$this->offset = null;
		return $this;
	}

	private function doDeletion($index, $where) {
		$modelPath = $this->deletions[$index];
		
		$options = $this->prepareGet();

		$where = '';
		$idName = 'id';
		$tableToDelete = $tableName = Model::getTableName($this->modelName);

		if( is_array($modelPath) && sizeof($modelPath) ){
			$columnName = array_pop($modelPath);
			$parentModelField = $this->resolveFieldByPath($modelPath);
			$parentModelName = $parentModelField ? $parentModelField['data'] : $this->modelName;
			$parentTableName = Model::getTableName($parentModelName);

			$modelPath[] = $columnName;
			$usingModelField = $this->resolveFieldByPath($modelPath);
			$usingModelName = $usingModelField['data'];
			$usingTableName = Model::getTableName($usingModelName);

			if( $usingModelField['hasMany'] ){
				$tableToDelete = $parentTableName . '_' . $columnName;
				$idName = 'id_' . $usingTableName;
				$options['select'] = sql_quote($tableToDelete, true) . '.' . sql_quote('id_' . $usingModelName, true) . ' AS id';
			}else{
				$tableToDelete = $usingTableName;
				$options['select'] = sql_quote($usingTableName, true) . '.`id` AS id';
			}
		}else{
			$options['select'] = sql_quote($tableName, true) . '.`id` AS id';
		}


		$tmp_ids = sql_get($tableName, $options);
		if( $tmp_ids ){
			$ids = array();
			foreach ($tmp_ids as $row) {
				$ids[] = (int)$row['id'];
			}
			$ids = array_unique($ids);
			sql_delete($tableToDelete, $where . $idName . ' IN (' . implode(', ', $ids) . ')');
		}
	}

	private function doInsertion($index, $modelName = null, $subKeys = null) {

		$datas = $this->insertions[$index];
		if( $subKeys ){
			$datas = var_get($subKeys, null, $datas);
		}else{
			$subKeys = array();
		}

		if( !$modelName ){
			$modelName = $this->modelName;
		}

		$model = &Model::$schema[$modelName];
		$tableName = Model::getTableName($modelName);
		$fields = &$model['fields'];

		$relations_ids = array();

		foreach( $fields as $fieldName => $field) {
			$field = array_merge(var_get('field/default', array()), $field);
			if( $field['type'] === 'relation' && isset($datas[$fieldName]) ){
				$data = $datas[$fieldName];
				$relation_id = null;
				if( !$field['hasMany'] ){
					$relation_id = $this->doInsertion($index, $field['data'], array_merge($subKeys, array($fieldName)));
				}else{
					$relations_ids[$fieldName] = array();
					if( is_array($data) ){
						if( is_assoc_array($data) ){
							$relationsData = array('data' => $data);
						}else{
							$relationsData = &$data;
						}
						foreach( $relationsData as $key => $relationData ) {
							if( is_array($relationData) ){
								$relations_ids[$fieldName][] = $this->doInsertion($index, $field['data'], array_merge($subKeys, is_numeric($key) ? array($fieldName, $key) : array($fieldName)));
							}else{
								$relations_ids[$fieldName][] = $relationData;
							}
						}
					}else{
						$relations_ids[$fieldName][] = $data;
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

		if( sizeof($relations_ids) ){
			foreach( $relations_ids as $fieldName => $relation_ids ) {
				$this->inserted_ids[$fieldName] = array();
				foreach ($relation_ids as $relation_id) {
					sql_insert($tableName . '_' . $fieldName, array('id_' . $tableName => $id, 'id_' . $model['fields'][$fieldName]['data'] => $relation_id));
					$this->inserted_ids[$fieldName][] = sql_last_id();
				}
			}
		}

		$this->inserted_ids[] = $id;
		return $id;
	}

	private function doReplacement($index, $modelName = null, $subKeys = null, $replacement_ids = array()) {
		// SOON...
		/*
		$datas = $this->replacements[$index];
		if( $subKeys ){
			$datas = var_get($subKeys, null, $datas);
		}else{
			$subKeys = array();
		}

		if( !$modelName ){
			$modelName = $this->modelName;
		}

		$model = &Model::$schema[$modelName];
		$tableName = Model::getTableName($modelName);
		$fields = &$model['fields'];

		$relations_ids = array();

		foreach( $fields as $fieldName => $field) {
			$field = array_merge(var_get('field/default', array()), $field);
			if( $field['type'] === 'relation' && isset($datas[$fieldName]) ){
				$data = $datas[$fieldName];
				$relation_id = null;
				if( !$field['hasMany'] ){
					$relation_id = $this->doInsertion($index, $field['data'], array_merge($subKeys, array($fieldName)));
				}else{
					$relations_ids[$fieldName] = array();
					if( is_array($data) ){
						if( is_assoc_array($data) ){
							$relationsData = array('data' => $data);
						}else{
							$relationsData = &$data;
						}
						foreach( $relationsData as $key => $relationData ) {
							if( is_array($relationData) ){
								$relations_ids[$fieldName][] = $this->doInsertion($index, $field['data'], array_merge($subKeys, is_numeric($key) ? array($fieldName, $key) : array($fieldName)));
							}else{
								$relations_ids[$fieldName][] = $relationData;
							}
						}
					}else{
						$relations_ids[$fieldName][] = $data;
					}
				}
				unset($datas[$fieldName]);
				if( $relation_id ){
					$datas[$fieldName] = $relation_id;
				}
			}
		}

		sql_update($tableName, $datas, implode(' OR ', $replacement_ids));
		$id = sql_last_id();

		if( sizeof($relations_ids) ){
			foreach( $relations_ids as $fieldName => $relation_ids ) {
				$this->inserted_ids[$fieldName] = array();
				foreach ($relation_ids as $relation_id) {
					sql_insert($tableName . '_' . $fieldName, array('id_' . $tableName => $id, 'id_' . $model['fields'][$fieldName]['data'] => $relation_id));
					$this->inserted_ids[$fieldName][] = sql_last_id();
				}
			}
		}

		$this->inserted_ids[] = $id;
		return $id;
		*/
	}	
};
