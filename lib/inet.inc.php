<?php
/**
 * inet
 * and PHP websockets
 * @package php-tool-suite
 * @subpackage inet
 */

/**
 * Returns the peer name
 * @param $socket The socket to search for
 * @return string the socket peer name (host:port)
 */
function inet_peername($socket) {
	$host = '';
	$port = '';
	socket_getpeername($socket, $host, $port);
	return $host . ':' . $port;
}

/**
 * Returns the last error occured on the specified socket
 * @param $socket The socket to search for
 * @return string The error message.
 */
function inet_error($socket = null) {
	return socket_strerror(socket_last_error($socket));
}

/**
 * Creates a basic TCP socket
 * @param $socket The socket to search for
 * @return string The error message.
 */
function inet_socket() {
	return socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
}

/**
 * Creates a TCP client socket and waits for data.
 * If an error occured, it stops the connection.
 * @param array $options The connection options
 * <ul>
 * 	<li>host string The host. '127.0.0.1' by default.</li>
 * 	<li>port string The port to use for the connection.</li>
 * 	<li>onInit callback A callback when the socket is initialized.</li>
 * 	<li>onData callback A callback when data have been received.</li>
 * 	<li>bufferLength int The maximum size of the buffer. Take care of that setting in production.</li>
 * </ul>
 */
function inet_client($options = array()) {

	$options = array_merge(array(
		'host' => '127.0.0.1',
		'port' => 9050,
		'onInit' => null,
		'onData' => null,
		'bufferLength' => 2048
	), $options);

	$socket = inet_socket();

	if( !@socket_connect($socket, $options['host'], $options['port']) ){
		print inet_error($socket);
	}else{
		$isRunning = true;

		if( is_callable($options['onInit']) ){
			$options['onInit']($socket);
		}
		
		do {

			if( ($buf = @socket_read($socket, $options['bufferLength'])) === FALSE ){
				print inet_error($socket);
				$isRunning = false;
			}else{
				if( is_callable($options['onData']) ){
					$isRunning = $options['onData']($buf);
				}else{
					print $buf;
				}
			}
		} while ($isRunning);
	}

	return inet_close($socket);
}

/**
 * Writes a string message into the socket.
 * @param $socket The socket where to send data
 * @param $msg The message to write
 * @return boolean TRUE if success, FALSE otherwise.
 */
function inet_write($socket, $msg) {
	return socket_write($socket, $msg, strlen($msg));
}


/**
 * Closes a socket
 * @param $socket The socket to close
 * @return boolean TRUE if success, FALSE otherwise.
 */
function inet_close($socket) {
	@socket_shutdown($socket);
	socket_close($socket);
}


/**
 * Creates a TCP server socket and waits for connections/socket transmission.
 * @param array $options The connection options
 * <ul>
 * 	<li>host string The host. '127.0.0.1' by default.</li>
 * 	<li>port string Thet port to use for the connection.</li>
 * 	<li>maxClients int The maximum number of connected clients at the same time.</li>
 * 	<li>onClientConnected callback A callback called when a client successfully connected to the server.</li>
 * 	<li>onClientData callback A callback called when data have been received for a particular client.</li>
 * 	<li>onClientDisconnected callback A callback called when a client has disconnected from the server.</li>
 * </ul>
 */
function inet_server($options = array()) {
	$options = array_merge(array(
		'host' => '127.0.0.1',
		'port' => 9050,
		'maxClients' => -1,
		'clientBuffer' => 2048,
		'onClientConnected' => null,
		'onClientDisconnected' => null,
		'onClientData' => null,
	), $options);


	
	$socket = inet_socket();
	if( $socket ){

		socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($socket, $options['host'], $options['port']);
		socket_listen($socket);

		$clients = array($socket);
		$isRunning = true;

		socket_set_nonblock($socket);

		while( $isRunning )	{
			sleep(0.04);
			$read = $clients;
			$write = NULL;
			$except = NULL;
			if (socket_select($read, $write, $except, 0) < 1)
				continue;

			if( in_array($socket, $read) ){
				$client = socket_accept($socket);
				if( sizeof($clients) < $options['maxClients'] ){
					$clients[] = $client;
					if( is_callable($options['onClientConnected']) ){
						$options['onClientConnected']($client);
					}
				}
				$key = array_search($socket, $read);
				unset($read[$key]);
			}


			foreach ($read as $readable_client) {
				$data = @socket_read($readable_client, $options['clientBuffer'], PHP_BINARY_READ);

				if( $data === FALSE ){
					if( $options['onClientDisconnected'] ){
						$options['onClientDisconnected']($readable_client);
					}
					$key = array_search($readable_client, $clients);
					unset($clients[$key]);
					continue;
				}
				if( is_callable($options['onClientData']) ){
					$options['onClientData']($readable_client, $data);
				}
			}
		}

	}else{
		return false;
	}
}