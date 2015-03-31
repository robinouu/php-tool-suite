<?php

class ModuleManager {

	public $modules = array();

	public function __construct() {
		$this->mergeInheritance(array());
	}

	static public function find_path($name, $value, &$path = array()){	
		$data = (is_array($path) && sizeof($path) ? array_get($GLOBALS['proto_data'], $path) : $GLOBALS['proto_data']);
		$isValid = false;

		if( is_array($data) ){
			foreach ($data as $key => $v) {
				array_push($path, $key);
				self::find_path($name, $value, $path);

				if( $key == $name && $v === $value ){
					var_dump($value);
		
					$isValid = true;
					return true;
				}
				array_pop($path);
			}
		}
		return $isValid;
	}

	public function mergeInheritance($path) {
		$allData = &$GLOBALS['proto_data'];
		$data = (sizeof($path) == 0) ? $allData : array_get($allData, $path);

		if( !is_array($data) ){
			return;
		}

		foreach ($data as $k => $v) {
			array_push($path, $k);

			$this->mergeInheritance($path);

			if( $k == 'inherits' ){		
				$inheritPath = array();

				self::find_path('id', $v, $inheritPath);

				if( sizeof($inheritPath) ){
					array_pop($path);
					print 'Importing "' . $path[sizeof($path)-1] . '" : ';
					copyNode($inheritPath, $path);
					print ' FINISHED.<br />';
				}
			}
			array_pop($path);
		}
	}

	public function browse(&$data, &$path) {
		
		$d = array_get($data, $path);
		
		if( !is_array($d) ){
			return;
		}

		foreach ($d as $k => $v) {
			if( is_array($v)) {

				if( $k === 'inherits' ){
					$module = $this->loadModule($v, $data, $path);
				}
				array_push($path, $k);
				if( isset($module) && is_object($module) ){
					$module->onBefore($data, $path);
				}
				$this->browse($data, $path);

				array_pop($path);
	
			}
		}
	//	var_dump(implode('&gt; ', $path), $path);
		if( isset($module) && is_object($module) ){
			$module->onAfter($data, $path);
		}
		return;
	}

	public function loadModule($name) {
		//print 'Loading module "' . $name . '" : ';
		
		
		//print ' NOT FOUND.<br />';
		return false;
	}
}
