<?php

class MinimalTest extends PHPUnit_Framework_TestCase {

    public function test_core() {
    	require_once('lib/core.inc.php');
    	print 'core : ';
    	
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

    	$this->assertNotEquals(guid(), guid());

    	print 'done' . "\r\n";
    }

    public function test_var() {
    	require_once('lib/var.inc.php');
    	print 'var : ';

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

		print 'done' . "\r\n";
    }

    public function test_hook() {
    	require_once('lib/hook.inc.php');

    	print 'hook : ';

		$this->assertNull(hook_do('my_first_hook'));

    	hook_register('my_first_hook', function () {
    		return true;
		});

    	$this->assertTrue(hook_do('my_first_hook'));

    	hook_unregister('my_first_hook');

		$this->assertNull(hook_do('my_first_hook'));


		hook_register('my_second_hook', function () {
    		return 'a';
		}, 2);

		hook_register('my_second_hook', function () {
    		return 'b';
		});

		hook_register('my_second_hook', function () {
    		return 'c';
		}, -2);

		$this->assertEquals(hook_do('my_second_hook'), 'cba');

    	print 'done' . "\r\n";
    }

    public function test_sanitize() {
    	require_once('lib/sanitize.inc.php');

    	print 'sanitize : ';

    	$this->assertEquals(slug('éàç'), 'eac');
    	$this->assertEquals(slug('éàç éàç éàç'), 'eac-eac-eac');
    	$this->assertEquals(slug('&~#{[|`\^@-_*$^!:;,\'()./§?'), '');
    	print 'done' . "\r\n";
    }

    public function test_sql() {
    	require_once('lib/sql.inc.php');

		print 'sql : ';

    	$sql = sql_connect();
    	$this->assertNotNull($sql);

		sql_delete_tables();

		$this->assertFalse(sql_table_exists('user'));
		$this->assertFalse(sql_table_exists('user_meta'));
		$this->assertFalse(sql_table_exists('news'));
		$this->assertFalse(sql_table_exists('keyword'));

		// Test schema insertion, with data relations
		sql_schema(array(
			'user' => array(
				'fields' => array(
					'login_id' => array('required' => true, 'unique' => true),
					'password' => array('type' => 'password')
				)
			),
			'user_meta' => array(
				'fields' => array(
					'user' => array('type' => 'relation', 'data' => 'user'),
					'meta_key' => array(),
					'meta_value' => array('maxlength' => 5000),
				)
			),
			'keyword' => array(
				'fields' => array(
					'name' => array('unique' => true)
				),
				'primaryKey' => array('id', 'name')
			),
			'news' => array(
				'fields' => array(
					'title' => array(),
					'keywords' => array('type' => 'relation', 'data' => 'keyword', 'hasMany' => true)
				)
			),
			/*'news_keywords' => array(
				'fields' => array(
					'news' => array('type' => 'relation', 'data' => 'news'),
					'keyword' => array('type' => 'relation', 'data' => 'keyword')
				)
			),*/
		));

		$this->assertTrue(sql_table_exists('user'));
		$this->assertTrue(sql_table_exists('user_meta'));
		$this->assertTrue(sql_table_exists('news'));
		$this->assertTrue(sql_table_exists('keyword'));
		$this->assertTrue(sql_table_exists('news_keywords'));

		// check data integrity
		$userData = array('login_id' => 'username', 'password' => sha1('my_pass'));
		$this->assertTrue(sql_insert('user', $userData));
		
		$userID = sql_last_id();
		$this->assertNotEquals($userID, 0);
		
		$data = sql_query(sql_select('user', array_keys($userData)) . ' WHERE ' . sql_where(array('id' => $userID)));
		$this->assertNotNull($data);
		$this->assertTrue(sizeof($data) === 1);

		$data = $data[0];
		$this->assertEquals($data['login_id'], $userData['login_id']);
		$this->assertEquals($data['password'], $userData['password']);

		// Data updates
		$userData = array('login_id' => 'my_new_username');
		$this->assertNotNull(sql_update('user', $userData, array('id' => $userID)));

		$data = sql_query(sql_select('user', array_keys($userData)) . ' WHERE ' . sql_where(array('id' => $userID)));
		$this->assertNotNull($data);
		$this->assertTrue(sizeof($data) === 1);

		$data = $data[0];
		$this->assertEquals($data['login_id'], 'my_new_username');	

    	// TODO : We need some stuff to handle data testing, it think there is some kind of datasets with phpunit
		
    	sql_disconnect();
		$this->assertNull(var_get('sql/dbConnection'));

		print 'done' . "\r\n";
    }

