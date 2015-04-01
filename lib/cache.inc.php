<?php
require_once('fs.inc.php');

function cache($name, $expire, $cb) {
	$fc = new FileCache($name, $expire);
	return $fc->cache($cb);
}

class FileCache {
	static public $dir = 'cache';

	private $filename, $expire, $name;
	public function __construct($name, $expire) {
		$this->name = $name;
		make_sure_dir_is_created($_SERVER['DOCUMENT_ROOT'] . '/'.FileCache::$dir);
		$this->filename = $_SERVER['DOCUMENT_ROOT'] . '/'.FileCache::$dir.'/'.$name.'.json';
		$this->expire = $expire;
	}

	public function getValue() {
		return $GLOBALS[Cache::globalVar][$this->name];
	}

	public function cache($cb) {
		
		if (is_file($this->filename) && time() < filemtime($this->filename) + strtotime($this->expire) - time() ) {
			return json_decode(file_get_contents($this->filename))->data;
		}else{

			if( is_callable($cb) ){
				ob_start();
				$cb();
				$data = ob_get_contents();
				ob_end_clean();
			}
			else
				$data = $cb;
			file_put_contents($this->filename, json_encode(array('data' => $data)));
			return $data;
		}
	}
}

//phpinfo();