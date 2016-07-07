[![Build Status](https://travis-ci.org/robinouu/php-tool-suite.svg?branch=master)](https://travis-ci.org/robinouu/php-tool-suite)

<h1>PHP Tool Suite</h1>
<p>PHP Tool Suite is a set of PHP utility methods that will help you to develop your applications.</p>

<p><a href="https://trello.com/b/Y6S5C0kd/php-tool-suite">&gt; Todo-list (Trello)</a></p>

<h2>Installation</h2>

Just download the repository, include the lib/ directory somewhere.
In your PHP code, type for example : 
```php
require_once(dirname(__FILE__)."/lib/core.inc.php");
```

You can also download the .phar file and include it : 
```php
require_once("pts.phar");
```

<h2>Configuration</h2>
It loads the minimum library requirement. Then you can use :
```php
plugin_require(array("i18n", "request", "response"));
```

in order to load wanted modules.

<h2>Documentation</h2>

<p>Essential packages :</p>
<ul>
	<li><a href="https://github.com/robinouu/php-tool-suite/wiki/Variables">Variables</a></li>
	<li><a href="https://github.com/robinouu/php-tool-suite/wiki/Events">Events</a></li>
	<li><a href="https://github.com/robinouu/php-tool-suite/wiki/Fields">Fields</a></li>
	<li><a href="https://github.com/robinouu/php-tool-suite/wiki/Data-models">Data models</a></li>
	<li><a href="https://github.com/robinouu/php-tool-suite/wiki/HTTP-response">Routing</a></li>
	<li><a href="https://github.com/robinouu/php-tool-suite/wiki/Sql">SQL</a></li>
	<li><a href="https://github.com/robinouu/php-tool-suite/wiki/I18n">Localisation</a></li>
	<li><a href="https://github.com/robinouu/php-tool-suite/wiki/Logging">Logging</a></li>
</ul>

<p>The following documentation has been generated by the PHP Tool Suite doc helpers.</p>
https://github.com/robinouu/php-tool-suite/wiki


<h2>About the library</h2>

<p>PTS is still under heavy development, use it with caution.</p>
<p>Official website (fr) : <a href="http://php-tool-suite.fr">http://php-tool-suite.fr</a></p>