    public function test_crypto() {

		print 'crypto : ';

    	if( extension_loaded('mcrypt') ){
			
			require_once('lib/crypto.inc.php');

	    	$content = 'Secured content';
	    	$decryptKey = 'MY_PRIVATE_KEY_OF_32_CHARACTERS!';

	    	encrypt($content, $decryptKey);
	    	
	    	$this->assertNotEquals('Secured content', $content);
	    	
	    	decrypt($content, $decryptKey);

	    	$this->assertEquals('Secured content', $content);
	    	print 'done' . "\r\n";
    	}else{
    		print 'mcrypt not found'. "\r\n";
    	}
    }

    public function test_file() {
		print 'file : ';

    	require_once('lib/file.inc.php');

    	$data[] = array( 'ISO', 'Name' );
    	$data[] = array( 'en', 'English' );
    	$data[] = array( 'fr', 'French' );
    	// ...

    	csv_write("data.csv", $data);

    	$data2 = csv_load("data.csv");

    	$this->assertEquals($data, $data2);
    	print 'done' . "\r\n";
    }

    public function test_fs() {
    	require_once('lib/fs.inc.php');
		print 'fs : ';

    	// Test dir creation
    	@rmdir('tmpdir');
    	mkdir_recursive('tmpdir');
    	$this->assertTrue(is_dir('tmpdir'));

    	// And dir deletion
    	mkdir_recursive('tmpdir/foo/bar');
    	touch('tmpdir/foo/bar/bar.json');
    	rmdir_recursive('tmpdir');
    	$this->assertFalse(is_dir('tmpdir'));

    	print 'done' . "\r\n";
	}

    public function test_form() {
    	require_once('lib/field.inc.php');
		print 'field : ';

    	// Required fields
		$requiredField = array('label' => 'Required field', 'required' => true);

		$this->assertFalse(field_validate($requiredField, ''));
		$this->assertFalse(field_validate($requiredField, array()));
		$this->assertTrue(field_validate($requiredField, 'not_empty'));
    	
		$requiredField['value'] = 'not_empty';
		$this->assertTrue(field_validate($requiredField));

    	// Maxlength validator
		$maxlengthField = array('label' => 'Maxlength field', 'maxlength' => 25);
		$this->assertFalse(field_validate($maxlengthField, 'abcdefghijklmnopqrstuvwxyz'));
		$this->assertTrue(field_validate($maxlengthField, ''));
		$this->assertTrue(field_validate($maxlengthField, 'Something'));

		// Minlength validator
		$minlengthField = array('label' => 'Minlength field', 'minlength' => 25);
		$this->assertTrue(field_validate($minlengthField, 'abcdefghijklmnopqrstuvwxyz'));
		$this->assertFalse(field_validate($minlengthField, ''));
		$this->assertFalse(field_validate($minlengthField, 'Something'));

    	// Required fields
		$requiredField = array('label' => 'Required field', 'required' => true);

		$this->assertFalse(field_validate($requiredField, ''));
		$this->assertFalse(field_validate($requiredField, array()));
		$this->assertTrue(field_validate($requiredField, 'not_empty'));
    	
		$requiredField['value'] = 'not_empty';
		$this->assertTrue(field_validate($requiredField));

		// Basic email check, 
		// TODO : add some more test to check RFC specifications
		$emailField = array('label' => 'Email field', 'type' => 'email', 'value' => 'user@example.com');
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

		// multiple field validation
		$emailField['value'] = 'x.x@x.x';
		$this->assertTrue(fields_validate(array($emailField, $requiredField)));
		$emailField['value'] = 'x';
		$this->assertFalse(fields_validate(array($emailField, $requiredField)));

		print 'done' . "\r\n";
    }

    public function test_cache() {
    	require_once('lib/cache.inc.php');
		print 'cache : ';

		mkdir_recursive('cache');

		$content = 'my cached content';
		$cachedContent = cache('cache/test.html', function () use ($content) {
			print $content;
		}, '+3 month');

		$this->assertTrue(file_exists('cache/test.html'));

		$this->assertEquals($cachedContent, $content);

		rmdir_recursive('cache');

		print 'done' . "\r\n";
    }


    protected $backupGlobals = FALSE;
}

