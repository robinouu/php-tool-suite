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
		$this->assertNull(var_get('path'));

		// Check string path accessors
		$this->assertTrue(var_set('path', true));
		$this->assertTrue($GLOBALS['path']);
		$this->assertTrue(var_get('path'));
		
		$this->assertTrue(var_set('path/path2', true));
		$this->assertTrue($GLOBALS['path']['path2']);
		$this->assertTrue(var_get('path/path2'));

		// Check array key path accessors
		$this->assertTrue(var_set(array('arrayPath'), true));
		$this->assertTrue($GLOBALS['arrayPath']);

		$this->assertTrue(var_set(array('arrayPath', 'arrayPath2'), true));
		$this->assertTrue($GLOBALS['arrayPath']['arrayPath2']);

		// Append
		var_append('arrayPath', 'arrayPath3');
		$this->assertContains('arrayPath3', $GLOBALS['arrayPath']);

		var_append(array('arrayPath', 'arrayPath2'), 'arrayPath4');
		$this->assertContains('arrayPath4', $GLOBALS['arrayPath']['arrayPath2']);

		// Array unset
		var_unset('arrayPath/arrayPath2');
		$this->assertArrayNotHasKey('arrayPath2', $GLOBALS['arrayPath']);
    }

    public function test_sanitize() {
    	require_once('lib/sanitize.inc.php');

    	$this->assertEquals(slug('éàç'), 'eac');
    	$this->assertEquals(slug('éàç éàç éàç'), 'eac-eac-eac');
    	$this->assertEquals(slug('&~#{[|`\^@-_*$^!:;,\'()./§?'), '');
    }

    public function test_sql() {
    	require_once('lib/sql.inc.php');

    	$sql = sql_connect();
    	$this->assertNotNull($sql);

    	// TODO : We need some stuff to handle data testing, it think there is some kind of datasets with phpunit

    	sql_disconnect();
		$this->assertNull(var_get('sql/dbConnection'));
    }

    public function test_crypto() {
	
    	if( extension_loaded('mcrypt') ){
			
			require_once('lib/crypto.inc.php');

	    	$content = 'Secured content';
	    	$decryptKey = 'MY_PRIVATE_KEY_OF_32_CHARACTERS!';

	    	encrypt($content, $decryptKey);
	    	
	    	$this->assertNotEquals('Secured content', $content);
	    	
	    	decrypt($content, $decryptKey);

	    	$this->assertEquals('Secured content', $content);
    	}
    }

    public function test_file() {
    	
    	require_once('lib/file.inc.php');

    	$data[] = [ 'ISO', 'Name' ];
    	$data[] = [ 'en', 'English' ];
    	$data[] = [ 'fr', 'French' ];
    	// ...

    	csv_write("data.csv", $data);

    	$data2 = csv_load("data.csv");

    	$this->assertEquals($data, $data2);
    }

    public function test_form() {
    	require_once('lib/form.inc.php');

    	// Required fields
		$requiredField = ['type' => 'text', 'required' => true];

		$this->assertFalse(field_validate($requiredField, ''));
		$this->assertFalse(field_validate($requiredField, array()));
		$this->assertTrue(field_validate($requiredField, 'not_empty'));
    	
		$requiredField['value'] = 'not_empty';
		$this->assertTrue(field_validate($requiredField));

		// Basic email check, but should support all the major RFC specifications
		$emailField = ['type' => 'email', 'value' => 'user@example.com'];
		$this->assertTrue(field_validate($emailField));
		$emailField['value'] = '';
		$this->assertTrue(field_validate($emailField));
		$emailField['value'] = 'x@x.x';
		$this->assertTrue(field_validate($emailField));
		$emailField['value'] = 'x@';
		$this->assertFalse(field_validate($emailField));
		$emailField['value'] = 'x';
		$this->assertFalse(field_validate($emailField));
		$emailField['value'] = 'éx@x.x';
		$this->assertFalse(field_validate($emailField));
    }


    protected $backupGlobals = FALSE;
}

