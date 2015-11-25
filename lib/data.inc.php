<?php
/**
 * Data
 * Thanks to nick2083 implementation (see 	)
 * @package php-tool-suite
 */
require_once('sql.inc.php');

/* Provides an easy to use interface for reading/writing associative array based information */
/* by exposing attributes that represents each key of the array */
class Data {

    /* Keeps the state of each attribute  */
    private $name;
    private $attributes; // CREDITS
    private $and_filter; // robin + charles + doriane
    private $or_filter; // charles
    private $order_by_filter; // doriane
    private $limit_filter; // charles
    private $offset_filter; // olivier

    private $filter_index; // inspiration

    /* Creates a new Data instance initialized with $attributes */
    public function __construct($name, $attributes = array()) {
    	$this->name = $name;
    	$this->attributes = $attributes;
		$this->and_filter = array();
        $this->or_filter = array();
        $this->order_by_filter = array();
        $this->limit_filter = null;
        $this->offset_filter = null;
        $this->filter_index = 0;
    }

	/**
     * Search a data by one or multiple filters
     * 
     * @param $filter array The filter(s) to use to search the data
     * @return $this data The data instance.
     */
    public function search($filter=array()){
		if( is_string($filter) ){
			$filter = array($filter);
		}
		++$this->filter_index;
		foreach( $filter as $and_filter ) {
			if( is_string($and_filter) ){
				$this->and_filter[$this->filter_index] = array('AND', $and_filter);
			}elseif( is_array($and_filter) && sizeof($and_filter) == 3 && sql_is_compare_operator($and_filter[1]) ){
				$this->and_filter[$this->filter_index] = array('AND', $and_filter[0] . ' ' . $and_filter[1] . ' ' . sql_quote($and_filter[2]));
			}else{
				throw new Exception("Could not interpret this 'AND' filter");
			}
		}
    	return $this;
    }

    public function and_search($filter=array()){
		return $this->search($filter);
    }

    /**
     * Search a data by filters using an OR condition
     * @param $filter The data filters to use to search the data
     * @return $this data The data instance
     */
    public function or_search($filter){
    	if( is_string($filter) ){
			$filter = array($filter);
		}
		++$this->filter_index;
		foreach( $filter as $or_filter ) {
			if( is_string($or_filter) ){
				$this->or_filter[$this->filter_index] = array('OR', $or_filter);
			}elseif( is_array($or_filter) && sizeof($or_filter) == 3 && is_sql_operator($or_filter[1]) ){
				$this->$or_filter[$this->filter_index] = array('OR', $or_filter[0] . ' ' . $or_filter[1] . ' ' . sql_quote($or_filter[2]));
			}else{
				throw new Exception("Could not interpret this 'OR' filter");
			}
		}
		return $this;
    }

    /**
     * Order data by one or multiple fields, using ascending or descending order
     * @param $order
     */
    public function order_by($filter=array()){
    	if( is_string($filter) ){
			$filter = array($filter);
		}
    	foreach( $filter as $order_by_filter ) {
			if( is_string($order_by_filter) ){
				$this->order_by_filter[] = $order_by_filter;
			}else{
				throw new Exception("Could not interpret this 'ORDER BY' filter request");
			}
		}
		return $this;
    }

	/**
	* Limit the number of data found
	* @param $limit int The number of datas to return
	*/
	public function limit($limit = 1){
		$this->limit_filter = $limit;
		return $this;
	}
	/**
	* Offset the data results
	* @param $offset int The number of datas to offset from
	*/
	public function offset($offset){ // KYLE 
		$this->offset_filter = $offsse;
		return $this;
	}

    /**
     * Executes a collection of filters on a particular data 
     */
    public function exec() {
    	$where = '';
		for( $i = 1; $i <= $this->filter_index; ++$i ){
			if( isset($this->and_filter[$i]) ){
				$where .= ' ' . (!empty($where) ? $this->and_filter[$i][0] : '') . ' ' . $this->and_filter[$i][1];
			}elseif( isset($this->or_filter[$i]) ){
				$where .= ' ' . (!empty($where) ? $this->or_filter[$i][0] : '') . ' ' . $this->or_filter[$i][1];
			}
		}		
		$options = array(
			'where' => ltrim($where),
			'orderBy' => implode(', ', $this->order_by_filter),
			'limit' => $this->limit_filter,
			'offset' => $this->offset_filter
		);

		$data = sql_get($this->name, $options);

		return $data;
    }
}


function data_register($table, $datas){
	$database = var_get('sql/schema');
	$realData = array();
	foreach ($database[$name]['fields'] as $key => $field) {
		if( isset($datas[$key]) ){
			$realData[$key] = $datas[$key];
		}
	}
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

function data_validate($dataName, $data, &$validationData = array()) {
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

function data($model){
	$instance = new Data($model);
	return $instance;
}

