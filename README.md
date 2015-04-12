[![Build Status](https://travis-ci.org/robinouu/php-tool-suite.svg?branch=master)](https://travis-ci.org/robinouu/php-tool-suite)<h1>PHP Tool Suite</h1><h1>Features</h1><nav role="navigation"><ul class="menu"><li class="item item-link">Routes</li><li class="item item-link">HTML helpers (valid W3C/WCAG)</li><li class="item item-link">Variable accessors (cookies, sessions, global vars, arrays)</li><li class="item item-link">Localization (sql, gettext, php)</li><li class="item item-link">Data validation and edition</li><li class="item item-link">Data cache</li><li class="item item-link">Hooks</li><li class="item item-link">(mcrypt) AES256 encryption</li></ul></nav><a href="https://trello.com/b/Y6S5C0kd/php-tool-suite">&gt; Todo-list (Trello)</a><h2>Documentation</h2><h3>contextify($context)</h3><p>Add a global context to all next variable accessor calls

</p><ul><li><strong>$context</strong> (<em>string|array</em>) :  The path of the context. NULL by default.</li></ul><h3>set_cookie($options)</h3><p>Set a cookie

</p><ul><li><strong>$options</strong> (<em>array</em>) :  The cookie options.</li></ul><h3>get_cookie($options)</h3><p>Get a cookie value.

</p><ul><li><strong>$options</strong> (<em>array</em>) :  The cookie options.</li></ul><h3>cookie_var_set($options)</h3><p>Set a cookie

</p><ul><li><strong>$options</strong> (<em>array</em>) :  The cookie options.
- name string The cookie unique id. Required.
- value mixed The value to set. NULL by default.
- expireAt null|int Timestamp of expiration or NULL if you don't want an expiration date. NULL by default.
- path string The cookie uri path. '/' by default.
- domain null|string The cookie domain scope. NULL by default.
- encryptionKey null|string If mcrypt is loaded, encrypt cookie data using this key. NULL by default.
- raw boolean If TRUE, send cookie without URL encoding. FALSE by default.
- https boolean The security parameter of your transmission. By default, get server_is_secure() is used.
- httpOnly boolean If TRUE, the cookie will only be accessible for HTTP connections. FALSE by default.
</li></ul><h3>cookie_var_get($options)</h3><p>Get a cookie value.

</p><ul><li><strong>$options</strong> (<em>array</em>) :  The cookie options.
- name string The cookie unique name
- defaultValue mixed The default cookie value. NULL by default.
- encryptionKey null|string The encryption key to use to decode content. NULL by default.</li></ul><h3>session_var_set($path)</h3><p>Set a session var.

</p><ul><li><strong>$path</strong> (<em>string|array</em>) :  The variable path where to insert the value.</li></ul><h3>session_var_unset($path)</h3><p>Unset a session var.

</p><ul><li><strong>$path</strong> (<em>string|array</em>) :  The path where to delete the variable.</li></ul><h3>session_var_get($path, $default)</h3><p>Get a session variable value.

</p><ul><li><strong>$path</strong> (<em>string|array</em>) :  The variable path.</li><li><strong>$default</strong> (<em>string|array</em>) :  The value to use if the variable is not set.</li></ul><h3>var_set($path)</h3><p>Set a global var.

</p><ul><li><strong>$path</strong> (<em>string|array</em>) :  The variable path.</li></ul><h3>var_append($path)</h3><p>Append a value to a global variable array.

</p><ul><li><strong>$path</strong> (<em>string|array</em>) :  The variable path.</li></ul><h3>var_unset($path)</h3><p>Unset a global variable.

</p><ul><li><strong>$path</strong> (<em>string|array</em>) :  The variable path.</li></ul><h3>var_get($path)</h3><p>Get a global variable.

</p><ul><li><strong>$path</strong> (<em>string|array</em>) :  The variable path.</li></ul>
