<?php

namespace OCA\Files_Antivirus\Scanner;
use RuntimeException;

class ICAPClient {
	private $host;
	private $port;
	private $writeHandle;

	public $userAgent = 'ownCloud-icap-client/0.1.0';

	public function __construct(string $host, int $port) {
		$this->host = $host;
		$this->port = $port;
	}

	private function connect(): void {
		// Shut stupid uncontrolled messaging up - we handle errors on our own
		$this->writeHandle = @\stream_socket_client(
			"tcp://{$this->host}:{$this->port}",
			$errorCode,
			$errorMessage,
			5
		);
		if (!$this->writeHandle) {
			throw new InitException(
				"Cannot connect to \"tcp://{$this->host}:{$this->port}\": $errorMessage (code $errorCode)"
			);
		}
	}

	private function disconnect(): void {
		// Due to suppressed output it could be a point of interest for debugging. Someday. Maybe.
		@\fclose($this->writeHandle);
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

	public function reqmod(string $service, array $body = [], array $headers = []): array {
		$request = $this->getRequest('REQMOD', $service, $body, $headers);
		$response = $this->send($request);
		return $response;
	}

	private function send(string $request): array {
		$this->connect();
		// Shut stupid uncontrolled messaging up - we handle errors on our own
		if (@\fwrite($this->writeHandle, $request) === false) {
			throw new InitException(
				"Writing to \"{$this->host}:{$this->port}}\" failed"
			);
		}

		$headers = [];
		$resHdr = [];
		$protocol = $this->readIcapStatusLine();
		
		// McAfee seems to not properly close the socket once all response bytes are sent to the client
		// So if ICAP status is 204 we just stop reading
		if ($protocol['code'] !== 204) {
			$headers = $this->readHeaders();
			if (isset($headers['Encapsulated'])) {
				$resHdr = $this->parseResHdr($headers['Encapsulated']);
			}
		}

		$this->disconnect();
		return [
			'protocol' => $protocol,
			'headers' => $headers,
			'body' => ['res-hdr' => $resHdr]
		];
	}

	private function readIcapStatusLine(): array {
		$icapHeader = \trim(\fgets($this->writeHandle));
		$numValues = \sscanf($icapHeader, "ICAP/%d.%d %d %s", $v1, $v2, $code, $status);
		if ($numValues !== 4) {
			throw new RuntimeException("Unknown ICAP response: \"$icapHeader\"");
		}
		return [
			'protocolVersion' => "$v1.$v2",
			'code' => $code,
			'status' => $status,
		];
	}

	private function parseResHdr(string $headerValue): array {
		$encapsulatedHeaders = [];
		$encapsulatedParts = \explode(",", $headerValue);
		foreach ($encapsulatedParts as $encapsulatedPart) {
			$pieces = \explode("=", \trim($encapsulatedPart));
			if ($pieces[1] === "0") {
				continue;
			}
			$rawEncapsulatedHeaders = \fread($this->writeHandle, $pieces[1]);
			$encapsulatedHeaders = $this->parseEncapsulatedHeaders($rawEncapsulatedHeaders);
			// According to the spec we have a single res-hdr part and are not interested in res-body content
			break;
		}
		return $encapsulatedHeaders;
	}

	private function readHeaders(): array {
		$headers = [];
		$prevString = "";
		while ($headerString = \fgets($this->writeHandle)) {
			$trimmedHeaderString = \trim($headerString);
			if ($prevString === "" && $trimmedHeaderString === "") {
				break;
			}
			list($headerName, $headerValue) = $this->parseHeader($trimmedHeaderString);
			if ($headerName !== '') {
				$headers[$headerName] = $headerValue;
				if ($headerName == "Encapsulated") {
					break;
				}
			}
			$prevString = $trimmedHeaderString;
		}
		return $headers;
	}

	private function parseEncapsulatedHeaders(string $headerString) : array {
		$headers = [];
		$split = \preg_split('/\r?\n/', \trim($headerString));
		$statusLine = \array_shift($split);
		if ($statusLine !== null) {
			$headers['HTTP_STATUS'] = $statusLine;
		}
		foreach (\preg_split('/\r?\n/', $headerString) as $line) {
			if ($line === '') {
				continue;
			}
			list($name, $value) = $this->parseHeader($line);
			if ($name !== '') {
				$headers[$name] = $value;
			}
		}

		return $headers;
	}

	private function parseHeader(string $headerString): array {
		$name = '';
		$value = '';
		$parts = \preg_split('/:\ /', $headerString, 2);
		if (isset($parts[0])) {
			$name = $parts[0];
			$value = $parts[1] ?? '';
		}
		return [$name, $value];
	}
}
