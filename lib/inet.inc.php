<?php

function inet_peername($socket) {
	$host = '';
	$port = '';
	socket_getpeername($socket, $host, $port);
	return $host . ':' . $port;
}

function inet_error($socket = null) {
	return socket_strerror(socket_last_error($socket));
}

function inet_socket() {
	return socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
}

function inet_client($options = array()) {

	$options = array_merge(array(
		'host' => '127.0.0.1',
		'port' => 9050,
		'onData' => null,
		'bufferLength' => 2048
	), $options);

	$socket = inet_socket();

	if( !@socket_connect($socket, $options['host'], $options['port']) ){
		print inet_error($socket);
	}else{
		$isRunning = true;
		do {

			if( ($buf = @socket_read($socket, $options['bufferLength'])) === FALSE ){
				print inet_error($socket);
				$isRunning = false;
			}else{
				if( is_callable($options['onData']) ){
					$options['onData']($buf);
				}else{
					print $buf;
				}
			}
		} while ($isRunning);
	}

	inet_close($socket);
}

function inet_close($socket) {
	@socket_shutdown($socket);
	socket_close($socket);
}

function inet_server($options = array()) {
	$options = array_merge(array(
		'host' => '127.0.0.1',
		'port' => 9050,
		'maxClients' => -1,
		'queueLength' => 80,
		'clientBuffer' => 2048,
		'onClientConnected' => null
	), $options);


	
	$socket = inet_socket();
	if( $socket ){

		socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($socket, $options['host'], $options['port']);
		socket_listen($socket, $options['queueLength']);

		$clients = array($socket);
		$isRunning = true;

		while( $isRunning )	{
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
				$data = @socket_read($readable_client, $options['clientBuffer'], PHP_NORMAL_READ);

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

?>