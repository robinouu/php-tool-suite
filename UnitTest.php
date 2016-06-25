<?php
session_start();

class MinimalTest extends PHPUnit_Framework_TestCase {

	public function test_core() {
		require_once('lib/core.inc.php');
		print 'core : ';
		
		// Is secured server connection ?
		unset($_SERVER['HTTPS']);
		$this->assertEquals(server_is_secure(), false);

		$_SERVER['HTTPS'] = 'off';
		$this->assertEquals(server_is_secure(), false);

		$_SERVER['HTTPS'] = 'OFF';
		$this->assertEquals(server_is_secure(), false);

		$_SERVER['HTTPS'] = 'on';
		$this->assertEquals(server_is_secure(), true);

		$_SERVER['HTTPS'] = 'not_empty_or_off';
		$this->assertEquals(server_is_secure(), true);

		$_SERVER['SERVER_PORT'] = 443;
		$this->assertEquals(server_is_secure(), true);

		// Are guid uniques ?
		$this->assertNotEquals(guid(), guid());

		// Are hash uniques to a particular object ?
		$this->assertEquals(object_hash(null), object_hash(''));
		$this->assertNotEquals(object_hash(null), object_hash(array()));

		$this->assertEquals(object_hash('a'), object_hash('a'));
		$this->assertNotEquals(object_hash('a'), object_hash('b'));

		$this->assertEquals(object_hash(array('a')), object_hash(array('a')));
		$this->assertNotEquals(object_hash(array()), object_hash(array('a')));
		$this->assertNotEquals(object_hash(array('a')), object_hash(array('b')));
		$this->assertNotEquals(object_hash(array('a')), object_hash(array('b')));

		$obj = new stdclass;
		$this->assertEquals(object_hash($obj), object_hash($obj));
		$this->assertNotEquals(object_hash($obj), object_hash(new stdclass));

		print 'done' . "\r\n";
	}

	public function test_var() {
		
		plugin_require('var');

		print 'var : ';

		$this->assertNull(var_get('path'));

		// Check string path accessors
		$this->assertTrue(var_set('path', true));
		$this->assertTrue(var_get('path'));
		
		$this->assertTrue(var_set('path/path2', true));
		$this->assertTrue(var_get('path/path2'));

		$data = array('path' => array('path2' => null));
		$this->assertTrue(var_set('path/path2', true, $data));
		$this->assertTrue(var_get('path/path2', null, $data));

		// Check array key path accessors
		$this->assertTrue(var_set(array('arrayPath'), true));
		$this->assertTrue(var_get('arrayPath'));

		$this->assertTrue(var_set(array('arrayPath', 'arrayPath2'), true));
		$this->assertTrue(var_get('arrayPath/arrayPath2'));

		$data = array('arrayPath' => array('arrayPath2' => null));
		$this->assertTrue(var_set(array('arrayPath', 'arrayPath2'), true, $data));
		$this->assertTrue(var_get(array('arrayPath', 'arrayPath2'), null, $data));

		// Check session accessors
		$this->assertTrue(session_var_set(array('arrayPath'), true));
		$this->assertTrue(session_var_get('arrayPath'));

		$this->assertTrue(session_var_set(array('arrayPath', 'arrayPath2'), true));
		$this->assertTrue(session_var_get('arrayPath/arrayPath2'));

		// Append to arrays
		var_append('arrayPath', 'arrayPath3');
		$this->assertContains('arrayPath3', var_get('arrayPath'));
		
		// Append only to arrays
		$this->assertFalse(var_append(array('arrayPath', 'arrayPath2'), 'arrayPath4'));
		
		var_set('arrayPath/arrayPath2', array());
		var_append(array('arrayPath', 'arrayPath2'), 'arrayPath4');

		$this->assertContains('arrayPath4', var_get('arrayPath/arrayPath2'));

		$data = array();
		var_append(array('arrayPath', 'arrayPath2'), 'arrayPath4', $data);
		$this->assertContains('arrayPath4', var_get('arrayPath/arrayPath2', null, $data));

		// Array unset
		var_unset('arrayPath/arrayPath2');
		$this->assertArrayNotHasKey('arrayPath2', var_get('arrayPath'));
		$this->assertArrayHasKey('arrayPath', vars());
		
		print 'done' . "\r\n";		
	}


