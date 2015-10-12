<?php
/**
 * Created by PhpStorm.
 * User: krishenriksen
 * Date: 12/10/15
 * Time: 12:45
 */

class bitcoin
{
	/**
	 * Username for bitcoind JSON-RPC
	 *
	 * @var string $username
	 */
	private $username;

	/**
	 * Password for bitcoind JSON-RPC
	 *
	 * @var string $password
	 */
	private $password;

	/**
	 * Host name for bitcoind JSON-RPC
	 *
	 * @var string $host
	 */
	private $host;

	/**
	 * Port for bitcoind JSON-RPC
	 * Standard 8332 for mainnet and 18332 for testnet
	 *
	 * @var int $port
	 */
	private $port;

	/**
	 * @param string $username
	 * @param string $password
	 * @param string $host
	 * @param int $port
	 */
	public function __construct($username, $password, $host = '127.0.0.1', $port = 8332) {
		$this->username = $username;
		$this->password = $password;
		$this->host = $host;
		$this->port = $port;
	}

	private function cmd($command, $testnet)
	{
		$args = func_get_args();
		array_shift($args);
		array_shift($args);

		$request = json_encode(array(
			'jsonrpc' => '2.0',
			'method' => $command,
			'params' => $args,
			'id' => time()
		));

		/*
		 * Investigate why HTTP/1.1 is so much faster, and make "Connection close" on 1.1
		 */
		$in = "POST / HTTP/1.0\r\n";
		$in .= "Authorization: Basic " . base64_encode($this->username . ':' . $this->password) . "\r\n";
		$in .= "Content-Type: application/json\r\n";
		$in .= "Content-Length: " . strlen($request) . "\r\n";
		$in .= "Connection: Close\r\n\r\n";
		$in .= $request;

		/*
		 * Create socket
		 */
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if ($socket === false) {
			// echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
		}

		/*
		 * Connect to bitcoind JSON-RPC
		 */
		$result = socket_connect($socket, $this->host, $this->port);
		if ($result === false) {
			// echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
		}

		/*
		 * Write request to socket
		 */
		socket_write($socket, $in, strlen($in));

		/*
		 * Receive raw content
		 */
		$response = '';

		/*
		 * Loop though bytes until end of pointer
		 */
		if (!socket_last_error($socket)) {
			while ($buffer = socket_read($socket, 512)) {
				$response .= $buffer;
			}
		}

		/*
		 * Shutdown socket connection
		 */
		socket_close($socket);

		/*
		 * Raw header and body
		 */
		list($header, $body) = preg_split("/\R\R/", $response, 2);

		$result_array = json_decode($body, true);
		return $result_array['result'];
	}
}