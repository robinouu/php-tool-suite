<?php

function make_sure_dir_is_created($directory, $chmod = 0777) {
	if ( !is_dir($directory) ) {
  		return mkdir($directory, 0777, true);
	}
	return true;
}