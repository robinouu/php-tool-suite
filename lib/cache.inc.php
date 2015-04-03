<?php
require_once('fs.inc.php');
require_once('log.inc.php');

if( !var_get('cache/dir') ){
	var_set('cache/dir', path_document_root().'/cache');
}

function cache_dir(){
	return var_get('cache/dir');
}

function cache($name, $cb, $expire = '+1 month') {
	$dir = var_get('cache/dir');
	make_sure_dir_is_created($dir);
	$filename = $dir.'/'.$name.'.json';
	$expire = $expire;

	if (is_file($filename) && filemtime($filename) + strtotime($expire) < 2*time() ) {
		return json_decode(file_get_contents($filename))->data;
	}else{

		if( is_callable($cb) ){
			ob_start();
			$cb();
			$data = ob_get_contents();
			ob_end_clean();
		}
		else
			$data = $cb;

		file_put_contents($filename, json_encode(array('data' => $data)));
		return $data;
	}
}

//phpinfo();