	public function test_event() {
		plugin_require('event');
		print 'event : ';

		$this->assertNull(trigger('my_first_event'));

		on('my_first_event', function () {
			return true;
		});

		$this->assertTrue(trigger('my_first_event'));

		off('my_first_event');

		$this->assertNull(trigger('my_first_event'));

		on('my_second_event', function () {
			return 'a';
		}, 2);

		on('my_second_event', function () {
			return 'b';
		});

		on('my_second_event', function () {
			return 'c';
		}, -2);

		$this->assertEquals(trigger('my_second_event'), 'cba');

		print 'done' . "\r\n";
	}

	public function test_sanitize() {
		plugin_require('sanitize');

		print 'sanitize : ';

		$this->assertEquals(slug('éàç'), 'eac');
		$this->assertEquals(slug('éàç éàç éàç'), 'eac-eac-eac');
		$this->assertEquals(slug('éàç éàç éàç', '_'), 'eac_eac_eac');
		$this->assertEquals(slug('éàç éàç éàç', null), 'eaceaceac');
		$this->assertEquals(slug('&~#{[|`\^@-_*$^!:;,\'()./§?'), '');

		print 'done' . "\r\n";
	}

	public function test_file() {
		print 'file : ';

		require_once('lib/file.inc.php');

		$data[] = array( 'ISO', 'Name' );
		$data[] = array( 'en', 'English' );
		$data[] = array( 'fr', 'French' );
		// ...

		csv_write("data.csv", $data);
		
		$loadedData = array();
		csv_load("data.csv", function ($line) use( &$loadedData ) { $loadedData[] = $line; });

		$this->assertEquals($data, $loadedData);

		$filename = dirname(__FILE__).'/example.json';

		$this->assertTrue(json_save($filename, array('test' => 1)));
		
		$data = json_load($filename);
		$this->assertTrue(is_array($data));

		$data2 = json_load($filename);
		$this->assertEquals($data, $data2);
		
		unlink($filename);


		print 'done' . "\r\n";
	}

	public function test_sql() {
		require_once('lib/sql.inc.php');

		print 'sql : ';

		$sql = sql_connect(array('db' => 'datas'));
		$this->assertNotNull($sql);

		// Test all tables deletion
		sql_delete_tables();
		$this->assertFalse(sql_table_exists('user'));
		
		// Test table creation
		$signable = array(
			'name' => 'signable', 
			'columns' => array(
				'login_id VARCHAR(255) NOT NULL',
				'password VARCHAR(255)'
			),
			'uniqueKeys' => array(
				array('name' => 'login_unique', 'columns' => array('login_id')),
				array('name' => 'password_unique', 'columns' => array('login_id', 'password'))
			)
		);

		sql_create_table($signable);
		sql_delete_table('signable');
		$this->assertFalse(sql_table_exists('signable'));

		$signable['uniqueKeys'] = array('login_id');
		sql_create_table($signable);

		// foreign keys
		sql_create_table(array(
			'name' => 'user',
			'columns' => array(
				'display_name VARCHAR(255) UNIQUE NOT NULL',
				'auth int(11) UNIQUE NOT NULL '
			),
			'foreignKeys' => array('auth' => array('name' => 'FK_auth', 'ref' => 'signable(id)'))
		));

		$this->assertTrue(sql_table_exists('user'));
		
		// SQL Values quote
		$this->assertEquals(sql_quote('test'), '\'test\'');
		$this->assertEquals(sql_quote(1), 1);
		$this->assertEquals(sql_quote(1.5), 1.5);
		$this->assertEquals(sql_quote(null), 'NULL');

		// Table/columns names
		$this->assertEquals(sql_quote('test', true), '`test`');
		$this->assertEquals(sql_quote('`test`', true), '```test```');

		// Logical clauses
		$this->assertEquals(sql_logic(array('test = 1')), 'test = 1');
		$this->assertEquals(sql_logic(array('test = %d' => array(1))), 'test = 1');
		$this->assertEquals(sql_logic(array(array('test = 1'), 'AND test = 2')), 'test = 1 AND test = 2');
		$this->assertEquals(sql_logic(array(array('test = 1'), array('test = 2'))), 'test = 1 AND test = 2');
		$this->assertEquals(sql_logic(array(array('test = 1'), array('test = 2'), 'OR', array('test = 3'))), 'test = 1 AND test = 2 OR test = 3');
		$this->assertEquals(sql_logic(array(array('test = %d' => 1), array('test = %d' => 2), 'OR', array('test = %d' => 3))), 'test = 1 AND test = 2 OR test = 3');
		$this->assertEquals(sql_logic(array(array(array('test = 1', 'OR'), 'test = 2'))), 'test = 1 OR test = 2');
		$this->assertEquals(sql_logic(array(array(array(array('test = 1'), 'OR', array('test = 2')), 'AND', 'test = 3'))), 'test = 1 OR test = 2 AND test = 3');
		$this->assertEquals(sql_logic(array('test = 1', 'OR', 'test = 2')), 'test = 1 OR test = 2');
		
		// check data integrity
		$userData = array('login_id' => 'username', 'password' => sha1('my_pass'));
		$this->assertTrue(sql_insert('signable', $userData));
		
		$userID = sql_last_id();
		$this->assertNotEquals($userID, 0);
		
		$data = sql_query(sql_select('signable', array_keys($userData)) . ' WHERE ' . sql_logic(array('id' => $userID)));
		$this->assertNotNull($data);
		$this->assertTrue(sizeof($data) === 1);

		$data = $data[0];
		$this->assertEquals($data['login_id'], $userData['login_id']);
		$this->assertEquals($data['password'], $userData['password']);

		// Data updates
		$userData = array('login_id' => 'my_new_username');
		$this->assertNotNull(sql_update('signable', $userData, array('id' => $userID)));

		$data = sql_query(sql_select('signable', array_keys($userData)) . ' WHERE ' . sql_logic(array('id' => $userID)));
		$this->assertNotNull($data);
		$this->assertTrue(sizeof($data) === 1);

		$data = $data[0];
		$this->assertEquals($data['login_id'], 'my_new_username');	

		// SQL Getter
		$signable = sql_get('signable', array('where' => array('id = %d' => $userID)));
		$this->assertNotNull($signable);
		$this->assertFalse(is_assoc_array($signable));

		$signable = sql_get('signable', array('where' => array('id = %d' => $userID), 'limit' => 1));
		$this->assertTrue(is_assoc_array($signable));

		sql_alter_table('signable', array(
			'columns' => array(),
			'charset' => 'latin1',
			'tableName' => 'authenticator'
		));

		$this->assertEquals(sql_driver(), 'mysql');

		/*csv_write($dataPath.'/signable.csv', array(
			array('login1', 'pass1'),
			array('login2', 'pass2'),
			array('login3', 'pass3')
		));

		$this->assertTrue(sql_import_csv(array(
			'table' => 'authenticator',
			'columns' => array('login_id', 'password'),
			'filename' => $dataPath . '/signable.csv'
		)));


		$someLogins = sql_get('authenticator', array('where' => array('login_id LIKE %s' => 'login%'), 'limit' => 2));
		$this->assertEquals(sizeof($someLogins), 2);
		*/

		sql_disconnect();
		$this->assertNull(var_get('sql/dbConnection'));

		print 'done' . "\r\n";
	}

