<?php

namespace OCA\Files_Antivirus\Scanner;
use RuntimeException;

class ICAPClient {
	private $host;
	private $port;
	private $socket;

	public $userAgent = 'ownCloud-icap-client/0.1.0';

	public function __construct(string $host, int $port) {
		$this->host = $host;
		$this->port = $port;
	}

	private function connect(): bool {
		$this->socket = \socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

		if (!\socket_connect($this->socket, $this->host, $this->port)) {
			return false;
		}

		return true;
	}

	private function disconnect(): void {
		@\socket_shutdown($this->socket);
		@\socket_close($this->socket);
	}

	public function getLastSocketError(): int {
		return \socket_last_error($this->socket);
	}

	public function getRequest(string $method, string $service, array $body = [], array $headers = []): string {
		if (!\array_key_exists('Host', $headers)) {
			$headers['Host'] = $this->host;
		}

		if (!\array_key_exists('User-Agent', $headers)) {
			$headers['User-Agent'] = $this->userAgent;
		}

		if (!\array_key_exists('Connection', $headers)) {
			$headers['Connection'] = 'close';
		}

		$bodyData = '';
		$hasBody = false;
		$encapsulated = [];
		foreach ($body as $type => $data) {
			switch ($type) {
				case 'req-hdr':
				case 'res-hdr':
					$encapsulated[$type] = \strlen($bodyData);
					$bodyData .= $data;
					break;

				case 'req-body':
				case 'res-body':
					$encapsulated[$type] = \strlen($bodyData);
					$bodyData .= \dechex(\strlen($data)) . "\r\n";
					$bodyData .= $data;
					$bodyData .= "\r\n";
					$hasBody = true;
					break;
			}
		}

		if ($hasBody) {
			$bodyData .= "0\r\n\r\n";
		} elseif (\count($encapsulated) > 0) {
			$encapsulated['null-body'] = \strlen($bodyData);
		}

		if (\count($encapsulated) > 0) {
			$headers['Encapsulated'] = '';
			foreach ($encapsulated as $section => $offset) {
				$headers['Encapsulated'] .= $headers['Encapsulated'] === '' ? '' : ', ';
				$headers['Encapsulated'] .= "{$section}={$offset}";
			}
		}

		$request = "{$method} icap://{$this->host}/{$service} ICAP/1.0\r\n";
		foreach ($headers as $header => $value) {
			$request .= "{$header}: {$value}\r\n";
		}

		$request .= "\r\n";
		$request .= $bodyData;

		return $request;
	}

	public function options(string $service): array {
		$request = $this->getRequest('OPTIONS', $service);
		$response = $this->send($request);

		return $this->parseResponse($response);
	}

	public function respmod(string $service, array $body = [], array $headers = []): array {
		$request = $this->getRequest('RESPMOD', $service, $body, $headers);
		$response = $this->send($request);

		return $this->parseResponse($response);
	}

	public function reqmod(string $service, array $body = [], array $headers = []): array {
		$request = $this->getRequest('REQMOD', $service, $body, $headers);
		$response = $this->send($request);

		return $this->parseResponse($response);
	}

	private function send(string $request): string {
		if (!$this->connect()) {
			throw new RuntimeException("Cannot connect to icap://{$this->host}:{$this->port} (Socket error: " . $this->getLastSocketError() . ")");
		}

		\socket_write($this->socket, $request);

		$response = '';
		while ($buffer = \socket_read($this->socket, 2048)) {
			$response .= $buffer;
		}

		$this->disconnect();
		return $response;
	}

	private function parseResponse(string $response): array {
		$responseArray = [
			'protocol' => [],
			'headers' => [],
			'body' => [],
			'rawBody' => ''
		];

		foreach (\preg_split('/\r?\n/', $response) as $line) {
			if ($responseArray['protocol'] === []) {
				if (\strpos($line, 'ICAP/') !== 0) {
					throw new RuntimeException('Unknown ICAP response');
				}

				$parts = \preg_split('/\ +/', $line, 3);

				$responseArray['protocol'] = [
					'icap' => $parts[0] ?? '',
					'code' => $parts[1] ?? '',
					'message' => $parts[2] ?? '',
				];

				continue;
			}

			if ($line === '') {
				break;
			}

			$parts = \preg_split('/:\ /', $line, 2);
			if (isset($parts[0])) {
				$responseArray['headers'][$parts[0]] = $parts[1] ?? '';
			}
		}

		$body = \preg_split('/\r?\n\r?\n/', $response, 2);
		if (isset($body[1])) {
			$responseArray['rawBody'] = $body[1];

			if (\array_key_exists('Encapsulated', $responseArray['headers'])) {
				$encapsulated = [];
				$params = \explode(", ", $responseArray['headers']['Encapsulated']);

				foreach ($params as $param) {
					$parts = \explode("=", $param);
					if (\count($parts) !== 2) {
						continue;
					}

					$encapsulated[$parts[0]] = $parts[1];
				}

				foreach ($encapsulated as $section => $offset) {
					$data = \substr($body[1], (int)$offset);
					switch ($section) {
						case 'req-hdr':
						case 'res-hdr':
							$responseArray['body'][$section] = \preg_split('/\r?\n\r?\n/', $data, 2)[0];
							break;

						case 'req-body':
						case 'res-body':
							$parts = \preg_split('/\r?\n/', $data, 2);
							if (\count($parts) === 2) {
								$responseArray['body'][$section] = \substr($parts[1], 0, \hexdec($parts[0]));
							}
							break;
					}
				}
			}
		}

		return $responseArray;
	}
}
