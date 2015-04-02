<?php

class MinimalTest extends PHPUnit_Framework_TestCase {

    public function test_core() {
    	require_once('lib/core.inc.php');

    	// Is secured server connection ?
		unset($_SERVER['HTTPS']);
    	$this->assertEquals(server_is_secure(), false);

		$_SERVER['HTTPS'] = 'off';
    	$this->assertEquals(server_is_secure(), false);

		$_SERVER['HTTPS'] = 'on';
    	$this->assertEquals(server_is_secure(), true);

		$_SERVER['HTTPS'] = 'not_empty_or_off';
    	$this->assertEquals(server_is_secure(), true);

		$_SERVER['SERVER_PORT'] = 443;
    	$this->assertEquals(server_is_secure(), true);
    }

    public function test_var() {
    	require_once('lib/var.inc.php');
		$this->assertEquals(var_get('path'), null);

		// Check string path accessors
		$this->assertEquals(var_set('path', true), true);
		$this->assertEquals($GLOBALS['path'], true);
		$this->assertEquals(var_get('path'), true);
		
		$this->assertEquals(var_set('path/path2', true), true);
		$this->assertEquals($GLOBALS['path']['path2'], true);
		$this->assertEquals(var_get('path/path2'), true);

		// Check array key path accessors
		$this->assertEquals(var_set(array('arrayPath'), true), true);
		$this->assertEquals($GLOBALS['arrayPath'], true);

		$this->assertEquals(var_set(array('arrayPath', 'arrayPath2'), true), true);
		$this->assertEquals($GLOBALS['arrayPath']['arrayPath2'], true);

		// Append
		var_append('arrayPath', 'arrayPath3');
		$this->assertContains('arrayPath3', $GLOBALS['arrayPath']);

		var_append(array('arrayPath', 'arrayPath2'), 'arrayPath4');
		$this->assertArrayNotHasKey('arrayPath4', $GLOBALS['arrayPath']['arrayPath2']);

		// Array unset
		var_unset('arrayPath/arrayPath2/arrayPath4');
		$this->assertArrayHasKey('arrayPath2', $GLOBALS['arrayPath']);
		$this->assertNotContains('arrayPath2', $GLOBALS['arrayPath']['arrayPath2']);
    }

    public function test_sanitize() {
    	require_once('lib/sanitize.inc.php');

    	$this->assertEquals(slug('éàç'), 'eac');
    	$this->assertEquals(slug('éàç éàç éàç'), 'eac-eac-eac');
    	$this->assertEquals(slug('&~#{[|`\^@-_*$^!:;,\'()./§?'), '');
    }
}