	public function test_crypto() {
		print 'crypto : ';

		if( extension_loaded('mcrypt') ){
			
			plugin_require('crypto');

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

	public function test_fs() {
		plugin_require('fs');
		print 'fs : ';

		if( is_dir('tmpdir') ){
			rmdir_recursive('tmpdir');
		}

		// Test dir creation
		mkdir_recursive('tmpdir');
		$this->assertTrue(is_dir('tmpdir'));

		// And dir deletion
		mkdir_recursive('tmpdir/foo/bar');
		touch('tmpdir/foo/bar/bar.json');
		rmdir_recursive('tmpdir');
		$this->assertFalse(is_dir('tmpdir'));

		print 'done' . "\r\n";
	}

	public function test_field() {

		require_once('lib/field.inc.php');
		print 'field : ';

		// Required fields
		$requiredField = new TextField(array('name' => 'requiredField', 'required' => true));

		$this->assertFalse($requiredField->validate(''));
		$this->assertFalse($requiredField->validate(array()));
		$this->assertTrue($requiredField->validate('not_empty'));
		
		// Maxlength validator
		$maxlengthField = new TextField(array('name' => 'maxlengthField', 'maxlength' => 25));
		$this->assertFalse($maxlengthField->validate('abcdefghijklmnopqrstuvwxyz'));
		$this->assertTrue($maxlengthField->validate(''));
		$this->assertTrue($maxlengthField->validate('Something'));

		// Minlength validator
		$minlengthField = new TextField(array('name' => 'minlengthField', 'minlength' => 25));
		$this->assertTrue($minlengthField->validate('abcdefghijklmnopqrstuvwxyz'));
		$this->assertFalse($minlengthField->validate(''));
		$this->assertFalse($minlengthField->validate('Something'));

		// Basic email check, 
		// TODO : add some more test to check RFC specifications
		$emailField = new EmailField(array('name' => 'emailField'));
		$this->assertTrue($emailField->validate('user@example.com'));
		$this->assertTrue($emailField->validate(''));
		$this->assertTrue($emailField->validate('x@x.x'));
		$this->assertFalse($emailField->validate('x@'));
		$this->assertFalse($emailField->validate('x'));
		$this->assertFalse($emailField->validate('éx@x.x'));

		print 'done' . "\r\n";
	}

	public function test_model() {
		plugin_require('model');

		$sql = sql_connect(array('db' => 'datas'));
		sql_delete_tables();

		$models = array(
			'account' => array(
				'fields' => array(
					'email' => new EmailField(),
					'password' => new PasswordField()
				)
			),
			'person' => array(
				'fields' => array(
					'firstname' => new TextField(),
					'lastname' => new TextField(),
					'accounts' => new RelationField(array('data' => 'account', 'hasMany' => true))
				)
			),
			'ingredient' => array(
				'fields' => array(
					'name' => new TextField(),
					'type' => new SelectField(array('datas' => array('fish', 'meat', '...')))
				)
			),
			'recipe' => array(
				'fields' => array(
					'name' => new TextField(),
					'authors' => new RelationField(array('data' => 'person', 'hasMany' => true)),
					'ingredients' => new RelationField(array('data' => 'ingredient', 'hasMany' => true))
				)
			)
		);

		$schema = new Schema($models);
		$this->assertTrue($schema->generateTables());

		$recipe = new Model('recipe');
		$recipe->insert(array(
			'name' => 'salmon pasta',
			'authors' => array(
				'firstname' => 'Jon',
				'lastname' => 'Silver',
				'accounts' => array(
					'email' => 'jon.silver@gmail.com',
					'password' => 'password'
				)
			),
			'ingredients' => array(
				array('name' => 'Salmon', 'type' => 'fish'),
				array('name' => 'Pasta', 'type' => 'starchy')
			)
		))->insert(array(
			'name' => 'another recipe',
			'authors' => array(
				array(
					'firstname' => 'Georges',
					'lastname' => 'Lucas',
					'accounts' => array(1)
				),
				1,
				array(
					'firstname' => 'Lucie',
					'lastname' => 'France',
					'accounts' => array(1)
				)
			),
			'ingredients' => array('name' => 'Truite', 'type' => 'fish')
		))->commit();

		$this->assertEquals(sizeof($recipe->get()), 2);
		$recipe->reset();
		$this->assertTrue(is_assoc_array($recipe->limit(1)->get()));
		$recipe->reset();
		$this->assertEquals(sizeof($recipe->using('authors')->get()), 4);
		$recipe->reset();
		$this->assertEquals(sizeof($recipe->using('authors.accounts')->get()), 4);
		$recipe->reset();
		$this->assertEquals(sizeof($recipe->using('ingredients')->get()), 3);
		$recipe->reset();
		$this->assertEquals(sizeof($recipe->where(array('id = %d' => 1))->get()), 1);
		$recipe->reset();
		$this->assertEquals(sizeof($recipe->using('authors')->where(array('authors.id = %d' => 1))->get()), 2);
		$recipe->reset();
		$this->assertEquals(sizeof($recipe->using('authors.accounts', 'accounts')->where(array('accounts.id = %d' => 1))->groupBy('id')->get()), 2);
		$recipe->reset();
		$this->assertEquals(sizeof($recipe->using('authors.accounts', 'accounts')->groupBy(array('recipe.id', 'accounts.id'))->get()), 2);
		$recipe->reset();
		$this->assertEquals(sizeof($recipe->using('ingredients')->get()), 3);

		$recipe->reset();

		
		$recipe->replace('', array(
			'name' => 'Trout with almonds',
			'ingredients' => array(3, array('name' => 'Almond', 'type' => 'fruit')),
			'authors' => array('accounts' => array(1, 1))
		))->where('recipe.id = 2')->commit();
		
		$recipe->reset();
		$recipe->delete('authors.accounts')->delete('authors')->delete('ingredients')->delete()->commit();
		$this->assertFalse($recipe->get());
	
	}

	public function test_cache() {

		plugin_require('cache');

		print 'cache : ';
		
		$this->assertTrue(is_dir(var_get('cache/dir')));

		$content = 'my cached content';
		
		if( !($cachedContent = cache_get('test')) ){
			$res = cache_set('test', $content, '+3 month');
			$this->assertTrue($res);
		}else{
			$this->assertEquals($cachedContent, $content);
		}

		rmdir_recursive(var_get('cache/dir'));

		print 'done' . "\r\n";
	}

	protected $backupGlobals = FALSE;
}

